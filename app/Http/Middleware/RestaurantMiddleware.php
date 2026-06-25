<?php

namespace App\Http\Middleware;

use App\Order;
use App\Restaurant;
use App\Services\FoodOrderStateMachineService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantMiddleware
{
    public function handle($request, Closure $next)
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        $user = auth()->user();
        if ($user->type !== 'restaurant') {
            return redirect('/')->with('error', 'Accès refusé. Cette page est réservée aux restaurants.');
        }

        $restaurant = $user->restaurant
            ?? Restaurant::where('user_id', $user->id)->first();

        if (! $restaurant) {
            return $this->deny($request, 'Aucun restaurant n’est associé à ce compte.', 403);
        }

        $routeName = (string) optional($request->route())->getName();

        if (in_array($routeName, [
            'restaurant.deliver_order',
            'restaurant.assign_order',
            'restaurant.assign_driver',
        ], true)) {
            return $this->deny(
                $request,
                'Cette action est désactivée : utilisez le workflow cuisine et le service de dispatch.',
                409
            );
        }

        if ($routeName === 'restaurant.prepaire_order' && $request->isMethod('get')) {
            return $this->deny(
                $request,
                'Cette ancienne action GET est désactivée. Utilisez le bouton d’acceptation sécurisé.',
                405
            );
        }

        if (in_array($routeName, [
            'restaurant.prepaire_orders',
            'restaurant.prepaire_order',
            'restaurant.cancel_order',
            'restaurant.orders.cash_dispute',
        ], true)) {
            if (! $this->requestTargetsRestaurantOrders($request, (int) $restaurant->id)) {
                return $this->deny($request, 'Commande introuvable pour ce restaurant.', 403);
            }
        }

        if ($routeName === 'restaurant.cancel_order') {
            $request->validate([
                'reason' => [
                    'required',
                    Rule::in([
                        'restaurant_closed',
                        'product_unavailable',
                        'too_many_orders',
                        'delivery_zone_issue',
                        'other',
                    ]),
                ],
                'cancel_note' => [
                    'nullable',
                    'string',
                    'max:500',
                    Rule::requiredIf(fn () => $request->input('reason') === 'other'),
                ],
            ], [
                'reason.required' => 'Le motif du refus est obligatoire.',
                'reason.in' => 'Le motif du refus est invalide.',
                'cancel_note.required' => 'Précisez le motif lorsque vous choisissez « Autre ».',
            ]);

            $order = $this->resolveRouteOrder($request, (int) $restaurant->id);
            if (! $order) {
                return $this->deny($request, 'Commande introuvable pour ce restaurant.', 403);
            }

            $order->loadMissing('delivery');
            $status = app(FoodOrderStateMachineService::class)->resolveCurrentBusinessStatus($order);
            if (in_array($status, [
                'picked_up',
                'out_for_delivery',
                'delivery_attempt_failed',
                'delivered',
                'picked_up_by_customer',
                'closed',
                'refunded',
            ], true)) {
                return $this->deny(
                    $request,
                    'Le restaurant ne peut plus annuler cette commande après sa prise en charge. Ouvrez un incident support.',
                    409
                );
            }
        }

        // La vue historique interroge /restaurant/notifications/{id} sans transmettre de curseur.
        // Le middleware maintient donc un curseur de session afin qu'une commande ne déclenche
        // le son qu'une seule fois, tout en restant visible tant qu'elle n'est pas traitée.
        $isNotificationPoll = $request->isMethod('get')
            && $request->is('restaurant/notifications/*');
        $cursorKey = 'restaurant_notification_cursor_' . $restaurant->id;

        if ($isNotificationPoll && ! $request->query->has('after_id')) {
            $request->query->set('after_id', (int) session($cursorKey, 0));
        }

        $response = $next($request);

        if ($isNotificationPoll && method_exists($response, 'getData')) {
            $payload = (array) $response->getData(true);
            if (isset($payload['next_cursor'])) {
                session([$cursorKey => max(0, (int) $payload['next_cursor'])]);
            }
        }

        return $response;
    }

    private function requestTargetsRestaurantOrders(Request $request, int $restaurantId): bool
    {
        $references = [];

        foreach (['order', 'orderNo'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof Order) {
                if ((int) $value->restaurant_id !== $restaurantId) {
                    return false;
                }
                continue;
            }

            if ($value !== null && $value !== '') {
                $references[] = $value;
            }
        }

        foreach ((array) $request->input('id', []) as $value) {
            if ($value !== null && $value !== '') {
                $references[] = $value;
            }
        }

        $references = array_values(array_unique(array_map('strval', $references)));
        if ($references === []) {
            return false;
        }

        foreach ($references as $reference) {
            if (! $this->referenceBelongsToRestaurant($reference, $restaurantId)) {
                return false;
            }
        }

        return true;
    }

    private function resolveRouteOrder(Request $request, int $restaurantId): ?Order
    {
        $value = $request->route('order') ?? $request->route('orderNo');

        if ($value instanceof Order) {
            return (int) $value->restaurant_id === $restaurantId ? $value : null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        $reference = (string) $value;

        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where(function ($query) use ($reference) {
                $query->where('order_no', $reference);
                if (ctype_digit($reference)) {
                    $query->orWhere('id', (int) $reference);
                }
            })
            ->first();
    }

    private function referenceBelongsToRestaurant(string $reference, int $restaurantId): bool
    {
        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where(function ($query) use ($reference) {
                $query->where('order_no', $reference);
                if (ctype_digit($reference)) {
                    $query->orWhere('id', (int) $reference);
                }
            })
            ->exists();
    }

    private function deny(Request $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->with('alert', [
            'type' => 'danger',
            'message' => $message,
        ]);
    }
}

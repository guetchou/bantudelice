<?php

namespace App\Services;

use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerOrderTimelineService
{
    private const CLIENT_STATUSES = [
        'pending_restaurant_acceptance',
        'accepted_awaiting_payment',
        'accepted',
        'confirmed',
        'in_kitchen',
        'ready_for_pickup',
        'dispatching',
        'driver_assigned',
        'driver_arrived_at_restaurant',
        'picked_up',
        'out_for_delivery',
        'delivered',
        'customer_arrived',
        'picked_up_by_customer',
        'closed',
        'cancelled',
        'no_show',
        'refunded',
    ];

    private const INTERNAL_REASON_CODES = [
        'status_transition_forced_unpaid',
        'admin_force',
        'technical_reconciliation',
    ];

    public function forOrder(Order $order): Collection
    {
        $initial = collect([
            $this->present('pending_restaurant_acceptance', Carbon::parse($order->created_at ?? now())),
        ]);

        if (! Schema::hasTable('order_status_logs')) {
            return $initial;
        }

        $rows = DB::table('order_status_logs')
            ->where('order_no', $order->order_no)
            ->whereIn('to_status', self::CLIENT_STATUSES)
            ->where(function ($query) {
                $query->whereNull('actor_type')->orWhere('actor_type', '!=', 'admin');
            })
            ->where(function ($query) {
                $query->whereNull('reason_code')->orWhereNotIn('reason_code', self::INTERNAL_REASON_CODES);
            })
            ->selectRaw('to_status, MIN(created_at) AS occurred_at')
            ->groupBy('to_status')
            ->orderBy('occurred_at')
            ->get();

        $history = $rows->map(fn ($row) => $this->present(
            (string) $row->to_status,
            Carbon::parse($row->occurred_at)
        ));

        return $initial
            ->concat($history)
            ->unique('status')
            ->sortBy('occurred_at')
            ->values();
    }

    private function present(string $status, Carbon $occurredAt): array
    {
        [$label, $description, $icon] = match ($status) {
            'pending_restaurant_acceptance' => ['Commande enregistrée', 'Votre commande a bien été transmise au restaurant.', 'fa-receipt'],
            'accepted_awaiting_payment' => ['Commande acceptée', 'Le restaurant attend la confirmation du paiement.', 'fa-credit-card'],
            'accepted', 'confirmed' => ['Commande confirmée', 'Le restaurant a confirmé la prise en charge.', 'fa-circle-check'],
            'in_kitchen' => ['En préparation', 'La cuisine prépare votre commande.', 'fa-fire-burner'],
            'ready_for_pickup' => ['Commande prête', 'Votre commande est prête au restaurant.', 'fa-bell-concierge'],
            'dispatching' => ['Recherche d’un livreur', 'Un livreur disponible est recherché.', 'fa-magnifying-glass-location'],
            'driver_assigned' => ['Livreur assigné', 'Un livreur a accepté la livraison.', 'fa-motorcycle'],
            'driver_arrived_at_restaurant' => ['Livreur au restaurant', 'Le livreur attend la remise de la commande.', 'fa-store'],
            'picked_up' => ['Commande récupérée', 'Le livreur a récupéré votre commande.', 'fa-box'],
            'out_for_delivery' => ['En route', 'La commande est en chemin vers votre adresse.', 'fa-route'],
            'delivered' => ['Commande livrée', 'La remise de la commande est confirmée.', 'fa-house-circle-check'],
            'customer_arrived' => ['Client arrivé', 'Votre arrivée au restaurant est enregistrée.', 'fa-person-walking'],
            'picked_up_by_customer', 'closed' => ['Commande retirée', 'La remise au comptoir est confirmée.', 'fa-bag-shopping'],
            'cancelled' => ['Commande annulée', 'La commande a été annulée.', 'fa-circle-xmark'],
            'no_show' => ['Retrait non finalisé', 'La commande n’a pas été retirée dans le délai prévu.', 'fa-clock'],
            'refunded' => ['Remboursement traité', 'Le remboursement de la commande a été traité.', 'fa-money-bill-transfer'],
            default => ['Mise à jour', 'Le traitement de la commande a évolué.', 'fa-circle'],
        };

        $local = $occurredAt->copy()->timezone(config('app.timezone'));

        return [
            'status' => $status,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'occurred_at' => $occurredAt,
            'date_label' => $local->format('d/m/Y'),
            'time_label' => $local->format('H:i'),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Address;
use App\Order;
use App\Services\CommerceSignalService;
use App\Services\DeliveryService;
use App\Services\GeolocationService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderModificationController extends Controller
{
    public function edit(Request $request, string $orderNo)
    {
        $order = Order::with(['restaurant', 'product', 'delivery', 'user'])
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $this->authorizeOrder($order);

        if (! $order->canBeModified()) {
            return redirect()
                ->route('track.order', ['orderNo' => $orderNo])
                ->with('message', 'Cette commande est déjà en préparation et ne peut plus être modifiée.');
        }

        $savedAddresses = collect();
        if (Schema::hasTable('user_address')) {
            $savedAddresses = Address::where('user_id', auth()->id())
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get();
        }

        $orderItems = Order::with('product')
            ->where('order_no', $orderNo)
            ->orderBy('id')
            ->get();

        return view('frontend.order_edit', [
            'order' => $order,
            'orderItems' => $orderItems,
            'savedAddresses' => $savedAddresses,
        ]);
    }

    public function update(Request $request, string $orderNo)
    {
        $order = Order::with(['restaurant', 'product', 'delivery', 'user'])
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $this->authorizeOrder($order);

        if (! $order->canBeModified()) {
            return back()->with('message', 'Cette commande est déjà en préparation et ne peut plus être modifiée.');
        }

        $isPickup = $order->isPickup();
        $rules = [
            'scheduled_date' => ['nullable', 'date', 'after:now'],
        ];

        if ($isPickup) {
            $rules['pickup_note'] = ['nullable', 'string', 'max:500'];
        } else {
            $rules['address_id'] = ['nullable', 'integer', 'exists:user_address,id'];
            $rules['delivery_address'] = ['required_without:address_id', 'string', 'max:500'];
            $rules['delivery_district'] = ['nullable', 'string', 'max:120'];
            $rules['delivery_landmark'] = ['nullable', 'string', 'max:255'];
            $rules['delivery_complement'] = ['nullable', 'string', 'max:500'];
            $rules['d_lat'] = ['nullable', 'numeric'];
            $rules['d_lng'] = ['nullable', 'numeric'];
        }

        $data = $request->validate($rules);

        $savedAddress = null;
        if (!$isPickup && !empty($data['address_id']) && Schema::hasTable('user_address')) {
            $savedAddress = Address::where('user_id', auth()->id())
                ->where('id', $data['address_id'])
                ->first();
        }

        $scheduledAt = null;
        if (!empty($data['scheduled_date'])) {
            $candidate = \Carbon\Carbon::parse($data['scheduled_date']);
            if ($candidate->isFuture()) {
                $scheduledAt = $candidate;
            }
        }

        if ($isPickup) {
            $pickupNote = trim((string) ($data['pickup_note'] ?? ''));
            $deliveryAddress = trim(implode(' | ', array_filter([
                'Retrait sur place',
                optional($order->restaurant)->name,
                optional($order->restaurant)->address,
                $pickupNote !== '' ? 'Note: ' . $pickupNote : null,
            ])));
            $deliveryLatitude = optional($order->restaurant)->latitude;
            $deliveryLongitude = optional($order->restaurant)->longitude;
        } else {
            if ($savedAddress) {
                $deliveryAddress = trim(implode(' | ', array_filter([
                    $savedAddress->title,
                    $savedAddress->complete_address,
                    $savedAddress->area,
                    $savedAddress->building_no,
                    $savedAddress->street_no,
                    $savedAddress->floor,
                ])));
                $deliveryLatitude = $savedAddress->latitude;
                $deliveryLongitude = $savedAddress->longitude;
            } else {
                $deliveryAddress = trim(implode(' | ', array_filter([
                    $data['delivery_address'] ?? null,
                    $data['delivery_district'] ?? null,
                    $data['delivery_landmark'] ?? null,
                    $data['delivery_complement'] ?? null,
                ])));
                $resolved = GeolocationService::geocode($deliveryAddress);
                $deliveryLatitude = $data['d_lat'] ?? ($resolved['lat'] ?? null);
                $deliveryLongitude = $data['d_lng'] ?? ($resolved['lng'] ?? null);
                if (empty($deliveryAddress) && !empty($resolved['formatted_address'])) {
                    $deliveryAddress = $resolved['formatted_address'];
                }
            }
        }

        $orders = Order::where('order_no', $orderNo)->get();
        if ($orders->isEmpty()) {
            abort(404);
        }

        $deliveryReset = false;

        DB::transaction(function () use ($orders, $scheduledAt, $deliveryAddress, $deliveryLatitude, $deliveryLongitude, $isPickup, &$deliveryReset) {
            $now = now();

            foreach ($orders as $line) {
                $payload = [
                    'updated_at' => $now,
                    'delivery_address' => $deliveryAddress,
                    'driver_id' => null,
                ];

                if ($scheduledAt || $line->scheduled_date) {
                    $payload['scheduled_date'] = $scheduledAt;
                }

                if (!$isPickup) {
                    $payload['latitude'] = $deliveryLatitude;
                    $payload['longitude'] = $deliveryLongitude;
                    $payload['d_lat'] = $deliveryLatitude;
                    $payload['d_lng'] = $deliveryLongitude;
                }

                if ($isPickup && Schema::hasColumn('orders', 'pickup_code') && empty($line->pickup_code)) {
                    $payload['pickup_code'] = (string) random_int(1000, 9999);
                }

                $line->forceFill($payload)->save();
            }

            if (!$isPickup && $orders->first()->delivery) {
                $delivery = $orders->first()->delivery;
                if (in_array(strtoupper((string) $delivery->status), ['PENDING', 'ASSIGNED'], true)) {
                    app(DeliveryService::class)->resetForOrderModification($delivery, [
                        'delivery_notes' => $deliveryAddress,
                        'delivery_latitude' => $deliveryLatitude,
                        'delivery_longitude' => $deliveryLongitude,
                    ]);
                    $deliveryReset = true;
                } else {
                    $delivery->update([
                        'delivery_latitude' => $deliveryLatitude,
                        'delivery_longitude' => $deliveryLongitude,
                        'delivery_notes' => $deliveryAddress,
                    ]);
                }
            }
        });

        if ($deliveryReset && !$isPickup && $orders->first()->delivery) {
            $delivery = $orders->first()->delivery->fresh(['order']);
            if ($delivery) {
                enqueue_job('food', 'auto_assign_delivery', [
                    'delivery' => $delivery,
                ]);
            }
        }

        app(CommerceSignalService::class)->emitOrder($orders->first(), 'order.modified', [
            'module' => 'food',
            'severity' => 'info',
            'actor_type' => auth()->user()->type ?? 'customer',
            'actor_id' => auth()->id(),
            'fulfillment_mode' => $isPickup ? 'pickup' : 'delivery',
            'delivery_reset' => $deliveryReset,
            'scheduled_date' => optional($scheduledAt)->toIso8601String(),
        ]);

        app(RiskService::class)->assessOrder($orders->first(), [
            'module' => 'food',
            'fulfillment_mode' => $isPickup ? 'pickup' : 'delivery',
            'delivery_reset' => $deliveryReset,
        ], 'order_modified');

        return redirect()
            ->route('track.order', ['orderNo' => $orderNo])
            ->with('success', 'Commande mise à jour avant préparation.');
    }

    protected function authorizeOrder(Order $order): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(
            auth()->id() === $order->user_id || (auth()->user()->type ?? null) === 'admin',
            403,
            'Accès non autorisé'
        );
    }
}

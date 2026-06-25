<?php

namespace App\Services;

use App\Driver;
use App\Order;
use App\User;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Transport\Models\TransportBooking;

class RealtimeChannelAuthorizer
{
    public function canAccessFoodOrderStatus(User $user, string $orderNo): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $order = Order::query()
            ->with(['restaurant', 'delivery'])
            ->where('order_no', $orderNo)
            ->first();

        if (! $order) {
            return false;
        }

        if ((int) $order->user_id === (int) $user->id) {
            return true;
        }

        if ((int) ($order->restaurant->user_id ?? 0) === (int) $user->id) {
            return true;
        }

        $driver = $this->resolveDriverForUser($user);
        if (! $driver) {
            return false;
        }

        return (int) $order->driver_id === (int) $driver->id
            || (int) ($order->delivery->driver_id ?? 0) === (int) $driver->id;
    }

    public function canAccessFoodRestaurantOrders(User $user, int $restaurantId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return (bool) \App\Restaurant::query()
            ->where('id', $restaurantId)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canAccessFoodDriverOrders(User $user, int $driverId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $driver = $this->resolveDriverForUser($user);

        return $driver && (int) $driver->id === $driverId;
    }

    public function canAccessTransportBooking(User $user, string $bookingUuid): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $booking = TransportBooking::query()
            ->where('uuid', $bookingUuid)
            ->first();

        if (! $booking) {
            return false;
        }

        if ((int) $booking->user_id === (int) $user->id) {
            return true;
        }

        $driver = $this->resolveDriverForUser($user);

        return $driver && (int) $booking->driver_id === (int) $driver->id;
    }

    public function canAccessTransportDriverRequests(User $user, int $driverId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $driver = $this->resolveDriverForUser($user);

        return $driver && (int) $driver->id === $driverId;
    }

    public function canAccessColisShipment(User $user, int $shipmentId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $shipment = Shipment::query()->find($shipmentId);
        if (! $shipment) {
            return false;
        }

        if ((int) $shipment->customer_id === (int) $user->id) {
            return true;
        }

        $driver = $this->resolveDriverForUser($user);

        return $driver && (int) $shipment->assigned_courier_id === (int) $driver->id;
    }

    protected function resolveDriverForUser(User $user): ?Driver
    {
        $driver = Driver::query()->where('user_id', $user->id)->first();
        if ($driver) {
            return $driver;
        }

        if (! $user->email || ! $user->phone) {
            return null;
        }

        return Driver::query()
            ->where('email', $user->email)
            ->where('phone', $user->phone)
            ->first();
    }

    protected function isAdmin(User $user): bool
    {
        return ($user->type ?? null) === 'admin';
    }
}

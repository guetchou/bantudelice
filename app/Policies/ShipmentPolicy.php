<?php

namespace App\Policies;

use App\Driver;
use App\User;
use App\Domain\Colis\Models\Shipment;
use App\Support\Auth\ActorType;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    public function view(AuthenticatableContract $actor, Shipment $shipment): bool
    {
        if ($this->isAdminUser($actor)) {
            return true;
        }

        return $actor instanceof User && (int) $actor->id === (int) $shipment->customer_id;
    }

    public function update(AuthenticatableContract $actor, Shipment $shipment): bool
    {
        if ($this->isAdminUser($actor)) {
            return true;
        }

        return $actor instanceof User && (int) $actor->id === (int) $shipment->customer_id;
    }

    public function updateAsCourier(AuthenticatableContract $actor, Shipment $shipment): bool
    {
        if ($this->isAdminUser($actor)) {
            return true;
        }

        return $actor instanceof Driver && (int) $actor->id === (int) $shipment->assigned_courier_id;
    }

    public function admin(AuthenticatableContract $actor): bool
    {
        return $this->isAdminUser($actor);
    }

    protected function isAdminUser(AuthenticatableContract $actor): bool
    {
        return ActorType::isAdminUser($actor);
    }
}

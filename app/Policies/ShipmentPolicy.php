<?php

namespace App\Policies;

use App\User;
use App\Domain\Colis\Models\Shipment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->customer_id;
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->customer_id;
    }

    public function updateAsCourier(User $user, Shipment $shipment): bool
    {
        // En supposant que le User peut aussi être un Driver (selon auth context)
        // Dans ce projet Driver est un modèle séparé, mais Passport/Sanctum peut mapper à User.
        // On vérifie l'ID du coursier assigné.
        return $user->id === $shipment->assigned_courier_id;
    }

    public function admin(User $user): bool
    {
        // Logique admin existante à vérifier dans le projet
        return $user->type === 'admin';
    }
}

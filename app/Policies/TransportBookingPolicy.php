<?php

namespace App\Policies;

use App\Driver;
use App\Domain\Transport\Models\TransportBooking;
use App\Support\Auth\ActorType;
use App\User;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransportBookingPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(AuthenticatableContract $actor, $ability)
    {
        if (ActorType::isAdminUser($actor)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AuthenticatableContract $actor, TransportBooking $transportBooking): bool
    {
        if ($actor instanceof Driver) {
            return (int) $actor->id === (int) $transportBooking->driver_id;
        }

        return $actor instanceof User && (int) $actor->id === (int) $transportBooking->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(AuthenticatableContract $actor, TransportBooking $transportBooking): bool
    {
        if ($actor instanceof Driver) {
            return (int) $actor->id === (int) $transportBooking->driver_id;
        }

        return $actor instanceof User && (int) $actor->id === (int) $transportBooking->user_id;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(AuthenticatableContract $actor, TransportBooking $transportBooking): bool
    {
        return $actor instanceof User
            && (int) $actor->id === (int) $transportBooking->user_id
            && in_array($transportBooking->status->value, ['requested', 'assigned'], true);
    }
}

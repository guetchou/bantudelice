<?php

namespace App\Policies;

use App\Domain\Transport\Models\TransportBooking;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransportBookingPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->type === 'admin') {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TransportBooking $transportBooking): bool
    {
        return $user->id === $transportBooking->user_id || $user->id === $transportBooking->driver_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TransportBooking $transportBooking): bool
    {
        return $user->id === $transportBooking->user_id || $user->id === $transportBooking->driver_id;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, TransportBooking $transportBooking): bool
    {
        return $user->id === $transportBooking->user_id && in_array($transportBooking->status->value, ['requested', 'assigned']);
    }
}

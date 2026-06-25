<?php

namespace App\Support\Auth;

use App\Driver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuthenticatedDriverResolver
{
    public function current(): ?Driver
    {
        $actor = Auth::guard('driver_api')->user();
        if ($actor instanceof Driver) {
            return (bool) $actor->approved ? $actor : null;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $driver = null;

        if (Schema::hasColumn('drivers', 'user_id')) {
            $driver = Driver::where('user_id', $user->id)->first();
        }

        if (! $driver && ! empty($user->email) && ! empty($user->phone)) {
            $driver = Driver::where('email', $user->email)
                ->where('phone', $user->phone)
                ->first();

            if ($driver && Schema::hasColumn('drivers', 'user_id') && ! $driver->user_id) {
                $driver->forceFill(['user_id' => $user->id])->save();
            }
        }

        return $driver && (bool) $driver->approved
            ? $driver
            : null;
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }
}

<?php

namespace App\Support\Auth;

use App\Driver;
use Illuminate\Support\Facades\Auth;

class AuthenticatedDriverResolver
{
    public function current(): ?Driver
    {
        $actor = Auth::guard('driver_api')->user();
        if ($actor instanceof Driver) {
            return $actor;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $driver = null;
        if (! empty($user->email) || ! empty($user->phone)) {
            $driver = Driver::query()
                ->where(function ($query) use ($user) {
                    if (! empty($user->email)) {
                        $query->orWhere('email', $user->email);
                    }

                    if (! empty($user->phone)) {
                        $query->orWhere('phone', $user->phone);
                    }
                })
                ->first();
        }

        if (! $driver && $user->type === 'driver' && ! empty($user->name)) {
            $driver = Driver::where('name', $user->name)->first();
        }

        return $driver;
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }
}

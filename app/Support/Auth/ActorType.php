<?php

namespace App\Support\Auth;

use App\Driver;
use App\User;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

final class ActorType
{
    public static function user(?AuthenticatableContract $actor): ?User
    {
        return $actor instanceof User ? $actor : null;
    }

    public static function driver(?AuthenticatableContract $actor): ?Driver
    {
        return $actor instanceof Driver ? $actor : null;
    }

    public static function hasUserRole(?AuthenticatableContract $actor, string $role): bool
    {
        return self::user($actor)?->type === $role;
    }

    public static function isAdminUser(?AuthenticatableContract $actor): bool
    {
        return self::hasUserRole($actor, 'admin');
    }
}

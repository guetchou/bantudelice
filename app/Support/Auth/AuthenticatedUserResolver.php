<?php

namespace App\Support\Auth;

use App\User;
use Illuminate\Support\Facades\Auth;

class AuthenticatedUserResolver
{
    /**
     * @param  string[]  $guards
     */
    public function current(array $guards = ['api', 'web']): ?User
    {
        foreach ($guards as $guard) {
            $actor = Auth::guard($guard)->user();

            if ($actor instanceof User) {
                return $actor;
            }
        }

        return null;
    }

    /**
     * @param  string[]  $guards
     */
    public function id(array $guards = ['api', 'web']): ?int
    {
        return $this->current($guards)?->id;
    }
}

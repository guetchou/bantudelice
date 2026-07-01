<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Allow only BantuDelice workspace administrators to access Horizon.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static function (?User $user): bool {
            return $user !== null
                && ($user->isSuperAdmin() || $user->hasAdminWorkspace('bantudelice'));
        });
    }
}

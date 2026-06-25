<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'App\Http\Controllers';

    public const HOME = '/home';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapApiV1FoodDriverRoutes();
        $this->mapWebRoutes();
        $this->mapTrackingRoutes();
        $this->mapAdminCashRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    protected function mapTrackingRoutes()
    {
        Route::middleware('web')
             ->group(base_path('routes/tracking.php'));
    }

    protected function mapAdminCashRoutes()
    {
        Route::middleware('web')
             ->group(base_path('routes/admin-cash.php'));
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapApiV1FoodDriverRoutes()
    {
        Route::prefix('api/v1')
             ->middleware('api')
             ->group(base_path('routes/api-v1-food-driver.php'));
    }
}

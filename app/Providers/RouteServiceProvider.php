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
        $this->mapHealthRoutes();
        $this->mapApiRoutes();
        $this->mapApiV1FoodDriverRoutes();
        $this->mapWebRoutes();
        $this->mapCustomerDashboardRoutes();
        $this->mapKendeRoutes();
        $this->mapTrackingRoutes();
        $this->mapAdminCashRoutes();
        $this->mapAdminReportRoutes();
        $this->mapRestaurantStaffRoutes();
        $this->mapCatalogSearchRoutes();
    }

    protected function mapHealthRoutes()
    {
        Route::prefix('health')
            ->middleware('api')
            ->group(base_path('routes/health.php'));
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    protected function mapCustomerDashboardRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/customer-dashboard.php'));
    }

    protected function mapKendeRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/kende.php'));
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

    protected function mapAdminReportRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/admin-reports.php'));
    }

    protected function mapRestaurantStaffRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/restaurant-staff.php'));
    }

    protected function mapCatalogSearchRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/search.php'));
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

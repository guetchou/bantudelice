<?php

namespace App\Providers;

use App\Domain\Checkout\Contracts\CheckoutOrchestratorInterface;
use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Services\CheckoutService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CheckoutOrchestratorInterface::class, CheckoutService::class);

        $this->app->singleton(PaymentGatewayFactory::class, function ($app) {
            return new PaymentGatewayFactory(
                mtn:    $app->make(MtnMomoAdapter::class),
                airtel: $app->make(AirtelMoneyAdapter::class),
                paypal: $app->make(PayPalAdapter::class),
                cash:   $app->make(CashDemoAdapter::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Helpers pour Google Maps
        \Blade::directive('googleMapsApiKey', function () {
            $key = config('external-services.geolocation.google_maps.api_key') 
                ?? env('GOOGLE_MAPS_API_KEY');
            return $key ? "'{$key}'" : "null";
        });
        
        \Blade::directive('googleMapsJsUrl', function ($expression) {
            $key = config('external-services.geolocation.google_maps.api_key') 
                ?? env('GOOGLE_MAPS_API_KEY');
            
            if (!$key) {
                return "''";
            }
            
            $libraries = $expression ? trim($expression, "()'\"") : '';
            $callback = '';
            
            // Parser les paramètres
            if (strpos($expression, ',') !== false) {
                list($libraries, $callback) = explode(',', $expression, 2);
                $libraries = trim($libraries, "()'\"");
                $callback = trim($callback, "()'\"");
            }
            
            $url = "https://maps.googleapis.com/maps/api/js?key={$key}";
            if ($libraries) {
                $url .= "&libraries=" . $libraries;
            }
            if ($callback) {
                $url .= "&callback=" . $callback;
            }
            
            return "'{$url}'";
        });
    }
}

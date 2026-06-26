<?php

namespace App\Providers;

use App\Domain\Checkout\Contracts\CheckoutOrchestratorInterface;
use App\Domain\Food\Services\OrderAcceptanceService;
use App\Domain\Food\Services\WorkflowOrderAcceptanceService;
use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Http\Middleware\AuthenticateGePayClient;
use App\Services\CheckoutService;
use App\Services\DeliveryService;
use App\Services\DispatchService;
use App\Services\FoodOrderStateMachineService;
use App\Services\SecureDeliveryService;
use App\Services\SecureDispatchService;
use App\Services\WorkflowCheckoutService;
use App\Services\WorkflowFoodOrderStateMachineService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CheckoutService::class, WorkflowCheckoutService::class);
        $this->app->bind(CheckoutOrchestratorInterface::class, WorkflowCheckoutService::class);
        $this->app->singleton(DeliveryService::class, SecureDeliveryService::class);
        $this->app->singleton(DispatchService::class, SecureDispatchService::class);
        $this->app->singleton(
            FoodOrderStateMachineService::class,
            WorkflowFoodOrderStateMachineService::class
        );
        $this->app->singleton(
            OrderAcceptanceService::class,
            WorkflowOrderAcceptanceService::class
        );

        $this->app->singleton(PaymentGatewayFactory::class, function ($app) {
            return new PaymentGatewayFactory(
                mtn: $app->make(MtnMomoAdapter::class),
                airtel: $app->make(AirtelMoneyAdapter::class),
                paypal: $app->make(PayPalAdapter::class),
                cash: $app->make(CashDemoAdapter::class),
            );
        });
    }

    public function boot()
    {
        $this->app['router']->aliasMiddleware('gepay.client', AuthenticateGePayClient::class);
        $this->loadRoutesFrom(base_path('routes/gepay.php'));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('gepay:reconcile --limit=100')
                ->everyMinute()
                ->name('gepay-reconcile')
                ->withoutOverlapping();
        });

        $this->callAfterResolving(
            \Illuminate\Broadcasting\BroadcastManager::class,
            function (\Illuminate\Broadcasting\BroadcastManager $manager) {
                try {
                    $broadcaster = $manager->connection();
                    if (! ($broadcaster instanceof \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster)) {
                        return;
                    }
                    $pusher = $broadcaster->getPusher();
                    $handler = new \GuzzleHttp\Handler\StreamHandler();
                    $stack = \GuzzleHttp\HandlerStack::create($handler);
                    $stack->push(\GuzzleHttp\Middleware::mapResponse(
                        static function (\Psr\Http\Message\ResponseInterface $response) {
                            if ($response->getStatusCode() === 200) {
                                $body = (string) $response->getBody();
                                if (trim($body) === '') {
                                    return $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor('{}'));
                                }
                            }
                            return $response;
                        }
                    ));
                    $client = new \GuzzleHttp\Client([
                        'handler' => $stack,
                        'timeout' => 10,
                        'connect_timeout' => 5,
                    ]);
                    $ref = new \ReflectionProperty(\Pusher\Pusher::class, 'client');
                    $ref->setAccessible(true);
                    $ref->setValue($pusher, $client);
                } catch (\Throwable $e) {
                    \Log::debug('[Pusher StreamHandler] skip: ' . $e->getMessage());
                }
            }
        );

        \Blade::directive('googleMapsApiKey', function () {
            $key = config('external-services.geolocation.google_maps.api_key')
                ?? config('services.google.maps_key');
            return $key ? "'{$key}'" : 'null';
        });

        \Blade::directive('googleMapsJsUrl', function ($expression) {
            $key = config('external-services.geolocation.google_maps.api_key')
                ?? config('services.google.maps_key');

            if (! $key) {
                return "''";
            }

            $libraries = $expression ? trim($expression, "()'\"") : '';
            $callback = '';

            if (strpos($expression, ',') !== false) {
                [$libraries, $callback] = explode(',', $expression, 2);
                $libraries = trim($libraries, "()'\"");
                $callback = trim($callback, "()'\"");
            }

            $url = "https://maps.googleapis.com/maps/api/js?key={$key}";
            if ($libraries) {
                $url .= '&libraries=' . $libraries;
            }
            if ($callback) {
                $url .= '&callback=' . $callback;
            }

            return "'{$url}'";
        });
    }
}

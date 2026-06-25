<?php

namespace App\Providers;

use App\Domain\Checkout\Contracts\CheckoutOrchestratorInterface;
use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Order;
use App\Services\CheckoutService;
use App\Services\CustomerOrderTimelineService;
use Illuminate\Support\Facades\View;
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
        // ── Fix Soketi/uWebSockets.js + Guzzle CurlMultiHandler incompatibility ──
        // Guzzle's CurlMultiHandler fails on uWebSockets.js HTTP responses (no
        // Content-Length on empty bodies). StreamHandler works correctly.
        // pusher-php-server doesn't expose setClient(), so we use reflection
        // to inject the StreamHandler client into the private $client property.
        $this->callAfterResolving(
            \Illuminate\Broadcasting\BroadcastManager::class,
            function (\Illuminate\Broadcasting\BroadcastManager $manager) {
                try {
                    $broadcaster = $manager->connection();
                    if (!($broadcaster instanceof \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster)) {
                        return;
                    }
                    $pusher  = $broadcaster->getPusher();
                    $handler = new \GuzzleHttp\Handler\StreamHandler();
                    $stack   = \GuzzleHttp\HandlerStack::create($handler);
                    // Middleware: Soketi 1.x retourne un corps vide sur les 200.
                    // Le Pusher SDK attend du JSON → on injecte '{}' si le corps est vide.
                    $stack->push(\GuzzleHttp\Middleware::mapResponse(
                        static function (\Psr\Http\Message\ResponseInterface $response) {
                            if ($response->getStatusCode() === 200) {
                                $body = (string) $response->getBody();
                                if (trim($body) === '') {
                                    return $response->withBody(
                                        \GuzzleHttp\Psr7\Utils::streamFor('{}')
                                    );
                                }
                            }
                            return $response;
                        }
                    ));
                    $client  = new \GuzzleHttp\Client([
                        'handler'         => $stack,
                        'timeout'         => 10,
                        'connect_timeout' => 5,
                    ]);
                    // Injection via réflexion car pusher-php-server ne fournit pas setClient()
                    $ref = new \ReflectionProperty(\Pusher\Pusher::class, 'client');
                    $ref->setAccessible(true);
                    $ref->setValue($pusher, $client);
                } catch (\Throwable $e) {
                    // Ne pas bloquer le boot si Pusher n'est pas le driver actif
                    \Log::debug('[Pusher StreamHandler] skip: ' . $e->getMessage());
                }
            }
        );

        // Helpers pour Google Maps
        \Blade::directive('googleMapsApiKey', function () {
            $key = config('external-services.geolocation.google_maps.api_key')
                ?? config('services.google.maps_key');
            return $key ? "'{$key}'" : "null";
        });
        
        \Blade::directive('googleMapsJsUrl', function ($expression) {
            $key = config('external-services.geolocation.google_maps.api_key')
                ?? config('services.google.maps_key');
            
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

        // La page de suivi historique est volumineuse et possède déjà sa propre
        // timeline de progression. Cette composition injecte uniquement le bloc
        // horodaté, sans exposer les colonnes internes du journal de transitions.
        View::composer('frontend.layouts.app-modern', function ($view): void {
            if (! request()->routeIs('track.order')) {
                return;
            }

            $orderNo = request()->route('orderNo') ?: request()->query('order_no');
            if (! $orderNo) {
                return;
            }

            $order = Order::where('order_no', $orderNo)->first();
            if (! $order) {
                return;
            }

            $statusHistory = app(CustomerOrderTimelineService::class)->forOrder($order);
            $partial = view('frontend.partials.order_status_history', compact('statusHistory'))->render();
            if ($partial === '') {
                return;
            }

            $factory = $view->getFactory();
            $content = (string) $factory->getSection('content', '');

            if (str_contains($content, 'class="trk-history"')) {
                return;
            }

            $marker = '<div class="trk-contacts">';
            $content = str_contains($content, $marker)
                ? str_replace($marker, $partial . $marker, $content)
                : $content . $partial;

            $factory->startSection('content', $content);
        });
    }
}

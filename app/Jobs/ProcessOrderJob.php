<?php

namespace App\Jobs;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Cart;
use App\Services\DeliveryService;
use App\Services\LoyaltyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour traiter la création d'une commande de manière asynchrone
 * 
 * Découpe la création de commande en plusieurs étapes :
 * 1. Création des orders
 * 2. Création des livraisons
 * 3. Dispatch automatique
 * 4. Points de fidélité
 * 5. Notifications
 */
class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $failOnTimeout = true;

    protected $userId;
    protected $orderNo;
    protected $cartItems;
    protected $orderData;

    /**
     * Create a new job instance.
     *
     * @param int $userId
     * @param string $orderNo
     * @param array $cartItems Données des items du panier
     * @param array $orderData Données de la commande (totals, address, etc.)
     */
    public function __construct(int $userId, string $orderNo, array $cartItems, array $orderData)
    {
        $this->userId = $userId;
        $this->orderNo = $orderNo;
        $this->cartItems = $cartItems;
        $this->orderData = $orderData;
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    /**
     * Ce job est intentionnellement désactivé.
     *
     * La création de commande, de livraison, de points de fidélité et
     * de notifications est déjà gérée de façon synchrone dans
     * CustomerOrderController::getOrders(). Si ce job était déclenché,
     * il dupliquerait Cart::delete() et LoyaltyService::addPointsFromOrder(),
     * causant des doubles attributions de points et des paniers supprimés deux fois.
     *
     * Ne pas supprimer ce fichier sans supprimer d'abord son enregistrement
     * dans ModuleQueueService (type 'process_order').
     */
    public function handle(): void
    {
        Log::warning('ProcessOrderJob déclenché alors qu\'il est désactivé — ignorer.', [
            'order_no' => $this->orderNo,
            'user_id'  => $this->userId,
        ]);
    }

    /**
     * Créer les commandes
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createOrders()
    {
        $orders = collect();
        
        foreach ($this->cartItems as $item) {
            $order = Order::create([
                'user_id' => $this->userId,
                'restaurant_id' => $item['restaurant_id'],
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'driver_id' => null,
                'order_no' => $this->orderNo,
                'offer_discount' => $this->orderData['discount'] ?? 0,
                'tax' => $this->orderData['tax'] ?? 0,
                'delivery_charges' => $this->orderData['delivery_fee'] ?? 0,
                'sub_total' => $this->orderData['sub_total'] ?? 0,
                'total' => $this->orderData['total'] ?? 0,
                'admin_commission' => $this->orderData['admin_commission'] ?? 2,
                'restaurant_commission' => $this->orderData['restaurant_commission'] ?? 4,
                'driver_tip' => $this->orderData['driver_tip'] ?? 0,
                'delivery_address' => $this->orderData['delivery_address'],
                'latitude' => $this->orderData['d_lat'] ?? null,
                'longitude' => $this->orderData['d_lng'] ?? null,
                'd_lat' => $this->orderData['d_lat'] ?? '-4.2767',
                'd_lng' => $this->orderData['d_lng'] ?? '15.2832',
                'payment_method' => $this->orderData['payment_method'] ?? 'cash',
                'payment_status' => OrderPaymentStatus::PENDING->value,
                'status' => 'pending',
                'ordered_time' => now(),
            ]);
            
            $orders->push($order);
        }
        
        return $orders;
    }

    /**
     * Créer les livraisons et déclencher dispatch
     * 
     * @param \Illuminate\Database\Eloquent\Collection $orders
     * @return void
     */
    protected function createDeliveries($orders)
    {
        $deliveryService = new DeliveryService();
        
        foreach ($orders as $order) {
            try {
                $delivery = $deliveryService->createForOrder($order);
                
                // Dispatch automatique
                enqueue_job('food', 'auto_assign_delivery', [
                    'delivery' => $delivery,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur création livraison dans job', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                // Ne pas bloquer si une livraison échoue
            }
        }
    }

    /**
     * Ajouter les points de fidélité
     * 
     * @param \Illuminate\Database\Eloquent\Collection $orders
     * @return void
     */
    protected function addLoyaltyPoints($orders)
    {
        $firstOrder = $orders->first();
        if ($firstOrder) {
            try {
                LoyaltyService::addPointsFromOrder(
                    $this->userId,
                    $firstOrder->id,
                    $this->orderData['total'] ?? 0
                );
            } catch (\Exception $e) {
                Log::warning('Erreur ajout points fidélité', [
                    'order_id' => $firstOrder->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [30, 60, 120]; // Retry après 30s, 60s, 120s
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("food:process-order:{$this->orderNo}"))->expireAfter(300),
        ];
    }
}

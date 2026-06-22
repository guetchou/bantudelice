<?php

namespace Tests\Feature;

use App\Delivery;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rendu HTML de track_order.blade.php — l'ETA active (hero + countdown JS)
 * ne doit jamais s'afficher pendant les statuts où aucune préparation/livraison
 * n'a démarré, et ne doit jamais afficher "0 minute restante" comme une ETA réelle.
 */
class TrackOrderEtaViewTest extends TestCase
{
    use RefreshDatabase;

    private const ESTIMATED_TIME = 30;

    private function createRestaurant(): int
    {
        $restUserId = User::factory()->create(['type' => 'restaurant', 'phone' => '0600077001'])->id;

        return DB::table('restaurants')->insertGetId([
            'user_id' => $restUserId,
            'name' => 'Restaurant ETA View Test',
            'user_name' => 'restaurant-eta-view-test',
            'email' => 'eta-view-test@example.com',
            'password' => bcrypt('secret'),
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0600077002',
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'avg_delivery_time' => self::ESTIMATED_TIME,
            'account_name' => 'Restaurant ETA View Test',
            'account_number' => 'REST-ETA-VIEW-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(int $restaurantId, string $orderNo, string $businessStatus, array $overrides = []): array
    {
        $owner = User::factory()->create(['type' => 'user', 'phone' => '06000' . random_int(70000, 79999)]);

        $orderId = DB::table('orders')->insertGetId(array_merge([
            'order_no' => $orderNo,
            'user_id' => $owner->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 1000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 1000,
            'total' => 1000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => $businessStatus,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Test',
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now(),
        ], $overrides));

        return [$owner, $orderId];
    }

    private function getTrackOrderHtml(User $owner, string $orderNo): string
    {
        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => $orderNo]));
        $response->assertStatus(200);

        return $response->getContent();
    }

    public function test_pending_restaurant_acceptance_has_no_active_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETAV-0001', 'pending_restaurant_acceptance');

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0001');

        $this->assertStringNotContainsString('id="etaCountdown"', $html);
        $this->assertStringNotContainsString('0 min</div>', $html);
    }

    public function test_accepted_awaiting_payment_shows_payment_block_not_zero_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETAV-0002', 'accepted_awaiting_payment');

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0002');

        $this->assertStringNotContainsString('id="etaCountdown"', $html);
        $this->assertStringContainsString('Finalisez votre paiement', $html);
    }

    public function test_confirmed_has_no_active_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETAV-0003', 'confirmed');

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0003');

        $this->assertStringNotContainsString('id="etaCountdown"', $html);
    }

    public function test_in_kitchen_can_show_an_active_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETAV-0004', 'in_kitchen', [
            'preparation_started_at' => now(),
        ]);

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0004');

        $this->assertStringContainsString('id="etaCountdown"', $html);
    }

    public function test_out_for_delivery_can_show_an_active_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner, $orderId] = $this->createOrder($restaurantId, 'TD-ETAV-0005', 'out_for_delivery', [
            'preparation_started_at' => now()->subMinutes(20),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(10),
            'picked_up_at' => now()->subMinutes(2),
        ]);

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0005');

        $this->assertStringContainsString('id="etaCountdown"', $html);
    }

    public function test_delivered_has_no_remaining_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner, $orderId] = $this->createOrder($restaurantId, 'TD-ETAV-0006', 'delivered', [
            'preparation_started_at' => now()->subMinutes(60),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'status' => 'DELIVERED',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(50),
            'picked_up_at' => now()->subMinutes(40),
            'delivered_at' => now()->subMinutes(5),
        ]);

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0006');

        $this->assertStringNotContainsString('id="etaCountdown"', $html);
    }

    public function test_cancelled_has_no_remaining_eta(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETAV-0007', 'cancelled');

        $html = $this->getTrackOrderHtml($owner, 'TD-ETAV-0007');

        $this->assertStringNotContainsString('id="etaCountdown"', $html);
        $this->assertStringContainsString('Commande annulée', $html);
    }
}

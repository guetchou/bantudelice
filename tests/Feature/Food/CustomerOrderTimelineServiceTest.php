<?php

namespace Tests\Feature\Food;

use App\Order;
use App\Services\CustomerOrderTimelineService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOrderTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deduplicates_product_rows_and_hides_internal_transition_data(): void
    {
        config(['app.timezone' => 'Africa/Brazzaville']);

        $client = User::factory()->create(['type' => 'user']);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Timeline',
            'user_name' => 'restaurant-timeline',
            'email' => 'timeline@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Brazzaville',
            'phone' => '0500000000',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Timeline',
            'account_number' => 'REST-TIMELINE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdAt = Carbon::parse('2026-06-25 10:00:00', 'UTC');
        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'TD-TIMELINE-001',
            'user_id' => $client->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 3000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 3000,
            'total' => 3500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'completed',
            'business_status' => 'delivered',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'delivery_address' => 'Adresse confidentielle',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->insertLog($orderId, 'pending_restaurant_acceptance', 'confirmed', 'system', null, $createdAt->copy()->addMinutes(5));
        $this->insertLog($orderId, 'confirmed', 'in_kitchen', 'restaurant', null, $createdAt->copy()->addMinutes(10));

        // Simule les lignes dupliquées produites par plusieurs produits d'une même commande.
        $this->insertLog($orderId + 1, 'confirmed', 'in_kitchen', 'restaurant', null, $createdAt->copy()->addMinutes(10)->addSecond());

        // Cette transition administrative ne doit jamais être rendue côté client.
        $this->insertLog(
            $orderId,
            'in_kitchen',
            'out_for_delivery',
            'admin',
            'status_transition_forced_unpaid',
            $createdAt->copy()->addMinutes(20),
            'Note interne confidentielle'
        );

        $this->insertLog($orderId, 'in_kitchen', 'delivered', 'driver', null, $createdAt->copy()->addMinutes(40));

        $order = Order::findOrFail($orderId);
        $history = app(CustomerOrderTimelineService::class)->forOrder($order);

        $this->assertSame([
            'pending_restaurant_acceptance',
            'confirmed',
            'in_kitchen',
            'delivered',
        ], $history->pluck('status')->all());

        $this->assertSame('11:00', $history->first()['time_label']);
        $this->assertSame('11:40', $history->last()['time_label']);
        $this->assertCount(1, $history->where('status', 'in_kitchen'));
        $this->assertFalse($history->contains('status', 'out_for_delivery'));

        foreach ($history as $entry) {
            $this->assertArrayNotHasKey('actor_type', $entry);
            $this->assertArrayNotHasKey('actor_id', $entry);
            $this->assertArrayNotHasKey('reason_code', $entry);
            $this->assertArrayNotHasKey('notes', $entry);
            $this->assertArrayNotHasKey('context', $entry);
        }
    }

    private function insertLog(
        int $orderId,
        string $from,
        string $to,
        string $actorType,
        ?string $reasonCode,
        Carbon $occurredAt,
        ?string $notes = null
    ): void {
        DB::table('order_status_logs')->insert([
            'order_no' => 'TD-TIMELINE-001',
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'legacy_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorType === 'admin' ? 99 : null,
            'reason_code' => $reasonCode,
            'notes' => $notes,
            'context' => $notes ? json_encode(['secret' => true]) : null,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }
}

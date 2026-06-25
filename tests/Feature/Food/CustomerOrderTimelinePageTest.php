<?php

namespace Tests\Feature\Food;

use App\Http\Middleware\EnsureModuleEnabled;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOrderTimelinePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_sees_timestamped_history_without_internal_notes(): void
    {
        config(['app.timezone' => 'Africa/Brazzaville']);

        $client = User::factory()->create(['type' => 'user']);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Timeline Page',
            'user_name' => 'restaurant-timeline-page',
            'email' => 'timeline-page@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Brazzaville',
            'phone' => '0500000000',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Timeline Page',
            'account_number' => 'REST-TIMELINE-PAGE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdAt = Carbon::parse('2026-06-25 10:00:00', 'UTC');
        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'TD-TIMELINE-PAGE-001',
            'user_id' => $client->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 3000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 3000,
            'total' => 3000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'prepairing',
            'business_status' => 'in_kitchen',
            'fulfillment_mode' => 'pickup',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Retrait au restaurant',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->insertLog($orderId, 'pending_restaurant_acceptance', 'confirmed', 'system', null, $createdAt->copy()->addMinutes(5));
        $this->insertLog($orderId, 'confirmed', 'in_kitchen', 'restaurant', null, $createdAt->copy()->addMinutes(10));
        $this->insertLog(
            $orderId,
            'in_kitchen',
            'out_for_delivery',
            'admin',
            'status_transition_forced_unpaid',
            $createdAt->copy()->addMinutes(20),
            'Note interne confidentielle'
        );

        $response = $this->actingAs($client)
            ->withoutMiddleware(EnsureModuleEnabled::class)
            ->get(route('track.order', ['orderNo' => 'TD-TIMELINE-PAGE-001']));

        $response->assertOk();
        $response->assertSee('Étapes de votre commande');
        $response->assertSee('Commande confirmée');
        $response->assertSee('En préparation');
        $response->assertSee('11:05');
        $response->assertSee('11:10');
        $response->assertDontSee('Note interne confidentielle');
        $response->assertDontSee('status_transition_forced_unpaid');
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
            'order_no' => 'TD-TIMELINE-PAGE-001',
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

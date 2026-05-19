<?php

namespace Tests\Feature;

use App\Delivery;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private function createTrackedOrderFixture(string $suffix = '1'): array
    {
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '060100' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
        ]);

        $restaurantUser = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '060200' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Tracking ' . $suffix,
            'user_name' => 'restaurant-tracking-' . $suffix,
            'email' => "restaurant-tracking-{$suffix}@example.com",
            'password' => bcrypt('secret'),
            'slogan' => 'Tracking',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse tracking',
            'latitude' => null,
            'longitude' => null,
            'phone' => '060300' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Tracking',
            'account_number' => 'REST-TRACK-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur Tracking ' . $suffix,
            'user_name' => 'driver-tracking-' . $suffix,
            'phone' => '070100' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
            'email' => "driver-tracking-{$suffix}@example.com",
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-TRACK-' . $suffix,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'ORD-TRACK-' . $suffix,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'product_id' => null,
            'qty' => 1,
            'price' => 4200,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 4200,
            'total' => 4700,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'assign',
            'business_status' => 'out_for_delivery',
            'technical_status' => null,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Adresse client',
            'scheduled_date' => null,
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now()->subHour(),
            'delivered_time' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $delivery = Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(15),
        ]);

        return [$customer, $delivery->fresh('order')];
    }

    public function test_customer_cannot_access_tracking_for_unknown_or_foreign_order(): void
    {
        [$customer] = $this->createTrackedOrderFixture('11');
        [, $foreignDelivery] = $this->createTrackedOrderFixture('12');

        $this->actingAs($customer, 'api')
            ->getJson('/api/orders/' . $foreignDelivery->order_id . '/tracking')
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Commande introuvable',
            ]);
    }

    public function test_customer_cannot_confirm_unknown_or_foreign_order_delivery(): void
    {
        [$customer] = $this->createTrackedOrderFixture('21');
        [, $foreignDelivery] = $this->createTrackedOrderFixture('22');

        $this->actingAs($customer, 'api')
            ->postJson('/api/orders/' . $foreignDelivery->order_id . '/confirm-delivery', [
                'customer_confirmed' => true,
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Commande introuvable',
            ]);
    }

    public function test_customer_cannot_report_incident_or_request_redelivery_for_unknown_or_foreign_order(): void
    {
        [$customer] = $this->createTrackedOrderFixture('31');
        [, $foreignDelivery] = $this->createTrackedOrderFixture('32');

        $this->actingAs($customer, 'api')
            ->postJson('/api/orders/' . $foreignDelivery->order_id . '/incident', [
                'reason' => 'missing_item',
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Commande introuvable',
            ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/orders/' . $foreignDelivery->order_id . '/redelivery', [
                'notes' => 'Merci de relancer',
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Commande introuvable',
            ]);
    }
}

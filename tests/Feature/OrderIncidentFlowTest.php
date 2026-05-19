<?php

namespace Tests\Feature;

use App\Delivery;
use App\SupportTicket;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderIncidentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createDeliveryOrderFixture(string $orderNo = 'ORD-INC-100'): array
    {
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '0600002001',
        ]);

        $restaurantUser = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600002002',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Incident Test',
            'user_name' => 'restaurant-incident-test',
            'email' => 'restaurant-incident@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Incident',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'latitude' => null,
            'longitude' => null,
            'phone' => '0600002003',
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Incident',
            'account_number' => 'REST-INC-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Incident Test',
            'user_name' => 'driver-incident-test',
            'phone' => '0700002001',
            'email' => 'driver-incident@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-INCIDENT-1',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => $orderNo,
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
            'assigned_at' => now()->subMinutes(45),
            'picked_up_at' => now()->subMinutes(20),
        ]);

        return [$customer, $restaurantUser, $delivery->fresh('order')];
    }

    public function test_customer_can_report_incident_and_support_ticket_is_created(): void
    {
        [$customer, , $delivery] = $this->createDeliveryOrderFixture('ORD-INC-101');

        $this->actingAs($customer)
            ->from(route('track.order', ['orderNo' => $delivery->order->order_no]))
            ->post(route('track.order.incident', ['orderNo' => $delivery->order->order_no]), [
                'reason' => 'customer_absent',
                'notes' => 'Client absent au point de livraison',
            ])
            ->assertRedirect(route('track.order', ['orderNo' => $delivery->order->order_no]));

        $delivery->refresh();

        $this->assertSame('open', $delivery->incident_status);
        $this->assertSame('customer_absent', $delivery->incident_reason);
        $this->assertSame('delivery_attempt_failed', $delivery->order->fresh()->business_status);

        $this->assertDatabaseHas('support_tickets', [
            'module' => 'food',
            'category' => 'incident',
            'delivery_id' => $delivery->id,
            'order_no' => $delivery->order->order_no,
            'status' => 'open',
        ]);
    }

    public function test_customer_can_request_redelivery_after_incident(): void
    {
        [$customer, , $delivery] = $this->createDeliveryOrderFixture('ORD-INC-102');

        $this->actingAs($customer)
            ->post(route('track.order.incident', ['orderNo' => $delivery->order->order_no]), [
                'reason' => 'customer_absent',
                'notes' => 'Premiere tentative ratee',
            ]);

        $this->actingAs($customer)
            ->from(route('track.order', ['orderNo' => $delivery->order->order_no]))
            ->post(route('track.order.redelivery', ['orderNo' => $delivery->order->order_no]), [
                'notes' => 'Merci de relancer la livraison',
            ])
            ->assertRedirect(route('track.order', ['orderNo' => $delivery->order->order_no]));

        $delivery->refresh();

        $this->assertSame('ON_THE_WAY', $delivery->status);
        $this->assertSame('pending_redelivery', $delivery->support_status);
        $this->assertNotNull($delivery->redelivery_requested_at);
        $this->assertSame('out_for_delivery', $delivery->order->fresh()->business_status);
        $this->assertDatabaseHas('support_tickets', [
            'delivery_id' => $delivery->id,
            'status' => 'pending_redelivery',
        ]);
    }

    public function test_admin_can_resolve_incident_without_cancelling_order(): void
    {
        [$customer, , $delivery] = $this->createDeliveryOrderFixture('ORD-INC-103');
        $admin = User::factory()->create(['type' => 'admin']);

        $this->actingAs($customer)
            ->post(route('track.order.incident', ['orderNo' => $delivery->order->order_no]), [
                'reason' => 'missing_item',
                'notes' => 'Incident signale par le client',
            ]);

        $adminShowOrderUrl = url('/admin/show_order/' . $delivery->order->order_no);

        $response = $this->actingAs($admin)
            ->from($adminShowOrderUrl)
            ->post(route('admin.resolve_incident', ['order' => $delivery->order->order_no]), [
                'resolution' => 'resolved',
                'support_notes' => 'Incident traite par le support',
            ]);

        $response->assertRedirect($adminShowOrderUrl);

        $delivery->refresh();
        $ticket = SupportTicket::where('delivery_id', $delivery->id)->latest('id')->first();

        $this->assertSame('resolved', $delivery->incident_status);
        $this->assertSame('resolved', $delivery->support_status);
        $this->assertSame('Incident traite par le support', $delivery->support_notes);
        $this->assertNotNull($delivery->support_resolved_at);
        $this->assertNotNull($ticket);
        $this->assertSame('resolved', $ticket->status);
    }
}

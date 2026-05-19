<?php

namespace Tests\Feature;

use App\Restaurant;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransportDriverDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_driver_can_accept_booking_from_web_session(): void
    {
        $restaurant = $this->createRestaurant();
        $driverUser = User::factory()->create([
            'type' => 'driver',
            'name' => 'Paul Conducteur',
            'email' => 'driver-transport-web@example.com',
            'phone' => '0600071001',
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurant->id,
            'name' => 'Paul Conducteur',
            'user_name' => 'paul-conducteur',
            'phone' => '0600071001',
            'email' => 'driver-transport-web@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver transport',
            'cnic' => 'CNIC-TRANSPORT-WEB-001',
            'approved' => 1,
            'status' => 'online',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehicleId = DB::table('transport_vehicles')->insertGetId([
            'uuid'         => (string) \Illuminate\Support\Str::uuid(),
            'make'         => 'Toyota',
            'model'        => 'Corolla',
            'year'         => '2023',
            'plate_number' => 'TR-WEB-001',
            'color'        => 'Noir',
            'type'         => 'taxi',
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('drivers')
            ->where('id', $driverId)
            ->update([
                'active_transport_vehicle_id' => $vehicleId,
                'updated_at' => now(),
            ]);

        $bookingUuid = '44444444-4444-4444-4444-444444444444';
        DB::table('transport_bookings')->insert([
            'uuid' => $bookingUuid,
            'booking_no' => 'TR-WEB-001',
            'type' => 'taxi',
            'user_id' => User::factory()->create([
                'type' => 'user',
                'phone' => '0600071002',
            ])->id,
            'driver_id' => null,
            'vehicle_id' => null,
            'pickup_address' => 'Centre-ville',
            'pickup_lat' => -4.2634,
            'pickup_lng' => 15.2429,
            'dropoff_address' => 'Moungali',
            'dropoff_lat' => -4.2500,
            'dropoff_lng' => 15.2800,
            'estimated_distance' => 6,
            'estimated_duration' => 14,
            'estimated_price' => 3200,
            'actual_price' => null,
            'tax' => 0,
            'discount' => 0,
            'total_price' => 3200,
            'payment_method' => 'cash',
            'payment_status' => 'pending_cash',
            'status' => 'requested',
            'notes' => null,
            'cancel_reason' => null,
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
            'deleted_at' => null,
        ]);

        $response = $this->actingAs($driverUser)
            ->postJson("/transport/xhr/driver/bookings/{$bookingUuid}/accept");

        $response->assertOk()
            ->assertJsonPath('booking.driver_id', $driverId)
            ->assertJsonPath('booking.status', 'assigned');

        $this->assertDatabaseHas('transport_bookings', [
            'uuid' => $bookingUuid,
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'status' => 'assigned',
        ]);
    }

    public function test_transport_driver_dashboard_keeps_picked_up_booking_visible(): void
    {
        $restaurant = $this->createRestaurant();
        $driverUser = User::factory()->create([
            'type' => 'driver',
            'name' => 'Jean Test',
            'email' => 'driver-transport-test@example.com',
            'phone' => '0600070001',
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurant->id,
            'name' => 'Jean Test',
            'user_name' => 'jean-test',
            'phone' => '0600070001',
            'email' => 'driver-transport-test@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-TRANSPORT-001',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transport_bookings')->insert([
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'booking_no' => 'TR-PICKUP-001',
            'type' => 'taxi',
            'user_id' => User::factory()->create([
                'type' => 'user',
                'phone' => '0600070002',
            ])->id,
            'driver_id' => $driverId,
            'vehicle_id' => null,
            'pickup_address' => 'Plateau',
            'pickup_lat' => 0,
            'pickup_lng' => 0,
            'dropoff_address' => 'Moungali',
            'dropoff_lat' => 0,
            'dropoff_lng' => 0,
            'estimated_distance' => 4,
            'estimated_duration' => 12,
            'estimated_price' => 2500,
            'actual_price' => null,
            'tax' => 0,
            'discount' => 0,
            'total_price' => 2500,
            'payment_method' => 'cash',
            'payment_status' => 'pending_cash',
            'status' => 'picked_up',
            'notes' => null,
            'cancel_reason' => null,
            'driver_arrived_at' => now()->subMinutes(5),
            'picked_up_at' => now()->subMinute(),
            'last_status_changed_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinute(),
            'deleted_at' => null,
        ]);

        $response = $this->actingAs($driverUser)->get(route('driver.transport.dashboard'));

        $response->assertOk();
        $response->assertViewHas('activeBooking', function ($booking) {
            return $booking !== null
                && $booking->status->value === 'picked_up'
                && $booking->pickup_address === 'Plateau'
                && $booking->dropoff_address === 'Moungali';
        });
    }

    private function createRestaurant(): Restaurant
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600070099',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Restaurant Transport Test',
            'user_name' => 'restaurant-transport-test',
            'email' => 'restaurant-transport-test@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0600070098',
            'admin_commission' => 20,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Transport Test',
            'account_number' => 'REST-TRANSPORT-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Restaurant::findOrFail($restaurantId);
    }
}

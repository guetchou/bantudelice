<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Driver;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class TransportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_estimate()
    {
        $response = $this->postJson('/api/v1/transport/estimate', [
            'type' => 'taxi',
            'distance' => 10,
            'duration' => 20
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['estimated_price', 'currency']);
    }

    public function test_user_can_create_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/transport/bookings', [
            'type' => 'taxi',
            'pickup_address' => 'Point A',
            'pickup_lat' => -4.27,
            'pickup_lng' => 15.28,
            'dropoff_address' => 'Point B',
            'dropoff_lat' => -4.28,
            'dropoff_lng' => 15.29,
            'payment_method' => 'cash'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('booking.status', 'requested');
            
        $this->assertDatabaseHas('transport_bookings', [
            'user_id' => $user->id,
            'pickup_address' => 'Point A'
        ]);
    }

    public function test_driver_can_accept_booking()
    {
        $user = User::factory()->create();
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        
        $restaurantId = \Illuminate\Support\Facades\DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Test Restaurant',
            'email' => 'res@test.com',
            'password' => bcrypt('password'),
            'address' => 'Test Address',
            'phone' => '064000001',
            'services' => 'delivery',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 5,
            'admin_commission' => 10,
            'account_name' => 'Test Acc',
            'account_number' => '123456789',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $driverId = \Illuminate\Support\Facades\DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Test Driver',
            'user_name' => 'testdriver',
            'email' => 'driver@test.com',
            'password' => bcrypt('password'),
            'phone' => '064000000',
            'address' => 'Driver Address',
            'cnic' => '12345',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $driver = Driver::find($driverId);
        $driver->update(['status' => 'online']);
        
        // Create and approve vehicle for driver
        $vehicle = TransportVehicle::create([
            'make' => 'Toyota', 'model' => 'Camry', 'year' => '2022', 'plate_number' => 'TEST-OK',
            'color' => 'Black', 'type' => 'taxi', 'status' => 'active'
        ]);
        $driver->update(['active_transport_vehicle_id' => $vehicle->id]);
        
        $booking = TransportBooking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'booking_no' => 'TR-TEST',
            'type' => TransportType::TAXI,
            'user_id' => $user->id,
            'pickup_address' => 'Point A',
            'pickup_lat' => -4.27,
            'pickup_lng' => 15.28,
            'dropoff_address' => 'Point B',
            'dropoff_lat' => -4.28,
            'dropoff_lng' => 15.29,
            'estimated_price' => 5000,
            'status' => TransportStatus::REQUESTED,
            'payment_method' => 'cash'
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept");

        $response->assertStatus(200);
        $this->assertEquals(TransportStatus::ASSIGNED->value, $booking->fresh()->status->value);
        $this->assertEquals($driver->id, $booking->fresh()->driver_id);
    }

    public function test_driver_can_see_nearby_requests()
    {
        $user = User::factory()->create();
        
        // Request 1: Close (1km)
        TransportBooking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'booking_no' => 'TR-CLOSE',
            'type' => 'taxi', 'user_id' => $user->id,
            'pickup_address' => 'Close', 'pickup_lat' => -4.27, 'pickup_lng' => 15.28,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.28, 'dropoff_lng' => 15.29,
            'status' => 'requested', 'payment_method' => 'cash'
        ]);

        // Request 2: Far (20km)
        TransportBooking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'booking_no' => 'TR-FAR',
            'type' => 'taxi', 'user_id' => $user->id,
            'pickup_address' => 'Far', 'pickup_lat' => -4.0, 'pickup_lng' => 15.0,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.28, 'dropoff_lng' => 15.29,
            'status' => 'requested', 'payment_method' => 'cash'
        ]);

        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = \Illuminate\Support\Facades\DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id, 'name' => 'Near Rest', 'email' => 'near@rest.com',
            'password' => bcrypt('password'), 'address' => 'Test', 'phone' => '064000444',
            'services' => 'delivery', 'delivery_charges' => 500, 'city' => 'Brazzaville',
            'tax' => 5, 'admin_commission' => 10, 'account_name' => 'Test', 'account_number' => '1',
            'created_at' => now(), 'updated_at' => now()
        ]);

        $driverId = \Illuminate\Support\Facades\DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId, 'name' => 'Near Driver', 'user_name' => 'neardriver',
            'email' => 'near@test.com', 'password' => bcrypt('password'), 'phone' => '064000333',
            'address' => 'Test', 'cnic' => 'NEAR1', 'created_at' => now(), 'updated_at' => now()
        ]);
        $driver = Driver::find($driverId);
        Sanctum::actingAs($driver);

        $response = $this->getJson("/api/v1/transport/driver/nearby?lat=-4.27&lng=15.28&radius=5");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('TR-CLOSE', $response->json()[0]['booking_no']);
    }

    public function test_driver_cannot_accept_without_approved_vehicle()
    {
        $user = User::factory()->create();
        
        // Setup driver (manual as before)
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = \Illuminate\Support\Facades\DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id, 'name' => 'Test Rest', 'email' => 'res@test.com',
            'password' => bcrypt('password'), 'address' => 'Test', 'phone' => '064000111',
            'services' => 'delivery', 'delivery_charges' => 500, 'city' => 'Brazzaville',
            'tax' => 5, 'admin_commission' => 10, 'account_name' => 'Test', 'account_number' => '1',
            'created_at' => now(), 'updated_at' => now()
        ]);
        $driverId = \Illuminate\Support\Facades\DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId, 'name' => 'Test Driver', 'user_name' => 'testdriver2',
            'email' => 'driver2@test.com', 'password' => bcrypt('password'), 'phone' => '064000222',
            'address' => 'Test', 'cnic' => '123', 'created_at' => now(), 'updated_at' => now()
        ]);
        $driver = Driver::find($driverId);
        $driver->update(['status' => 'online']);

        // Create a vehicle but not approved
        $vehicle = TransportVehicle::create([
            'make' => 'Toyota', 'model' => 'Corolla', 'year' => '2020', 'plate_number' => 'ABC-123',
            'color' => 'White', 'type' => 'taxi', 'status' => 'pending'
        ]);

        $driver->update(['active_transport_vehicle_id' => $vehicle->id]);

        $booking = TransportBooking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'booking_no' => 'TR-FAIL',
            'type' => TransportType::TAXI,
            'user_id' => $user->id,
            'pickup_address' => 'Point A',
            'pickup_lat' => -4.27,
            'pickup_lng' => 15.28,
            'dropoff_address' => 'Point B',
            'dropoff_lat' => -4.28,
            'dropoff_lng' => 15.29,
            'estimated_price' => 5000,
            'status' => TransportStatus::REQUESTED,
            'payment_method' => 'cash'
        ]);

        Sanctum::actingAs($driver);
        $response = $this->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Votre véhicule n\'est pas encore approuvé par l\'administration']);
    }

    public function test_full_e2e_flow_with_logs()
    {
        // 1. Setup
        $user = User::factory()->create();
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = \Illuminate\Support\Facades\DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id, 'name' => 'E2E Rest', 'email' => 'e2e@test.com',
            'password' => bcrypt('password'), 'address' => 'Test', 'phone' => '064000999',
            'services' => 'delivery', 'delivery_charges' => 500, 'city' => 'Brazzaville',
            'tax' => 5, 'admin_commission' => 10, 'account_name' => 'Test', 'account_number' => '1',
            'created_at' => now(), 'updated_at' => now()
        ]);
        $driverId = \Illuminate\Support\Facades\DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId, 'name' => 'E2E Driver', 'user_name' => 'e2edriver',
            'email' => 'e2edriver@test.com', 'password' => bcrypt('password'), 'phone' => '064000888',
            'address' => 'Test', 'cnic' => 'E2E123', 'created_at' => now(), 'updated_at' => now()
        ]);
        $driver = Driver::find($driverId);
        $driver->update(['status' => 'online']);

        // Create and approve vehicle for E2E driver
        $vehicle = TransportVehicle::create([
            'make' => 'Toyota', 'model' => 'RAV4', 'year' => '2021', 'plate_number' => 'E2E-PLATE',
            'color' => 'Silver', 'type' => 'taxi', 'status' => 'active'
        ]);
        $driver->update(['active_transport_vehicle_id' => $vehicle->id]);

        // 2. Client: Create Booking
        Sanctum::actingAs($user);
        $res = $this->postJson('/api/v1/transport/bookings', [
            'type' => 'taxi', 'pickup_address' => 'A', 'pickup_lat' => -4.2, 'pickup_lng' => 15.2,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.3, 'dropoff_lng' => 15.3, 'payment_method' => 'cash'
        ]);
        $bookingUuid = $res->json('booking.uuid');

        // 3. Driver: Accept
        Sanctum::actingAs($driver);
        $this->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/accept")->assertStatus(200);

        // 4. Driver: Status Updates
        $this->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'driver_arriving'])->assertStatus(200);
        $this->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'in_progress'])->assertStatus(200);
        $this->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'completed'])->assertStatus(200);

        // 5. Client: Pay (Simulate MoMo)
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/transport/bookings/{$bookingUuid}/pay", ['provider' => 'momo'])->assertStatus(200);

        $this->assertTrue(true);
    }
}

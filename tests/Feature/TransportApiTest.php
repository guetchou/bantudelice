<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Driver;
use App\Payment;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportRequestBroadcasted;
use App\Domain\Transport\Events\TransportRequestCreated;
use App\Domain\Transport\Events\TransportBookingStatusUpdated;
use App\Domain\Transport\Events\BookingAssigned;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class TransportApiTest extends TestCase
{
    use RefreshDatabase;

    private function provisionOperationalTransportDriver(
        string $suffix = '1',
        float $lat = -4.27,
        float $lng = 15.28
    ): array {
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = \Illuminate\Support\Facades\DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Transport Hub ' . $suffix,
            'email' => "transport-hub-{$suffix}@test.com",
            'password' => bcrypt('password'),
            'address' => 'Test Address',
            'phone' => '0641000' . $suffix,
            'services' => 'transport',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'admin_commission' => 10,
            'account_name' => 'Transport Hub',
            'account_number' => 'TR-HUB-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = \Illuminate\Support\Facades\DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Transport Driver ' . $suffix,
            'user_name' => 'transport-driver-' . $suffix,
            'email' => "transport-driver-{$suffix}@test.com",
            'password' => bcrypt('password'),
            'phone' => '0642000' . $suffix,
            'address' => 'Driver Address',
            'cnic' => 'TR-CNIC-' . $suffix,
            'status' => 'online',
            'latitude' => $lat,
            'longitude' => $lng,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::findOrFail($driverId);

        $vehicle = TransportVehicle::create([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => '2022',
            'plate_number' => 'TR-' . $suffix,
            'color' => 'Black',
            'type' => 'taxi',
            'status' => 'active',
        ]);

        $driver->update(['active_transport_vehicle_id' => $vehicle->id]);

        return [$driver, $vehicle];
    }

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
        Event::fake([TransportRequestBroadcasted::class]);

        $user = User::factory()->create();
        [$driver] = $this->provisionOperationalTransportDriver('create', -4.2705, 15.2805);

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/transport/bookings', [
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
            ->assertJsonPath('booking.status', 'requested')
            ->assertJsonPath('serviceability.assignment_status', 'broadcasting')
            ->assertJsonPath('booking.driver_id', null)
            ->assertJsonPath('serviceability.best_driver.id', $driver->id)
            ->assertJsonPath('serviceability.first_accept_wins', true);
            
        $this->assertDatabaseHas('transport_bookings', [
            'user_id' => $user->id,
            'pickup_address' => 'Point A',
            'status' => TransportStatus::REQUESTED->value,
        ]);

        Event::assertDispatched(TransportRequestBroadcasted::class, function (TransportRequestBroadcasted $event) use ($driver) {
            $channels = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-transport.driver.' . $driver->id . '.requests'
                && $event->broadcastAs() === 'transport.request.broadcasted'
                && $payload['first_accept_wins'] === true
                && $payload['status'] === TransportStatus::REQUESTED->value;
        });
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

        $response = $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept");

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
        $response = $this->actingAs($driver, 'driver_api')
            ->getJson("/api/v1/transport/driver/nearby?lat=-4.27&lng=15.28&radius=5");

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

        $response = $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Votre véhicule n\'est pas encore approuvé par l\'administration']);
    }

    public function test_first_driver_accepting_wins_the_broadcast_request(): void
    {
        $user = User::factory()->create();
        [$driverOne] = $this->provisionOperationalTransportDriver('win1', -4.2705, 15.2805);
        [$driverTwo] = $this->provisionOperationalTransportDriver('win2', -4.2707, 15.2807);

        $booking = TransportBooking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'booking_no' => 'TR-FIRST-WIN',
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
            'payment_method' => 'cash',
        ]);

        $this->actingAs($driverOne, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept")
            ->assertStatus(200)
            ->assertJsonPath('booking.driver_id', $driverOne->id);

        $this->actingAs($driverTwo, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$booking->uuid}/accept")
            ->assertStatus(409)
            ->assertJson(['error' => 'Cette demande a déjà été prise par un autre chauffeur']);

        $this->assertSame($driverOne->id, $booking->fresh()->driver_id);
        $this->assertSame(TransportStatus::ASSIGNED->value, $booking->fresh()->status->value);
    }

    public function test_full_e2e_flow_with_logs()
    {
        // 1. Setup
        $user = User::factory()->create();
        [$driver] = $this->provisionOperationalTransportDriver('22', -4.2705, 15.2805);

        // 2. Client: Create Booking
        $res = $this->actingAs($user, 'api')->postJson('/api/v1/transport/bookings', [
            'type' => 'taxi', 'pickup_address' => 'A', 'pickup_lat' => -4.27, 'pickup_lng' => 15.28,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.28, 'dropoff_lng' => 15.29, 'payment_method' => 'cash'
        ]);
        $res->assertStatus(201)
            ->assertJsonPath('serviceability.assignment_status', 'broadcasting');
        $bookingUuid = $res->json('booking.uuid');

        // 3. Driver accepts the broadcast request
        $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/accept")
            ->assertStatus(200);

        // 4. Driver: Status Updates
        $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'driver_arriving'])
            ->assertStatus(200);
        $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'picked_up'])
            ->assertStatus(200);
        $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'in_progress'])
            ->assertStatus(200);
        $this->actingAs($driver, 'driver_api')
            ->postJson("/api/v1/transport/driver/bookings/{$bookingUuid}/status", ['status' => 'completed'])
            ->assertStatus(200);

        // 5. Client: Pay (Simulate MoMo)
        $booking = TransportBooking::where('uuid', $bookingUuid)->firstOrFail();
        $payment = Payment::create([
            'user_id' => $user->id,
            'transport_booking_id' => $booking->id,
            'provider' => 'momo',
            'provider_reference' => 'TEST-TRANSPORT-MOMO-001',
            'status' => 'PENDING',
            'amount' => (int) round((float) ($booking->total_price ?? $booking->estimated_price ?? 0)),
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($payment) {
            $mock->shouldReceive('startManagedPayment')
                ->once()
                ->andReturn([
                    'payment' => $payment,
                    'payment_payload' => [
                        'redirect_url' => null,
                    ],
                ]);
        });

        $this->actingAs($user, 'api')
            ->postJson("/api/v1/transport/bookings/{$bookingUuid}/pay", ['provider' => 'momo', 'phone' => '060000000'])
            ->assertStatus(200);

        $this->assertTrue(true);
    }

    public function test_pay_with_airtel_provider()
    {
        Event::fake([
            TransportRequestBroadcasted::class,
            TransportRequestCreated::class,
            TransportBookingStatusUpdated::class,
            BookingAssigned::class,
            TransportMissionPresenceUpdated::class,
        ]);

        $user = User::factory()->create();
        [$driver] = $this->provisionOperationalTransportDriver('airtel', -4.2705, 15.2805);

        $res = $this->actingAs($user, 'api')->postJson('/api/v1/transport/bookings', [
            'type' => 'taxi', 'pickup_address' => 'A', 'pickup_lat' => -4.27, 'pickup_lng' => 15.28,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.28, 'dropoff_lng' => 15.29, 'payment_method' => 'airtel'
        ]);
        $res->assertStatus(201);
        $bookingUuid = $res->json('booking.uuid');

        $booking = TransportBooking::where('uuid', $bookingUuid)->firstOrFail();
        $payment = Payment::create([
            'user_id' => $user->id,
            'transport_booking_id' => $booking->id,
            'provider' => 'airtel',
            'provider_reference' => 'TEST-TRANSPORT-AIRTEL-001',
            'status' => 'PENDING',
            'amount' => (int) round((float) ($booking->total_price ?? $booking->estimated_price ?? 0)),
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($payment) {
            $mock->shouldReceive('startManagedPayment')
                ->once()
                ->andReturn([
                    'payment' => $payment,
                    'payment_payload' => [
                        'redirect_url' => null,
                    ],
                ]);
        });

        $this->actingAs($user, 'api')
            ->postJson("/api/v1/transport/bookings/{$bookingUuid}/pay", ['provider' => 'airtel', 'phone' => '050000000'])
            ->assertStatus(200)
            ->assertJsonPath('payment.provider', 'airtel');

        $this->assertSame('airtel', $booking->fresh()->payment_method);
    }

    public function test_pay_rejects_unsupported_provider()
    {
        Event::fake([
            TransportRequestBroadcasted::class,
            TransportRequestCreated::class,
            TransportBookingStatusUpdated::class,
            BookingAssigned::class,
            TransportMissionPresenceUpdated::class,
        ]);

        $user = User::factory()->create();
        [$driver] = $this->provisionOperationalTransportDriver('unsupported', -4.2705, 15.2805);

        $res = $this->actingAs($user, 'api')->postJson('/api/v1/transport/bookings', [
            'type' => 'taxi', 'pickup_address' => 'A', 'pickup_lat' => -4.27, 'pickup_lng' => 15.28,
            'dropoff_address' => 'B', 'dropoff_lat' => -4.28, 'dropoff_lng' => 15.29, 'payment_method' => 'cash'
        ]);
        $res->assertStatus(201);
        $bookingUuid = $res->json('booking.uuid');

        $this->actingAs($user, 'api')
            ->postJson("/api/v1/transport/bookings/{$bookingUuid}/pay", ['provider' => 'stripe', 'phone' => '060000000'])
            ->assertStatus(422);
    }
}

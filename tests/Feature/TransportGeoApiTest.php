<?php

namespace Tests\Feature;

use App\Driver;
use App\User;
use App\Domain\Transport\Events\TransportRequestBroadcasted;
use App\Domain\Transport\Models\TransportVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransportGeoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_geocode_returns_normalized_results(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-4.2694407',
                    'lon' => '15.2712256',
                    'display_name' => 'Rue Béhangle, Poto-Poto, Poto-Poto (arrondissement 3), Brazzaville (commune), Brazzaville (département), Congo-Brazzaville',
                    'name' => 'Rue Béhangle',
                    'address' => [
                        'road' => 'Rue Béhangle',
                        'suburb' => 'Poto-Poto',
                        'city_district' => 'Poto-Poto (arrondissement 3)',
                        'city' => 'Brazzaville',
                        'state' => 'Brazzaville',
                        'country' => 'Congo-Brazzaville',
                    ],
                ],
            ], 200),
        ]);

        $this->getJson('/transport/xhr/geocode?q=Brazzaville&limit=1')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Rue Béhangle, Poto-Poto, Brazzaville, Congo')
            ->assertJsonPath('data.0.address_line', 'Rue Béhangle')
            ->assertJsonPath('data.0.lat', -4.2694407)
            ->assertJsonPath('data.0.lng', 15.2712256)
            ->assertJsonPath('data.0.components.road', 'Rue Béhangle')
            ->assertJsonPath('data.0.components.suburb', 'Poto-Poto')
            ->assertJsonPath('data.0.components.city', 'Brazzaville')
            ->assertJsonPath('data.0.components.country', 'Congo')
            ->assertJsonPath('data.0.administrative.department', 'Brazzaville')
            ->assertJsonPath('data.0.administrative.commune', 'Brazzaville')
            ->assertJsonPath('data.0.administrative.district', 'Poto-Poto')
            ->assertJsonPath('data.0.administrative.district_known', true)
            ->assertJsonPath('data.0.precision.source', 'nominatim')
            ->assertJsonPath('data.0.precision.level', 'street')
            ->assertJsonPath('data.0.precision.house_number_confirmed', false)
            ->assertJsonPath('data.0.precision.road_confirmed', true);
    }

    public function test_transport_reverse_returns_normalized_result(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Ouenzé-Konda, Rue Makoko, Ouenzé, Ouenze (arrondissement 5), Brazzaville (commune), Brazzaville (département), Congo-Brazzaville',
                'name' => 'Rue Makoko',
                'address' => [
                    'road' => 'Rue Makoko',
                    'neighbourhood' => 'Ouenzé-Konda',
                    'suburb' => 'Ouenzé',
                    'city_district' => 'Ouenze (arrondissement 5)',
                    'city' => 'Brazzaville',
                    'state' => 'Brazzaville',
                    'country' => 'Congo-Brazzaville',
                ],
            ], 200),
        ]);

        $this->getJson('/transport/xhr/reverse?lat=-4.2634&lng=15.2429')
            ->assertOk()
            ->assertJsonPath('data.label', 'Rue Makoko, Ouenzé, Brazzaville, Congo')
            ->assertJsonPath('data.address_line', 'Rue Makoko')
            ->assertJsonPath('data.components.road', 'Rue Makoko')
            ->assertJsonPath('data.components.neighbourhood', 'Ouenzé-Konda')
            ->assertJsonPath('data.components.suburb', 'Ouenzé')
            ->assertJsonPath('data.administrative.department', 'Brazzaville')
            ->assertJsonPath('data.administrative.commune', 'Brazzaville')
            ->assertJsonPath('data.administrative.district', 'Ouenzé')
            ->assertJsonPath('data.precision.level', 'street')
            ->assertJsonPath('data.precision.house_number_confirmed', false)
            ->assertJsonPath('data.precision.district_confirmed', true);
    }

    public function test_transport_geocode_returns_clarification_hints_for_unknown_street_query(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-4.2437108',
                    'lon' => '15.2820228',
                    'display_name' => 'Ouenzé-Konda, Rue Makoko, Ouenzé, Ouenze (arrondissement 5), Brazzaville (commune), Brazzaville (département), Congo',
                    'name' => 'Ouenzé-Konda',
                    'address' => [
                        'man_made' => 'Ouenzé-Konda',
                        'road' => 'Rue Makoko',
                        'suburb' => 'Ouenzé',
                        'city_district' => 'Ouenze (arrondissement 5)',
                        'city' => 'Brazzaville',
                        'state' => 'Brazzaville',
                        'country' => 'Congo-Brazzaville',
                    ],
                ],
            ], 200),
        ]);

        $this->getJson('/transport/xhr/geocode?q=Rue%20Konda&limit=5')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.query_type', 'street')
            ->assertJsonPath('meta.needs_clarification', true)
            ->assertJsonPath('meta.clarification_suggestions.0.kind', 'neighbourhood')
            ->assertJsonPath('meta.clarification_suggestions.0.address_line', 'Ouenzé-Konda');
    }

    public function test_transport_geocode_matches_local_alias_for_known_road(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);

        $this->getJson('/transport/xhr/geocode?q=av%20trois%20martyrs&limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Avenue des Trois Martyrs, Moungali, Brazzaville, Congo')
            ->assertJsonPath('data.0.kind', 'road')
            ->assertJsonPath('data.0.precision.source', 'kende_local');
    }

    public function test_transport_geocode_ranks_local_district_for_district_query(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);

        $this->getJson('/transport/xhr/geocode?q=Ouenz%C3%A9&limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Ouenzé, Brazzaville, Congo')
            ->assertJsonPath('data.0.kind', 'district')
            ->assertJsonPath('data.0.administrative.department', 'Brazzaville')
            ->assertJsonPath('data.0.administrative.district', 'Ouenzé')
            ->assertJsonPath('data.0.precision.level', 'district');
    }

    public function test_transport_geocode_ranks_local_landmark_for_landmark_query(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);

        $this->getJson('/transport/xhr/geocode?q=massamba%20debat&limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Stade Massamba-Débat, Centre-ville, Brazzaville, Congo')
            ->assertJsonPath('data.0.kind', 'landmark')
            ->assertJsonPath('data.0.precision.source', 'kende_local');
    }

    public function test_transport_route_returns_distance_duration_geometry_and_server_price(): void
    {
        DB::table('transport_pricing_rules')->insert([
            'type' => 'taxi',
            'zone' => 'Brazzaville',
            'base_fare' => 500,
            'price_per_km' => 200,
            'price_per_minute' => 50,
            'minimum_fare' => 1000,
            'surge_multiplier' => 1.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://router.project-osrm.org/route/v1/driving/*' => Http::response([
                'routes' => [[
                    'distance' => 10000,
                    'duration' => 1200,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [15.2832, -4.2767],
                            [15.2429, -4.2634],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $driver = $this->createAvailableTransportDriver(-4.2768, 15.2831);

        $response = $this->postJson('/transport/xhr/route', [
            'type' => 'taxi',
            'pickup_lat' => -4.2767,
            'pickup_lng' => 15.2832,
            'dropoff_lat' => -4.2634,
            'dropoff_lng' => 15.2429,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 20)
            ->assertJsonPath('data.mode', 'osrm')
            ->assertJsonPath('data.estimated_price', 3500)
            ->assertJsonPath('data.currency', 'FCFA')
            ->assertJsonPath('data.available_drivers_count', 1)
            ->assertJsonPath('data.serviceable', true)
            ->assertJsonPath('data.operating_zone', 'Brazzaville')
            ->assertJsonPath('data.supply_state', 'tight')
            ->assertJsonPath('data.retry_after_minutes', 0)
            ->assertJsonPath('data.trip_window_minutes.min', 20)
            ->assertJsonPath('data.best_driver.id', $driver->id)
            ->assertJsonPath('data.geometry.type', 'LineString');

        $this->assertEquals(10.0, $response->json('data.distance_km'));
    }

    public function test_taxi_booking_is_rejected_when_no_driver_is_available_nearby(): void
    {
        $user = User::factory()->create();

        DB::table('transport_pricing_rules')->insert([
            'type' => 'taxi',
            'zone' => 'Brazzaville',
            'base_fare' => 500,
            'price_per_km' => 200,
            'price_per_minute' => 50,
            'minimum_fare' => 1000,
            'surge_multiplier' => 1.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://router.project-osrm.org/route/v1/driving/*' => Http::response([
                'routes' => [[
                    'distance' => 5000,
                    'duration' => 900,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [15.2832, -4.2767],
                            [15.2429, -4.2634],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson('/transport/xhr/bookings', [
                'type' => 'taxi',
                'pickup_address' => 'Point A',
                'pickup_lat' => -4.2767,
                'pickup_lng' => 15.2832,
                'dropoff_address' => 'Point B',
                'dropoff_lat' => -4.2634,
                'dropoff_lng' => 15.2429,
                'estimated_price' => 1,
                'total_price' => 1,
                'payment_method' => 'cash',
            ])
            ->assertStatus(409)
            ->assertJsonPath('available_drivers_count', 0)
            ->assertJsonPath('operating_zone', 'Brazzaville')
            ->assertJsonPath('supply_state', 'unavailable')
            ->assertJsonPath('retry_after_minutes', 6);
    }

    public function test_taxi_booking_recomputes_route_and_price_server_side(): void
    {
        Event::fake([TransportRequestBroadcasted::class]);

        $user = User::factory()->create();

        DB::table('transport_pricing_rules')->insert([
            'type' => 'taxi',
            'zone' => 'Brazzaville',
            'base_fare' => 500,
            'price_per_km' => 200,
            'price_per_minute' => 50,
            'minimum_fare' => 1000,
            'surge_multiplier' => 1.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = $this->createAvailableTransportDriver(-4.2768, 15.2831);

        Http::fake([
            'https://router.project-osrm.org/route/v1/driving/*' => Http::response([
                'routes' => [[
                    'distance' => 10000,
                    'duration' => 1200,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [15.2832, -4.2767],
                            [15.2429, -4.2634],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/transport/xhr/bookings', [
                'type' => 'taxi',
                'pickup_address' => 'Point A',
                'pickup_lat' => -4.2767,
                'pickup_lng' => 15.2832,
                'dropoff_address' => 'Point B',
                'dropoff_lat' => -4.2634,
                'dropoff_lng' => 15.2429,
                'estimated_distance' => 1,
                'estimated_duration' => 1,
                'estimated_price' => 1,
                'total_price' => 1,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('booking.status', 'requested')
            ->assertJsonPath('booking.driver', null)
            ->assertJsonPath('booking.estimated_price', '3500.00')
            ->assertJsonPath('booking.total_price', '3500.00')
            ->assertJsonPath('serviceability.available_drivers_count', 1)
            ->assertJsonPath('serviceability.operating_zone', 'Brazzaville')
            ->assertJsonPath('serviceability.supply_state', 'tight')
            ->assertJsonPath('serviceability.assignment_status', 'broadcasting')
            ->assertJsonPath('serviceability.retry_after_minutes', 1)
            ->assertJsonPath('serviceability.trip_window_minutes.min', 20)
            ->assertJsonPath('serviceability.best_driver.id', $driver->id)
            ->assertJsonPath('serviceability.first_accept_wins', true);

        $this->assertDatabaseHas('transport_bookings', [
            'user_id' => $user->id,
            'driver_id' => null,
            'status' => 'requested',
            'estimated_distance' => 10.00,
            'estimated_price' => 3500.00,
            'total_price' => 3500.00,
        ]);

        Event::assertDispatched(TransportRequestBroadcasted::class);
    }

    public function test_taxi_booking_is_rejected_when_route_is_out_of_kende_zone(): void
    {
        $user = User::factory()->create();

        DB::table('transport_pricing_rules')->insert([
            'type' => 'taxi',
            'zone' => 'Brazzaville',
            'base_fare' => 500,
            'price_per_km' => 200,
            'price_per_minute' => 50,
            'minimum_fare' => 1000,
            'surge_multiplier' => 1.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createAvailableTransportDriver(-4.2768, 15.2831);

        Http::fake([
            'https://router.project-osrm.org/route/v1/driving/*' => Http::response([
                'routes' => [[
                    'distance' => 40000,
                    'duration' => 3600,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [15.2832, -4.2767],
                            [15.6429, -4.0634],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson('/transport/xhr/bookings', [
                'type' => 'taxi',
                'pickup_address' => 'Point A',
                'pickup_lat' => -4.2767,
                'pickup_lng' => 15.2832,
                'dropoff_address' => 'Point B',
                'dropoff_lat' => -4.0634,
                'dropoff_lng' => 15.6429,
                'payment_method' => 'cash',
            ])
            ->assertStatus(409)
            ->assertJsonPath('within_zone', false)
            ->assertJsonPath('service_radius_km', 35);
    }

    public function test_taxi_booking_requires_pin_confirmation_for_district_level_pickup(): void
    {
        $user = User::factory()->create();

        DB::table('transport_pricing_rules')->insert([
            'type' => 'taxi',
            'zone' => 'Brazzaville',
            'base_fare' => 500,
            'price_per_km' => 200,
            'price_per_minute' => 50,
            'minimum_fare' => 1000,
            'surge_multiplier' => 1.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createAvailableTransportDriver(-4.2437, 15.2820);

        $this->actingAs($user)
            ->postJson('/transport/xhr/bookings', [
                'type' => 'taxi',
                'pickup_address' => 'Ouenzé, Brazzaville, Congo',
                'pickup_lat' => -4.2438,
                'pickup_lng' => 15.2819,
                'pickup_precision_level' => 'district',
                'pickup_pin_confirmed' => false,
                'dropoff_address' => 'Avenue des Trois Martyrs, Moungali, Brazzaville, Congo',
                'dropoff_lat' => -4.2634,
                'dropoff_lng' => 15.2429,
                'dropoff_precision_level' => 'street',
                'dropoff_pin_confirmed' => false,
                'payment_method' => 'cash',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Confirmez precisement votre point de depart sur la carte avant de reserver.',
            ])
            ->assertJsonPath('address_confirmation.pickup_requires_pin_confirmation', true);
    }

    protected function createAvailableTransportDriver(float $lat, float $lng): Driver
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Transport Test Restaurant',
            'email' => 'transport-owner@example.test',
            'password' => bcrypt('password'),
            'address' => 'Brazzaville',
            'phone' => '064001111',
            'services' => 'delivery',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 5,
            'admin_commission' => 10,
            'account_name' => 'Test',
            'account_number' => '123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Kende Driver',
            'user_name' => 'kende-driver',
            'email' => 'kende-driver@example.test',
            'password' => bcrypt('password'),
            'phone' => '064009999',
            'address' => 'Brazzaville',
            'cnic' => 'CNIC-TEST',
            'status' => 'online',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $vehicle = TransportVehicle::create([
            'make' => 'Toyota',
            'model' => 'Yaris',
            'year' => '2024',
            'plate_number' => 'TR-GEO-001',
            'color' => 'Orange',
            'type' => 'taxi',
            'status' => 'active',
        ]);

        $driver->update(['active_transport_vehicle_id' => $vehicle->id]);

        return $driver->fresh();
    }
}

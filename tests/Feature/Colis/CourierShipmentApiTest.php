<?php

namespace Tests\Feature\Colis;

use App\Driver;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_endpoint_returns_only_active_shipments_for_current_courier()
    {
        $courier = $this->makeCourierUserContext();
        $otherCourier = $this->makeCourierUserContext();

        $visibleShipment = Shipment::factory()->create([
            'assigned_courier_id' => $courier->id,
            'status' => ShipmentStatus::CREATED,
        ]);

        Shipment::factory()->create([
            'assigned_courier_id' => $courier->id,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        Shipment::factory()->create([
            'assigned_courier_id' => $otherCourier->id,
            'status' => ShipmentStatus::CREATED,
        ]);

        $this->actingAs($courier, 'driver_api')
            ->getJson('/api/v1/courier/shipments/assigned')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $visibleShipment->id);
    }

    public function test_courier_cannot_update_foreign_shipment()
    {
        $courier = $this->makeCourierUserContext();
        $otherCourier = $this->makeCourierUserContext();
        $foreignShipment = Shipment::factory()->create([
            'assigned_courier_id' => $otherCourier->id,
            'status' => ShipmentStatus::CREATED,
        ]);

        $this->actingAs($courier, 'driver_api')
            ->postJson('/api/v1/courier/shipments/' . $foreignShipment->id . '/events', [
                'status' => 'picked_up',
            ])
            ->assertStatus(403);
    }

    public function test_deliver_requires_valid_otp_or_proof()
    {
        $courier = $this->makeCourierUserContext();
        $shipment = Shipment::factory()->create([
            'assigned_courier_id' => $courier->id,
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($courier, 'driver_api')
            ->postJson('/api/v1/courier/shipments/' . $shipment->id . '/deliver', [])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Un OTP valide ou une preuve de remise est obligatoire.',
            ]);
    }

    public function test_courier_can_upload_proof_for_owned_shipment()
    {
        Storage::fake('private');

        $courier = $this->makeCourierUserContext();
        $shipment = Shipment::factory()->create([
            'assigned_courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($courier, 'driver_api')
            ->postJson('/api/v1/courier/shipments/' . $shipment->id . '/proofs', [
                'type' => 'photo',
                'file' => UploadedFile::fake()->create('proof.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'proof_id']);

        $this->assertDatabaseHas('shipment_proofs', [
            'shipment_id' => $shipment->id,
            'type' => 'photo',
        ]);
    }

    protected function makeCourierUserContext(): Driver
    {
        $owner = User::factory()->create();

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant test coursier',
            'email' => 'courier-restaurant-' . uniqid() . '@bantudelice.cg',
            'password' => bcrypt('password'),
            'services' => 'delivery',
            'delivery_charges' => 1500,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Centre-ville',
            'phone' => '06' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'admin_commission' => 15,
            'account_name' => 'Restaurant Test',
            'account_number' => 'ACC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Coursier Test',
            'user_name' => 'courier_' . uniqid(),
            'phone' => '05' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'email' => 'courier-' . uniqid() . '@bantudelice.cg',
            'password' => bcrypt('password'),
            'address' => 'Brazzaville',
            'cnic' => 'CNIC-' . uniqid(),
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Driver::findOrFail($driverId);
    }
}

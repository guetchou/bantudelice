<?php

namespace Tests\Feature\Admin;

use App\User;
use App\Driver;
use App\DriverDocument;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverKycControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeDriver(array $attrs = []): Driver
    {
        $driver = Driver::create(array_merge([
            'restaurant_id' => null,
            'name' => 'Driver Test',
            'user_name' => 'driver_' . uniqid(),
            'email' => uniqid() . '@drivers.test',
            'password' => bcrypt('password'),
            'phone' => '06' . random_int(10000000, 99999999),
            'address' => 'Brazzaville',
            'cnic' => 'CNIC' . uniqid(),
            'status' => 'active',
            'approved' => true,
        ], $attrs));

        return $driver;
    }

    public function test_admin_cannot_approve_document_belonging_to_another_driver(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $driverA = $this->makeDriver();
        $driverB = $this->makeDriver();

        $document = DriverDocument::create([
            'driver_id' => $driverB->id,
            'type' => 'permis',
            'file_path' => 'driver_documents/' . $driverB->id . '/permis.pdf',
            'original_name' => 'permis.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver.kyc.approve', [$driverA->id, $document->id]))
            ->assertStatus(403);

        $this->assertSame('pending', $document->refresh()->status);
    }

    public function test_admin_cannot_reject_document_belonging_to_another_driver(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $driverA = $this->makeDriver();
        $driverB = $this->makeDriver();

        $document = DriverDocument::create([
            'driver_id' => $driverB->id,
            'type' => 'cni',
            'file_path' => 'driver_documents/' . $driverB->id . '/cni.pdf',
            'original_name' => 'cni.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver.kyc.reject', [$driverA->id, $document->id]), [
                'reason' => 'Document illisible',
            ])
            ->assertStatus(403);

        $this->assertSame('pending', $document->refresh()->status);
    }

    public function test_admin_can_approve_document_belonging_to_the_correct_driver(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $driver = $this->makeDriver();

        $document = DriverDocument::create([
            'driver_id' => $driver->id,
            'type' => 'assurance',
            'file_path' => 'driver_documents/' . $driver->id . '/assurance.pdf',
            'original_name' => 'assurance.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver.kyc.approve', [$driver->id, $document->id]))
            ->assertRedirect();

        $this->assertSame('approved', $document->refresh()->status);
    }
}

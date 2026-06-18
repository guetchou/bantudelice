<?php

namespace Tests\Feature\Driver;

use App\User;
use App\Driver;
use App\DriverDocument;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverDocumentViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeDriverWithUser(array $driverAttrs = []): array
    {
        $user = User::factory()->create(['type' => 'driver']);

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
        ], $driverAttrs));

        // user_id n'est pas dans $fillable du modèle Driver — affectation directe requise
        $driver->user_id = $user->id;
        $driver->save();

        return [$user, $driver];
    }

    private function makeDocument(Driver $driver, string $disk = 'local'): DriverDocument
    {
        $file = UploadedFile::fake()->create('cni.pdf', 10, 'application/pdf');
        $path = $file->store('driver_documents/' . $driver->id, $disk);

        return DriverDocument::create([
            'driver_id' => $driver->id,
            'type' => 'cni',
            'file_path' => $path,
            'original_name' => 'cni.pdf',
            'status' => 'pending',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        [, $driver] = $this->makeDriverWithUser();
        $document = $this->makeDocument($driver);

        $this->get(route('driver.documents.view', $document->id))
            ->assertRedirect(route('login'));
    }

    public function test_driver_cannot_view_another_drivers_document(): void
    {
        Storage::fake('local');

        [, $driverA] = $this->makeDriverWithUser();
        [$userB, $driverB] = $this->makeDriverWithUser();

        $document = $this->makeDocument($driverB);

        $this->actingAs($userB)
            ->get(route('driver.documents.view', $document->id))
            ->assertOk();

        // userA tente d'accéder au document de driverB par son ID -> doit être refusé
        $userA = User::find($driverA->user_id);

        $this->actingAs($userA)
            ->get(route('driver.documents.view', $document->id))
            ->assertStatus(403);
    }

    public function test_driver_can_view_own_document(): void
    {
        Storage::fake('local');

        [$user, $driver] = $this->makeDriverWithUser();
        $document = $this->makeDocument($driver);

        $this->actingAs($user)
            ->get(route('driver.documents.view', $document->id))
            ->assertOk();
    }

    public function test_admin_can_view_any_drivers_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['type' => 'admin']);
        [, $driver] = $this->makeDriverWithUser();
        $document = $this->makeDocument($driver);

        $this->actingAs($admin)
            ->get(route('driver.documents.view', $document->id))
            ->assertOk();
    }

    public function test_document_is_not_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [, $driver] = $this->makeDriverWithUser();
        $document = $this->makeDocument($driver);

        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }
}

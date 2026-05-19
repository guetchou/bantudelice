<?php

namespace Tests\Feature;

use App\Driver;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'user.role:admin,api'])
            ->get('/_test/api/admin-only', fn () => response()->json(['ok' => true]));

        Route::middleware(['api', 'auth:driver_api', 'user.role:admin,api'])
            ->get('/_test/api/admin-only-wrong-guard', fn () => response()->json(['ok' => true]));
    }

    public function test_admin_api_user_can_pass_role_middleware(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);

        $this->actingAs($admin, 'api')
            ->getJson('/_test/api/admin-only')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_non_admin_api_user_is_rejected(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user, 'api')
            ->getJson('/_test/api/admin-only')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Accès refusé pour ce rôle.',
                'required_role' => 'admin',
            ]);
    }

    public function test_driver_context_is_not_mistaken_for_api_user_context(): void
    {
        $driver = $this->createDriverContext();

        $this->actingAs($driver, 'driver_api')
            ->getJson('/_test/api/admin-only-wrong-guard')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Authentification utilisateur requise.',
                'required_role' => 'admin',
                'guards' => ['api'],
            ]);
    }

    protected function createDriverContext(): Driver
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant middleware auth',
            'email' => 'middleware-auth-' . uniqid() . '@bantudelice.cg',
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
            'name' => 'Livreur middleware auth',
            'user_name' => 'middleware_driver_' . uniqid(),
            'phone' => '05' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'email' => 'middleware-driver-' . uniqid() . '@bantudelice.cg',
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

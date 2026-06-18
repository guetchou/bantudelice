<?php

namespace Tests\Feature\Admin;

use App\User;
use App\AdminAuditLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditTrailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_view_audit_trail(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.audit_trail'))
            ->assertStatus(302);
    }

    public function test_admin_can_view_audit_trail(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);

        AdminAuditLog::create([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'method' => 'POST',
            'path' => 'admin/drivers/1/kyc/1/approve',
            'route_name' => 'admin.driver.kyc.approve',
            'action' => 'approve',
            'payload' => [],
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'response_status' => 302,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit_trail'))
            ->assertOk()
            ->assertSee('admin.driver.kyc.approve');
    }

    public function test_filter_by_method_only_returns_matching_entries(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);

        AdminAuditLog::create([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'method' => 'POST',
            'path' => 'admin/drivers/1/kyc/1/approve',
            'route_name' => 'admin.driver.kyc.approve',
            'action' => 'approve',
            'payload' => [],
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'response_status' => 302,
        ]);

        AdminAuditLog::create([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'method' => 'DELETE',
            'path' => 'admin/users/2',
            'route_name' => 'admin.users.destroy',
            'action' => 'destroy',
            'payload' => [],
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'response_status' => 302,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit_trail', ['method' => 'delete']))
            ->assertOk()
            ->assertSee('admin.users.destroy')
            ->assertDontSee('admin.driver.kyc.approve');
    }
}

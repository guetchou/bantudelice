<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function grantAdminWorkspace(User $admin, string $workspace = 'bantudelice'): void
    {
        DB::table('admin_permissions')->insert([
            'user_id'    => $admin->id,
            'workspace'  => $workspace,
            'granted_by' => null,
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

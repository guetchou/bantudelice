<?php

namespace Tests\Unit\Auth;

use App\Driver;
use App\Support\Auth\ActorType;
use App\User;
use Tests\TestCase;

class ActorTypeTest extends TestCase
{
    public function test_it_identifies_admin_users_only(): void
    {
        $admin = User::factory()->make(['type' => 'admin']);
        $customer = User::factory()->make(['type' => 'user']);
        $driver = Driver::factory()->make();

        $this->assertTrue(ActorType::isAdminUser($admin));
        $this->assertFalse(ActorType::isAdminUser($customer));
        $this->assertFalse(ActorType::isAdminUser($driver));
        $this->assertFalse(ActorType::isAdminUser(null));
    }

    public function test_it_recognizes_user_roles_without_mixing_driver_accounts(): void
    {
        $restaurantUser = User::factory()->make(['type' => 'restaurant']);
        $driver = Driver::factory()->make();

        $this->assertTrue(ActorType::hasUserRole($restaurantUser, 'restaurant'));
        $this->assertFalse(ActorType::hasUserRole($restaurantUser, 'admin'));
        $this->assertFalse(ActorType::hasUserRole($driver, 'driver'));
    }
}

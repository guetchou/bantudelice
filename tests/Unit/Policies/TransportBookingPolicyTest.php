<?php

namespace Tests\Unit\Policies;

use App\Driver;
use App\Domain\Transport\Models\TransportBooking;
use App\Policies\TransportBookingPolicy;
use App\User;
use Tests\TestCase;

class TransportBookingPolicyTest extends TestCase
{
    public function test_admin_user_is_pre_authorized(): void
    {
        $policy = new TransportBookingPolicy();
        $admin = User::factory()->make(['id' => 1, 'type' => 'admin']);

        $this->assertTrue($policy->before($admin, 'view'));
    }

    public function test_driver_can_only_view_owned_booking(): void
    {
        $policy = new TransportBookingPolicy();
        $driver = Driver::factory()->make(['id' => 12]);
        $ownedBooking = TransportBooking::factory()->make(['user_id' => 44, 'driver_id' => 12]);
        $foreignBooking = TransportBooking::factory()->make(['user_id' => 44, 'driver_id' => 99]);

        $this->assertTrue($policy->view($driver, $ownedBooking));
        $this->assertFalse($policy->view($driver, $foreignBooking));
    }

    public function test_customer_cancel_requires_own_requested_or_assigned_booking(): void
    {
        $policy = new TransportBookingPolicy();
        $customer = User::factory()->make(['id' => 44, 'type' => 'user']);
        $requestedBooking = TransportBooking::factory()->make(['user_id' => 44, 'status' => 'requested']);
        $completedBooking = TransportBooking::factory()->make(['user_id' => 44, 'status' => 'completed']);

        $this->assertTrue($policy->cancel($customer, $requestedBooking));
        $this->assertFalse($policy->cancel($customer, $completedBooking));
    }
}

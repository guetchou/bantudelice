<?php

namespace Tests\Feature;

use App\Services\FoodIntegrityRepairService;
use App\Services\FoodIntegrityReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoodIntegrityRepairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');
        parent::tearDown();
    }

    public function test_report_detects_payment_duplicates(): void
    {
        $this->payment(10);
        $this->payment(10, deletedAt: now());

        $report = app(FoodIntegrityReportService::class)->report();

        self::assertSame('violations_detected', $report['status']);
        self::assertCount(1, $report['checks']['duplicate_payments']);
    }

    public function test_plan_separates_safe_and_manual_groups(): void
    {
        $this->payment(20);
        $this->payment(20);
        $this->payment(21, status: 'PAID', reference: 'TX-A');
        $this->payment(21, status: 'PAID', reference: 'TX-A');
        $this->delivery(30);
        $this->delivery(30);
        $this->delivery(31, status: 'ASSIGNED', driverId: 7);
        $this->delivery(31);

        $plan = app(FoodIntegrityRepairService::class)->plan();

        self::assertSame(2, $plan['safe_repairs_count']);
        self::assertSame(2, $plan['manual_reviews_count']);
        self::assertSame('possible_double_charge', $plan['payments']['manual'][0]['reason']);
        self::assertSame('delivery_already_progressed', $plan['deliveries']['manual'][0]['reason']);
    }

    public function test_apply_removes_only_safe_duplicates(): void
    {
        $this->payment(40);
        $this->payment(40);
        $this->payment(41, status: 'PAID', reference: 'TX-A');
        $this->payment(41, status: 'PAID', reference: 'TX-B');
        $this->delivery(50);
        $this->delivery(50);

        $result = app(FoodIntegrityRepairService::class)->apply();

        self::assertCount(1, $result['deleted_payment_ids']);
        self::assertCount(1, $result['deleted_delivery_ids']);
        self::assertSame(1, DB::table('payments')->where('order_id', 40)->count());
        self::assertSame(2, DB::table('payments')->where('order_id', 41)->count());
        self::assertSame(1, DB::table('deliveries')->where('order_id', 50)->count());
    }

    private function createTables(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('status')->default('PENDING');
            $table->integer('amount')->default(0);
            $table->string('currency', 8)->default('XAF');
            $table->text('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status')->default('PENDING');
            $table->integer('delivery_fee')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('pickup_proof_path')->nullable();
            $table->string('delivery_proof_path')->nullable();
            $table->string('incident_status')->nullable();
            $table->timestamps();
        });
    }

    private function payment(
        int $orderId,
        string $status = 'PENDING',
        ?string $reference = null,
        mixed $deletedAt = null
    ): int {
        return (int) DB::table('payments')->insertGetId([
            'user_id' => 1,
            'order_id' => $orderId,
            'provider' => 'cash',
            'provider_reference' => $reference,
            'status' => $status,
            'amount' => 5000,
            'currency' => 'XAF',
            'meta' => null,
            'deleted_at' => $deletedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function delivery(int $orderId, string $status = 'PENDING', ?int $driverId = null): int
    {
        return (int) DB::table('deliveries')->insertGetId([
            'order_id' => $orderId,
            'restaurant_id' => 1,
            'driver_id' => $driverId,
            'status' => $status,
            'delivery_fee' => 1000,
            'assigned_at' => $status === 'ASSIGNED' ? now() : null,
            'picked_up_at' => null,
            'delivered_at' => null,
            'pickup_proof_path' => null,
            'delivery_proof_path' => null,
            'incident_status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

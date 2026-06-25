<?php

namespace Tests\Feature;

use App\Services\FoodIntegrityConstraintService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FoodIntegrityConstraintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('status')->default('PENDING');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');
        parent::tearDown();
    }

    public function test_dirty_audit_blocks_constraint_creation(): void
    {
        $this->payment(60);
        $this->payment(60);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('audit contient encore');

        app(FoodIntegrityConstraintService::class)->apply();
    }

    public function test_unique_indexes_reject_second_active_write(): void
    {
        $this->payment(70, 'mtn_momo');
        $this->delivery(70);

        $result = app(FoodIntegrityConstraintService::class)->apply();

        self::assertTrue($result['payment_constraint_active']);
        self::assertTrue($result['delivery_constraint_active']);

        try {
            $this->payment(70, 'mtn_momo');
            self::fail('Le deuxième paiement actif devait être refusé.');
        } catch (QueryException) {
            self::assertTrue(true);
        }

        DB::table('payments')->where('order_id', 70)->update(['deleted_at' => now()]);
        $this->payment(70, 'mtn_momo');
        self::assertSame(2, DB::table('payments')->where('order_id', 70)->count());

        $this->expectException(QueryException::class);
        $this->delivery(70);
    }

    private function payment(int $orderId, string $provider = 'cash'): int
    {
        return (int) DB::table('payments')->insertGetId([
            'order_id' => $orderId,
            'provider' => $provider,
            'status' => 'PENDING',
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function delivery(int $orderId): int
    {
        return (int) DB::table('deliveries')->insertGetId([
            'order_id' => $orderId,
            'status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

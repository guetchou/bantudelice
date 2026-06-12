<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'business_status')) {
                $table->string('business_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'technical_status')) {
                $table->string('technical_status')->nullable()->after('business_status');
            }
            if (!Schema::hasColumn('orders', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('ordered_time');
            }
            if (!Schema::hasColumn('orders', 'preparation_started_at')) {
                $table->timestamp('preparation_started_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('preparation_started_at');
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('ready_at');
            }
        });

        if (!Schema::hasTable('order_status_logs')) {
            Schema::create('order_status_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_no')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->string('legacy_status')->nullable();
                $table->string('actor_type')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('reason_code')->nullable();
                $table->text('notes')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_status_logs')) {
            Schema::drop('order_status_logs');
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['business_status', 'technical_status', 'accepted_at', 'preparation_started_at', 'ready_at', 'cancelled_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

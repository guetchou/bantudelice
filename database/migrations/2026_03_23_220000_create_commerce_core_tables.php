<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommerceCoreTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('commerce_signals')) {
            Schema::create('commerce_signals', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('signal_type', 120)->index();
                $table->string('domain', 80)->default('commerce')->index();
                $table->string('module', 80)->nullable()->index();
                $table->string('severity', 20)->default('info')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('order_no', 80)->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->string('subject_type', 120)->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('financial_ledger_entries')) {
            Schema::create('financial_ledger_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('module', 80)->index();
                $table->string('entry_type', 80)->index();
                $table->string('direction', 20)->default('credit')->index();
                $table->string('status', 30)->default('posted')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('order_no', 80)->nullable()->index();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->string('reference', 120)->nullable()->index();
                $table->string('currency', 10)->default('FCFA');
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('balance_before', 14, 2)->nullable();
                $table->decimal('balance_after', 14, 2)->nullable();
                $table->string('actor_type', 40)->nullable()->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('voucher_redemptions')) {
            Schema::create('voucher_redemptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('voucher_id')->nullable()->index();
                $table->string('voucher_code', 120)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('order_no', 80)->nullable()->index();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_amount', 14, 2)->default(0);
                $table->string('status', 30)->default('reserved')->index();
                $table->timestamp('redeemed_at')->nullable()->index();
                $table->timestamp('released_at')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('risk_assessments')) {
            Schema::create('risk_assessments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('scope', 80)->default('order')->index();
                $table->string('subject_type', 120)->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->decimal('score', 5, 2)->default(0);
                $table->string('level', 20)->default('low')->index();
                $table->text('reason')->nullable();
                $table->string('action', 80)->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('external_integrations')) {
            Schema::create('external_integrations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('provider', 80)->index();
                $table->string('module', 80)->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->timestamp('last_healthy_at')->nullable()->index();
                $table->timestamp('last_error_at')->nullable()->index();
                $table->text('last_error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('external_integrations');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('commerce_signals');
    }
}

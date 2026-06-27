<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerWithdrawalsTable extends Migration
{
    public function up(): void
    {
        Schema::create('partner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('partner_type', 20);          // restaurant | driver
            $table->unsignedBigInteger('partner_id');
            $table->string('operator', 20)->default('mtn');     // mtn | airtel
            $table->string('provider', 30)->default('mtn_momo');
            $table->string('phone', 30);
            $table->unsignedInteger('requested_amount');  // FCFA entiers
            $table->unsignedInteger('fee_amount')->default(0);
            $table->unsignedInteger('net_amount');        // requested - fee
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('created'); // created|reserved|submitted|pending|paid|failed|unknown|reversed|cancelled
            $table->string('external_reference', 100)->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->string('provider_reference', 100)->nullable()->unique();
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->string('source', 20)->default('self_service'); // self_service|admin|system
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['partner_type', 'partner_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_withdrawals');
    }
}

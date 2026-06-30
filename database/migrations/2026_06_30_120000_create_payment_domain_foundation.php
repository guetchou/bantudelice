<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('business_status', 30)->nullable()->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('business_status');
            $table->timestamp('failed_at')->nullable()->after('confirmed_at');
            $table->timestamp('reversed_at')->nullable()->after('failed_at');
            $table->timestamp('refunded_at')->nullable()->after('reversed_at');
            $table->timestamp('reconciled_at')->nullable()->after('refunded_at');
            $table->index('business_status');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('payment_id');
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id');
            $table->string('target_reference', 120)->nullable();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('active');
            $table->string('idempotency_key', 150)->unique();
            $table->timestamp('allocated_at');
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('restrict');
            $table->index(['target_type', 'target_id']);
            $table->index(['payment_id', 'status']);
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 30)->default('requested');
            $table->text('reason');
            $table->string('provider_reference', 150)->nullable()->unique();
            $table->string('idempotency_key', 150)->unique();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('restrict');
            $table->index(['payment_id', 'status']);
            $table->index('requested_at');
        });

        Schema::table('financial_ledger_entries', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('account_type', 40)->nullable()->after('module');
            $table->unsignedBigInteger('account_id')->nullable()->after('account_type');
            $table->string('source_type', 60)->nullable()->after('payment_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('idempotency_key', 180)->nullable()->unique()->after('reference');
            $table->unsignedBigInteger('related_entry_id')->nullable()->after('idempotency_key');
            $table->timestamp('effective_at')->nullable()->after('related_entry_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('actor_id');

            $table->foreign('related_entry_id')
                ->references('id')
                ->on('financial_ledger_entries')
                ->onDelete('restrict');
            $table->index(['account_type', 'account_id', 'status'], 'ledger_account_status_idx');
            $table->index(['source_type', 'source_id'], 'ledger_source_idx');
        });

        Schema::create('payment_reconciliation_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->string('case_type', 40);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('expected_amount')->nullable();
            $table->unsignedBigInteger('observed_amount')->nullable();
            $table->char('currency', 3)->default('XAF');
            $table->string('internal_status', 40)->nullable();
            $table->string('provider_status', 80)->nullable();
            $table->string('provider_reference', 150)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'severity']);
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_cases');

        Schema::table('financial_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['related_entry_id']);
            $table->dropIndex('ledger_account_status_idx');
            $table->dropIndex('ledger_source_idx');
            $table->dropUnique(['uuid']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'uuid',
                'account_type',
                'account_id',
                'source_type',
                'source_id',
                'idempotency_key',
                'related_entry_id',
                'effective_at',
                'created_by',
            ]);
        });

        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payment_allocations');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['business_status']);
            $table->dropColumn([
                'business_status',
                'confirmed_at',
                'failed_at',
                'reversed_at',
                'refunded_at',
                'reconciled_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 120)->unique();
            $table->string('type', 50)->index();
            $table->string('normal_balance', 10);
            $table->nullableMorphs('owner', 'financial_accounts_owner_index');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_type', 'owner_id', 'type', 'currency'],
                'financial_accounts_owner_type_currency_unique'
            );
        });

        Schema::create('financial_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('idempotency_key', 160)->unique();
            $table->string('type', 60)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('reference', 160)->nullable()->index();
            $table->nullableMorphs('source', 'financial_journal_entries_source_index');
            $table->string('description', 500)->nullable();
            $table->char('currency', 3)->default('XAF');
            $table->timestamp('effective_at');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_entry_id')
                ->nullable()
                ->constrained('financial_journal_entries')
                ->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')
                ->constrained('financial_journal_entries')
                ->cascadeOnDelete();
            $table->foreignId('financial_account_id')
                ->constrained('financial_accounts')
                ->restrictOnDelete();
            $table->string('direction', 10);
            $table->unsignedBigInteger('amount');
            $table->string('narrative', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['financial_account_id', 'direction'],
                'financial_journal_lines_account_direction_index'
            );
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->restrictOnDelete();
            $table->string('allocatable_type');
            $table->unsignedBigInteger('allocatable_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('active')->index();
            $table->string('idempotency_key', 160)->unique();
            $table->timestamp('allocated_at');
            $table->timestamp('reversed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['allocatable_type', 'allocatable_id'],
                'payment_allocations_target_index'
            );
        });

        Schema::create('payment_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->restrictOnDelete();
            $table->string('from_status', 40);
            $table->string('to_status', 40);
            $table->string('source', 40)->index();
            $table->nullableMorphs('actor', 'payment_status_transitions_actor_index');
            $table->string('reason', 500)->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('occurred_at');
            $table->string('idempotency_key', 160)->unique();
            $table->timestamps();

            $table->index(
                ['payment_id', 'occurred_at'],
                'payment_status_transitions_payment_time_index'
            );
        });

        Schema::create('payment_reconciliation_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('case_number', 40)->unique();
            $table->string('fingerprint', 160)->unique();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('provider', 60)->nullable()->index();
            $table->string('external_reference', 160)->nullable()->index();
            $table->string('provider_reference', 160)->nullable()->index();
            $table->string('status', 30)->default('open')->index();
            $table->string('discrepancy_code', 40)->index();
            $table->unsignedBigInteger('expected_amount')->nullable();
            $table->unsignedBigInteger('observed_amount')->nullable();
            $table->char('currency', 3)->default('XAF');
            $table->string('expected_status', 60)->nullable();
            $table->string('observed_status', 60)->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(
                ['subject_type', 'subject_id'],
                'payment_reconciliation_subject_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_cases');
        Schema::dropIfExists('payment_status_transitions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('financial_journal_lines');
        Schema::dropIfExists('financial_journal_entries');
        Schema::dropIfExists('financial_accounts');
    }
};

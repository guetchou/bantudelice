<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_allocations')) {
            Schema::create('payment_allocations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('allocatable_type', 40)->nullable();
                $table->unsignedBigInteger('allocatable_id')->nullable();
                $table->string('allocation_key', 160)->unique();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('XAF');
                $table->string('status', 24)->default('allocated');
                $table->timestamp('allocated_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['payment_id', 'status'], 'pay_alloc_payment_status_idx');
                $table->index(['order_id', 'status'], 'pay_alloc_order_status_idx');
                $table->index(['allocatable_type', 'allocatable_id'], 'pay_alloc_target_idx');
            });
        }

        if (! Schema::hasTable('payment_reconciliation_cases')) {
            Schema::create('payment_reconciliation_cases', function (Blueprint $table): void {
                $table->id();
                $table->string('case_key', 180)->unique();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->unsignedBigInteger('withdrawal_id')->nullable();
                $table->string('case_type', 60);
                $table->string('severity', 16)->default('warning');
                $table->string('status', 20)->default('open');
                $table->decimal('expected_amount', 14, 2)->nullable();
                $table->decimal('observed_amount', 14, 2)->nullable();
                $table->string('currency', 3)->default('XAF');
                $table->string('provider', 50)->nullable();
                $table->string('provider_reference', 191)->nullable();
                $table->string('summary', 255);
                $table->json('details')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamps();

                $table->index(['status', 'severity'], 'pay_case_status_severity_idx');
                $table->index(['payment_id', 'status'], 'pay_case_payment_status_idx');
                $table->index(['withdrawal_id', 'status'], 'pay_case_withdrawal_status_idx');
            });
        }

        if (Schema::hasTable('financial_ledger_entries')) {
            Schema::table('financial_ledger_entries', function (Blueprint $table): void {
                if (! Schema::hasColumn('financial_ledger_entries', 'entry_key')) {
                    $table->string('entry_key', 191)->nullable()->unique();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'owner_type')) {
                    $table->string('owner_type', 40)->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'account_code')) {
                    $table->string('account_code', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'source_type')) {
                    $table->string('source_type', 60)->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'withdrawal_id')) {
                    $table->unsignedBigInteger('withdrawal_id')->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'reversal_of_id')) {
                    $table->unsignedBigInteger('reversal_of_id')->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'effective_at')) {
                    $table->timestamp('effective_at')->nullable()->index();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
                if (! Schema::hasColumn('financial_ledger_entries', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_cases');
        Schema::dropIfExists('payment_allocations');

        if (Schema::hasTable('financial_ledger_entries')) {
            $columns = collect([
                'entry_key', 'owner_type', 'owner_id', 'account_code', 'source_type',
                'source_id', 'withdrawal_id', 'reversal_of_id', 'effective_at',
                'metadata', 'created_by',
            ])->filter(fn (string $column) => Schema::hasColumn('financial_ledger_entries', $column))->all();

            if ($columns !== []) {
                Schema::table('financial_ledger_entries', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'integrity_duplicate_of_id')) {
                    $table->unsignedBigInteger('integrity_duplicate_of_id')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'integrity_quarantined_at')) {
                    $table->timestamp('integrity_quarantined_at')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'integrity_quarantine_reason')) {
                    $table->string('integrity_quarantine_reason', 120)->nullable();
                }
            });
        }

        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                if (! Schema::hasColumn('deliveries', 'integrity_duplicate_of_id')) {
                    $table->unsignedBigInteger('integrity_duplicate_of_id')->nullable()->index();
                }
                if (! Schema::hasColumn('deliveries', 'integrity_quarantined_at')) {
                    $table->timestamp('integrity_quarantined_at')->nullable()->index();
                }
                if (! Schema::hasColumn('deliveries', 'integrity_quarantine_reason')) {
                    $table->string('integrity_quarantine_reason', 120)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                foreach ([
                    'integrity_duplicate_of_id',
                    'integrity_quarantined_at',
                    'integrity_quarantine_reason',
                ] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                foreach ([
                    'integrity_duplicate_of_id',
                    'integrity_quarantined_at',
                    'integrity_quarantine_reason',
                ] as $column) {
                    if (Schema::hasColumn('deliveries', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'pickup_notes')) {
                $table->text('pickup_notes')->nullable()->after('picked_up_at');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable()->after('pickup_notes');
            }
            if (!Schema::hasColumn('deliveries', 'pickup_proof_path')) {
                $table->string('pickup_proof_path')->nullable()->after('delivery_notes');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_proof_path')) {
                $table->string('delivery_proof_path')->nullable()->after('pickup_proof_path');
            }
            if (!Schema::hasColumn('deliveries', 'customer_confirmed_at')) {
                $table->timestamp('customer_confirmed_at')->nullable()->after('delivery_proof_path');
            }
            if (!Schema::hasColumn('deliveries', 'cash_collected_at')) {
                $table->timestamp('cash_collected_at')->nullable()->after('customer_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [
                'pickup_notes',
                'delivery_notes',
                'pickup_proof_path',
                'delivery_proof_path',
                'customer_confirmed_at',
                'cash_collected_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

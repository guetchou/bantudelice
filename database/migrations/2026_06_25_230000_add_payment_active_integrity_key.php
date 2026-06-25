<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('payments')
            || ! Schema::hasColumn('payments', 'deleted_at')
            || Schema::hasColumn('payments', 'active_order_provider_key')) {
            return;
        }

        DB::statement(
            'ALTER TABLE payments ADD COLUMN active_order_provider_key VARCHAR(191) '
            . 'GENERATED ALWAYS AS ('
            . "CASE WHEN deleted_at IS NULL AND order_id IS NOT NULL AND provider IS NOT NULL "
            . "THEN CONCAT(CAST(order_id AS CHAR), ':', LEFT(provider, 150)) ELSE NULL END"
            . ') STORED'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'active_order_provider_key')) {
            DB::statement('ALTER TABLE payments DROP COLUMN active_order_provider_key');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('deliveries', 'restaurant_arrived_at')) {
                $table->timestamp('restaurant_arrived_at')->nullable()->after('assigned_at');
            }

            if (! Schema::hasColumn('deliveries', 'restaurant_arrival_latitude')) {
                $table->decimal('restaurant_arrival_latitude', 10, 7)->nullable()->after('restaurant_arrived_at');
            }

            if (! Schema::hasColumn('deliveries', 'restaurant_arrival_longitude')) {
                $table->decimal('restaurant_arrival_longitude', 10, 7)->nullable()->after('restaurant_arrival_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [
                'restaurant_arrived_at',
                'restaurant_arrival_latitude',
                'restaurant_arrival_longitude',
            ];

            $existing = array_values(array_filter(
                $columns,
                static fn (string $column): bool => Schema::hasColumn('deliveries', $column)
            ));

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};

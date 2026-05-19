<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'delivery_otp_code')) {
                $table->string('delivery_otp_code', 12)->nullable()->after('delivery_proof_path');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_otp_expires_at')) {
                $table->timestamp('delivery_otp_expires_at')->nullable()->after('delivery_otp_code');
            }
            if (!Schema::hasColumn('deliveries', 'otp_verified_at')) {
                $table->timestamp('otp_verified_at')->nullable()->after('delivery_otp_expires_at');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_confirmation_method')) {
                $table->string('delivery_confirmation_method', 40)->nullable()->after('otp_verified_at');
            }
            if (!Schema::hasColumn('deliveries', 'pickup_latitude')) {
                $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_notes');
            }
            if (!Schema::hasColumn('deliveries', 'pickup_longitude')) {
                $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_notes');
            }
            if (!Schema::hasColumn('deliveries', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [
                'delivery_otp_code',
                'delivery_otp_expires_at',
                'otp_verified_at',
                'delivery_confirmation_method',
                'pickup_latitude',
                'pickup_longitude',
                'delivery_latitude',
                'delivery_longitude',
            ];

            $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('deliveries', $column)));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};

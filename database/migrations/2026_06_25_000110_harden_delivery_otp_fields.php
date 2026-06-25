<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('deliveries', 'delivery_otp_attempts')) {
                $table->unsignedTinyInteger('delivery_otp_attempts')->default(0)->after('delivery_otp_expires_at');
            }
            if (! Schema::hasColumn('deliveries', 'delivery_otp_last_attempt_at')) {
                $table->timestamp('delivery_otp_last_attempt_at')->nullable()->after('delivery_otp_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('deliveries', 'delivery_otp_last_attempt_at')) {
                $columns[] = 'delivery_otp_last_attempt_at';
            }
            if (Schema::hasColumn('deliveries', 'delivery_otp_attempts')) {
                $columns[] = 'delivery_otp_attempts';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

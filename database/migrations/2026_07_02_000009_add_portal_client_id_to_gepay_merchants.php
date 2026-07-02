<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gepay_merchants', function (Blueprint $table) {
            // Circular FK resolved: gepay_clients.merchant_id exists first (migration 8)
            $table->unsignedBigInteger('portal_client_id')->nullable()->after('status');
            $table->foreign('portal_client_id')
                ->references('id')
                ->on('gepay_clients')
                ->restrictOnDelete()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gepay_merchants', function (Blueprint $table) {
            $table->dropForeign(['portal_client_id']);
            $table->dropColumn('portal_client_id');
        });
    }
};

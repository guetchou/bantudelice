<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $blueprint) {
            $blueprint->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['shipment_id']);
            $blueprint->dropColumn('shipment_id');
        });
    }
};

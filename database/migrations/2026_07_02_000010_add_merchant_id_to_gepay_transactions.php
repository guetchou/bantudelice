<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gepay_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('merchant_id')->nullable()->after('client_id');
            $table->foreign('merchant_id')
                ->references('id')
                ->on('gepay_merchants')
                ->restrictOnDelete();
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::table('gepay_transactions', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropIndex(['merchant_id']);
            $table->dropColumn('merchant_id');
        });
    }
};

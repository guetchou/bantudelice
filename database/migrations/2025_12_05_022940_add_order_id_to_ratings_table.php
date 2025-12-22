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
        Schema::table('ratings', function (Blueprint $table) {
            if (!Schema::hasColumn('ratings', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('restaurant_id');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            }
            // Renommer 'review' en 'reviews' si nécessaire (la migration existante utilise 'review')
            if (Schema::hasColumn('ratings', 'review') && !Schema::hasColumn('ratings', 'reviews')) {
                $table->renameColumn('review', 'reviews');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            if (Schema::hasColumn('ratings', 'order_id')) {
                $table->dropForeign(['order_id']);
                $table->dropColumn('order_id');
            }
        });
    }
};

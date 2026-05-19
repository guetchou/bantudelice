<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index manquants sur colonnes de filtrage fréquent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // orders.payment_status — filtres fréquents sur paiements en attente
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_status')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->index('payment_status', 'idx_orders_payment_status');
            });
        }

        // restaurants.is_paused — requête "restaurants actifs" à chaque page catalogue
        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'is_paused')) {
            Schema::table('restaurants', function (Blueprint $t) {
                $t->index('is_paused', 'idx_restaurants_is_paused');
            });
        }

        // drivers.is_active — assignation automatique livreur
        if (Schema::hasTable('drivers') && Schema::hasColumn('drivers', 'is_active')) {
            Schema::table('drivers', function (Blueprint $t) {
                $t->index('is_active', 'idx_drivers_is_active');
            });
        }

        // deliveries.driver_id + status — requêtes dashboard livreur
        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'driver_id')) {
            Schema::table('deliveries', function (Blueprint $t) {
                $t->index(['driver_id', 'status'], 'idx_deliveries_driver_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', fn(Blueprint $t) => $t->dropIndexIfExists('idx_orders_payment_status'));
        }
        if (Schema::hasTable('restaurants')) {
            Schema::table('restaurants', fn(Blueprint $t) => $t->dropIndexIfExists('idx_restaurants_is_paused'));
        }
        if (Schema::hasTable('drivers')) {
            Schema::table('drivers', fn(Blueprint $t) => $t->dropIndexIfExists('idx_drivers_is_active'));
        }
        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', fn(Blueprint $t) => $t->dropIndexIfExists('idx_deliveries_driver_status'));
        }
    }
};

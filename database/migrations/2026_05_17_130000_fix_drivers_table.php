<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S3.2 — restaurant_id nullable  : permet les livreurs indépendants
 * S3.3 — is_available             : filtre disponibilité dans DispatchService
 * S3.4 — user_id                  : liaison directe User↔Driver (évite lookup par email/phone)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // S3.2 — rendre restaurant_id nullable (livreurs indépendants)
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['restaurant_id']);
            }
            $table->unsignedBigInteger('restaurant_id')->nullable()->change();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->nullOnDelete();
            }

            // S3.3 — is_available (true par défaut pour les drivers déjà en ligne)
            $table->boolean('is_available')->default(true)->after('status');

            // S3.4 — user_id : lien vers la table users
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
            }
            $table->dropColumn('user_id');
            $table->dropColumn('is_available');
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['restaurant_id']);
                $table->unsignedBigInteger('restaurant_id')->nullable(false)->change();
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            }
        });
    }
};

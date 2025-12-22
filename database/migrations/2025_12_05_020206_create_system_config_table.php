<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clé de configuration (ex: default_delivery_fee)');
            $table->text('value')->nullable()->comment('Valeur de configuration');
            $table->string('type')->default('string')->comment('Type: string, integer, float, boolean');
            $table->text('description')->nullable()->comment('Description de la configuration');
            $table->timestamps();
        });
        
        // Insérer les valeurs par défaut depuis la table charges si elle existe
        $defaultDeliveryFee = 1500.0;
        if (Schema::hasTable('charges')) {
            $charge = DB::table('charges')->first();
            if ($charge) {
                $defaultDeliveryFee = (float)($charge->delivery_fee ?? $charge->delivery_charges ?? 1500);
            }
        }
        
        // Insérer les configurations par défaut
        DB::table('system_config')->insert([
            [
                'key' => 'default_delivery_fee',
                'value' => (string)$defaultDeliveryFee,
                'type' => 'float',
                'description' => 'Frais de livraison par défaut en FCFA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_delivery_time_min',
                'value' => '20',
                'type' => 'integer',
                'description' => 'Temps de livraison minimum par défaut (en minutes)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_delivery_time_max',
                'value' => '35',
                'type' => 'integer',
                'description' => 'Temps de livraison maximum par défaut (en minutes)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_rating',
                'value' => '4.5',
                'type' => 'float',
                'description' => 'Note par défaut si aucun avis n\'existe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'top_rated_threshold',
                'value' => '4.5',
                'type' => 'float',
                'description' => 'Seuil de note minimum pour afficher le badge "Top noté"',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'top_rated_min_reviews',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Nombre minimum d\'avis requis pour le badge "Top noté"',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};

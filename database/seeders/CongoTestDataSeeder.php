<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CongoTestDataSeeder extends Seeder
{
    /**
     * Seed des données de test pour BantuDelice
     * Restaurants congolais réels, livreurs et produits
     */
    public function run()
    {
        $now = Carbon::now();
        
        // ========================================
        // 1. UTILISATEUR DE TEST CLIENT
        // ========================================
        $testUserId = DB::table('users')->insertGetId([
            'name' => 'Client Test BantuDelice',
            'email' => 'client_test_colis@bantudelice.cg', // Changed to avoid duplicate with other seeders
            'password' => Hash::make('test123456'),
            'phone' => '+242 06 500 00 01',
            'type' => 'user',
            'blocked' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // ========================================
        // 2. UTILISATEURS RESTAURANTS (Minimal pour le test Colis)
        // ========================================
        $userId = DB::table('users')->insertGetId([
            'name' => 'Resto Colis',
            'email' => 'resto_colis@bantudelice.cg',
            'password' => Hash::make('resto123456'),
            'phone' => '+242 06 600 00 01',
            'type' => 'restaurant',
            'blocked' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $restoId = DB::table('restaurants')->insertGetId([
            'user_id' => $userId,
            'name' => 'Resto Colis',
            'user_name' => 'resto_colis',
            'email' => 'resto_colis@bantudelice.cg',
            'password' => Hash::make('resto123456'),
            'slogan' => 'Slogan Colis',
            'city' => 'Brazzaville',
            'address' => 'Adresse Colis',
            'phone' => '+242 06 600 00 01',
            'approved' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ========================================
        // 3. LIVREURS
        // ========================================
        DB::table('drivers')->insert([
            'restaurant_id' => $restoId,
            'name' => 'Livreur Colis',
            'user_name' => 'livreur_colis',
            'phone' => '+242 06 700 00 01',
            'email' => 'livreur_colis@bantudelice.cg',
            'password' => Hash::make('driver123456'),
            'approved' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // ========================================
        // 4. ADMIN
        // ========================================
        DB::table('users')->updateOrInsert(
            ['email' => 'admin_colis@bantudelice.cg'],
            [
                'name' => 'Admin Colis',
                'password' => Hash::make('admin123456'),
                'phone' => '+242 06 000 00 00',
                'type' => 'admin',
                'blocked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}


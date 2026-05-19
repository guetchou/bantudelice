<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T1.5 — Surcharge tarifaire configurable par admin (saison des pluies, E2C)
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            [
                'key'         => 'weather_surcharge_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => 'Active une majoration des frais de livraison (saison des pluies, routes impraticables).',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'weather_surcharge_percent',
                'value'       => '20',
                'type'        => 'float',
                'description' => 'Pourcentage ajouté aux frais de livraison quand la majoration est activée. Ex: 20 = +20%.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'weather_surcharge_label',
                'value'       => 'Majoration saison des pluies',
                'type'        => 'string',
                'description' => 'Texte visible sur la page checkout et le reçu quand la majoration est active.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('system_config')->updateOrInsert(['key' => $row['key']], $row);
        }
    }

    public function down(): void
    {
        DB::table('system_config')->whereIn('key', [
            'weather_surcharge_enabled',
            'weather_surcharge_percent',
            'weather_surcharge_label',
        ])->delete();
    }
};

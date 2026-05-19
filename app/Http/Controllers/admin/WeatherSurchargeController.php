<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * T1.5 — Gestion de la surcharge tarifaire saison des pluies.
 * Permet à l'admin d'activer/désactiver la majoration et de configurer le %.
 */
class WeatherSurchargeController extends Controller
{
    public function index()
    {
        $enabled = (bool) ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
        $percent = (float) ConfigService::getConfigValue('weather_surcharge_percent', 20, 'float');
        $label   = ConfigService::getConfigValue('weather_surcharge_label', 'Majoration saison des pluies', 'string');

        return view('admin.weather_surcharge', compact('enabled', 'percent', 'label'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'weather_surcharge_enabled' => 'required|boolean',
            'weather_surcharge_percent' => 'required|numeric|min:0|max:200',
            'weather_surcharge_label'   => 'required|string|max:100',
        ]);

        $updates = [
            'weather_surcharge_enabled' => $request->boolean('weather_surcharge_enabled') ? '1' : '0',
            'weather_surcharge_percent' => (string) $request->input('weather_surcharge_percent'),
            'weather_surcharge_label'   => $request->input('weather_surcharge_label'),
        ];

        foreach ($updates as $key => $value) {
            DB::table('system_config')
                ->where('key', $key)
                ->update(['value' => $value, 'updated_at' => now()]);

            Cache::forget('config_' . $key);
        }

        $enabled = $request->boolean('weather_surcharge_enabled');

        return redirect()->back()->with('alert', [
            'type'    => $enabled ? 'warning' : 'success',
            'message' => $enabled
                ? 'Surcharge saison des pluies activée (+' . $request->input('weather_surcharge_percent') . '% sur les frais de livraison).'
                : 'Surcharge saison des pluies désactivée. Tarifs normaux rétablis.',
        ]);
    }

    public function toggle(Request $request)
    {
        $current = (bool) ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
        $new     = !$current;

        DB::table('system_config')
            ->where('key', 'weather_surcharge_enabled')
            ->update(['value' => $new ? '1' : '0', 'updated_at' => now()]);

        Cache::forget('config_weather_surcharge_enabled');

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => true,
                'enabled' => $new,
                'message' => $new ? 'Surcharge activée.' : 'Surcharge désactivée.',
            ]);
        }

        return redirect()->back()->with('alert', [
            'type'    => $new ? 'warning' : 'success',
            'message' => $new ? 'Surcharge pluies activée.' : 'Surcharge pluies désactivée.',
        ]);
    }
}

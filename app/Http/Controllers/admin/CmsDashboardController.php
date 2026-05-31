<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;

class CmsDashboardController extends Controller
{
    public function index()
    {
        $workspaces = [
            [
                'key'      => 'bantudelice',
                'label'    => 'BantuDelice',
                'tagline'  => 'Livraison de repas',
                'domain'   => 'bantudelice.cg',
                'icon'     => 'fas fa-utensils',
                'accent'   => '#009543',
                'sections' => ['Hero', 'Editorial', 'Avis clients', 'Opportunites', 'Mosaique', 'Support'],
                'edit_url' => route('admin.home-content.edit', ['workspace' => 'bantudelice']),
                'site_url' => route('home'),
            ],
            [
                'key'      => 'kende',
                'label'    => 'Kende',
                'tagline'  => 'Transport & taxi',
                'domain'   => 'kende.cg',
                'icon'     => 'fas fa-car',
                'accent'   => '#1d4ed8',
                'sections' => ['Hero', 'Avis clients', 'Opportunites', 'Mosaique', 'Support'],
                'edit_url' => route('admin.home-content.edit', ['workspace' => 'kende']),
                'site_url' => route('transport.taxi'),
            ],
            [
                'key'      => 'mema',
                'label'    => 'Mema',
                'tagline'  => 'Colis & logistique',
                'domain'   => 'mema.cg',
                'icon'     => 'fas fa-box',
                'accent'   => '#c2410c',
                'sections' => ['Hero', 'Avis clients', 'Opportunites', 'Mosaique', 'Support'],
                'edit_url' => route('admin.home-content.edit', ['workspace' => 'mema']),
                'site_url' => route('colis.landing'),
            ],
        ];

        foreach ($workspaces as &$ws) {
            $content  = ConfigService::getHomeContent($ws['key']);
            $isBD     = $ws['key'] === 'bantudelice';
            $heroKey  = $ws['key'] === 'kende' ? 'hero_transport_image' : 'hero_colis_image';

            $mediaKeys = $isBD
                ? ['hero_main_image', 'hero_colis_image', 'hero_transport_image', 'service_food_image', 'service_colis_image', 'service_transport_image', 'opportunity_1_image', 'opportunity_2_image', 'opportunity_3_image']
                : [$heroKey, 'opportunity_1_image', 'opportunity_2_image', 'opportunity_3_image'];

            $ws['media_total']  = count($mediaKeys);
            $ws['media_filled'] = collect($mediaKeys)->filter(fn($k) => filled((string) ($content[$k] ?? '')))->count();
            $ws['media_status'] = $ws['media_filled'] >= $ws['media_total'] ? 'ok' : ($ws['media_filled'] > 0 ? 'warn' : 'missing');
        }

        return view('admin.cms.dashboard', compact('workspaces'));
    }
}

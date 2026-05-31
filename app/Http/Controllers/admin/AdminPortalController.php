<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

class AdminPortalController extends Controller
{
    private const APPS = [
        'bantudelice' => [
            'key'     => 'bantudelice',
            'label'   => 'BantuDelice',
            'tagline' => 'Livraison de repas',
            'desc'    => 'Commandes, restaurants, livreurs, catalogue, paiements food.',
            'icon'    => 'fas fa-utensils',
            'color'   => '#009543',
            'url'     => 'admin.dashboard',
        ],
        'kende' => [
            'key'     => 'kende',
            'label'   => 'Kende',
            'tagline' => 'Transport & taxi',
            'desc'    => 'Reservations, vehicules, tarification, dispatch transport.',
            'icon'    => 'fas fa-car',
            'color'   => '#1d4ed8',
            'url'     => 'admin.transport.dashboard',
        ],
        'mema' => [
            'key'     => 'mema',
            'label'   => 'Mema',
            'tagline' => 'Colis & logistique',
            'desc'    => 'Expeditions, points relais, suivi colis, finance livraison.',
            'icon'    => 'fas fa-box',
            'color'   => '#c2410c',
            'url'     => 'admin.colis.index',
        ],
    ];

    public function index()
    {
        $user       = auth()->user();
        $workspaces = $user->adminWorkspaces();

        if (empty($workspaces)) {
            abort(403, 'Aucune application assignee a votre compte. Contactez un super administrateur.');
        }

        if (count($workspaces) === 1) {
            $ws  = $workspaces[0];
            $app = self::APPS[$ws] ?? null;
            if ($app) {
                return redirect()->route($app['url']);
            }
        }

        $apps = collect($workspaces)
            ->map(fn($ws) => self::APPS[$ws] ?? null)
            ->filter()
            ->values()
            ->toArray();

        $isSuperAdmin = $user->isSuperAdmin();

        return view('admin.portal', compact('apps', 'isSuperAdmin'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Delivery;
use App\Services\PartnerFinancialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Pages statiques / informatives de l'espace livreur
 * (Gains, Historique, Note & Avis, Support)
 */
class DriverPageController extends Controller
{
    private function resolveDriver(): ?Driver
    {
        $user = auth()->user();
        if (!$user) return null;

        if (Schema::hasColumn('drivers', 'user_id')) {
            $d = Driver::where('user_id', $user->id)->first();
            if ($d) return $d;
        }
        $d = Driver::where('email', $user->email)
                   ->orWhere('phone', $user->phone)
                   ->first();
        if (!$d && $user->type === 'driver') {
            $d = Driver::where('name', $user->name)->first();
        }
        return $d;
    }

    private function driverOrRedirect()
    {
        $driver = $this->resolveDriver();
        if (!$driver) {
            return redirect()->route('driver.deliveries')
                ->with('alert', ['type' => 'warning', 'message' => 'Profil livreur introuvable.']);
        }
        return $driver;
    }

    /**
     * Vue Mes Gains
     */
    public function gains()
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        $financialDashboard = app(PartnerFinancialDashboardService::class)->forDeliveryDriver($driver);

        return view('driver.gains', compact('driver', 'financialDashboard'));
    }

    /**
     * Vue Historique des courses
     */
    public function historique(Request $request)
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        return view('driver.historique', compact('driver'));
    }

    /**
     * Vue Ma note & avis clients
     */
    public function note()
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        $financialDashboard = [];

        return view('driver.note', compact('driver', 'financialDashboard'));
    }

    /**
     * Vue Support & aide
     */
    public function support()
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        return view('driver.support', compact('driver'));
    }
}

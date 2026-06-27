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

        $d = Driver::where('user_id', $user->id)->first();
        if ($d) return $d;

        // email ET téléphone doivent correspondre tous les deux (l'orWhere et le
        // fallback par nom permettaient un IDOR par correspondance partielle/faible).
        $d = Driver::where('email', $user->email)
                   ->where('phone', $user->phone)
                   ->first();

        if ($d && !$d->user_id) {
            $d->update(['user_id' => $user->id]);
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

    public function requestPayout(\Illuminate\Http\Request $request)
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $dashboard     = app(PartnerFinancialDashboardService::class)->forDeliveryDriver($driver);
        $availableCard = collect($dashboard['cards'])->firstWhere('label', 'Disponible au retrait');
        $available     = (float) ($availableCard['amount'] ?? 0);

        if ($available < 500) {
            return response()->json(['success' => false, 'message' => 'Montant minimum de versement : 500 FCFA.']);
        }

        $alreadyPending = \App\DriverPayment::where('driver_id', $driver->id)
            ->where('status', 'pending')->exists();
        if ($alreadyPending) {
            return response()->json(['success' => false, 'message' => 'Une demande de versement est déjà en cours de traitement.']);
        }

        \App\DriverPayment::create([
            'driver_id'      => $driver->id,
            'payout_amount'  => (int) round($available),
            'transaction_id' => 'REQ-' . $driver->id . '-' . time(),
        ]);

        \Illuminate\Support\Facades\Log::info('Driver payout request', [
            'driver_id' => $driver->id,
            'amount'    => $available,
        ]);

        return response()->json([
            'success' => true,
            'amount'  => $available,
            'message' => number_format($available, 0, ',', ' ') . ' FCFA — demande envoyée. L\'administrateur traitera le reversement depuis le tableau de bord Finance.',
        ]);
    }

    /**
     * Vue Historique des courses
     */
    public function historique(Request $request)
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        $status  = $request->get('status', 'all');
        $period  = $request->get('period', '30');
        $perPage = 30;

        $historique = Delivery::with(['order.user', 'restaurant'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['DELIVERED', 'CANCELLED'])
            ->when($status !== 'all', fn($q) => $q->where('status', strtoupper($status)))
            ->when($period, fn($q) => $q->where('created_at', '>=', now()->subDays((int) $period)))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $totalDelivered = Delivery::where('driver_id', $driver->id)->where('status', 'DELIVERED')->count();
        $totalCancelled = Delivery::where('driver_id', $driver->id)->where('status', 'CANCELLED')->count();
        $totalFees      = Delivery::where('driver_id', $driver->id)->where('status', 'DELIVERED')->sum('delivery_fee');
        $avgFee         = $totalDelivered > 0 ? round($totalFees / $totalDelivered) : 0;
        $grouped        = $historique->getCollection()->groupBy(fn($d) => $d->created_at->format('Y-m-d'));

        return view('driver.historique', compact(
            'driver', 'historique', 'grouped', 'status', 'period',
            'totalDelivered', 'totalCancelled', 'totalFees', 'avgFee'
        ));
    }

    /**
     * Vue Ma note & avis clients
     */
    public function note()
    {
        $driver = $this->driverOrRedirect();
        if (!($driver instanceof Driver)) return $driver;

        // Charger les vrais avis depuis la table ratings (filtrés par driver_id)
        $reviews = \App\Rating::with('user')
            ->where('driver_id', $driver->id)
            ->orderByDesc('created_at')
            ->get();

        $avgRating    = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : null;
        $totalRatings = $reviews->count();

        // Distribution étoiles 1-5
        $starDist = array_fill(1, 5, 0);
        foreach ($reviews as $r) {
            $starDist[min(5, max(1, (int) $r->rating))]++;
        }

        return view('driver.note', compact('driver', 'reviews', 'avgRating', 'totalRatings', 'starDist'));
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

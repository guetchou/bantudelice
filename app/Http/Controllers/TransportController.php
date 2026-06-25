<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportRide;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Models\TransportPricingRule;
use App\Domain\Transport\Enums\TransportType;
use App\Services\ConfigService;
use App\Services\PartnerFinancialDashboardService;
use App\Services\PaymentExperienceService;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    /**
     * Landing page for transport services
     */
    public function index()
    {
        $homeContent = ConfigService::getHomeContent('kende');

        return view('frontend.transport.index', compact('homeContent'));
    }

    /**
     * Taxi booking page
     */
    public function taxi()
    {
        $pricing = TransportPricingRule::where('type', TransportType::TAXI)
            ->where('is_active', true)
            ->first();

        $homeContent = ConfigService::getHomeContent('kende');

        return view('frontend.transport.taxi', compact('pricing', 'homeContent'));
    }

    /**
     * Carpool listing and booking page
     */
    public function carpool()
    {
        $rides = TransportRide::where('status', 'published')
            ->where('departure_time', '>', now())
            ->with(['driver', 'vehicle'])
            ->get();
            
        return view('frontend.transport.carpool', compact('rides'));
    }

    /**
     * Car rental catalog page
     */
    public function rental()
    {
        $vehicles = TransportVehicle::where('type', 'rental')
            ->where('is_available', true)
            ->get();
            
        return view('frontend.transport.rental', compact('vehicles'));
    }

    /**
     * Bus booking page
     */
    public function bus()
    {
        $pricing = TransportPricingRule::where('type', TransportType::BUS)
            ->where('is_active', true)
            ->first();

        $busLines = collect([
            [
                'name' => 'Brazzaville -> Pointe-Noire',
                'frequency' => 'Tous les jours',
                'departure' => '06:30',
                'arrival' => '13:30',
                'price' => 12000,
            ],
            [
                'name' => 'Brazzaville -> Dolisie',
                'frequency' => 'Lun, Mer, Ven',
                'departure' => '07:00',
                'arrival' => '11:30',
                'price' => 8000,
            ],
            [
                'name' => 'Brazzaville -> Nkayi',
                'frequency' => 'Samedi',
                'departure' => '08:00',
                'arrival' => '12:30',
                'price' => 7000,
            ],
        ]);

        return view('frontend.transport.bus', compact('pricing', 'busLines'));
    }

    /**
     * My transport bookings
     */
    public function myBookings()
    {
        $bookings = auth()->user()->transportBookings()
            ->with(['driver', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('frontend.transport.my_bookings', compact('bookings'));
    }

    /**
     * Booking details page
     */
    public function showBooking($id)
    {
        abort_unless($this->looksLikeUuid($id), 404);

        $booking = TransportBooking::where('uuid', $id)
            ->with(['driver', 'vehicle', 'trackingPoints', 'payments' => function ($query) {
                $query->latest('id');
            }])
            ->firstOrFail();

        $this->authorize('view', $booking);

        $paymentExperience = app(PaymentExperienceService::class)->describe($booking->payments->first());

        return response()
            ->view('frontend.transport.booking_detail', compact('booking', 'paymentExperience'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Driver Transport Dashboard
     */
    public function driverDashboard()
    {
        $driver = $this->resolveDriverFromAuthUser();

        if (! $driver) {
            return redirect()->route('home')->with('alert', [
                'type' => 'warning',
                'message' => 'Aucun compte chauffeur transport associé à votre profil'
            ]);
        }
        
        $activeBooking = TransportBooking::with(['user', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'driver_arriving', 'picked_up', 'in_progress'])
            ->latest('created_at')
            ->first();
            
        $nearbyRequests = TransportBooking::where('status', 'requested')
            ->whereNull('driver_id')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now()->addMinutes(5));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $financialDashboard = app(PartnerFinancialDashboardService::class)->forTransportDriver($driver);
            
        return view('driver.transport.index', compact('activeBooking', 'nearbyRequests', 'driver', 'financialDashboard'));
    }

    protected function resolveDriverFromAuthUser(): ?Driver
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();

        if (! $driver && $user->type === 'driver') {
            $driver = Driver::where('name', $user->name)->first();
        }

        return $driver;
    }

    protected function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-fA-F-]{36}$/', $value);
    }
}

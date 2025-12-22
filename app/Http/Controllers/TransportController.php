<?php

namespace App\Http\Controllers;

use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportRide;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Models\TransportPricingRule;
use App\Domain\Transport\Enums\TransportType;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    /**
     * Landing page for transport services
     */
    public function index()
    {
        return view('frontend.transport.index');
    }

    /**
     * Taxi booking page
     */
    public function taxi()
    {
        $pricing = TransportPricingRule::where('type', TransportType::TAXI)
            ->where('is_active', true)
            ->first();
            
        return view('frontend.transport.taxi', compact('pricing'));
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
        $booking = TransportBooking::where('uuid', $id)
            ->orWhere('id', $id)
            ->with(['driver', 'vehicle', 'trackingPoints'])
            ->firstOrFail();
            
        return view('frontend.transport.booking_detail', compact('booking'));
    }

    /**
     * Driver Transport Dashboard
     */
    public function driverDashboard()
    {
        $driver = auth()->user(); // Assuming the user is a driver or has a driver profile
        
        $activeBooking = TransportBooking::where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'driver_arriving', 'in_progress'])
            ->first();
            
        $nearbyRequests = TransportBooking::where('status', 'requested')
            ->whereNull('driver_id')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('driver.transport.index', compact('activeBooking', 'nearbyRequests'));
    }
}


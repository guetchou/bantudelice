<?php

namespace App\Http\Controllers\admin\Transport;

use App\Http\Controllers\Controller;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Models\TransportPricingRule;
use Illuminate\Http\Request;

class AdminTransportController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_bookings' => TransportBooking::count(),
            'pending_bookings' => TransportBooking::where('status', 'requested')->count(),
            'active_bookings' => TransportBooking::where('status', 'in_progress')->count(),
            'completed_bookings' => TransportBooking::where('status', 'completed')->count(),
            'total_revenue' => TransportBooking::where('status', 'completed')->sum('total_price'),
        ];

        return view('admin.transport.dashboard', compact('stats'));
    }

    public function bookings()
    {
        $bookings = TransportBooking::with(['user', 'driver'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.transport.bookings.index', compact('bookings'));
    }

    public function vehicles()
    {
        $vehicles = TransportVehicle::with('owner')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.transport.vehicles.index', compact('vehicles'));
    }

    public function approveVehicle($id)
    {
        $vehicle = TransportVehicle::findOrFail($id);
        $vehicle->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null
        ]);

        return redirect()->back()->with('success', 'Véhicule approuvé avec succès.');
    }

    public function rejectVehicle(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $vehicle = TransportVehicle::findOrFail($id);
        $vehicle->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'approved_at' => null,
            'approved_by' => null
        ]);

        return redirect()->back()->with('success', 'Véhicule rejeté.');
    }

    public function pricingRules()
    {
        $rules = TransportPricingRule::all();
        return view('admin.transport.pricing.index', compact('rules'));
    }

    public function storePricingRule(Request $request)
    {
        $request->validate([
            'type' => 'required|in:taxi,carpool,rental',
            'base_fare' => 'required|numeric',
            'price_per_km' => 'required|numeric',
            'price_per_minute' => 'required|numeric',
            'minimum_fare' => 'required|numeric',
        ]);

        TransportPricingRule::create($request->all());

        return redirect()->back()->with('success', 'Règle de tarification ajoutée');
    }

    public function updatePricingRule(Request $request, $id)
    {
        $rule = TransportPricingRule::findOrFail($id);
        $rule->update($request->all());

        return redirect()->back()->with('success', 'Règle de tarification mise à jour');
    }
}


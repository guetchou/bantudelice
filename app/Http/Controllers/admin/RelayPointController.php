<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Domain\Colis\Models\RelayPoint;
use Illuminate\Http\Request;

class RelayPointController extends Controller
{
    public function index()
    {
        $relayPoints = RelayPoint::latest()->paginate(15);
        return view('admin.colis.relay_points.index', compact('relayPoints'));
    }

    public function create()
    {
        return view('admin.colis.relay_points.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string',
            'district' => 'required|string',
            'address' => 'required|string',
            'opening_hours' => 'nullable|string',
            'contact_phone' => 'nullable|string',
        ]);

        RelayPoint::create($data);

        return redirect()->route('admin.relay-points.index')
            ->with('success', 'Point relais ajouté avec succès.');
    }

    public function toggle(RelayPoint $relayPoint)
    {
        $relayPoint->update(['is_active' => !$relayPoint->is_active]);
        return back()->with('success', 'Statut mis à jour.');
    }
}


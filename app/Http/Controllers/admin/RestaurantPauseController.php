<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestaurantPauseController extends Controller
{
    public function index()
    {
        $paused = Restaurant::where('is_paused', true)
            ->with(['orders' => fn($q) => $q->whereIn('status', ['pending', 'accepted'])])
            ->orderByDesc('updated_at')
            ->get();

        $all = Restaurant::where('is_paused', false)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'last_activity_at', 'status']);

        return view('admin.restaurant.paused', compact('paused', 'all'));
    }

    public function forcePause(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'reason'       => 'nullable|string|max:255',
            'paused_until' => 'nullable|date|after:now',
        ]);

        $restaurant->update([
            'is_paused'    => true,
            'pause_reason' => $request->reason ?? 'Mis en pause par l\'administrateur',
            'paused_until' => $request->paused_until,
        ]);

        Log::warning('Admin force-pause restaurant', [
            'restaurant_id' => $restaurant->id,
            'name'          => $restaurant->name,
            'reason'        => $request->reason,
            'admin_id'      => auth()->id(),
        ]);

        return back()->with('success', "Restaurant « {$restaurant->name} » mis en pause.");
    }

    public function forceResume(Restaurant $restaurant)
    {
        $restaurant->update([
            'is_paused'    => false,
            'pause_reason' => null,
            'paused_until' => null,
        ]);

        Log::info('Admin force-resume restaurant', [
            'restaurant_id' => $restaurant->id,
            'name'          => $restaurant->name,
            'admin_id'      => auth()->id(),
        ]);

        return back()->with('success', "Restaurant « {$restaurant->name} » remis en ligne.");
    }
}

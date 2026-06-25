<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\RestaurantStaffMember;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantStaffController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;

        return view('restaurant.staff.index', [
            'restaurant' => $restaurant,
            'members' => RestaurantStaffMember::with('user')
                ->where('restaurant_id', $restaurant->id)
                ->orderByDesc('is_active')
                ->orderBy('role')
                ->get(),
            'roles' => $this->roles($request),
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;
        $roles = $this->roles($request);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(array_keys($roles))],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ((int) $user->id === (int) $restaurant->user_id) {
            return back()->withErrors(['email' => 'Le propriétaire possède déjà tous les droits.']);
        }

        if ((string) $user->type !== 'restaurant') {
            return back()->withErrors(['email' => 'Ce compte doit être de type restaurant.']);
        }

        RestaurantStaffMember::updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'user_id' => $user->id],
            ['role' => $validated['role'], 'is_active' => true, 'invited_by' => auth()->id()]
        );

        return back()->with('alert', ['type' => 'success', 'message' => 'Accès collaborateur enregistré.']);
    }

    public function update(Request $request, RestaurantStaffMember $staff)
    {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;
        abort_unless((int) $staff->restaurant_id === (int) $restaurant->id, 404);

        $roles = $this->roles($request);
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys($roles))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $staff->update([
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('alert', ['type' => 'success', 'message' => 'Droits mis à jour.']);
    }

    public function deactivate(Request $request, RestaurantStaffMember $staff)
    {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;
        abort_unless((int) $staff->restaurant_id === (int) $restaurant->id, 404);

        if ((int) $staff->user_id === (int) auth()->id()) {
            return back()->withErrors(['staff' => 'Vous ne pouvez pas désactiver votre propre accès.']);
        }

        $staff->update(['is_active' => false]);

        return back()->with('alert', ['type' => 'success', 'message' => 'Accès désactivé.']);
    }

    private function roles(Request $request): array
    {
        $roles = [
            'manager' => 'Manager',
            'kitchen' => 'Cuisine',
            'cashier' => 'Caisse',
            'catalog' => 'Catalogue',
            'viewer' => 'Lecture seule',
        ];

        if ((string) $request->attributes->get('restaurant_staff_role', 'owner') === 'manager') {
            unset($roles['manager']);
        }

        return $roles;
    }
}

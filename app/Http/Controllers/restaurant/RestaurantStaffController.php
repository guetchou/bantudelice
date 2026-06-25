<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\RestaurantStaffMember;
use App\Services\AuditLogService;
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
            'currentRole' => (string) $request->attributes->get('restaurant_staff_role', 'owner'),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogs)
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

        $membership = RestaurantStaffMember::firstOrNew([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
        ]);
        $before = $membership->exists
            ? ['role' => $membership->role, 'is_active' => (bool) $membership->is_active]
            : null;

        $membership->fill([
            'role' => $validated['role'],
            'is_active' => true,
            'invited_by' => auth()->id(),
        ])->save();

        $auditLogs->record([
            'actor_type' => 'restaurant',
            'actor_id' => auth()->id(),
            'target_type' => 'restaurant_staff_member',
            'target_id' => $membership->id,
            'target_ref' => $user->email,
            'action' => $before ? 'restaurant_staff_reactivated' : 'restaurant_staff_added',
            'status' => 'active',
            'meta' => [
                'restaurant_id' => $restaurant->id,
                'before' => $before,
                'after' => ['role' => $membership->role, 'is_active' => true],
            ],
        ]);

        return back()->with('alert', ['type' => 'success', 'message' => 'Accès collaborateur enregistré.']);
    }

    public function update(
        Request $request,
        RestaurantStaffMember $staff,
        AuditLogService $auditLogs
    ) {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;
        abort_unless((int) $staff->restaurant_id === (int) $restaurant->id, 404);
        $this->guardManagerHierarchy($request, $staff);

        $roles = $this->roles($request);
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys($roles))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ((int) $staff->user_id === (int) auth()->id() && ! $request->boolean('is_active', true)) {
            return back()->withErrors(['is_active' => 'Vous ne pouvez pas désactiver votre propre accès.']);
        }

        $before = ['role' => $staff->role, 'is_active' => (bool) $staff->is_active];

        $staff->update([
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $auditLogs->record([
            'actor_type' => 'restaurant',
            'actor_id' => auth()->id(),
            'target_type' => 'restaurant_staff_member',
            'target_id' => $staff->id,
            'target_ref' => (string) $staff->user_id,
            'action' => 'restaurant_staff_updated',
            'status' => $staff->is_active ? 'active' : 'inactive',
            'meta' => [
                'restaurant_id' => $restaurant->id,
                'before' => $before,
                'after' => ['role' => $staff->role, 'is_active' => (bool) $staff->is_active],
            ],
        ]);

        return back()->with('alert', ['type' => 'success', 'message' => 'Droits mis à jour.']);
    }

    public function deactivate(
        Request $request,
        RestaurantStaffMember $staff,
        AuditLogService $auditLogs
    ) {
        $restaurant = $request->attributes->get('restaurant_context') ?? auth()->user()->restaurant;
        abort_unless((int) $staff->restaurant_id === (int) $restaurant->id, 404);
        $this->guardManagerHierarchy($request, $staff);

        if ((int) $staff->user_id === (int) auth()->id()) {
            return back()->withErrors(['staff' => 'Vous ne pouvez pas désactiver votre propre accès.']);
        }

        $staff->update(['is_active' => false]);

        $auditLogs->record([
            'actor_type' => 'restaurant',
            'actor_id' => auth()->id(),
            'target_type' => 'restaurant_staff_member',
            'target_id' => $staff->id,
            'target_ref' => (string) $staff->user_id,
            'action' => 'restaurant_staff_deactivated',
            'status' => 'inactive',
            'meta' => ['restaurant_id' => $restaurant->id, 'role' => $staff->role],
        ]);

        return back()->with('alert', ['type' => 'success', 'message' => 'Accès désactivé.']);
    }

    private function guardManagerHierarchy(Request $request, RestaurantStaffMember $staff): void
    {
        $currentRole = (string) $request->attributes->get('restaurant_staff_role', 'owner');

        if ($currentRole === 'manager' && $staff->role === 'manager') {
            abort(403, 'Un manager ne peut pas modifier un autre manager.');
        }
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

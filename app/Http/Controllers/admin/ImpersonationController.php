<?php

namespace App\Http\Controllers\admin;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Restaurant;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    public function restaurant(Restaurant $restaurant): RedirectResponse
    {
        $targetUser = User::query()
            ->where('id', $restaurant->user_id)
            ->where('type', 'restaurant')
            ->first();

        if (! $targetUser) {
            $targetUser = User::query()
                ->where('email', $restaurant->email)
                ->where('type', 'restaurant')
                ->first();
        }

        if (! $targetUser) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Aucun compte restaurant exploitable pour cette session.',
            ]);
        }

        return $this->beginImpersonation(
            $targetUser,
            route('restaurant.dashboard'),
            'Session restaurant ouverte depuis l’admin.'
        );
    }

    public function driver(Driver $driver): RedirectResponse
    {
        $targetUser = $this->resolveDriverUser($driver);

        return $this->beginImpersonation(
            $targetUser,
            route('driver.deliveries'),
            'Session livreur ouverte depuis l’admin.'
        );
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = session('admin_impersonator_id');
        $adminUser = $adminId ? User::query()->find($adminId) : null;

        if (! $adminUser || $adminUser->type !== 'admin') {
            session()->forget([
                'admin_impersonator_id',
                'admin_impersonation_context',
            ]);

            return redirect()->route('login')->with('alert', [
                'type' => 'warning',
                'message' => 'La session admin d’origine est introuvable.',
            ]);
        }

        $request->session()->forget([
            'admin_impersonator_id',
            'admin_impersonation_context',
        ]);
        auth()->login($adminUser, true);
        $request->session()->regenerateToken();

        return redirect()->route('admin.dashboard')->with('alert', [
            'type' => 'success',
            'message' => 'Retour au compte admin effectué.',
        ]);
    }

    protected function beginImpersonation(User $targetUser, string $redirectTo, string $message): RedirectResponse
    {
        $adminId = auth()->id();

        Log::warning('Admin impersonation démarrée', [
            'admin_id'       => $adminId,
            'admin_email'    => auth()->user()?->email,
            'target_user_id' => $targetUser->id,
            'target_type'    => $targetUser->type,
            'target_email'   => $targetUser->email,
            'ip'             => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);

        session([
            'admin_impersonator_id' => $adminId,
            'admin_impersonation_context' => [
                'target_user_id' => $targetUser->id,
                'target_name'    => $targetUser->name,
                'target_type'    => $targetUser->type,
                'started_at'     => now()->toIso8601String(),
            ],
        ]);

        auth()->login($targetUser);
        session()->regenerateToken();

        return redirect($redirectTo)->with('alert', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    protected function resolveDriverUser(Driver $driver): User
    {
        $targetUser = User::query()
            ->where(function ($query) use ($driver) {
                $query->when($driver->email, fn ($q) => $q->orWhere('email', $driver->email))
                    ->when($driver->phone, fn ($q) => $q->orWhere('phone', $driver->phone));
            })
            ->orderByRaw("CASE WHEN type = 'driver' THEN 0 WHEN type = 'delivery' THEN 1 ELSE 2 END")
            ->first();

        if ($targetUser) {
            if (! in_array($targetUser->type, ['driver', 'delivery'], true)) {
                $targetUser->type = 'driver';
                $targetUser->save();
            }

            return $targetUser;
        }

        $driverEmail = $driver->email;
        $emailIsFree = $driverEmail
            && ! User::query()->where('email', $driverEmail)->exists();

        return User::create([
            'name' => $driver->name,
            'email' => $emailIsFree ? $driverEmail : sprintf('driver-%d@bantudelice.cg', $driver->id),
            'password' => Hash::make(Str::random(40)),
            'phone' => $driver->phone,
            'type' => 'driver',
        ]);
    }
}

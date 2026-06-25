<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login_view(Request $request)
    {
        $redirectTarget = $this->safeRedirectTarget($request->query('redirect'));

        if (auth()->check()) {
            return $redirectTarget
                ? redirect()->to($redirectTarget)
                : $this->redirectByType(auth()->user()->type);
        }

        if ($redirectTarget) {
            $request->session()->put('url.intended', $redirectTarget);
        }

        return view('auth.login', [
            'redirectTarget' => $redirectTarget,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:191',
            'password'   => 'required|string|max:191',
            'redirect'   => 'nullable|string|max:2048',
        ]);

        $identifier     = trim($request->identifier);
        $user           = $this->findUser($identifier);
        $redirectTarget = $this->safeRedirectTarget($request->input('redirect'));

        if (!$user || !Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withInput($request->only('identifier', 'redirect'))
                ->with('alert', [
                    'type'    => 'danger',
                    'heading' => 'Échec de connexion',
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ]);
        }

        // Admin avec 2FA activé → challenge avant de finaliser la session
        if ($user->type === 'admin' && !empty($user->two_factor_enabled)) {
            $request->session()->put('2fa_pending_user_id',   $user->id);
            $request->session()->put('2fa_pending_remember',  $request->boolean('remember'));
            if ($redirectTarget) {
                $request->session()->put('url.intended', $redirectTarget);
            }
            return redirect()->route('admin.2fa.challenge');
        }

        auth()->login($user, $request->boolean('remember'));
        \App\Services\CartService::migrateSessionCartToDb($user->id);

        if ($redirectTarget) {
            return redirect()->to($redirectTarget);
        }

        return redirect()->intended($this->defaultRedirect($user->type));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('login');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findUser(string $identifier): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $identifier)->first();
            if (!$user) {
                $user = $this->syncDriverAsUser($identifier, 'email');
            }
            return $user;
        }

        // Téléphone : +242…, 06…, 05…, 04…, ou 8+ chiffres consécutifs
        if (preg_match('/^(\+?242|0[456])\s?\d[\d\s]{5,}$|^\d{8,}$/', $identifier)) {
            $user = User::where('phone', $identifier)->first();
            if (!$user) {
                $user = $this->syncDriverAsUser($identifier, 'phone');
            }
            return $user;
        }

        return User::where('username', $identifier)->first();
    }

    /**
     * Si un livreur existe dans `drivers` mais pas dans `users`, le synchronise
     * pour permettre la connexion web. Shim pour les livreurs créés avant la
     * correction de driverRegistration().
     *
     * Sécurité :
     * - Refusé si l'email appartient déjà à un User de type non-driver (évite le takeover).
     * - Refusé si le Driver n'a pas d'email vérifié (email_verified_at requis).
     * - Le type reste toujours 'driver' — pas d'escalade de privilèges.
     */
    private function syncDriverAsUser(string $identifier, string $field): ?User
    {
        $driver = \App\Driver::where($field, $identifier)->first();
        if (!$driver || empty($driver->email)) {
            return null;
        }

        // Bloquer si l'email est déjà pris par un compte non-driver
        $existingUser = User::where('email', $driver->email)->first();
        if ($existingUser && $existingUser->type !== 'driver') {
            \Log::warning('[syncDriverAsUser] Email appartient à un compte non-driver', [
                'email'         => $driver->email,
                'existing_type' => $existingUser->type,
            ]);
            return null;
        }

        // Shim uniquement pour drivers déjà vérifiés (email_verified_at présent)
        if (empty($driver->email_verified_at) && !$existingUser) {
            \Log::info('[syncDriverAsUser] Sync refusé : email non vérifié', ['driver_id' => $driver->id]);
            return null;
        }

        return User::firstOrCreate(
            ['email' => $driver->email],
            [
                'name'     => $driver->name,
                'email'    => $driver->email,
                'phone'    => $driver->phone,
                'password' => $driver->password,
                'type'     => 'driver', // jamais escaladé
            ]
        );
    }

    private function redirectByType(?string $type): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to($this->defaultRedirect($type));
    }

    private function defaultRedirect(?string $type): string
    {
        return match($type) {
            'admin'              => route('admin.portal'),
            'restaurant'         => route('restaurant.dashboard'),
            'driver', 'delivery' => route('driver.deliveries'),
            default              => route('home'),
        };
    }

    private function safeRedirectTarget(?string $target): ?string
    {
        $target = trim((string) $target);
        if ($target === '') {
            return null;
        }

        if (str_starts_with($target, '/')) {
            return $target;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($target, $appUrl . '/')) {
            return $target;
        }

        $currentHost = request()->getSchemeAndHttpHost();
        if ($currentHost !== '' && str_starts_with($target, $currentHost . '/')) {
            return $target;
        }

        return null;
    }
}

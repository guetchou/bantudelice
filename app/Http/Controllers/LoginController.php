<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login_view()
    {
        if (auth()->check()) {
            return $this->redirectByType(auth()->user()->type);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:191',
            'password'   => 'required|string|max:191',
        ]);

        $identifier = trim($request->identifier);
        $user       = $this->findUser($identifier);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withInput($request->only('identifier'))
                ->with('alert', [
                    'type'    => 'danger',
                    'heading' => 'Échec de connexion',
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ]);
        }

        auth()->login($user, $request->boolean('remember'));

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
}

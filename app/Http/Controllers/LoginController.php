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
            return User::where('email', $identifier)->first();
        }

        // Téléphone : +242…, 06…, 05…, 04…, ou 8+ chiffres consécutifs
        if (preg_match('/^(\+?242|0[456])\s?\d[\d\s]{5,}$|^\d{8,}$/', $identifier)) {
            return User::where('phone', $identifier)->first();
        }

        return User::where('username', $identifier)->first();
    }

    private function redirectByType(?string $type): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route($this->defaultRedirect($type));
    }

    private function defaultRedirect(?string $type): string
    {
        return match($type) {
            'admin'              => route('admin.dashboard'),
            'restaurant'         => route('restaurant.dashboard'),
            'driver', 'delivery' => route('driver.deliveries'),
            default              => route('home'),
        };
    }
}

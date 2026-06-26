<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/**
 * Vues d'authentification — login, signup, mot de passe oublié, déconnexion.
 * Aucune écriture DB. Aucune logique métier.
 * La logique d'inscription et de réinitialisation de mot de passe reste dans IndexController.
 */
class AuthViewController extends Controller
{
    use RemembersFrontendBrand;

    public function Login(Request $request): View|RedirectResponse
    {
        if ($request->filled('redirect')) {
            $redirectTarget = (string) $request->query('redirect');
            if (\Illuminate\Support\Str::startsWith($redirectTarget, ['/']) && $this->redirectPath($redirectTarget) !== '/profile') {
                session()->put('url.intended', $redirectTarget);
            }
        }

        if (auth()->check()) {
            return $this->redirectAuthenticatedUser();
        }

        return view('frontend.login');
    }

    public function SignUp(Request $request): View|RedirectResponse
    {
        if ($request->filled('redirect')) {
            $redirectTarget = (string) $request->query('redirect');
            if (\Illuminate\Support\Str::startsWith($redirectTarget, ['/']) && $this->redirectPath($redirectTarget) !== '/profile') {
                session()->put('url.intended', $redirectTarget);
            }
        }

        if (auth()->check()) {
            return $this->redirectAuthenticatedUser();
        }

        return view('frontend.signup');
    }

    public function forgot(Request $request): View
    {
        if ($request->filled('redirect')) {
            $redirectTarget = (string) $request->query('redirect');
            if (\Illuminate\Support\Str::startsWith($redirectTarget, ['/']) && $this->redirectPath($redirectTarget) !== '/profile') {
                session()->put('url.intended', $redirectTarget);
            }
        }

        return view('frontend.forgot');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function redirectAuthenticatedUser(): RedirectResponse
    {
        return match (auth()->user()->type ?? null) {
            'admin' => redirect()->route('admin.portal'),
            'restaurant' => redirect()->route('restaurant.dashboard'),
            'driver' => redirect()->route('driver.deliveries'),
            'delivery' => redirect()->route('delivery.dashboard'),
            default => redirect()->route('user.dashboard'),
        };
    }

    private function redirectPath(string $target): string
    {
        $path = parse_url($target, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $target;
        }

        $path = '/' . ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }
}

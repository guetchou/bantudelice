<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    protected function assertProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }
    }

    public function redirect(Request $request, string $provider)
    {
        $this->assertProvider($provider);

        try {
            $state = Str::random(40);
            $request->session()->put("social_auth.{$provider}.state", $state);

            $redirectTarget = (string) $request->query('redirect', '');
            if (Str::startsWith($redirectTarget, ['/'])) {
                $request->session()->put('url.intended', $redirectTarget);
            }

            if ($provider === 'google') {
                return redirect()->away(SocialAuthService::getGoogleAuthUrl(state: $state));
            }
            if ($provider === 'facebook') {
                return redirect()->away(SocialAuthService::getFacebookAuthUrl(state: $state));
            }
        } catch (\Throwable $e) {
            Log::warning('Social auth redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('user.login')->with('alert', [
                'type' => 'danger',
                'message' => 'Connexion ' . ucfirst($provider) . ' indisponible (configuration manquante).',
            ]);
        }

        abort(404);
    }

    public function callback(Request $request, string $provider)
    {
        $this->assertProvider($provider);

        if ($request->filled('error')) {
            return redirect()->route('user.login')->with('alert', [
                'type' => 'danger',
                'message' => 'Connexion ' . ucfirst($provider) . ' annulée.',
            ]);
        }

        $expectedState = (string) $request->session()->pull("social_auth.{$provider}.state", '');
        if (!$request->filled('state') || $expectedState === '' || !hash_equals($expectedState, (string) $request->state)) {
            return redirect()->route('user.login')->with('alert', [
                'type' => 'danger',
                'message' => 'Erreur de sécurité (state invalide). Veuillez réessayer.',
            ]);
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('user.login')->with('alert', [
                'type' => 'danger',
                'message' => 'Code de connexion manquant.',
            ]);
        }

        try {
            $result = $provider === 'google'
                ? SocialAuthService::handleGoogleCallback($code)
                : SocialAuthService::handleFacebookCallback($code);

            $user = $result['user'] ?? null;
            if (!$user) {
                throw new \RuntimeException('Utilisateur introuvable après callback');
            }

            auth()->login($user, true);
            $request->session()->regenerate();
            \App\Services\CartService::migrateSessionCartToDb($user->id);

            $isNew    = $result['is_new'] ?? false;
            $provider = ucfirst($provider);

            // Redirection par type d'utilisateur
            switch ($user->type) {
                case 'admin':
                    return redirect()->route('admin.dashboard')
                        ->with('message', 'Connexion Administration réussie !');

                case 'restaurant':
                    return redirect()->intended(route('restaurant.dashboard'))
                        ->with('message', "Connexion {$provider} réussie !");

                case 'driver':
                    return redirect()->intended(route('driver.deliveries'))
                        ->with('message', "Connexion {$provider} réussie !");

                default: // 'user' — client
                    $message = $isNew
                        ? 'Bienvenue sur BantuDelice ! Complétez votre profil pour commander plus rapidement.'
                        : "Connexion {$provider} réussie !";

                    return redirect()->intended(route('user.profile'))
                        ->with('message', $message);
            }
        } catch (\Throwable $e) {
            Log::error('Social auth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('user.login')->with('alert', [
                'type' => 'danger',
                'message' => 'Erreur lors de la connexion ' . ucfirst($provider) . '.',
            ]);
        }
    }
}


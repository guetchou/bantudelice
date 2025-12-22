<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            if ($provider === 'google') {
                return redirect()->away(SocialAuthService::getGoogleAuthUrl());
            }
            if ($provider === 'facebook') {
                return redirect()->away(SocialAuthService::getFacebookAuthUrl());
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

        // Vérification simple du state (SocialAuthService utilise csrf_token() comme state)
        if ($request->filled('state') && $request->state !== csrf_token()) {
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

            // Redirection intelligente selon le type d'utilisateur
            if ($user->type === 'admin') {
                return redirect()->route('admin.dashboard')->with('message', 'Connexion Administration réussie !');
            }

            return redirect()->route('home')->with('message', 'Connexion réussie !');
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



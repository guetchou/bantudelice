<?php

namespace App\Http\Controllers\admin;

use App\User;
use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private function g2fa(): Google2FA
    {
        return new Google2FA();
    }

    // ── Challenge (login 2e étape ou session existante) ───────────────────────

    public function showChallenge(Request $request)
    {
        // Cas 1 : login en cours (pending user non encore auth)
        $pendingId = $request->session()->get('2fa_pending_user_id');
        if ($pendingId) {
            return view('admin.2fa.challenge', ['mode' => 'login']);
        }

        // Cas 2 : admin déjà auth mais 2FA non vérifié pour cette session
        if (auth()->check() && auth()->user()->type === 'admin' && auth()->user()->two_factor_enabled) {
            return view('admin.2fa.challenge', ['mode' => 'session']);
        }

        return redirect()->route('login');
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $pendingId = $request->session()->get('2fa_pending_user_id');
        $remember  = $request->session()->get('2fa_pending_remember', false);

        // Résoudre l'utilisateur (login en cours ou déjà auth)
        if ($pendingId) {
            $user = User::find($pendingId);
        } elseif (auth()->check() && auth()->user()->type === 'admin') {
            $user = auth()->user();
        } else {
            return redirect()->route('login');
        }

        if (!$user || !$user->two_factor_secret) {
            return redirect()->route('login');
        }

        $valid = $this->g2fa()->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré. Réessayez.']);
        }

        // Finaliser la connexion si c'était un login en attente
        if ($pendingId) {
            auth()->login($user, $remember);
            \App\Services\CartService::migrateSessionCartToDb($user->id);
            $request->session()->forget(['2fa_pending_user_id', '2fa_pending_remember']);
        }

        $request->session()->put('admin_2fa_verified', true);

        return redirect()->intended(route('admin.portal'));
    }

    // ── Setup ────────────────────────────────────────────────────────────────

    public function showSetup(Request $request)
    {
        $user   = auth()->user();
        $g2fa   = $this->g2fa();
        $secret = $g2fa->generateSecretKey();
        $request->session()->put('2fa_setup_secret', $secret);

        $qrUrl = $g2fa->getQRCodeUrl(
            config('app.name', 'BantuDelice'),
            $user->email,
            $secret
        );

        // QR généré localement — le secret ne quitte jamais le serveur
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $qrSvg    = base64_encode((new Writer($renderer))->writeString($qrUrl));

        return view('admin.2fa.setup', compact('secret', 'qrSvg', 'user'));
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $secret = $request->session()->get('2fa_setup_secret');
        if (!$secret) {
            return back()->withErrors(['code' => 'Session expirée. Recommencez la configuration.']);
        }

        $valid = $this->g2fa()->verifyKey($secret, $request->code);
        if (!$valid) {
            return back()->withErrors(['code' => 'Code invalide. Vérifiez que l\'heure de votre téléphone est synchronisée.']);
        }

        $currentUser = auth()->user();
        $currentUser->two_factor_secret  = $secret;
        $currentUser->two_factor_enabled = true;
        $currentUser->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('admin_2fa_verified', true);

        return redirect()->route('admin.profile')->with('success', '2FA activé avec succès. Votre compte est maintenant sécurisé.');
    }

    public function disable(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user  = auth()->user();
        $valid = $this->g2fa()->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Code incorrect. Entrez le code actuel de votre application.']);
        }

        $user->two_factor_secret  = null;
        $user->two_factor_enabled = false;
        $user->save();

        $request->session()->forget('admin_2fa_verified');

        return redirect()->route('admin.profile')->with('success', '2FA désactivé.');
    }
}

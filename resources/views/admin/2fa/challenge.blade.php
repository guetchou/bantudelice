<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA | BantuDelice Admin</title>
    <link rel="stylesheet" href="{{ asset('css/font-awesome/all.min.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .tfa-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            text-align: center;
        }
        .tfa-shield {
            width: 64px; height: 64px; border-radius: 50%;
            background: #dcfce7;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.6rem; color: #007836;
        }
        .tfa-title { font-size: 1.3rem; font-weight: 700; color: #111827; margin-bottom: 6px; }
        .tfa-sub   { font-size: .85rem; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
        .tfa-input-wrap { position: relative; margin-bottom: 16px; }
        .tfa-input {
            width: 100%;
            padding: 14px 16px;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: .35em;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            outline: none;
            transition: border-color .2s;
            background: #f9fafb;
        }
        .tfa-input:focus { border-color: #007836; background: #fff; }
        .tfa-input.is-invalid { border-color: #dc2626; }
        .tfa-error {
            color: #dc2626;
            font-size: .8rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }
        .tfa-btn {
            width: 100%;
            padding: 13px;
            background: #007836;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            margin-bottom: 16px;
        }
        .tfa-btn:hover { background: #005a28; }
        .tfa-btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .tfa-back { font-size: .82rem; color: #6b7280; }
        .tfa-back a { color: #007836; text-decoration: none; }
        .tfa-back a:hover { text-decoration: underline; }
        .tfa-hint {
            background: #f0faf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: .78rem;
            color: #166534;
            margin-bottom: 20px;
            text-align: left;
        }
        .tfa-hint i { margin-right: 6px; }
    </style>
</head>
<body>
<div class="tfa-card">
    <div class="tfa-shield"><i class="fas fa-shield-halved"></i></div>
    <h1 class="tfa-title">Vérification en 2 étapes</h1>
    <p class="tfa-sub">
        Entrez le code à 6 chiffres affiché dans votre application d'authentification
        (Google Authenticator, Authy…).
    </p>

    <div class="tfa-hint">
        <i class="fas fa-mobile-screen-button"></i>
        Le code change toutes les <strong>30 secondes</strong>. Utilisez le code actuellement affiché.
    </div>

    <form method="POST" action="{{ route('admin.2fa.verify') }}" id="tfaForm">
        @csrf
        <div class="tfa-input-wrap">
            <input
                type="text"
                name="code"
                id="tfaCode"
                class="tfa-input @error('code') is-invalid @enderror"
                placeholder="000000"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                autofocus
                required
            >
        </div>

        @error('code')
            <div class="tfa-error">
                <i class="fas fa-circle-exclamation"></i> {{ $message }}
            </div>
        @enderror

        <button type="submit" class="tfa-btn" id="tfaSubmit">
            <i class="fas fa-check"></i> Vérifier
        </button>
    </form>

    <div class="tfa-back">
        <a href="{{ route('login') }}"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
    </div>
</div>

<script>
// Auto-submit quand 6 chiffres saisis
document.getElementById('tfaCode').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length === 6) {
        document.getElementById('tfaSubmit').disabled = true;
        document.getElementById('tfaSubmit').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification…';
        document.getElementById('tfaForm').submit();
    }
});
</script>
</body>
</html>

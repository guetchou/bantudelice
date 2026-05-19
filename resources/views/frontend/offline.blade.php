<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors ligne — BantuDelice</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="manifest" href="/manifest.webmanifest">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', 'Outfit', system-ui, sans-serif;
            background: #fff7f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
            color: #1a1a1a;
        }
        .offline-icon {
            font-size: 72px;
            margin-bottom: 24px;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.08); }
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #e85d04;
        }
        p {
            font-size: 15px;
            color: #555;
            max-width: 340px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .hint {
            font-size: 13px;
            color: #999;
            margin-top: 4px;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: #e85d04;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s;
        }
        .btn:hover { background: #c94d00; }
        .status-bar {
            margin-top: 40px;
            font-size: 12px;
            color: #bbb;
        }
        #online-indicator {
            display: none;
            margin-top: 16px;
            padding: 10px 20px;
            background: #dcfce7;
            color: #15803d;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="offline-icon">🍽️</div>
    <h1>Pas de connexion</h1>
    <p>Vérifiez votre réseau et réessayez. Si vous étiez en train de passer une commande, elle sera envoyée automatiquement dès le retour du réseau.</p>
    <p class="hint">Les coupures de réseau sont fréquentes à Brazzaville — pas d'inquiétude !</p>

    <div id="online-indicator">
        ✅ Réseau de retour — vous pouvez continuer
    </div>

    <button class="btn" onclick="window.location.reload()">
        Réessayer
    </button>

    <div class="status-bar" id="status-bar">Hors ligne</div>

    <script>
    function updateStatus() {
        const bar       = document.getElementById('status-bar');
        const indicator = document.getElementById('online-indicator');
        if (navigator.onLine) {
            bar.textContent = '● Réseau disponible';
            bar.style.color = '#15803d';
            indicator.style.display = 'block';
        } else {
            bar.textContent = '○ Hors ligne';
            bar.style.color = '#bbb';
            indicator.style.display = 'none';
        }
    }
    window.addEventListener('online',  () => { updateStatus(); });
    window.addEventListener('offline', () => { updateStatus(); });
    updateStatus();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur BantuDelice</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; line-height:1.6; color:#333; background:#f5f5f5; }
        .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; }
        .header { background:linear-gradient(135deg,#009543 0%,#007a36 100%); padding:36px 30px; text-align:center; }
        .header img { max-height:52px; width:auto; }
        .header h1 { color:#fff; font-size:22px; margin-top:16px; font-weight:700; letter-spacing:-.02em; }
        .body { padding:36px 32px; }
        .greeting { font-size:18px; font-weight:700; color:#0f172a; margin-bottom:16px; }
        .msg { color:#475569; font-size:15px; margin-bottom:18px; line-height:1.75; }
        .badge { display:inline-flex; align-items:center; gap:10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:14px 18px; margin:20px 0; width:100%; }
        .badge-icon { font-size:22px; flex-shrink:0; }
        .badge p { color:#166534; font-size:14px; margin:0; font-weight:600; }
        .feature { display:flex; align-items:flex-start; gap:14px; background:#f8fafc; border-radius:10px; padding:16px; margin-bottom:10px; }
        .fi { width:40px; height:40px; background:#dcfce7; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
        .ft h4 { color:#0f172a; font-size:15px; margin-bottom:4px; }
        .ft p { color:#64748b; font-size:13px; margin:0; }
        .cta-wrap { text-align:center; margin:28px 0; }
        .cta { display:inline-block; background:#009543; color:#fff!important; text-decoration:none; padding:14px 36px; border-radius:99px; font-weight:700; font-size:15px; letter-spacing:-.01em; }
        .footer { background:#0f172a; padding:28px 30px; text-align:center; }
        .footer p { color:rgba(255,255,255,.55); font-size:13px; margin-bottom:8px; }
        .social { margin:16px 0; }
        .social a { display:inline-block; width:34px; height:34px; background:rgba(255,255,255,.1); border-radius:50%; line-height:34px; color:#fff!important; text-decoration:none; font-size:13px; margin:0 4px; }
        .footer-links a { color:rgba(255,255,255,.4); text-decoration:none; font-size:11px; margin:0 8px; }
        @media(max-width:600px){ .body{padding:28px 20px;} .header{padding:28px 20px;} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <img src="{{ url('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
        <h1>Bienvenue sur BantuDelice !</h1>
    </div>

    <div class="body">
        <p class="greeting">Bonjour {{ $data['name'] }},</p>

        <p class="msg">Votre compte BantuDelice est maintenant actif. Commandez vos plats préférés auprès des meilleurs restaurants de Brazzaville et Pointe-Noire.</p>

        <div class="badge">
            <div class="badge-icon">🎉</div>
            <p>Votre compte est vérifié — vous pouvez commander dès maintenant, 7j/7.</p>
        </div>

        <div class="feature">
            <div class="fi">🍽️</div>
            <div class="ft">
                <h4>Commander des repas</h4>
                <p>Accédez aux meilleurs restaurants de votre quartier, en quelques secondes.</p>
            </div>
        </div>
        <div class="feature">
            <div class="fi">🛵</div>
            <div class="ft">
                <h4>Livraison en 30–45 min</h4>
                <p>Brazzaville et Pointe-Noire. Suivi en temps réel de votre commande.</p>
            </div>
        </div>
        <div class="feature">
            <div class="fi">📱</div>
            <div class="ft">
                <h4>Paiement Mobile Money</h4>
                <p>MTN MoMo et Airtel Money acceptés. Paiement 100 % sécurisé.</p>
            </div>
        </div>

        <div class="cta-wrap">
            <a href="{{ url('/restaurants') }}" class="cta">Explorer les restaurants</a>
        </div>

        <p class="msg">Une question ? Notre équipe est disponible via <a href="{{ url('/contact-us') }}" style="color:#009543">le formulaire de contact</a>.</p>
        <p class="msg">À très bientôt,<br><strong>L'équipe BantuDelice</strong></p>
    </div>

    <div class="footer">
        <p>Suivez-nous</p>
        <div class="social">
            <a href="https://www.facebook.com/BantuDelice" title="Facebook">f</a>
            <a href="https://www.instagram.com/bantudelice.cg/" title="Instagram">in</a>
            <a href="https://www.tiktok.com/@bantudelice" title="TikTok">T</a>
        </div>
        <p>© {{ date('Y') }} BantuDelice. Tous droits réservés.<br>Brazzaville, République du Congo</p>
        <div class="footer-links">
            <a href="{{ url('/terms-and-conditions') }}">Conditions générales</a>
            <a href="{{ url('/return-policy') }}">Remboursement</a>
            <a href="{{ url('/contact-us') }}">Contact</a>
        </div>
    </div>
</div>
</body>
</html>

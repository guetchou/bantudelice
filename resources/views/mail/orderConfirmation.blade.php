<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée — BantuDelice</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; line-height:1.6; color:#333; background:#f5f5f5; }
        .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; }
        .header { background:linear-gradient(135deg,#009543 0%,#007a36 100%); padding:32px 30px; text-align:center; }
        .header img { max-height:48px; width:auto; }
        .header h1 { color:#fff; font-size:20px; margin-top:14px; font-weight:700; }
        .status-band { background:#f0fdf4; border-bottom:2px solid #bbf7d0; padding:20px 32px; text-align:center; }
        .status-band .icon { font-size:36px; }
        .status-band h2 { color:#166534; font-size:18px; font-weight:700; margin-top:8px; }
        .status-band p { color:#166534; font-size:13px; margin-top:4px; }
        .body { padding:32px; }
        .order-meta { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .meta-block { flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; }
        .meta-label { font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; font-weight:600; }
        .meta-value { font-size:16px; font-weight:700; color:#0f172a; margin-top:4px; }
        .section-title { font-size:14px; font-weight:700; color:#0f172a; margin:20px 0 10px; text-transform:uppercase; letter-spacing:.04em; }
        .item-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f1f5f9; }
        .item-row:last-child { border-bottom:none; }
        .item-name { font-size:14px; color:#1e293b; font-weight:500; }
        .item-qty { font-size:12px; color:#94a3b8; }
        .item-price { font-size:14px; font-weight:700; color:#0f172a; }
        .total-row { display:flex; justify-content:space-between; padding:8px 0; font-size:14px; color:#475569; }
        .total-row.grand { border-top:2px solid #e2e8f0; margin-top:8px; padding-top:14px; }
        .total-row.grand span:last-child { font-size:18px; font-weight:900; color:#009543; }
        .total-row.grand span:first-child { font-weight:700; color:#0f172a; }
        .info-box { background:#f8fafc; border-radius:10px; padding:16px 18px; margin:20px 0; }
        .info-box p { font-size:13px; color:#475569; margin-bottom:6px; }
        .info-box p:last-child { margin-bottom:0; }
        .info-box strong { color:#0f172a; }
        .cta-wrap { text-align:center; margin:28px 0; }
        .cta { display:inline-block; background:#009543; color:#fff!important; text-decoration:none; padding:14px 32px; border-radius:99px; font-weight:700; font-size:15px; }
        .msg { font-size:14px; color:#64748b; line-height:1.75; margin-bottom:14px; }
        .footer { background:#0f172a; padding:28px 30px; text-align:center; }
        .footer p { color:rgba(255,255,255,.5); font-size:12px; margin-bottom:8px; }
        .footer-links a { color:rgba(255,255,255,.35); text-decoration:none; font-size:11px; margin:0 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <img src="{{ url('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
        <h1>Commande confirmée</h1>
    </div>

    <div class="status-band">
        <div class="icon">✅</div>
        <h2>Votre commande est en cours de préparation</h2>
        <p>Nous avons bien reçu votre commande et le restaurant prépare vos plats.</p>
    </div>

    <div class="body">
        <div class="order-meta">
            <div class="meta-block">
                <div class="meta-label">Numéro de commande</div>
                <div class="meta-value">#{{ $order->order_no }}</div>
            </div>
            <div class="meta-block">
                <div class="meta-label">Date</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($order->ordered_time ?? $order->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="meta-block">
                <div class="meta-label">Mode</div>
                <div class="meta-value">{{ ($order->fulfillment_mode ?? 'delivery') === 'pickup' ? 'Retrait' : 'Livraison' }}</div>
            </div>
        </div>

        @if($order->cartDetails && $order->cartDetails->count())
        <div class="section-title">Articles commandés</div>
        @foreach($order->cartDetails as $item)
        <div class="item-row">
            <div>
                <div class="item-name">{{ $item->name ?? $item->product->name ?? 'Article' }}</div>
                <div class="item-qty">Qté : {{ $item->qty ?? 1 }}</div>
            </div>
            <div class="item-price">{{ number_format((float)($item->price ?? 0), 0, ',', ' ') }} FCFA</div>
        </div>
        @endforeach
        @endif

        <div class="section-title">Récapitulatif</div>
        <div class="total-row"><span>Sous-total</span><span>{{ number_format((float)($order->total_amount ?? 0), 0, ',', ' ') }} FCFA</span></div>
        @if(($order->delivery_charges ?? 0) > 0)
        <div class="total-row"><span>Frais de livraison</span><span>{{ number_format((float)$order->delivery_charges, 0, ',', ' ') }} FCFA</span></div>
        @endif
        @if(($order->tax ?? 0) > 0)
        <div class="total-row"><span>Taxes</span><span>{{ number_format((float)$order->tax, 0, ',', ' ') }} FCFA</span></div>
        @endif
        <div class="total-row grand"><span>Total payé</span><span>{{ number_format((float)($order->grand_total ?? $order->total_amount ?? 0), 0, ',', ' ') }} FCFA</span></div>

        @if($order->delivery_address)
        <div class="info-box">
            <p><strong>Adresse de livraison :</strong> {{ $order->delivery_address }}</p>
            @if($order->payment_method)
            <p><strong>Mode de paiement :</strong> {{ $order->payment_method === 'mobile_money' ? 'Mobile Money (MTN / Airtel)' : ucfirst($order->payment_method) }}</p>
            @endif
        </div>
        @endif

        <div class="cta-wrap">
            <a href="{{ route('track.order', ['orderNo' => $order->order_no]) }}" class="cta">Suivre ma commande</a>
        </div>

        <p class="msg">Délai estimé : <strong>30 à 45 minutes</strong>. Vous recevrez une notification à chaque étape.</p>
        <p class="msg">Un problème ? Contactez-nous via <a href="{{ url('/contact-us') }}" style="color:#009543">notre formulaire</a> ou <a href="https://wa.me/242064000000" style="color:#009543">WhatsApp</a>.</p>
        <p class="msg">Merci de votre confiance,<br><strong>L'équipe BantuDelice</strong></p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} BantuDelice. Tous droits réservés. — Brazzaville, République du Congo</p>
        <div class="footer-links">
            <a href="{{ url('/terms-and-conditions') }}">Conditions générales</a>
            <a href="{{ url('/contact-us') }}">Contact</a>
        </div>
    </div>
</div>
</body>
</html>

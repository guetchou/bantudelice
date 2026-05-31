@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
    $isPickup = isset($order) && method_exists($order, 'isPickup')
        ? $order->isPickup()
        : ((isset($order) && ($order->fulfillment_mode ?? 'delivery') === 'pickup'));

    // Statut réel de la commande → détermine l'affichage
    $bizStatus = isset($order) ? ($order->business_status ?? 'pending_restaurant_acceptance') : 'pending_restaurant_acceptance';

    // Mapping statut → affichage page de confirmation
    $statusConfig = [
        'pending_restaurant_acceptance' => [
            'badge'      => 'En attente du restaurant',
            'badge_class'=> 'badge--pending',
            'title'      => 'Commande reçue !',
            'copy'       => 'Votre commande a bien été envoyée. Elle est <strong>en attente de confirmation du restaurant</strong>.',
            'step1_state'=> 'is-active is-blinking',
            'step1_label'=> 'En attente',
            'step2_state'=> '',
        ],
        'in_kitchen' => [
            'badge'      => 'En préparation',
            'badge_class'=> 'badge--kitchen',
            'title'      => 'Le restaurant prépare votre commande',
            'copy'       => 'Votre commande a été <strong>acceptée par le restaurant</strong> et est en cours de préparation.',
            'step1_state'=> 'is-done',
            'step1_label'=> 'Confirmée',
            'step2_state'=> 'is-active is-blinking',
        ],
        'driver_assigned' => [
            'badge'      => 'Livreur en route',
            'badge_class'=> 'badge--onway',
            'title'      => 'Livreur assigné !',
            'copy'       => 'Votre commande est prête et un <strong>livreur a pris en charge</strong> votre livraison.',
            'step1_state'=> 'is-done',
            'step1_label'=> 'Confirmée',
            'step2_state'=> 'is-done',
        ],
    ];

    $cfg = $statusConfig[$bizStatus] ?? $statusConfig['pending_restaurant_acceptance'];
@endphp
@section('title', 'Commande envoyée | ' . $foodBrandName)
@section('description', 'Votre commande a été envoyée avec succès. Suivez-la en temps réel.')
@section('body_class', 'bd-thanks-page')

@section('content')
<section class="thanks-hero">
    <div class="container thanks-hero-inner">
        <div class="thanks-hero-badge {{ $cfg['badge_class'] }}">{{ $cfg['badge'] }}</div>
        <h1 class="thanks-hero-title">{{ $cfg['title'] }}</h1>
        <p class="thanks-hero-copy">{!! $cfg['copy'] !!}</p>
    </div>
</section>

<section class="section thanks-section">
    <div class="container">
        <div class="thanks-shell">
            <article class="thanks-card">
                <div class="thanks-card-head">
                    <div class="thanks-meta-block">
                        <p class="thanks-meta-label">Numéro de commande</p>
                        <p class="thanks-meta-value">#{{ isset($order) && $order->order_no ? $order->order_no : session('order_no', 'BD' . rand(100000, 999999)) }}</p>
                    </div>
                    <div class="thanks-meta-block thanks-meta-block--right">
                        <p class="thanks-meta-label">Date</p>
                        <p class="thanks-meta-date">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                {{-- Bandeau d'attente visible uniquement en pending --}}
                @if($bizStatus === 'pending_restaurant_acceptance')
                <div class="thanks-waiting-banner">
                    <span class="thanks-waiting-dot"></span>
                    <span class="thanks-waiting-text">En attente de confirmation du restaurant…</span>
                    @if(isset($order) && $order->order_no)
                        <a href="{{ route('track.order', $order->order_no) }}" class="thanks-waiting-link">Suivre en direct →</a>
                    @endif
                </div>
                @endif

                <div class="thanks-card-body">
                    <h3 class="thanks-section-title">Suivi de commande</h3>

                    <div class="thanks-timeline">
                        <div class="thanks-timeline-line">
                            <div class="thanks-timeline-line-fill {{ $cfg['step1_state'] === 'is-done' ? 'fill--step2' : '' }}"></div>
                        </div>

                        <div class="thanks-step {{ $cfg['step1_state'] }}">
                            <div class="thanks-step-dot">1</div>
                            <p class="thanks-step-label">{{ $cfg['step1_label'] }}</p>
                        </div>

                        <div class="thanks-step {{ $cfg['step2_state'] }}">
                            <div class="thanks-step-dot">2</div>
                            <p class="thanks-step-label">En préparation</p>
                        </div>

                        <div class="thanks-step">
                            <div class="thanks-step-dot">3</div>
                            <p class="thanks-step-label">{{ $isPickup ? 'Prête au retrait' : 'En livraison' }}</p>
                        </div>

                        <div class="thanks-step">
                            <div class="thanks-step-dot">4</div>
                            <p class="thanks-step-label">{{ $isPickup ? 'Retirée' : 'Livrée' }}</p>
                        </div>
                    </div>
                </div>

                <div class="thanks-estimate">
                    <p class="thanks-estimate-label">
                        @if($bizStatus === 'pending_restaurant_acceptance')
                            Le restaurant confirmera votre commande sous peu
                        @else
                            {{ $isPickup ? 'Temps de préparation estimé' : 'Temps de livraison estimé' }}
                        @endif
                    </p>
                    <p class="thanks-estimate-value">
                        @if($bizStatus === 'pending_restaurant_acceptance')
                            <span class="thanks-estimate-pending">En attente…</span>
                        @else
                            30 - 45 min
                        @endif
                    </p>
                </div>

                <div class="thanks-actions">
                    @if(isset($order) && $order->order_no)
                        <a href="{{ route('track.order', $order->order_no) }}" class="thanks-btn-primary">
                            <i class="fa fa-location-arrow me-1"></i> Suivre ma commande
                        </a>
                        <a href="{{ route('order.receipt', $order->order_no) }}" class="thanks-btn-secondary">Voir le reçu</a>
                    @else
                        <a href="{{ route('user.profile') }}" class="thanks-btn-primary">Mes commandes</a>
                    @endif
                    <a href="{{ route('home') }}" class="thanks-btn-secondary">Retour à l'accueil</a>
                </div>
            </article>

            <article class="thanks-info-card">
                <h4 class="thanks-info-title">Suivi actif</h4>
                <p class="thanks-info-copy">
                    Vous recevrez des SMS et notifications à chaque étape de votre commande.
                </p>
            </article>

            <div class="thanks-support">
                <p class="thanks-support-copy">Un problème avec votre commande ?</p>
                <div class="thanks-support-links">
                    <a href="{{ route('help') }}" class="thanks-support-link">Centre d'aide</a>
                    <a href="https://wa.me/242064000000" class="thanks-support-link thanks-support-link--whatsapp">WhatsApp</a>
                    <a href="{{ route('contact.us') }}" class="thanks-support-link">Contact</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bip de confirmation commande — déclenché au premier clic/touch de l'utilisateur
    var _played = false;
    function _playConfirm() {
        if (_played) return;
        _played = true;
        if (window.BdAudio) window.BdAudio.play('confirm');
    }
    document.addEventListener('click',     _playConfirm, { once: true });
    document.addEventListener('touchstart', _playConfirm, { once: true, passive: true });
    document.addEventListener('keydown',   _playConfirm, { once: true });
    setTimeout(function () {
        if (!_played && window.BdAudio) {
            window.BdAudio.unlock();
            window.BdAudio.play('confirm');
            _played = true;
        }
    }, 800);

    // Auto-refresh léger si on est en pending : on vérifie toutes les 15s si le restaurant a confirmé
    // et on redirige vers la page de suivi dès que le statut change
    @if($bizStatus === 'pending_restaurant_acceptance' && isset($order) && $order->order_no)
    (function() {
        var orderNo  = @json($order->order_no);
        var trackUrl = @json(route('track.order', $order->order_no));
        var apiUrl   = '/order/' + orderNo + '/status';
        var interval = setInterval(function() {
            fetch(apiUrl, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var biz = data.business_status || data.biz_status || '';
                    if (biz && biz !== 'pending_restaurant_acceptance') {
                        clearInterval(interval);
                        window.location.href = trackUrl;
                    }
                })
                .catch(function() {}); // silencieux si API non dispo
        }, 15000);
    })();
    @endif
});
</script>
@endsection

@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
    $isPickup = isset($order) && method_exists($order, 'isPickup')
        ? $order->isPickup()
        : ((isset($order) && ($order->fulfillment_mode ?? 'delivery') === 'pickup'));
@endphp
@section('title', 'Commande Confirmée | ' . $foodBrandName)
@section('description', 'Votre commande a été confirmée avec succès. Merci de votre confiance !')
@section('body_class', 'bd-thanks-page')

@section('content')
<section class="thanks-hero">
    <div class="container thanks-hero-inner">
        <div class="thanks-hero-badge">Commande validée</div>
        <h1 class="thanks-hero-title">Merci pour votre commande</h1>
        <p class="thanks-hero-copy">
            {{ $isPickup ? 'Votre commande retrait a été confirmée et passe en préparation.' : 'Votre commande a été confirmée et est en cours de préparation.' }}
        </p>
    </div>
</section>

<section class="section thanks-section">
    <div class="container">
        <div class="thanks-shell">
            <article class="thanks-card">
                <div class="thanks-card-head">
                    <div class="thanks-meta-block">
                        <p class="thanks-meta-label">Numéro de commande</p>
                        <p class="thanks-meta-value">#{{ session('order_no', 'BD' . rand(100000, 999999)) }}</p>
                    </div>
                    <div class="thanks-meta-block thanks-meta-block--right">
                        <p class="thanks-meta-label">Date</p>
                        <p class="thanks-meta-date">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div class="thanks-card-body">
                    <h3 class="thanks-section-title">Suivi de commande</h3>

                    <div class="thanks-timeline">
                        <div class="thanks-timeline-line">
                            <div class="thanks-timeline-line-fill"></div>
                        </div>

                        <div class="thanks-step is-done">
                            <div class="thanks-step-dot">1</div>
                            <p class="thanks-step-label">Confirmée</p>
                        </div>

                        <div class="thanks-step is-active">
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
                    <p class="thanks-estimate-label">{{ $isPickup ? 'Temps de préparation estimé' : 'Temps de livraison estimé' }}</p>
                    <p class="thanks-estimate-value">30 - 45 min</p>
                </div>

                <div class="thanks-actions">
                    @if(isset($order) && $order->order_no)
                        <a href="{{ route('track.order', $order->order_no) }}" class="thanks-btn-primary">Suivre ma commande</a>
                        <a href="{{ route('order.receipt', $order->order_no) }}" class="thanks-btn-secondary">Voir le reçu</a>
                    @else
                        <a href="{{ route('user.profile') }}" class="thanks-btn-primary">Suivre ma commande</a>
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
    // (les navigateurs bloquent l'audio sans interaction préalable)
    var _played = false;
    function _playConfirm() {
        if (_played) return;
        _played = true;
        if (window.BdAudio) window.BdAudio.play('confirm');
    }
    document.addEventListener('click',     _playConfirm, { once: true });
    document.addEventListener('touchstart', _playConfirm, { once: true, passive: true });
    document.addEventListener('keydown',   _playConfirm, { once: true });
    // Tentative auto après 800 ms (marche si la page est déjà autorisée)
    setTimeout(function () {
        if (!_played && window.BdAudio) {
            window.BdAudio.unlock();
            window.BdAudio.play('confirm');
            _played = true;
        }
    }, 800);
});
</script>
@endsection

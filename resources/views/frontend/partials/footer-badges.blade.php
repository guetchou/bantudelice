{{--
    Badges footer : téléchargement application + moyens de paiement acceptés.
    Aucun asset officiel App Store / Google Play présent dans public/ — placeholders
    "Bientôt disponible" en HTML/CSS uniquement (pas d'imitation des badges officiels).
    MTN MoMo / Airtel Money / Espèces : icônes Font Awesome Free, pas de logo de marque.
--}}
<div class="bd-footer-extra">
    <div class="bd-footer-extra__col">
        <h4 class="bd-footer-extra__title">Téléchargez l'application</h4>
        <div class="bd-app-badges">
            <a href="#"
               class="bd-app-badge"
               aria-disabled="true"
               data-coming-soon="true"
               aria-label="Application bientôt disponible sur l'App Store">
                <i class="fab fa-apple" aria-hidden="true"></i>
                <span class="bd-app-badge__copy">
                    <small>Bientôt disponible sur</small>
                    <strong>App Store</strong>
                </span>
            </a>
            <a href="#"
               class="bd-app-badge"
               aria-disabled="true"
               data-coming-soon="true"
               aria-label="Application bientôt disponible sur Google Play">
                <i class="fab fa-google-play" aria-hidden="true"></i>
                <span class="bd-app-badge__copy">
                    <small>Bientôt disponible sur</small>
                    <strong>Google Play</strong>
                </span>
            </a>
        </div>
    </div>

    <div class="bd-footer-extra__col">
        <h4 class="bd-footer-extra__title">Moyens de paiement acceptés</h4>
        <div class="bd-payment-badges">
            <span class="bd-payment-badge bd-payment-badge--mtn" aria-label="Paiement par MTN Mobile Money">
                <i class="fas fa-mobile-screen-button" aria-hidden="true"></i> MTN MoMo
            </span>
            <span class="bd-payment-badge bd-payment-badge--airtel" aria-label="Paiement par Airtel Money">
                <i class="fas fa-mobile-screen-button" aria-hidden="true"></i> Airtel Money
            </span>
            <span class="bd-payment-badge bd-payment-badge--cash" aria-label="Paiement en espèces à la livraison">
                <i class="fas fa-money-bill-wave" aria-hidden="true"></i> Espèces à la livraison
            </span>
        </div>
        <p class="bd-payment-note">Paiement sécurisé selon les moyens disponibles au Congo.</p>
    </div>
</div>

@once
<script>
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-coming-soon="true"]');
        if (trigger) {
            event.preventDefault();
        }
    });
</script>
@endonce

/**
 * Service API pour le checkout
 */
class CheckoutAPI {
    constructor() {
        this.config = window.checkoutApiConfig || {};
        this.baseURL = this.config.baseURL || '/api';
        this.createCheckoutURL = this.config.createCheckoutUrl || `${this.baseURL}/checkout`;
        this.paymentStatusBaseURL = this.config.paymentStatusBaseUrl || `${this.baseURL}/payments`;
        this.loginURL = this.config.loginUrl || `/user/login?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
        this.csrfToken = this.getCsrfToken();
    }

    getCsrfToken() {
        // Récupérer le token CSRF depuis les meta tags
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        return metaToken ? metaToken.content : null;
    }

    isLoginRedirectResponse(response) {
        if (!response) return false;

        if (response.redirected && response.url) {
            return /\/(?:user\/login|login)(?:\?|$)/.test(response.url);
        }

        const contentType = response.headers.get('content-type') || '';
        return contentType.includes('text/html');
    }

    redirectToLogin() {
        window.location.href = this.loginURL;
    }

    async readJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }

    async createCheckout(checkoutData) {
        try {
            const response = await fetch(this.createCheckoutURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Inclure les cookies de session
                body: JSON.stringify(checkoutData)
            });

            if (response.status === 401 || response.status === 419 || this.isLoginRedirectResponse(response)) {
                this.redirectToLogin();
                throw new Error('Authentification requise');
            }

            const data = await this.readJson(response);
            
            if (!response.ok) {
                const error = new Error(data?.message || 'Erreur lors du checkout');
                error.payload = data;
                error.statusCode = response.status;
                throw error;
            }

            if (data?.status === false) {
                const error = new Error(data.message || 'Le checkout a été refusé');
                error.payload = data;
                error.statusCode = response.status;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('Checkout error:', error);
            throw error;
        }
    }

    async getPaymentStatus(paymentId) {
        try {
            const requestUrl = new URL(`${this.paymentStatusBaseURL}/${paymentId}`, window.location.origin);
            requestUrl.searchParams.set('_ts', Date.now().toString());

            const response = await fetch(requestUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (response.status === 401 || response.status === 419 || this.isLoginRedirectResponse(response)) {
                this.redirectToLogin();
                throw new Error('Authentification requise');
            }

            const data = await this.readJson(response);
            
            if (!response.ok) {
                const error = new Error(data?.message || 'Erreur lors de la récupération du statut');
                error.payload = data;
                error.statusCode = response.status;
                throw error;
            }

            if (data?.status === false) {
                const error = new Error(data.message || 'Le statut de paiement a été refusé');
                error.payload = data;
                error.statusCode = response.status;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('Payment status error:', error);
            throw error;
        }
    }

    async pollPaymentStatus(paymentId, onStatusChange, maxAttempts = 120, interval = 5000) {
        let attempts = 0;
        
        const poll = async () => {
            try {
                const data = await this.getPaymentStatus(paymentId);
                const status = String(data?.payment?.status || '').trim().toUpperCase();
                
                if (onStatusChange) {
                    onStatusChange(status, data);
                }
                
                // Si le paiement est terminé, arrêter le polling
                if ([
                    'SUCCESS',
                    'SUCCESSFUL',
                    'PAID',
                    'COMPLETED',
                    'CAPTURED',
                    'APPROVED',
                    'FAILED',
                    'CANCELLED',
                    'EXPIRED',
                    'REFUNDED',
                ].includes(status)) {
                    return;
                }
                
                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(poll, interval);
                } else {
                    console.warn('Polling timeout atteint');
                    if (onStatusChange) {
                        onStatusChange('TIMEOUT', data);
                    }
                }
            } catch (error) {
                console.error('Polling error:', error);
                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(poll, interval);
                } else if (onStatusChange) {
                    onStatusChange('TIMEOUT', null);
                }
            }
        };
        
        poll();
    }

}

/**
 * Gestionnaire de checkout
 */
class CheckoutManager {
    constructor() {
        this.api = new CheckoutAPI();
        this.isProcessing = false;
        this.paymentScreenAutoCloseTimer = null;
        this.currentPaymentId = null;
        this.currentPaymentPhone = '';
        this.currentPaymentAmount = '';
        this.boundRefreshPaymentStatus = this.refreshPaymentStatusOnResume.bind(this);
    }

    async processCheckout(formData) {
        if (this.isProcessing) {
            return;
        }

        this.isProcessing = true;
        this.showLoading();

        try {
            const checkoutData = {
                payment_method: formData.payment_method,
                fulfillment_mode: formData.fulfillment_mode || 'delivery',
                delivery_address: formData.delivery_address,
                delivery_area: formData.delivery_area || null,
                delivery_city: formData.delivery_city || null,
                delivery_department: formData.delivery_department || null,
                delivery_address_confirmed: Boolean(
                    formData.delivery_address_confirmed === true
                    || formData.delivery_address_confirmed === 1
                    || formData.delivery_address_confirmed === '1'
                    || formData.delivery_address_confirmed === 'true'
                ),
                d_lat: formData.d_lat,
                d_lng: formData.d_lng,
                driver_tip: parseFloat(formData.driver_tip) || 0,
                voucher_code: formData.voucher_code || null,
                scheduled_date: formData.scheduled_date || null,
                address_id: formData.address_id || null,
                pickup_note: formData.pickup_note || null,
                phone: formData.phone || null
            };

            const result = await this.api.createCheckout(checkoutData);

            // Gérer la réponse selon le format (peut être result.data ou result directement)
            const response = result.data || result;
            const hasOrder = Boolean(response?.order_no || response?.order?.order_no || response?.order?.id);

            if (response.requires_external_payment) {
                // Paiement en ligne immédiat (chemin legacy, conservé pour compat. — le flux
                // normal ne déclenche plus jamais ce cas : le paiement en ligne est désormais
                // demandé après acceptation restaurant, voir handleSuccess()).
                this.handleExternalPayment(response);
            } else if (hasOrder) {
                // Commande créée, en attente d'acceptation restaurant (cash ET en ligne désormais)
                this.handleSuccess(response);
            } else {
                throw new Error('Réponse checkout invalide: commande manquante.');
            }
        } catch (error) {
            const errorMessage = error.message || (error.response?.data?.message) || 'Erreur lors du checkout';
            this.handleError(errorMessage, error.payload || error.response?.data || null);
        } finally {
            this.isProcessing = false;
            this.hideLoading();
        }
    }

    handleExternalPayment(result) {
        const payment = result.payment || result.data?.payment;
        const paymentPayload = result.payment_payload || result.data?.payment_payload;

        if (!payment || !payment.id) {
            console.error('Payment data missing', result);
            this.handleError('Erreur: données de paiement manquantes');
            return;
        }

        const redirectUrl = paymentPayload?.redirect_url || paymentPayload?.meta?.redirect_url || null;
        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        // Afficher l'écran de statut de paiement
        this.showPaymentStatusScreen(payment, paymentPayload);

        // Démarrer le polling pour vérifier le statut
        this.api.pollPaymentStatus(
            payment.id,
            (status, data) => {
                this.updatePaymentStatus(status, data);
            }
        );
    }

    handleSuccess(result) {
        // Rediriger vers la page de confirmation
        const orderNo = result.order_no || result.order?.order_no || result.order_no;
        if (orderNo) {
            window.location.href = `/track-order/${orderNo}?success=1`;
        } else {
            // Fallback vers la page de commandes
            window.location.href = `/user/pending-orders?success=1`;
        }
    }

    handleError(message, payload = null) {
        const normalizedMessage = String(message || '').toLowerCase();
        if (
            normalizedMessage.includes('auth') ||
            normalizedMessage.includes('connect') ||
            normalizedMessage.includes('session') ||
            normalizedMessage.includes('csrf') ||
            normalizedMessage.includes('419') ||
            normalizedMessage.includes('login')
        ) {
            this.api.redirectToLogin();
            return;
        }

        if (typeof window.handleCheckoutClientError === 'function' && window.handleCheckoutClientError(message, payload) === true) {
            return;
        }

        this.showError(message);
    }

    showPaymentStatusScreen(payment, paymentPayload) {
        // Créer ou afficher l'écran de statut
        const statusScreen = document.getElementById('paymentStatusScreen');
        if (!statusScreen) {
            this.createPaymentStatusScreen();
        }

        this.currentPaymentId = payment.id;
        this.currentPaymentPhone = paymentPayload?.meta?.phone || paymentPayload?.phone || '';
        this.currentPaymentAmount = paymentPayload?.amount
            || paymentPayload?.meta?.amount
            || payment?.amount
            || 'N/A';

        document.getElementById('paymentPhoneValue').textContent = this.formatPhone(this.currentPaymentPhone);
        document.getElementById('paymentAmountValue').textContent = this.formatAmount(this.currentPaymentAmount);
        this.updatePaymentStatus(payment?.status || 'INITIATED', { payment });
        document.getElementById('paymentStatusScreen').style.display = 'block';
        this.bindResumeStatusChecks();
    }

    createPaymentStatusScreen() {
        const screen = document.createElement('div');
        screen.id = 'paymentStatusScreen';
        screen.className = 'payment-status-overlay';

        screen.innerHTML = `
            <div id="paymentStatusCard" class="payment-status-card">
                <div class="payment-status-waves" aria-hidden="true">
                    <span class="payment-status-wave"></span>
                    <span class="payment-status-wave"></span>
                    <span class="payment-status-wave"></span>
                </div>

                <div class="payment-status-body">
                    <div id="paymentStatusBadge" class="payment-status-badge">
                        <span class="payment-status-badge-dot"></span>
                        Paiement Mobile Money
                    </div>

                    <h2 class="payment-status-title">Transaction en cours</h2>
                    <p id="paymentStatusSubtitle" class="payment-status-subtitle">
                        Veuillez confirmer l’opération sur votre téléphone pour finaliser le paiement.
                    </p>

                    <div class="payment-status-box">
                        <span class="payment-status-label">Numéro</span>
                        <div id="paymentPhoneValue" class="payment-status-value">Numéro non renseigné</div>
                    </div>

                    <div class="payment-status-box">
                        <span class="payment-status-label">Montant</span>
                        <div id="paymentAmountValue" class="payment-status-value">N/A</div>
                    </div>

                    <div class="payment-status-box payment-status-status-box">
                        <div>
                            <span class="payment-status-label">Statut</span>
                            <div id="paymentStatusText" class="payment-status-value">En attente de confirmation</div>
                        </div>
                        <div class="payment-status-loader" aria-hidden="true"></div>
                    </div>

                    <div class="payment-status-actions">
                        <button id="verifyPaymentScreen" type="button" class="payment-status-button">Vérifier le paiement</button>
                        <a href="#" id="closePaymentScreen" class="payment-status-close">Fermer cette fenêtre</a>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(screen);
        
        document.getElementById('verifyPaymentScreen').addEventListener('click', () => {
            this.checkPaymentNow();
        });

        document.getElementById('closePaymentScreen').addEventListener('click', (event) => {
            event.preventDefault();
            this.hidePaymentStatusScreen();
        });
    }

    hidePaymentStatusScreen() {
        const screen = document.getElementById('paymentStatusScreen');
        if (screen) {
            screen.style.display = 'none';
        }
        this.unbindResumeStatusChecks();

        if (this.paymentScreenAutoCloseTimer) {
            clearTimeout(this.paymentScreenAutoCloseTimer);
            this.paymentScreenAutoCloseTimer = null;
        }
    }

    schedulePaymentScreenAutoClose(delay = 4000) {
        if (this.paymentScreenAutoCloseTimer) {
            clearTimeout(this.paymentScreenAutoCloseTimer);
        }

        this.paymentScreenAutoCloseTimer = setTimeout(() => {
            this.hidePaymentStatusScreen();
        }, delay);
    }

    formatPhone(phone) {
        const digits = String(phone || '').replace(/\D+/g, '');

        if (!digits) {
            return 'Numéro non renseigné';
        }

        if (digits.startsWith('242') && digits.length >= 11) {
            return `+242 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8, 10)} ${digits.slice(10, 12)}`.trim();
        }

        if (digits.length >= 9) {
            return `${digits.slice(0, 2)} ${digits.slice(2, 5)} ${digits.slice(5, 7)} ${digits.slice(7, 9)}`.trim();
        }

        return phone;
    }

    formatAmount(amount) {
        const numericAmount = Number(amount);

        if (Number.isFinite(numericAmount)) {
            return `${numericAmount.toLocaleString('fr-FR')} FCFA`;
        }

        return `${amount || 'N/A'} FCFA`;
    }

    async checkPaymentNow() {
        if (!this.currentPaymentId) {
            return;
        }

        const verifyButton = document.getElementById('verifyPaymentScreen');
        if (verifyButton) {
            verifyButton.disabled = true;
            verifyButton.textContent = 'Vérification...';
        }

        try {
            const data = await this.api.getPaymentStatus(this.currentPaymentId);
            this.updatePaymentStatus(data?.payment?.status || 'PENDING', data);
        } catch (error) {
            console.error('Manual payment verification error:', error);
        } finally {
            if (verifyButton) {
                verifyButton.disabled = false;
                verifyButton.textContent = 'Vérifier le paiement';
            }
        }
    }

    bindResumeStatusChecks() {
        this.unbindResumeStatusChecks();
        window.addEventListener('focus', this.boundRefreshPaymentStatus);
        window.addEventListener('pageshow', this.boundRefreshPaymentStatus);
        document.addEventListener('visibilitychange', this.boundRefreshPaymentStatus);
    }

    unbindResumeStatusChecks() {
        window.removeEventListener('focus', this.boundRefreshPaymentStatus);
        window.removeEventListener('pageshow', this.boundRefreshPaymentStatus);
        document.removeEventListener('visibilitychange', this.boundRefreshPaymentStatus);
    }

    refreshPaymentStatusOnResume() {
        const screen = document.getElementById('paymentStatusScreen');
        const isVisible = screen && screen.style.display !== 'none';

        if (!this.currentPaymentId || !isVisible) {
            return;
        }

        if (document.visibilityState && document.visibilityState !== 'visible') {
            return;
        }

        this.checkPaymentNow();
    }

    normalizePaymentStatus(status) {
        const normalizedStatus = String(status || '').trim().toUpperCase();

        switch (normalizedStatus) {
            case 'INITIATED':
                return 'INITIATED';
            case 'PENDING':
                return 'PENDING';
            case 'PROCESSING':
            case 'PROCESS':
            case 'IN_PROGRESS':
                return 'PROCESSING';
            case 'SUCCESS':
            case 'SUCCESSFUL':
            case 'PAID':
            case 'COMPLETED':
            case 'CAPTURED':
            case 'APPROVED':
                return 'SUCCESS';
            case 'FAILED':
            case 'ERROR':
                return 'FAILED';
            case 'CANCELLED':
                return 'CANCELLED';
            case 'EXPIRED':
                return 'EXPIRED';
            case 'TIMEOUT':
                return 'TIMEOUT';
            case 'REFUNDED':
                return 'REFUNDED';
            default:
                return normalizedStatus || 'PENDING';
        }
    }

    resolveDisplayStatus(status, data) {
        const normalizedStatus = this.normalizePaymentStatus(status);
        const reconciliationStatus = this.normalizePaymentStatus(data?.reconciliation?.status);
        const experienceStatus = this.normalizePaymentStatus(data?.payment_experience?.status);

        if (['SUCCESS', 'FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED', 'PROCESSING'].includes(reconciliationStatus)) {
            return reconciliationStatus;
        }

        if (['SUCCESS', 'FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED', 'PROCESSING'].includes(experienceStatus)) {
            return experienceStatus;
        }

        return normalizedStatus;
    }

    updatePaymentStatus(status, data) {
        const card = document.getElementById('paymentStatusCard');
        const title = document.querySelector('#paymentStatusCard .payment-status-title');
        const statusText = document.getElementById('paymentStatusText');
        const subtitle = document.getElementById('paymentStatusSubtitle');
        const badge = document.getElementById('paymentStatusBadge');
        const verifyButton = document.getElementById('verifyPaymentScreen');
        if (!card || !title || !statusText || !subtitle || !badge) return;

        const normalizedStatus = this.resolveDisplayStatus(status, data);
        const customerMessage = data?.payment_experience?.customer_message || '';
        const failureAction = data?.payment_experience?.failure_action || '';
        card.classList.remove('is-paid', 'is-failed', 'is-timeout', 'is-processing', 'is-initiated', 'is-expired');

        switch(normalizedStatus) {
            case 'INITIATED':
                card.classList.add('is-initiated');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Initialisation';
                title.textContent = 'Initialisation du paiement';
                subtitle.textContent = customerMessage || 'La transaction est créée. La demande Mobile Money est en cours d’envoi à votre téléphone.';
                statusText.textContent = 'Initialisation en cours';
                if (verifyButton) verifyButton.style.display = '';
                break;
            case 'PROCESSING':
                card.classList.add('is-processing');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Traitement opérateur';
                title.textContent = 'Traitement en cours';
                subtitle.textContent = customerMessage || 'Votre validation a été transmise. L’opérateur traite maintenant la transaction.';
                statusText.textContent = 'Traitement en cours';
                if (verifyButton) verifyButton.style.display = '';
                break;
            case 'SUCCESS':
                card.classList.add('is-paid');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement confirmé';
                title.textContent = 'Paiement réussi';
                subtitle.textContent = customerMessage || 'Le paiement a été validé. Redirection vers la confirmation de commande...';
                statusText.textContent = 'Paiement confirmé';
                if (verifyButton) verifyButton.style.display = 'none';
                setTimeout(() => {
                    const orderNo = data?.order?.order_no || data?.order_no || data?.payment?.order_no;
                    if (orderNo) {
                        window.location.href = `/track-order/${orderNo}?success=1`;
                    } else {
                        window.location.href = `/user/pending-orders?success=1`;
                    }
                }, 2000);
                break;
            case 'FAILED':
                card.classList.add('is-failed');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement échoué';
                title.textContent = 'Paiement échoué';
                subtitle.textContent = customerMessage || failureAction || 'Le paiement a été refusé par l’opérateur. Vous pouvez réessayer après confirmation sur votre téléphone.';
                statusText.textContent = 'Paiement échoué';
                if (verifyButton) verifyButton.style.display = '';
                if (this.paymentScreenAutoCloseTimer) {
                    clearTimeout(this.paymentScreenAutoCloseTimer);
                    this.paymentScreenAutoCloseTimer = null;
                }
                break;
            case 'CANCELLED':
                card.classList.add('is-failed');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement annulé';
                title.textContent = 'Paiement annulé';
                subtitle.textContent = customerMessage || 'Le paiement a été annulé. Vous pouvez relancer une nouvelle tentative.';
                statusText.textContent = 'Paiement annulé';
                if (verifyButton) verifyButton.style.display = '';
                if (this.paymentScreenAutoCloseTimer) {
                    clearTimeout(this.paymentScreenAutoCloseTimer);
                    this.paymentScreenAutoCloseTimer = null;
                }
                break;
            case 'PENDING':
                if (this.paymentScreenAutoCloseTimer) {
                    clearTimeout(this.paymentScreenAutoCloseTimer);
                    this.paymentScreenAutoCloseTimer = null;
                }
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement Mobile Money';
                title.textContent = 'Transaction en cours';
                subtitle.textContent = customerMessage || 'Veuillez confirmer l’opération sur votre téléphone pour finaliser le paiement.';
                statusText.textContent = 'En attente de confirmation';
                if (verifyButton) verifyButton.style.display = '';
                break;
            case 'EXPIRED':
                card.classList.add('is-expired');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement expiré';
                title.textContent = 'Paiement expiré';
                subtitle.textContent = customerMessage || 'Le délai de confirmation est dépassé. Relancez le paiement si vous souhaitez réessayer.';
                statusText.textContent = 'Paiement expiré';
                if (verifyButton) verifyButton.style.display = '';
                this.schedulePaymentScreenAutoClose(5000);
                break;
            case 'REFUNDED':
                card.classList.add('is-paid');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement remboursé';
                title.textContent = 'Paiement remboursé';
                subtitle.textContent = customerMessage || 'Le paiement a été remboursé. Vous pouvez fermer cette fenêtre.';
                statusText.textContent = 'Paiement remboursé';
                if (verifyButton) verifyButton.style.display = 'none';
                break;
            case 'TIMEOUT':
                card.classList.add('is-timeout');
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Vérification prolongée';
                title.textContent = 'Vérification prolongée';
                subtitle.textContent = customerMessage || 'Le paiement est toujours en attente chez l’opérateur. Rechargez la page pour revoir l’état réel.';
                statusText.textContent = 'Vérification prolongée';
                if (verifyButton) verifyButton.style.display = '';
                this.schedulePaymentScreenAutoClose(5000);
                break;
            default:
                badge.innerHTML = '<span class="payment-status-badge-dot"></span>Paiement Mobile Money';
                title.textContent = 'Transaction en cours';
                subtitle.textContent = 'La transaction est en cours de synchronisation avec l’opérateur.';
                statusText.textContent = normalizedStatus;
                if (verifyButton) verifyButton.style.display = '';
                break;
        }
    }

    showLoading() {
        const btn = document.getElementById('checkoutSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        }
    }

    hideLoading() {
        const btn = document.getElementById('checkoutSubmitBtn');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> <span id="btnText">Passer la commande</span>';
        }
    }

    showError(message) {
        // Afficher un message d'erreur
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            max-width: 400px;
        `;
        const errorWrap = document.createElement('div');
        errorWrap.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';
        const errorIcon = document.createElement('i');
        errorIcon.className = 'fas fa-exclamation-circle';
        const errorText = document.createElement('span');
        errorText.textContent = String(message ?? '');
        errorWrap.append(errorIcon, errorText);
        errorDiv.appendChild(errorWrap);

        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

// Initialiser le gestionnaire de checkout
const checkoutManager = new CheckoutManager();
window.checkoutManager = checkoutManager;

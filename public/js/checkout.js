/**
 * Service API pour le checkout
 */
class CheckoutAPI {
    constructor() {
        this.baseURL = '/api';
        this.csrfToken = this.getCsrfToken();
    }

    getCsrfToken() {
        // Récupérer le token CSRF depuis les meta tags
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        return metaToken ? metaToken.content : null;
    }

    async createCheckout(checkoutData) {
        try {
            const response = await fetch(`${this.baseURL}/checkout`, {
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

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors du checkout');
            }

            return data;
        } catch (error) {
            console.error('Checkout error:', error);
            throw error;
        }
    }

    async getPaymentStatus(paymentId) {
        try {
            const response = await fetch(`${this.baseURL}/payments/${paymentId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de la récupération du statut');
            }

            return data;
        } catch (error) {
            console.error('Payment status error:', error);
            throw error;
        }
    }

    async pollPaymentStatus(paymentId, onStatusChange, maxAttempts = 60, interval = 2000) {
        let attempts = 0;
        
        const poll = async () => {
            try {
                const data = await this.getPaymentStatus(paymentId);
                const status = data.payment.status;
                
                if (onStatusChange) {
                    onStatusChange(status, data);
                }
                
                // Si le paiement est terminé (PAID ou FAILED), arrêter le polling
                if (status === 'PAID' || status === 'FAILED' || status === 'CANCELLED') {
                    return;
                }
                
                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(poll, interval);
                } else {
                    console.warn('Polling timeout atteint');
                }
            } catch (error) {
                console.error('Polling error:', error);
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
                delivery_address: formData.delivery_address,
                d_lat: formData.d_lat,
                d_lng: formData.d_lng,
                driver_tip: parseFloat(formData.driver_tip) || 0,
                voucher_code: formData.voucher_code || null
            };

            const result = await this.api.createCheckout(checkoutData);

            // Gérer la réponse selon le format (peut être result.data ou result directement)
            const response = result.data || result;

            if (response.requires_external_payment) {
                // Paiement en ligne requis
                this.handleExternalPayment(response);
            } else {
                // Paiement cash - commande créée immédiatement
                this.handleSuccess(response);
            }
        } catch (error) {
            const errorMessage = error.message || (error.response?.data?.message) || 'Erreur lors du checkout';
            this.handleError(errorMessage);
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

    handleError(message) {
        this.showError(message);
    }

    showPaymentStatusScreen(payment, paymentPayload) {
        // Créer ou afficher l'écran de statut
        const statusScreen = document.getElementById('paymentStatusScreen');
        if (!statusScreen) {
            this.createPaymentStatusScreen();
        }

        // Afficher les instructions selon le provider
        const provider = payment.provider;
        const instructions = this.getPaymentInstructions(provider, paymentPayload);
        
        document.getElementById('paymentInstructions').innerHTML = instructions;
        document.getElementById('paymentStatusScreen').style.display = 'block';
    }

    createPaymentStatusScreen() {
        const screen = document.createElement('div');
        screen.id = 'paymentStatusScreen';
        screen.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        screen.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 1rem; max-width: 500px; width: 90%;">
                <h2 style="margin-bottom: 1rem;">Paiement en cours</h2>
                <div id="paymentInstructions"></div>
                <div id="paymentStatusIndicator" style="margin-top: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 0.5rem;">
                    <p style="margin: 0;">Vérification du paiement...</p>
                </div>
                <button id="closePaymentScreen" style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
                    Annuler
                </button>
            </div>
        `;
        
        document.body.appendChild(screen);
        
        document.getElementById('closePaymentScreen').addEventListener('click', () => {
            screen.style.display = 'none';
        });
    }

    getPaymentInstructions(provider, paymentPayload) {
        switch(provider) {
            case 'momo':
                return `
                    <p>Instructions pour Mobile Money :</p>
                    <ol>
                        <li>Composez le code USSD ou utilisez l'application Mobile Money</li>
                        <li>Entrez le montant : <strong>${paymentPayload?.amount || 'N/A'} FCFA</strong></li>
                        <li>Confirmez la transaction</li>
                    </ol>
                    <p>Nous vérifions automatiquement votre paiement...</p>
                `;
            case 'paypal':
                if (paymentPayload?.redirect_url) {
                    return `
                        <p>Redirection vers PayPal...</p>
                        <a href="${paymentPayload.redirect_url}" target="_blank" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #0070ba; color: white; text-decoration: none; border-radius: 0.5rem;">
                            Ouvrir PayPal
                        </a>
                    `;
                }
                return '<p>Paiement PayPal en cours...</p>';
            default:
                return '<p>Paiement en cours de traitement...</p>';
        }
    }

    updatePaymentStatus(status, data) {
        const indicator = document.getElementById('paymentStatusIndicator');
        if (!indicator) return;

        switch(status) {
            case 'PAID':
                indicator.innerHTML = `
                    <p style="color: #10b981; font-weight: 600;">✓ Paiement confirmé !</p>
                    <p>Redirection vers la confirmation de commande...</p>
                `;
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
                indicator.innerHTML = `
                    <p style="color: #ef4444; font-weight: 600;">✗ Paiement échoué</p>
                    <p>Veuillez réessayer ou choisir un autre mode de paiement.</p>
                `;
                break;
            case 'PENDING':
                indicator.innerHTML = `
                    <p>Vérification du paiement...</p>
                    <div style="margin-top: 0.5rem; width: 100%; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                        <div style="width: 50%; height: 100%; background: #3b82f6; animation: pulse 2s infinite;"></div>
                    </div>
                `;
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
        errorDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

// Initialiser le gestionnaire de checkout
const checkoutManager = new CheckoutManager();



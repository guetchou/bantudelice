<script>
(function () {
    const match = window.location.pathname.match(/\/transport\/booking\/([0-9a-fA-F-]{36})$/);
    if (!match) return;

    const bookingUuid = match[1];
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const cancelableStatuses = ['requested', 'assigned'];

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info');
            return;
        }

        alert(message);
    }

    async function readJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return {};
        }
    }

    async function refreshCancelAction() {
        try {
            const response = await fetch(`/transport/xhr/bookings/${bookingUuid}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const data = await readJson(response);

            if (!response.ok || !data || !data.status) {
                removeCancelAction();
                return;
            }

            if (!cancelableStatuses.includes(data.status)) {
                removeCancelAction();
                return;
            }

            renderCancelAction(data);
        } catch (error) {
            removeCancelAction();
        }
    }

    function removeCancelAction() {
        document.getElementById('kendeCancelBookingBox')?.remove();
    }

    function renderCancelAction(booking) {
        if (document.getElementById('kendeCancelBookingBox')) return;

        const target = document.querySelector('.trip-fare-card') || document.querySelector('.trip-side-card');
        if (!target) return;

        const box = document.createElement('div');
        box.id = 'kendeCancelBookingBox';
        box.style.marginTop = '14px';
        box.style.paddingTop = '14px';
        box.style.borderTop = '1px solid rgba(15,23,42,0.08)';

        const note = document.createElement('small');
        note.style.display = 'block';
        note.style.color = '#64748b';
        note.style.lineHeight = '1.5';
        note.textContent = booking.status === 'assigned'
            ? 'Vous pouvez encore annuler avant le démarrage de la course.'
            : 'Vous pouvez annuler tant qu’aucun chauffeur n’a démarré la course.';

        const button = document.createElement('button');
        button.id = 'kendeCancelBookingBtn';
        button.type = 'button';
        button.className = 'trip-pay-btn';
        button.style.background = '#dc2626';
        button.style.marginTop = '10px';
        button.innerHTML = '<i class="fas fa-ban"></i> Annuler la réservation';
        button.addEventListener('click', () => cancelBooking(button));

        box.appendChild(note);
        box.appendChild(button);
        target.appendChild(box);
    }

    async function cancelBooking(button) {
        if (!confirm('Confirmer l’annulation de cette réservation Kende ?')) return;

        const originalLabel = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Annulation...';

        try {
            const response = await fetch(`/transport/xhr/bookings/${bookingUuid}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ cancel_reason: 'Annulation client depuis la page de suivi' }),
            });
            const data = await readJson(response);

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Annulation impossible pour cette réservation.');
            }

            notify(data.message || 'Réservation annulée.', 'success');
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            button.disabled = false;
            button.innerHTML = originalLabel;
            notify(error.message || 'Erreur pendant l’annulation.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', refreshCancelAction);
    window.setInterval(refreshCancelAction, 15000);
})();
</script>

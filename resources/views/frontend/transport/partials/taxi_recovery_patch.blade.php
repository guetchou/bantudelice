<script>
(function () {
    if (!document.getElementById('taxiBookingForm') || typeof TAXI_CONFIG === 'undefined') return;

    const DRAFT_KEY = 'kende.taxi.sessionDraft';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, { once: true });
        else fn();
    }

    function field(id) { return document.getElementById(id); }
    function value(id) { return field(id)?.value || ''; }
    function setValue(id, v) { if (field(id)) field(id).value = v || ''; }
    function json(value, fallback = null) { try { return JSON.parse(value); } catch (e) { return fallback; } }

    function saveDraft() {
        const draft = {
            t: Date.now(),
            pickup: value('pickupInput'),
            dropoff: value('dropoffInput'),
            pickupNote: value('pickupNote'),
            dropoffNote: value('dropoffNote'),
            passengerCount: value('passengerCount') || '1',
            scheduledAt: value('scheduledAtInput'),
            rideOption: value('selectedRideOption') || 'eco',
            paymentMethod: document.querySelector('input[name="payment_method"]:checked')?.value || 'cash',
            timing: document.querySelector('input[name="ride_timing"]:checked')?.value || 'now',
        };
        sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    }

    function restoreDraft() {
        const draft = json(sessionStorage.getItem(DRAFT_KEY));
        if (!draft || Date.now() - Number(draft.t || 0) > 30 * 60 * 1000) {
            sessionStorage.removeItem(DRAFT_KEY);
            return;
        }

        setValue('pickupInput', draft.pickup);
        setValue('dropoffInput', draft.dropoff);
        setValue('pickupNote', draft.pickupNote);
        setValue('dropoffNote', draft.dropoffNote);
        setValue('passengerCount', draft.passengerCount || '1');
        setValue('scheduledAtInput', draft.scheduledAt);
        setValue('selectedRideOption', draft.rideOption || 'eco');

        const payment = document.querySelector(`input[name="payment_method"][value="${draft.paymentMethod || 'cash'}"]`);
        if (payment) { payment.checked = true; payment.dispatchEvent(new Event('change', { bubbles: true })); }

        const timing = document.querySelector(`input[name="ride_timing"][value="${draft.timing || 'now'}"]`);
        if (timing) { timing.checked = true; timing.dispatchEvent(new Event('change', { bubbles: true })); }

        document.querySelectorAll('[data-ride-option]').forEach(function (button) {
            const active = button.dataset.optionKey === (draft.rideOption || 'eco');
            button.classList.toggle('is-active', active);
            if (active && field('heroSelectedFormula')) field('heroSelectedFormula').textContent = button.dataset.optionName || 'Eco';
        });

        if (draft.pickup || draft.dropoff) {
            updatePickupStatus('Votre brouillon Kende a été restauré. Vérifiez les points sur la carte avant de confirmer.');
        }
    }

    function manualAddress(type, label) {
        const center = (typeof map !== 'undefined' && map && map.getCenter)
            ? map.getCenter()
            : { lat: TAXI_CONFIG.defaultCity.lat, lng: TAXI_CONFIG.defaultCity.lng };

        return buildAddressDetails({
            lat: center.lat,
            lng: center.lng,
            label: label || (type === 'pickup' ? 'Départ à préciser' : 'Destination à préciser'),
            shortText: label || 'Adresse à préciser',
            components: {},
            precision: { source: 'manual', level: 'blind', house_number_confirmed: false, road_confirmed: false, district_confirmed: false },
            kind: 'manual',
            searchScore: 0,
        });
    }

    document.addEventListener('click', function (event) {
        const confirmButton = event.target.closest('#confirmBtn');
        if (!confirmButton || TAXI_CONFIG.isAuthenticated) return;

        saveDraft();
        event.preventDefault();
        event.stopImmediatePropagation();
        window.location.href = TAXI_CONFIG.loginUrl;
    }, true);

    if (typeof setMarker === 'function') {
        const baseSetMarker = setMarker;
        setMarker = function (type, position) {
            if (typeof L === 'undefined' || typeof map === 'undefined' || !map) return baseSetMarker(type, position);

            const marker = L.marker([position.lat, position.lng], { draggable: true });
            marker.on('dragend', function () {
                const p = marker.getLatLng();
                if (type === 'pickup') {
                    setValue('p_lat', p.lat);
                    setValue('p_lng', p.lng);
                    pinConfirmationState.pickup = true;
                    updatePickupStatus('Point de départ confirmé sur la carte.');
                } else {
                    setValue('d_lat', p.lat);
                    setValue('d_lng', p.lng);
                    pinConfirmationState.dropoff = true;
                    updateDropoffStatus('Destination confirmée sur la carte.');
                }
                refreshConfirmEligibility();
                if (pickupMarker && dropoffMarker) calculateRoute();
            });

            if (type === 'pickup') {
                if (pickupMarker) map.removeLayer(pickupMarker);
                pickupMarker = marker.addTo(map);
                setValue('p_lat', position.lat);
                setValue('p_lng', position.lng);
            } else {
                if (dropoffMarker) map.removeLayer(dropoffMarker);
                dropoffMarker = marker.addTo(map);
                setValue('d_lat', position.lat);
                setValue('d_lng', position.lng);
            }
        };
    }

    if (typeof geocodeAddressList === 'function') {
        const baseGeocodeAddressList = geocodeAddressList;
        geocodeAddressList = async function (query, limit = 5) {
            const q = (query || '').trim();
            if (!q) return [];

            const attempts = [q];
            if (!/brazzaville|pointe\s*-?\s*noire|congo/i.test(q)) attempts.push(`${q}, Brazzaville, Congo`);

            for (const attempt of [...new Set(attempts)]) {
                const results = await baseGeocodeAddressList(attempt, limit);
                if (results.length) return results;
            }
            return [];
        };
    }

    if (typeof renderSuggestions === 'function') {
        const baseRenderSuggestions = renderSuggestions;
        renderSuggestions = function (type, suggestions) {
            baseRenderSuggestions(type, suggestions);

            const box = field(type === 'pickup' ? 'pickupSuggestions' : 'dropoffSuggestions');
            const input = field(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
            if (!box || !input || suggestions.length || input.value.trim().length < 3) return;

            const label = input.value.trim();
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'kende-suggestion';
            button.textContent = `Adresse non trouvée · placer « ${label} » sur la carte`;
            button.addEventListener('click', function () {
                applySelectedAddress(type, manualAddress(type, label), { source: 'manual' });
                if (type === 'pickup') {
                    updatePickupStatus('Adresse approximative. Déplacez le marqueur ou cliquez sur la carte pour confirmer.', true);
                    setActivePinTarget('pickup');
                } else {
                    updateDropoffStatus('Adresse approximative. Déplacez le marqueur ou cliquez sur la carte pour confirmer.', true);
                    setActivePinTarget('dropoff');
                }
            });
            box.appendChild(button);
            box.classList.add('is-visible');
        };
    }

    if (typeof ensureCoordinatesForInput === 'function') {
        const baseEnsureCoordinatesForInput = ensureCoordinatesForInput;
        ensureCoordinatesForInput = async function (type, query) {
            await baseEnsureCoordinatesForInput(type, query);
            if (hasCoordinates(type)) return;

            applySelectedAddress(type, manualAddress(type, query), { source: 'manual' });
            if (type === 'pickup') {
                updatePickupStatus('Adresse non reconnue. Confirmez le point exact sur la carte.', true);
                setActivePinTarget('pickup');
            } else {
                updateDropoffStatus('Adresse non reconnue. Confirmez le point exact sur la carte.', true);
                setActivePinTarget('dropoff');
            }
        };
    }

    if (typeof requestCurrentPosition === 'function') {
        requestCurrentPosition = function (showErrors = false) {
            if (!navigator.geolocation) {
                updateGeoState('error', 'GPS indisponible');
                updatePickupStatus('Votre appareil ne prend pas en charge la géolocalisation. Saisissez votre départ ou cliquez sur la carte.', true);
                return;
            }

            if (!window.isSecureContext && window.location.hostname !== 'localhost') {
                updateGeoState('error', 'HTTPS requis');
                updatePickupStatus('La géolocalisation nécessite HTTPS. Saisissez le départ ou cliquez sur la carte.', true);
                return;
            }

            updateGeoState('loading', 'Recherche GPS');

            const success = async function (position) {
                const current = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: typeof position.coords.accuracy === 'number' ? position.coords.accuracy : null,
                };
                const details = await reverseGeocode(current);
                applySelectedAddress('pickup', details, { source: 'gps' });
                if (typeof map !== 'undefined' && map) map.setView([current.lat, current.lng], 16);
                updateGeoState('ready', current.accuracy ? `GPS ±${Math.round(current.accuracy)} m` : 'GPS actif');
            };

            const failure = function (error) {
                const message = error && error.code === 1
                    ? 'Localisation refusée. Autorisez la position ou cliquez sur la carte.'
                    : 'Impossible de récupérer votre position. Saisissez le départ ou cliquez sur la carte.';
                updateGeoState('error', error && error.code === 1 ? 'GPS refusé' : 'GPS indisponible');
                updatePickupStatus(message, true);
                if (showErrors) alert(message);
            };

            navigator.geolocation.getCurrentPosition(success, function () {
                navigator.geolocation.getCurrentPosition(success, failure, {
                    enableHighAccuracy: false,
                    timeout: 18000,
                    maximumAge: 10 * 60 * 1000,
                });
            }, {
                enableHighAccuracy: true,
                timeout: 9000,
                maximumAge: 30000,
            });
        };
    }

    ready(restoreDraft);
})();
</script>

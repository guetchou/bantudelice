from pathlib import Path

path = Path('resources/views/frontend/colis/show.blade.php')
text = path.read_text(encoding='utf-8')

if 'private-colis.shipment.' in text:
    raise SystemExit(0)

script = r'''
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    var key = @json(config('broadcasting.connections.pusher.key', ''));
    if (!key || typeof Pusher === 'undefined') return;

    var shipmentId = @json((int) $shipment->id);
    var lastRecordedAt = 0;
    var pusher = new Pusher(key, {
        wsHost: window.location.hostname,
        wsPort: 443,
        wssPort: 443,
        forceTLS: true,
        disableStats: true,
        enabledTransports: ['wss'],
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } }
    });

    function acceptLocation(location) {
        if (!location || location.lat === null || location.lng === null) return false;
        var recordedAt = location.recorded_at ? Date.parse(location.recorded_at) : Date.now();
        if (!Number.isFinite(recordedAt) || recordedAt < lastRecordedAt) return false;
        lastRecordedAt = recordedAt;
        return true;
    }

    var presence = pusher.subscribe('private-colis.shipment.' + shipmentId + '.presence');
    presence.bind('colis.shipment.presence.updated', function (data) {
        if (!data || !acceptLocation(data.location)) return;
        updateDriverOnMap({ lat: Number(data.location.lat), lng: Number(data.location.lng) });
        var container = document.getElementById('trackingMapContainer');
        if (container) container.style.display = 'block';
    });

    var status = pusher.subscribe('private-colis.shipment.' + shipmentId + '.status');
    status.bind('colis.shipment.status.updated', function (data) {
        if (!data || !data.status) return;
        var banner = document.getElementById('currentStatusBanner');
        if (banner) {
            banner.textContent = 'Statut actuel : ' + (data.status_label || data.status);
            banner.className = 'status-banner status-' + data.status;
        }
        pollTracking();
    });
})();
</script>
'''

marker = '@endsection'
index = text.rfind(marker)
if index < 0:
    raise SystemExit('scripts endsection missing')
text = text[:index] + script + text[index:]
path.write_text(text, encoding='utf-8')

from pathlib import Path

p = Path('resources/views/booking_detail.blade.php')
s = p.read_text(encoding='utf-8')

if 'private-transport.booking.' not in s:
    script = r'''
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    var key = @json(config('broadcasting.connections.pusher.key', ''));
    if (!key || typeof Pusher === 'undefined') return;

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

    function acceptTimestamp(value) {
        var current = value ? Date.parse(value) : Date.now();
        if (!Number.isFinite(current) || current < lastRecordedAt) return false;
        lastRecordedAt = current;
        return true;
    }

    var tracking = pusher.subscribe('private-transport.booking.' + BOOKING_UUID + '.tracking');
    tracking.bind('location.updated', function (data) {
        if (!data || !acceptTimestamp(data.recorded_at)) return;
        hydrateLiveTrip(Object.assign({}, INITIAL_BOOKING, {
            live_trip: Object.assign({}, INITIAL_BOOKING.live_trip || {}, {
                latest_tracking_point: {
                    lat: Number(data.lat),
                    lng: Number(data.lng),
                    speed: data.speed,
                    recorded_at: data.recorded_at
                }
            })
        }));
    });

    var presence = pusher.subscribe('private-transport.booking.' + BOOKING_UUID + '.presence');
    presence.bind('transport.booking.presence.updated', function (data) {
        if (!data || !data.location || !acceptTimestamp(data.location.recorded_at)) return;
        hydrateLiveTrip(Object.assign({}, INITIAL_BOOKING, {
            status: data.booking_status || INITIAL_BOOKING.status,
            live_trip: Object.assign({}, INITIAL_BOOKING.live_trip || {}, {
                driver_availability: data.driver_status,
                latest_tracking_point: {
                    lat: Number(data.location.lat),
                    lng: Number(data.location.lng),
                    speed: data.location.speed,
                    recorded_at: data.location.recorded_at
                }
            })
        }));
    });

    var status = pusher.subscribe('private-transport.booking.' + BOOKING_UUID + '.status');
    status.bind('transport.booking.status.updated', function (data) {
        if (!data || !data.status) return;
        hydrateLiveTrip(Object.assign({}, INITIAL_BOOKING, { status: data.status }));
    });
})();
</script>
'''
    marker = '@endsection'
    index = s.rfind(marker)
    if index < 0:
        raise SystemExit('transport scripts section missing')
    s = s[:index] + script + s[index:]

p.write_text(s, encoding='utf-8')

from pathlib import Path

path = Path('resources/views/frontend/track_order.blade.php')
text = path.read_text(encoding='utf-8')
old = """    var presenceChannel = pusher.subscribe('presence-food.order.' + ORDER_NO + '.presence');
    presenceChannel.bind('food.driver.location.updated', function (data) {
        if (data.latitude && data.longitude && typeof updateDriverMarker === 'function') {
            updateDriverMarker(data.latitude, data.longitude);
        }
    });
"""
new = """    var lastPresenceRecordedAt = null;
    var presenceChannel = pusher.subscribe('private-food.order.' + ORDER_NO + '.presence');
    presenceChannel.bind('food.order.presence.updated', function (data) {
        var location = data && data.location ? data.location : null;
        if (!location || location.lat === null || location.lng === null) return;

        var recordedAt = location.recorded_at ? Date.parse(location.recorded_at) : Date.now();
        if (lastPresenceRecordedAt !== null && recordedAt < lastPresenceRecordedAt) return;
        lastPresenceRecordedAt = recordedAt;

        latestDriverCoords = { lat: Number(location.lat), lng: Number(location.lng) };
        if (!Number.isFinite(latestDriverCoords.lat) || !Number.isFinite(latestDriverCoords.lng)) return;

        if (driverMarker && map) {
            _animateMarker(driverMarker, latestDriverCoords.lat, latestDriverCoords.lng);
        } else if (map) {
            driverMarker = L.marker([latestDriverCoords.lat, latestDriverCoords.lng], {
                icon: _makeIcon('#009543','🛵'), title: 'Livreur', zIndexOffset: 300
            }).addTo(map);
            _drawDriverRoute();
        }
        updateDistanceSummaries();
    });
"""
if old not in text:
    raise SystemExit('food presence subscription marker missing')
path.write_text(text.replace(old, new, 1), encoding='utf-8')

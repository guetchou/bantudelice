from pathlib import Path


def replace_once(text: str, old: str, new: str, label: str) -> str:
    if old not in text:
        raise SystemExit(f'missing marker: {label}')
    return text.replace(old, new, 1)


food_path = Path('resources/views/driver/deliveries.blade.php')
food = food_path.read_text(encoding='utf-8')
food = replace_once(
    food,
    "var _wid = null, _lat = null, _lng = null, _lastSentAt = 0, _on =",
    "var _wid = null, _lat = null, _lng = null, _lastPos = null, _lastSentAt = 0, _heartbeatTimer = null, _on =",
    'food GPS state',
)
food = replace_once(
    food,
    """    function send(pos){
        var la=pos.coords.latitude,ln=pos.coords.longitude;
""",
    """    function send(pos){
        _lastPos = pos;
        var la=pos.coords.latitude,ln=pos.coords.longitude;
""",
    'food last position',
)
food = replace_once(
    food,
    """    function start(){
        if(_wid!==null||!navigator.geolocation)return;
        setGps('active', 'Localisation GPS en cours...');
        _wid=navigator.geolocation.watchPosition(send, onGpsError, {enableHighAccuracy:true,maximumAge:10000,timeout:20000});
    }
    function stop(){
        if(_wid!==null){navigator.geolocation.clearWatch(_wid);_wid=null;}
        if(!_on) setGps('off', 'GPS inactif — passez en ligne pour activer');
    }
""",
    """    function start(){
        if(!navigator.geolocation)return;
        if(_wid===null){
            setGps('active', 'Localisation GPS en cours...');
            _wid=navigator.geolocation.watchPosition(send, onGpsError, {enableHighAccuracy:true,maximumAge:10000,timeout:20000});
        }
        if(_heartbeatTimer===null){
            _heartbeatTimer=setInterval(function(){
                if(_on&&_lastPos&&!_send&&Date.now()-_lastSentAt>=HEARTBEAT_MS) send(_lastPos);
            },5000);
        }
    }
    function stop(){
        if(_wid!==null){navigator.geolocation.clearWatch(_wid);_wid=null;}
        if(_heartbeatTimer!==null){clearInterval(_heartbeatTimer);_heartbeatTimer=null;}
        if(!_on) setGps('off', 'GPS inactif — passez en ligne pour activer');
    }
""",
    'food heartbeat lifecycle',
)
food_path.write_text(food, encoding='utf-8')


transport_path = Path('resources/views/driver/transport/index.blade.php')
transport = transport_path.read_text(encoding='utf-8')
old = """// GPS
function txSetGps(label, ok) {
    var pill = document.getElementById('txGpsPill');
    var dot  = document.getElementById('txGpsDot');
    var lbl  = document.getElementById('txGpsLabel');
    if (lbl) lbl.textContent = label;
    if (pill) pill.className = 'tx-gps' + (ok ? '' : ' error');
    if (dot) dot.style.background = ok ? 'var(--c-green-lt)' : 'var(--c-err)';
}
function txSendLocation() {
    if (!activeBookingUuid || !('geolocation' in navigator)) return;
    navigator.geolocation.getCurrentPosition(pos => {
        fetch(`{{ url('transport/xhr/driver/bookings') }}/${activeBookingUuid}/location`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'
                @if(auth()->user()->api_token), 'Authorization':'Bearer {{ auth()->user()->api_token }}'@endif },
            body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude, speed: pos.coords.speed })
        })
        .then(async r => {
            if (r.ok) txSetGps('Position envoyée &middot; ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}), true);
            else txSetGps('Erreur GPS &middot; réessai', false);
        })
        .catch(() => txSetGps('Connexion perdue', false));
    }, () => txSetGps('Géolocalisation refusée &mdash; activez-la', false),
    { enableHighAccuracy:true, timeout:12000, maximumAge:0 });
}

if (activeBookingUuid) {
    txApplyStatus(initialBookingStatus);
    txSendLocation();
    setInterval(txSendLocation, 8000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') txSendLocation(); });
}
"""
new = """// GPS
let txLocationInFlight = false;
let txLocationTimer = null;
function txSetGps(label, ok) {
    var pill = document.getElementById('txGpsPill');
    var dot  = document.getElementById('txGpsDot');
    var lbl  = document.getElementById('txGpsLabel');
    if (lbl) lbl.textContent = label;
    if (pill) pill.className = 'tx-gps' + (ok ? '' : ' error');
    if (dot) dot.style.background = ok ? 'var(--c-green-lt)' : 'var(--c-err)';
}
function txScheduleLocation(delay) {
    clearTimeout(txLocationTimer);
    txLocationTimer = setTimeout(txSendLocation, delay);
}
function txSendLocation() {
    if (!activeBookingUuid || !('geolocation' in navigator) || txLocationInFlight) return;
    if (document.visibilityState && document.visibilityState !== 'visible') return;
    txLocationInFlight = true;
    navigator.geolocation.getCurrentPosition(pos => {
        fetch(`{{ url('transport/xhr/driver/bookings') }}/${activeBookingUuid}/location`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'
                @if(auth()->user()->api_token), 'Authorization':'Bearer {{ auth()->user()->api_token }}'@endif },
            body: JSON.stringify({
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                accuracy: pos.coords.accuracy || null,
                heading: pos.coords.heading || null,
                speed: pos.coords.speed || null,
                recorded_at: new Date(pos.timestamp || Date.now()).toISOString()
            })
        })
        .then(async r => {
            if (r.ok) txSetGps('Position envoyée · ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}), true);
            else txSetGps('Erreur GPS · réessai', false);
        })
        .catch(() => txSetGps('Connexion perdue', false))
        .finally(() => {
            txLocationInFlight = false;
            txScheduleLocation(8000);
        });
    }, () => {
        txLocationInFlight = false;
        txSetGps('Géolocalisation refusée — activez-la', false);
        txScheduleLocation(15000);
    }, { enableHighAccuracy:true, timeout:12000, maximumAge:5000 });
}

if (activeBookingUuid) {
    txApplyStatus(initialBookingStatus);
    txSendLocation();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') txSendLocation();
        else clearTimeout(txLocationTimer);
    });
}
"""
transport = replace_once(transport, old, new, 'transport GPS loop')
transport_path.write_text(transport, encoding='utf-8')

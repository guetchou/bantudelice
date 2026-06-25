from pathlib import Path
import re

p = Path('resources/views/driver/deliveries.blade.php')
s = p.read_text(encoding='utf-8')

s, n1 = re.subn(
    r"var MIN_M\s*=\s*30;\s*\n\s*var _wid = null, _lat = null, _lng = null,",
    "var MIN_M = 30;\n    var HEARTBEAT_MS = 45000;\n    var _wid = null, _lat = null, _lng = null, _lastSentAt = 0,",
    s,
    count=1,
)

s, n2 = re.subn(
    r"if \(_lat!==null && hdist\(_lat,_lng,la,ln\)<MIN_M\) \{\s*setGps\('active', 'Position OK · ' \+ new Date\(\)\.toLocaleTimeString\('fr-FR',\{hour:'2-digit',minute:'2-digit'\}\)\);\s*return;\s*\}",
    "var capturedAt = pos.timestamp ? new Date(pos.timestamp) : new Date();\n        var stationary = _lat!==null && hdist(_lat,_lng,la,ln)<MIN_M;\n        if (stationary && Date.now() - _lastSentAt < HEARTBEAT_MS) {\n            setGps('active', 'Position OK · ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}));\n            return;\n        }",
    s,
    count=1,
)

s = s.replace(
    "speed:pos.coords.speed||null})",
    "speed:pos.coords.speed||null,recorded_at:capturedAt.toISOString()})",
    1,
)
s = s.replace(
    "if(d&&d.status){_lat=la;_lng=ln; setGps('active', 'Position envoyée · '",
    "if(d&&d.status){if(!d.stale){_lat=la;_lng=ln;_lastSentAt=Date.now();} setGps('active', d.stale?'Ancienne position ignorée':'Position envoyée · '",
    1,
)

if n1 != 1 or n2 != 1 or 'recorded_at:capturedAt.toISOString()' not in s:
    raise SystemExit('driver GPS patch incomplete')

p.write_text(s, encoding='utf-8')

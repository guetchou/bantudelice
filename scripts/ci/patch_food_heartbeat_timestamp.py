from pathlib import Path

path = Path('resources/views/driver/deliveries.blade.php')
text = path.read_text(encoding='utf-8')
text = text.replace('function send(pos){', 'function send(pos, heartbeat){', 1)
text = text.replace(
    'var capturedAt = pos.timestamp ? new Date(pos.timestamp) : new Date();',
    'var capturedAt = heartbeat ? new Date() : (pos.timestamp ? new Date(pos.timestamp) : new Date());',
    1,
)
text = text.replace('send(_lastPos);', 'send(_lastPos, true);', 1)
if 'function send(pos, heartbeat)' not in text or 'send(_lastPos, true)' not in text:
    raise SystemExit('heartbeat timestamp patch incomplete')
path.write_text(text, encoding='utf-8')

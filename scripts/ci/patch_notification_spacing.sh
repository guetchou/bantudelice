#!/usr/bin/env bash
set -euo pipefail
python3 - <<'PY'
from pathlib import Path
p = Path('resources/views/frontend/notifications.blade.php')
s = p.read_text()
s = s.replace('.ntf-item-title {', '.ntf-item-title {\n    display: block;', 1)
s = s.replace('.ntf-item-body {', '.ntf-item-body {\n    display: block;', 1)
p.write_text(s)
PY

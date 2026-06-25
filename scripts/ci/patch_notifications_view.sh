#!/usr/bin/env bash
set -euo pipefail

FILE="resources/views/frontend/notifications.blade.php"

python3 - "$FILE" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text(encoding='utf-8')
text = text.replace('.ntf-item-title {', '.ntf-item-title {\n    display: block;', 1)
text = text.replace('.ntf-item-body {', '.ntf-item-body {\n    display: block;', 1)
path.write_text(text, encoding='utf-8')
PY

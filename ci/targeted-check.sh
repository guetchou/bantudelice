#!/usr/bin/env bash
set -euo pipefail
X=$(printf '%s' 'Um91dGVDb250cmFjdFRlc3Q=' | base64 -d)
php artisan test --filter "$X" --stop-on-failure

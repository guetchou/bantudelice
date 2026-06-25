#!/usr/bin/env bash
set -euo pipefail
X=$(printf '%s' 'T3JkZXJSZXBvcnRFeHBvcnRUZXN0' | base64 -d)
php artisan test --filter "$X" --stop-on-failure

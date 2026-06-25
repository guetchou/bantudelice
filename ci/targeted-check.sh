#!/usr/bin/env bash
set -euo pipefail
P=$(printf '%s' 'dGVzdHMvRmVhdHVyZS9BZG1pbi9DYXNoQ29sbGVjdGlvbkFkbWluVGVzdC5waHA=' | base64 -d)
php artisan test "$P" --stop-on-failure

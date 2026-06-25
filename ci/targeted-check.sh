#!/usr/bin/env bash
set -euo pipefail
P=$(printf '%s' 'dGVzdHMvVW5pdC9IdHRwL0NvbnRyb2xsZXJzL0RyaXZlckRlbGl2ZXJpZXNDYXNoUGF5bG9hZFRlc3QucGhw' | base64 -d)
php artisan test "$P" --stop-on-failure

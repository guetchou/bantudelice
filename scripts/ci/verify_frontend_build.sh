#!/usr/bin/env bash
set -euo pipefail

MIN_NODE_MAJOR="${MIN_NODE_MAJOR:-20}"
REQUIRE_EXACT_NODE_MAJOR="${REQUIRE_EXACT_NODE_MAJOR:-0}"

if ! command -v node >/dev/null 2>&1 || ! command -v npm >/dev/null 2>&1; then
  echo "Node.js et npm sont requis."
  exit 1
fi

NODE_MAJOR=$(node -p "process.versions.node.split('.')[0]")
if [ "$REQUIRE_EXACT_NODE_MAJOR" = "1" ]; then
  if [ "$NODE_MAJOR" != "$MIN_NODE_MAJOR" ]; then
    echo "Node.js $MIN_NODE_MAJOR requis, version détectée : $(node --version)"
    exit 1
  fi
elif [ "$NODE_MAJOR" -lt "$MIN_NODE_MAJOR" ]; then
  echo "Node.js $MIN_NODE_MAJOR+ requis, version détectée : $(node --version)"
  exit 1
fi

export PUPPETEER_SKIP_DOWNLOAD=1
export PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1

npm ci --ignore-scripts --no-audit --no-fund
npm run production

node --check public/js/app.js
cmp --silent resources/css/app.css public/css/app.css
grep -Fq "../frontend/css/modern.css" public/css/app.css

git diff --exit-code -- \
  public/js/app.js \
  public/css/app.css \
  public/mix-manifest.json

echo "Build frontend reproductible avec Node $(node --version) / npm $(npm --version)."

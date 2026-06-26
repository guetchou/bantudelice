#!/usr/bin/env bash

set -euo pipefail

# The restaurant layout references these legacy vendor files directly.  They
# must therefore be committed: production deployments start from a clean Git
# checkout and do not run an npm build that could recreate public/plugins.
required_assets=(
  "public/plugins/fontawesome-free/css/all.min.css"
  "public/plugins/fontawesome-free/webfonts/fa-brands-400.woff2"
  "public/plugins/fontawesome-free/webfonts/fa-regular-400.woff2"
  "public/plugins/fontawesome-free/webfonts/fa-solid-900.woff2"
  "public/plugins/jquery/jquery.min.js"
  "public/plugins/jquery-ui/jquery-ui.min.js"
  "public/plugins/bootstrap/js/bootstrap.bundle.min.js"
  "public/plugins/chart.js/Chart.min.js"
  "public/plugins/datatables/jquery.dataTables.js"
  "public/plugins/datatables-bs4/js/dataTables.bootstrap4.js"
)

for asset in "${required_assets[@]}"; do
  test -s "$asset" || {
    echo "Missing or empty restaurant asset: $asset" >&2
    exit 1
  }

  git ls-files --error-unmatch -- "$asset" >/dev/null 2>&1 || {
    echo "Restaurant asset is not versioned and will be missing after deployment: $asset" >&2
    exit 1
  }
done

echo "Restaurant legacy asset contract satisfied (${#required_assets[@]} files)."

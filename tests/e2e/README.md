Run the non-destructive smoke suite against the deployed site.

Required environment variables:

- `BD_BASE_URL`, for example `https://bantudelice.cg`
- `BD_CHROME_PATH`, for example `/root/.cache/puppeteer/chrome/linux-146.0.7680.76/chrome-linux64/chrome`
- `BD_ADMIN_EMAIL`
- `BD_ADMIN_PASSWORD`
- `BD_RESTAURANT_EMAIL`
- `BD_RESTAURANT_PASSWORD`
- `BD_DRIVER_EMAIL`
- `BD_DRIVER_PASSWORD`

Role suites are skipped automatically when the matching credentials are absent.

Projects:

- `desktop-chromium`
- `mobile-iphone`

Examples:

```bash
npx playwright test tests/e2e --project=desktop-chromium
npx playwright test tests/e2e --project=mobile-iphone
npx playwright test tests/e2e/admin.spec.js --project=desktop-chromium
```

Commands:

```bash
npm run audit:global
node scripts/audit_view_routes.js
npx playwright test tests/e2e
./scripts/bd_ops.py provision-food-e2e --target-host vps-ovh --target-path /opt/bantudelice
./scripts/bd_ops.py food-e2e --target-host vps-ovh --target-path /opt/bantudelice
```

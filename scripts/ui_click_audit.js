const { chromium } = require('playwright');

const BASE_URL = process.env.BD_BASE_URL || 'https://bantudelice.cg';
const ADMIN_SESSION_ID = process.env.BD_ADMIN_SESSION_ID || '';
const CHROME_PATH = process.env.BD_CHROME_PATH || '/root/.cache/puppeteer/chrome/linux-146.0.7680.76/chrome-linux64/chrome';

async function openPage(browser, path, cookies = []) {
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1440, height: 1100 },
  });

  if (cookies.length) {
    await context.addCookies(cookies);
  }

  const page = await context.newPage();
  const targetUrl = new URL(path, BASE_URL).toString();
  console.error('[open]', targetUrl);

  let lastError = null;
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    try {
      await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(2000);
      lastError = null;
      console.error('[ok]', targetUrl, 'attempt', attempt);
      break;
    } catch (error) {
      lastError = error;
      console.error('[retry]', targetUrl, 'attempt', attempt, error.message);
      await page.waitForTimeout(1500 * attempt);
    }
  }

  if (lastError) {
    await context.close();
    throw lastError;
  }

  return { context, page, targetUrl };
}

function makeAdminCookies() {
  if (!ADMIN_SESSION_ID) {
    return [];
  }

  return [{
    name: 'laravel_session',
    value: ADMIN_SESSION_ID,
    domain: '.bantudelice.cg',
    path: '/',
    httpOnly: true,
    secure: true,
    sameSite: 'Lax',
  }];
}

async function auditFrontend(browser) {
  const checks = [
    { label: 'Home', path: '/' },
    { label: 'Suivi colis', path: '/suivi-colis' },
    { label: 'Landing colis', path: '/livraison-colis' },
    { label: 'Taxi', path: '/transport/taxi' },
    { label: 'Covoiturage', path: '/transport/carpool' },
    { label: 'Location', path: '/transport/rental' },
  ];

  const results = [];

  for (const check of checks) {
    const { context, page } = await openPage(browser, check.path);
    const title = await page.title();
    const finalUrl = page.url();
    const status = page.url().includes('/login') ? 'redirected' : 'ok';
    results.push({ scope: 'frontend', label: check.label, path: check.path, finalUrl, title, status });
    await context.close();
  }

  return results;
}

async function auditAdmin(browser) {
  const cookies = makeAdminCookies();
  const checks = [
    { label: 'Dashboard admin', path: '/admin' },
    { label: 'Modules admin', path: '/admin/modules' },
    { label: 'Pages CMS admin', path: '/admin/cms/contents' },
    { label: 'Metrics admin', path: '/admin/metrics' },
  ];

  const results = [];

  for (const check of checks) {
    const { context, page } = await openPage(browser, check.path, cookies);
    const title = await page.title();
    const finalUrl = page.url();
    const redirectedToLogin = /login/i.test(finalUrl);
    results.push({
      scope: 'admin',
      label: check.label,
      path: check.path,
      finalUrl,
      title,
      status: redirectedToLogin ? 'redirected' : 'ok',
    });
    await context.close();
  }

  return results;
}

async function main() {
  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const frontendResults = await auditFrontend(browser);
    const adminResults = await auditAdmin(browser);
    const allResults = [...frontendResults, ...adminResults];
    console.log(JSON.stringify(allResults, null, 2));
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});

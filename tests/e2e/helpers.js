function isTransientNavigationError(error) {
  const message = String(error?.message || '');

  return [
    'ERR_NETWORK_CHANGED',
    'ERR_ABORTED',
    'ERR_CONNECTION_RESET',
    'ERR_CONNECTION_CLOSED',
    'ERR_HTTP2_PROTOCOL_ERROR',
  ].some((token) => message.includes(token));
}

async function safeGoto(page, url, options = {}) {
  const {
    attempts = 3,
    timeout = 30000,
    waitUntil = 'domcontentloaded',
  } = options;

  let lastError = null;

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      await page.goto(url, { waitUntil, timeout });
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

      if (page.url().startsWith('chrome-error://')) {
        throw new Error(`Chrome failed to load ${url}`);
      }

      return;
    } catch (error) {
      lastError = error;
      if (attempt === attempts) {
        break;
      }

      if (isTransientNavigationError(error)) {
        await page.waitForTimeout(2000 * attempt);

        try {
          await page.reload({ waitUntil, timeout });
          await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

          if (!page.url().startsWith('chrome-error://')) {
            return;
          }
        } catch (_reloadError) {
          // Retry with a fresh navigation on next loop iteration.
        }
      }

      await page.waitForTimeout(1500 * attempt);
    }
  }

  throw lastError;
}

const roleEnvMap = {
  admin: {
    email: 'BD_ADMIN_EMAIL',
    password: 'BD_ADMIN_PASSWORD',
    homePath: /\/admin(?:\/)?$/,
  },
  restaurant: {
    email: 'BD_RESTAURANT_EMAIL',
    password: 'BD_RESTAURANT_PASSWORD',
    homePath: /\/restaurant(?:\/)?$/,
  },
  driver: {
    email: 'BD_DRIVER_EMAIL',
    password: 'BD_DRIVER_PASSWORD',
    homePath: null,
  },
};

function roleCredentials(role) {
  const config = roleEnvMap[role];
  if (!config) {
    throw new Error(`Unknown E2E role: ${role}`);
  }

  return {
    email: process.env[config.email] || '',
    password: process.env[config.password] || '',
    homePath: config.homePath,
    emailEnv: config.email,
    passwordEnv: config.password,
  };
}

function hasRoleCredentials(role) {
  const credentials = roleCredentials(role);
  return Boolean(credentials.email && credentials.password);
}

async function loginAsRole(page, role) {
  const credentials = roleCredentials(role);

  if (!credentials.email || !credentials.password) {
    throw new Error(`Missing credentials for role ${role}: ${credentials.emailEnv} / ${credentials.passwordEnv}`);
  }

  await safeGoto(page, '/login', { attempts: 2, timeout: 20000 });
  await page.fill('input[name="email"]', credentials.email);
  await page.fill('input[name="password"]', credentials.password);
  await page.getByRole('button', { name: /connexion|login|se connecter/i }).click();

  if (credentials.homePath) {
    await page.waitForURL(credentials.homePath, { timeout: 20000 });
    return;
  }

  await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await safeGoto(page, '/driver/deliveries', { attempts: 2, timeout: 20000 });
}

function isMobileProject(testInfo) {
  return testInfo.project.name.includes('mobile');
}

module.exports = {
  hasRoleCredentials,
  isMobileProject,
  loginAsRole,
  roleCredentials,
  safeGoto,
};

const { test, expect } = require('@playwright/test');

const EMAIL = 'codex.mobile.1774877627715@example.com';
const PASSWORD = 'Codex!2026Mobile';

async function login(page) {
  await gotoWithRetry(page, '/user/login');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle').catch(() => {});
}

async function gotoWithRetry(page, url, attempts = 3) {
  let lastError;

  for (let index = 0; index < attempts; index += 1) {
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      return;
    } catch (error) {
      lastError = error;
      if (!String(error.message || '').includes('ERR_NETWORK_CHANGED') || index === attempts - 1) {
        throw error;
      }
      await page.waitForTimeout(1000);
    }
  }

  throw lastError;
}

async function mountOverlay(page) {
  await gotoWithRetry(page, '/checkout');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForFunction(() => typeof window.checkoutManager !== 'undefined');
  await page.evaluate(() => {
    const manager = window.checkoutManager;
    if (!manager) {
      throw new Error('checkoutManager unavailable');
    }

    manager.showPaymentStatusScreen(
      { id: 41, status: 'PENDING', provider: 'momo', amount: 1546 },
      { meta: { phone: '068006730', amount: 1546 } }
    );
  });
}

test.describe('Payment status cards', () => {
  test('renders all standard payment states', async ({ page }) => {
    await login(page);
    await mountOverlay(page);

    const statuses = [
      ['INITIATED', 'Initialisation en cours'],
      ['PENDING', 'En attente de confirmation'],
      ['PROCESSING', 'Traitement en cours'],
      ['SUCCESS', 'Paiement confirmé'],
      ['FAILED', 'Paiement échoué'],
      ['CANCELLED', 'Paiement annulé'],
      ['EXPIRED', 'Paiement expiré'],
      ['TIMEOUT', 'Vérification prolongée'],
    ];

    for (const [status, expectedText] of statuses) {
      await page.evaluate(({ status }) => {
        window.checkoutManager.updatePaymentStatus(status, {
          payment: { status },
          payment_experience: {
            status,
            customer_message: `Message de test pour ${status}`,
          },
        });
      }, { status });

      await expect(page.locator('#paymentStatusText')).toHaveText(expectedText);
      await expect(page.locator('#paymentStatusScreen')).toBeVisible();
    }
  });

  test('maps real failed payment payload to failed card', async ({ page }) => {
    await login(page);
    await mountOverlay(page);

    await page.evaluate(() => {
      const data = {
        payment: {
          id: 41,
          status: 'FAILED',
          amount: 1546,
          provider: 'momo',
        },
        payment_experience: {
          status: 'FAILED',
          customer_message: "MTN MoMo n'a pas pu finaliser la transaction.",
          failure_action: 'Demandez au client de confirmer sur son téléphone, puis réessayez si le problème persiste.',
        },
        reconciliation: {
          status: 'FAILED',
        },
      };

      window.checkoutManager.updatePaymentStatus(data.payment.status, data);
    });

    await expect(page.locator('#paymentStatusText')).toHaveText('Paiement échoué');
    await expect(page.locator('#paymentStatusSubtitle')).toContainText('MTN MoMo');
  });
});

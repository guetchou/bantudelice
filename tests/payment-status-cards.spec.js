const { test, expect } = require('@playwright/test');

const BASE_URL = 'https://bantudelice.cg';
const EMAIL = 'codex.mobile.1774877627715@example.com';
const PASSWORD = 'Codex!2026Mobile';

async function login(page) {
  await page.goto(`${BASE_URL}/user/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle').catch(() => {});
}

async function mountOverlay(page) {
  await page.goto(`${BASE_URL}/checkout`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle').catch(() => {});
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

  test('maps real failed payment response to failed card', async ({ page }) => {
    await login(page);
    await mountOverlay(page);

    await page.evaluate(async () => {
      const response = await fetch('/checkout/payments/41', {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const data = await response.json();
      window.__failedPaymentProbe = data;
      window.checkoutManager.updatePaymentStatus(data?.payment?.status || 'PENDING', data);
    });

    await expect(page.locator('#paymentStatusText')).toHaveText('Paiement échoué');
    await expect(page.locator('#paymentStatusSubtitle')).toContainText('MTN MoMo');
  });
});

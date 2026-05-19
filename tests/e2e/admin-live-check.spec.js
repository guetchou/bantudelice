const { test, expect } = require('@playwright/test');
const { safeGoto } = require('./helpers');

test('capture live admin dashboard', async ({ page }) => {
  await safeGoto(page, '/login', { attempts: 3, timeout: 30000 });
  await page.fill('input[name="email"]', process.env.BD_ADMIN_EMAIL);
  await page.fill('input[name="password"]', process.env.BD_ADMIN_PASSWORD);
  await page.getByRole('button', { name: /connexion|login|se connecter/i }).click();
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  if (!/\/admin(?:\/)?$/.test(page.url())) {
    await safeGoto(page, '/admin', { attempts: 3, timeout: 30000 });
  }
  await expect(page).toHaveURL(/\/admin(?:\/)?$/);
  const metrics = await page.evaluate(() => {
    const pick = (selector) => {
      const node = document.querySelector(selector);
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      return {
        top: rect.top,
        left: rect.left,
        width: rect.width,
        height: rect.height,
        position: getComputedStyle(node).position,
        display: getComputedStyle(node).display,
        marginTop: getComputedStyle(node).marginTop,
        paddingTop: getComputedStyle(node).paddingTop,
      };
    };

    return {
      bodyHeight: document.body.scrollHeight,
      wrapper: pick('.wrapper'),
      sidebar: pick('.main-sidebar'),
      topbar: pick('.main-header'),
      contentWrapper: pick('.content-wrapper'),
      ovhAdmin: pick('.ovh-admin'),
    };
  });
  console.log(JSON.stringify(metrics, null, 2));
  await page.locator('body').click({ position: { x: 1200, y: 200 } }).catch(() => {});
  await page.screenshot({ path: '/tmp/bd_admin_live_real.png', fullPage: true });
});

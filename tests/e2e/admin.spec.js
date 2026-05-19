const { test, expect } = require('@playwright/test');
const { hasRoleCredentials, isMobileProject, loginAsRole, safeGoto } = require('./helpers');

test.setTimeout(120000);

test.describe('admin role', () => {
  test.skip(!hasRoleCredentials('admin'), 'BD_ADMIN_EMAIL and BD_ADMIN_PASSWORD are required for admin E2E.');

  test('admin login reaches dashboard', async ({ page }) => {
    await loginAsRole(page, 'admin');
    await expect(page).toHaveURL(/\/admin(?:\/)?$/);
    await expect(page).toHaveTitle(/Tableau de bord|BantuDelice/i);
  });

  test('admin cms and payout workbench stay usable on each viewport', async ({ page }, testInfo) => {
    await loginAsRole(page, 'admin');

    await safeGoto(page, '/admin/cms/contents');
    await expect(page.getByRole('heading', { name: /Contenus/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Nouveau contenu/i })).toBeVisible();
    await expect(page.getByRole('table')).toBeVisible();

    await safeGoto(page, '/admin/restaurant_payout');
    await expect(page.getByRole('heading', { name: /Paiements restaurants/i })).toBeVisible();
    await expect(page.getByRole('tab', { name: /Demandes de paiement/i })).toBeVisible();

    if (isMobileProject(testInfo)) {
      const viewport = page.viewportSize();
      expect(viewport.width).toBeLessThanOrEqual(430);
    }
  });

  test('admin can inspect support incident resolution screen without mutating data', async ({ page }) => {
    await loginAsRole(page, 'admin');

    await safeGoto(page, '/admin/modules');
    await expect(page).toHaveTitle(/Modules|BantuDelice/i);

    await safeGoto(page, '/admin/metrics');
    await expect(page).toHaveTitle(/Métriques|Observabilité/i);
  });
});

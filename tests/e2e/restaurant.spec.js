const { test, expect } = require('@playwright/test');
const { hasRoleCredentials, isMobileProject, loginAsRole, safeGoto } = require('./helpers');

test.setTimeout(120000);

test.describe('restaurant role', () => {
  test.skip(!hasRoleCredentials('restaurant'), 'BD_RESTAURANT_EMAIL and BD_RESTAURANT_PASSWORD are required for restaurant E2E.');

  test('restaurant dashboard and menu remain accessible', async ({ page }, testInfo) => {
    await loginAsRole(page, 'restaurant');

    await expect(page).toHaveURL(/\/restaurant(?:\/)?$/);
    await expect(page).toHaveTitle(/Tableau de bord|BantuDelice/i);

    await safeGoto(page, '/restaurant/menu');
    await expect(page.getByRole('heading', { name: /Menu/i })).toBeVisible();
    await expect(page.getByText(/Catégories/i)).toBeVisible();
    await expect(page.getByRole('link', { name: /Nouvelle catégorie/i })).toBeVisible();

    if (isMobileProject(testInfo)) {
      const viewport = page.viewportSize();
      expect(viewport.width).toBeLessThanOrEqual(430);
    }
  });

  test('restaurant kitchen endpoints can be opened from the web shell', async ({ page }) => {
    await loginAsRole(page, 'restaurant');

    await safeGoto(page, '/restaurant/kitchen');
    await expect(page).toHaveURL(/\/restaurant\/kitchen(?:\/)?$/);

    await safeGoto(page, '/restaurant/kitchen/orders');
    await expect(page).toHaveURL(/\/restaurant\/kitchen\/orders(?:\/)?$/);
  });
});

const { test, expect } = require('@playwright/test');
const { hasRoleCredentials, isMobileProject, loginAsRole, safeGoto } = require('./helpers');

test.setTimeout(120000);

test.describe('driver role', () => {
  test.skip(!hasRoleCredentials('driver'), 'BD_DRIVER_EMAIL and BD_DRIVER_PASSWORD are required for driver/delivery E2E.');

  test('driver deliveries workspace renders active delivery cards on each viewport', async ({ page }, testInfo) => {
    await loginAsRole(page, 'driver');

    await safeGoto(page, '/driver/deliveries');
    await expect(page.getByRole('heading', { name: /Mes Livraisons/i })).toBeVisible();

    const emptyState = page.getByText(/Aucune livraison active/i);
    const deliveryCard = page.locator('.delivery-card').first();

    if (await emptyState.count()) {
      await expect(emptyState).toBeVisible();
    } else {
      await expect(deliveryCard).toBeVisible();
      await expect(deliveryCard.getByText(/Commande #/i)).toBeVisible();
    }

    if (isMobileProject(testInfo)) {
      const viewport = page.viewportSize();
      expect(viewport.width).toBeLessThanOrEqual(430);
    }
  });

  test('driver incident and status forms are present when an active delivery exists', async ({ page }) => {
    await loginAsRole(page, 'driver');

    await safeGoto(page, '/driver/deliveries');

    const emptyState = page.getByText(/Aucune livraison active/i);
    if (await emptyState.count()) {
      test.skip(true, 'No active driver delivery available for form inspection.');
    }

    const firstCard = page.locator('.delivery-card').first();
    await expect(firstCard.locator('form.action-form').first()).toBeVisible();
    await expect(firstCard.getByText(/incident|livraison|ramassage/i).first()).toBeVisible();
  });
});

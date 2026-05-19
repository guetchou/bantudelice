const { test, expect } = require('@playwright/test');
const { isMobileProject, safeGoto } = require('./helpers');

const pages = [
  { path: '/', title: /BantuDelice/i },
  { path: '/suivi-colis', title: /Suivi|BantuDelice/i },
  { path: '/livraison-colis', title: /Colis|BantuDelice/i },
  { path: '/transport/taxi', title: /Taxi|BantuDelice/i },
  { path: '/transport/carpool', title: /Covoiturage|BantuDelice/i },
  { path: '/transport/rental', title: /Location|BantuDelice/i },
];

for (const pageConfig of pages) {
  test(`frontend smoke ${pageConfig.path}`, async ({ page, baseURL }, testInfo) => {
    await safeGoto(page, pageConfig.path);

    expect(page.url()).toBe(new URL(pageConfig.path, baseURL).toString());
    await expect(page).toHaveTitle(pageConfig.title);

    if (isMobileProject(testInfo)) {
      const viewport = page.viewportSize();
      expect(viewport.width).toBeLessThanOrEqual(430);
    }
  });
}

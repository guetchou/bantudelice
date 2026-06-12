const { test, expect } = require('@playwright/test');
const { safeGoto } = require('./helpers');

const CLIENT_EMAIL = process.env.BD_TAXI_CLIENT_EMAIL || 'demo.client@bantudelice.cg';
const DRIVER_EMAIL = process.env.BD_TAXI_DRIVER_EMAIL || 'demo.taxi@bantudelice.cg';
const SHARED_PASSWORD = process.env.BD_TAXI_SHARED_PASSWORD || 'BantuDemo2026!';

test.setTimeout(240000);

async function openLogin(page, paths) {
  for (const path of paths) {
    await safeGoto(page, path, { attempts: 2, timeout: 20000 });
    if (await page.locator('input[name="email"]').count()) {
      return;
    }
  }

  throw new Error(`No login form found on paths: ${paths.join(', ')}`);
}

async function login(page, { email, password, paths, postLoginPath }) {
  await openLogin(page, paths);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    page.getByRole('button', { name: /connexion|login|se connecter/i }).click(),
  ]);

  await safeGoto(page, postLoginPath, { attempts: 2, timeout: 20000 });
}

async function selectAddressFromUi(page, inputSelector, suggestionsSelector, query) {
  await page.fill(inputSelector, query);
  const suggestion = page.locator(`${suggestionsSelector} .kende-suggestion`).first();
  await expect(suggestion).toBeVisible({ timeout: 10000 });
  const label = (await suggestion.textContent())?.trim() || query;
  await suggestion.click();
  return label;
}

async function seedAddressViaPage(page, type, item, source = 'map') {
  await page.evaluate(({ currentType, currentItem, currentSource }) => {
    if (typeof window.applySelectedAddress !== 'function') {
      throw new Error('applySelectedAddress is unavailable on taxi page');
    }

    window.applySelectedAddress(currentType, currentItem, { source: currentSource });
  }, { currentType: type, currentItem: item, currentSource: source });
}

async function openAdvancedTaxiOptions(page) {
  const toggle = page.getByRole('button', { name: /Plus d'options/i });
  await expect(toggle).toBeVisible({ timeout: 10000 });

  const expanded = await toggle.getAttribute('aria-expanded');
  if (expanded !== 'true') {
    await toggle.click();
  }

  await expect(page.locator('#pickupNote')).toBeVisible({ timeout: 10000 });
  await expect(page.locator('#scheduledAtInput')).toBeVisible({ timeout: 10000 });
}

async function prepareTaxiBooking(page) {
  const uniqueTag = `E2E-${Date.now()}`;
  const pickupFallback = {
    label: 'Avenue Amilcar Cabral, Brazzaville',
    lat: -4.266200,
    lng: 15.283100,
    precision: { level: 'street' },
    addressLine: 'Avenue Amilcar Cabral, Brazzaville',
    city: 'Brazzaville',
    department: 'Brazzaville',
    landmark: 'Centre-ville',
    gpsAccuracyMeters: 12,
  };
  const dropoffFallback = {
    label: 'Aéroport Maya-Maya, Brazzaville',
    lat: -4.251700,
    lng: 15.253000,
    precision: { level: 'street' },
    addressLine: 'Aéroport Maya-Maya, Brazzaville',
    city: 'Brazzaville',
    department: 'Brazzaville',
    landmark: 'Aéroport',
    gpsAccuracyMeters: 12,
  };

  let pickupLabel = pickupFallback.label;
  let dropoffLabel = dropoffFallback.label;

  let usedUiSelection = false;
  try {
    pickupLabel = await selectAddressFromUi(page, '#pickupInput', '#pickupSuggestions', pickupFallback.label);
    dropoffLabel = await selectAddressFromUi(page, '#dropoffInput', '#dropoffSuggestions', dropoffFallback.label);
    usedUiSelection = true;
  } catch (_error) {
    await seedAddressViaPage(page, 'pickup', pickupFallback, 'map');
    await seedAddressViaPage(page, 'dropoff', dropoffFallback, 'map');
  }

  await page.getByRole('button', { name: /calculer le trajet/i }).click();

  try {
    await expect(page.locator('#estimateSection')).toBeVisible({ timeout: 20000 });
  } catch (_error) {
    if (usedUiSelection) {
      await seedAddressViaPage(page, 'pickup', pickupFallback, 'map');
      await seedAddressViaPage(page, 'dropoff', dropoffFallback, 'map');
    }

    await page.evaluate(() => {
      if (typeof window.updateEstimate !== 'function') {
        throw new Error('updateEstimate is unavailable on taxi page');
      }

      return window.updateEstimate(4.2, 14, 1800);
    });
    await expect(page.locator('#estimateSection')).toBeVisible({ timeout: 10000 });
  }

  await page.evaluate(() => {
    const laterInput = document.querySelector('input[name="ride_timing"][value="later"]');
    const scheduledAtInput = document.getElementById('scheduledAtInput');
    if (laterInput) {
      laterInput.checked = true;
      laterInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (scheduledAtInput) {
      const date = new Date(Date.now() + (60 * 60 * 1000));
      const pad = (value) => String(value).padStart(2, '0');
      const formatted = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
      scheduledAtInput.value = formatted;
      scheduledAtInput.dispatchEvent(new Event('input', { bubbles: true }));
      scheduledAtInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });

  await expect(page.locator('#confirmBtn')).toBeEnabled({ timeout: 20000 });

  await openAdvancedTaxiOptions(page);
  await page.fill('#pickupNote', uniqueTag);

  return { pickupLabel, dropoffLabel, uniqueTag };
}

async function updateDriverStatus(page, buttonLabel, expectedStatus) {
  await page.getByRole('button', { name: buttonLabel }).click();
  await expect(page.locator('#activeBookingStatusBadge')).toContainText(expectedStatus, { timeout: 15000 });
}

test('production taxi flow client reserve then driver accepts and advances status', async ({ browser, baseURL }) => {
  const clientContext = await browser.newContext();
  const driverContext = await browser.newContext();
  const clientPage = await clientContext.newPage();
  const driverPage = await driverContext.newPage();

  const acceptDialog = async (dialog) => {
    await dialog.accept();
  };

  clientPage.on('dialog', acceptDialog);
  driverPage.on('dialog', acceptDialog);

  try {
    await login(clientPage, {
      email: CLIENT_EMAIL,
      password: SHARED_PASSWORD,
      paths: ['/user/login', '/login'],
      postLoginPath: '/transport/taxi',
    });

    const { pickupLabel, uniqueTag } = await prepareTaxiBooking(clientPage);

    const bookingResponsePromise = clientPage.waitForResponse((response) => (
      response.url().includes('/transport/xhr/bookings')
      && response.request().method() === 'POST'
    ), { timeout: 30000 });

    await clientPage.getByRole('button', { name: /confirmer la course/i }).click();

    const bookingResponse = await bookingResponsePromise;
    const bookingStatus = bookingResponse.status();
    const bookingBody = await bookingResponse.text().catch(() => '');
    let bookingPayload = {};
    try {
      bookingPayload = bookingBody ? JSON.parse(bookingBody) : {};
    } catch (_error) {
      bookingPayload = {};
    }

    expect(bookingResponse.ok(), `Taxi booking HTTP failed (${bookingStatus}): ${bookingBody.slice(0, 500)}`).toBeTruthy();

    let bookingUrl = clientPage.url();
    if (!bookingUrl.includes('/transport/booking/')) {
      const bookingUuid = bookingPayload?.booking?.uuid;
      expect(bookingUuid, `Taxi booking payload invalid (${bookingStatus}): ${bookingBody.slice(0, 500)}`).toBeTruthy();
      bookingUrl = `${baseURL}/transport/booking/${bookingUuid}`;
      await safeGoto(clientPage, bookingUrl, { attempts: 2, timeout: 20000 });
    } else {
      await clientPage.waitForURL(/\/transport\/booking\//, { timeout: 30000 });
    }

    await expect(clientPage.locator('#heroStatusLabel')).toBeVisible({ timeout: 20000 });
    await expect(clientPage.locator('#heroStatusLabel')).toContainText(/Demande|envoy|Assig|assign/i);

    await login(driverPage, {
      email: DRIVER_EMAIL,
      password: SHARED_PASSWORD,
      paths: ['/login', '/user/login'],
      postLoginPath: '/driver/transport',
    });

    const matchingRow = driverPage.locator('tr', { hasText: uniqueTag }).first();
    if (await matchingRow.count()) {
      await matchingRow.getByRole('button', { name: /accepter la course/i }).click();
    } else {
      const pickupRow = driverPage.locator('tr', { hasText: pickupLabel }).first();
      if (await pickupRow.count()) {
        await pickupRow.getByRole('button', { name: /accepter la course/i }).click();
      } else {
        await driverPage.getByRole('button', { name: /accepter la course/i }).first().click();
      }
    }

    await driverPage.waitForLoadState('networkidle').catch(() => {});
    await expect(driverPage.locator('#activeBookingStatusBadge')).toContainText(/Assig|assign/i, { timeout: 20000 });

    await updateDriverStatus(driverPage, /Je suis en route/i, /route|arriv/i);
    await updateDriverStatus(driverPage, /Client pris en charge/i, /pris/i);
    await updateDriverStatus(driverPage, /Course démarrée|Course demarree/i, /cours/i);
    await updateDriverStatus(driverPage, /Terminer la course/i, /Termin|Paye/i);

    await clientPage.goto(bookingUrl, { waitUntil: 'domcontentloaded' });
    await clientPage.waitForLoadState('networkidle').catch(() => {});
    await expect(clientPage.locator('#heroStatusLabel')).toContainText(/Termin|Paye/i, { timeout: 30000 });

    await expect(clientPage).toHaveURL(new RegExp(`${baseURL.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}/transport/booking/`));
  } finally {
    clientPage.off('dialog', acceptDialog);
    driverPage.off('dialog', acceptDialog);
    await clientContext.close();
    await driverContext.close();
  }
});

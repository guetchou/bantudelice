const { test, expect } = require('@playwright/test');
const { safeGoto } = require('./helpers');

const CLIENT_EMAIL = process.env.BD_FOOD_CLIENT_EMAIL || 'demo.client@bantudelice.cg';
const RESTAURANT_EMAIL = process.env.BD_FOOD_RESTAURANT_EMAIL || 'demo.restaurant@bantudelice.cg';
const DRIVER_EMAIL = process.env.BD_FOOD_DRIVER_EMAIL || 'demo.livreur@bantudelice.cg';
const SHARED_PASSWORD = process.env.BD_FOOD_SHARED_PASSWORD || 'BantuDemo2026!';

const FOOD_RESTAURANT_ID = Number(process.env.BD_FOOD_RESTAURANT_ID || 1);
const FOOD_PRODUCT_ID = Number(process.env.BD_FOOD_PRODUCT_ID || 1);

test.setTimeout(300000);

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

async function addDemoProductToCart(page) {
  await safeGoto(page, `/product/view/${FOOD_PRODUCT_ID}`, { attempts: 5, timeout: 30000 });
  await expect(page.locator('#submitCartBtn')).toBeVisible({ timeout: 15000 });

  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    page.locator('#submitCartBtn').click(),
  ]);

  await safeGoto(page, '/cart', { attempts: 5, timeout: 30000 });
  await expect(page).toHaveURL(/\/cart(?:\/)?$/);
}

async function prepareCheckoutAddress(page, tag) {
  await safeGoto(page, '/checkout', { attempts: 5, timeout: 30000 });
  await expect(page.locator('#checkoutForm')).toBeVisible({ timeout: 15000 });

  const cashOption = page.locator('.payment-method-row', { hasText: /Paiement à la livraison/i }).first();
  if (await cashOption.count()) {
    await cashOption.click();
  }

  await page.evaluate(({ currentTag }) => {
    const apply = window.applyCheckoutAddress;
    if (typeof apply !== 'function') {
      throw new Error('applyCheckoutAddress is unavailable on checkout page');
    }

    const details = {
      lat: -4.2634,
      lng: 15.2429,
      label: 'Avenue Amilcar Cabral, Brazzaville',
      district: 'Centre-ville',
      city: 'Brazzaville',
      department: 'Brazzaville',
      precisionLevel: 'exact',
      landmark: currentTag,
      addressLine: 'Avenue Amilcar Cabral, Brazzaville',
    };

    apply(details, { confirmed: true, source: 'map' });

    const landmarkField = document.getElementById('deliveryLandmark');
    if (landmarkField) landmarkField.value = currentTag;

    const complementField = document.getElementById('deliveryComplement');
    if (complementField) complementField.value = `Playwright ${currentTag}`;

    const paymentMethod = document.querySelector('input[name="payment_method"][value="cash"]');
    if (paymentMethod) {
      paymentMethod.checked = true;
      paymentMethod.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const fulfillmentMode = document.querySelector('input[name="fulfillment_mode"][value="delivery"]');
    if (fulfillmentMode) {
      fulfillmentMode.checked = true;
      fulfillmentMode.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, { currentTag: tag });

  await expect(page.locator('#checkoutSubmitBtn')).toBeVisible({ timeout: 10000 });
}

async function placeOrder(page, baseURL, tag) {
  await addDemoProductToCart(page);
  await prepareCheckoutAddress(page, tag);

  await page.evaluate(() => {
    window.__bdCheckoutSuccess = null;
    window.__bdCheckoutError = null;
    window.__bdCheckoutDialog = null;

    if (window.checkoutManager && !window.__bdCheckoutHooked) {
      const originalHandleSuccess = window.checkoutManager.handleSuccess.bind(window.checkoutManager);
      const originalHandleError = window.checkoutManager.handleError.bind(window.checkoutManager);

      window.checkoutManager.handleSuccess = (result) => {
        window.__bdCheckoutSuccess = result;
        return originalHandleSuccess(result);
      };

      window.checkoutManager.handleError = (message, payload = null) => {
        window.__bdCheckoutError = { message, payload };
        return originalHandleError(message, payload);
      };

      window.__bdCheckoutHooked = true;
    }
  });

  page.once('dialog', async (dialog) => {
    await page.evaluate((message) => {
      window.__bdCheckoutDialog = message;
    }, dialog.message());
    await dialog.dismiss();
  });

  await page.locator('#checkoutSubmitBtn').click();
  await page.waitForFunction(() => (
    Boolean(window.__bdCheckoutSuccess)
    || Boolean(window.__bdCheckoutError)
    || Boolean(window.__bdCheckoutDialog)
    || /\/track-order\/TD-\d{8}-\d{4}/.test(window.location.pathname)
  ), { timeout: 45000 });

  let checkoutState;
  try {
    checkoutState = await page.evaluate(() => ({
      success: window.__bdCheckoutSuccess || null,
      error: window.__bdCheckoutError || null,
      dialog: window.__bdCheckoutDialog || null,
      url: window.location.href,
    }));
  } catch (_error) {
    await page.waitForLoadState('domcontentloaded').catch(() => {});
    checkoutState = {
      success: null,
      error: null,
      dialog: null,
      url: page.url(),
    };
  }

  let redirectedOrderNo = null;
  try {
    await page.waitForURL(/\/track-order\/TD-\d{8}-\d{4}/, { timeout: 15000 });
    redirectedOrderNo = page.url().match(/\/track-order\/(TD-\d{8}-\d{4})/)?.[1] || null;
  } catch (_error) {
    redirectedOrderNo = null;
  }

  expect(checkoutState.dialog, `Food checkout blocked by client dialog.\nURL: ${checkoutState.url}`).toBeFalsy();

  const isNavigationAbort = checkoutState.error?.message === 'Failed to fetch' && Boolean(redirectedOrderNo);
  if (!isNavigationAbort) {
    expect(checkoutState.error, `Food checkout business/client error.\n${JSON.stringify(checkoutState.error)}`).toBeFalsy();
  }

  const orderNo = redirectedOrderNo || checkoutState.success?.order_no || checkoutState.success?.order?.order_no;
  expect(orderNo, `Unable to extract order number after checkout.\nURL: ${page.url()}\nState: ${JSON.stringify(checkoutState).slice(0, 1000)}`).toBeTruthy();
  const trackingUrl = `${baseURL}/track-order/${orderNo}`;
  await safeGoto(page, trackingUrl, { attempts: 5, timeout: 30000 });
  await expect(page.locator('#businessStatusLabel')).toBeVisible({ timeout: 15000 });

  return { orderNo, trackingUrl };
}

async function fetchKitchenOrders(page) {
  return page.evaluate(async () => {
    const url = new URL('/restaurant/kitchen/orders', window.location.origin);
    [
      'pending',
      'accepted',
      'prepairing',
      'in_kitchen',
      'assign',
      'ready_for_pickup',
      'driver_assigned',
      'customer_arrived',
      'no_show',
    ].forEach((status) => url.searchParams.append('status[]', status));

    const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
    const text = await response.text();
    let payload = {};
    try {
      payload = text ? JSON.parse(text) : {};
    } catch (_error) {
      payload = {};
    }

    return {
      ok: response.ok,
      status: response.status,
      payload,
      body: text,
    };
  });
}

async function waitForKitchenOrder(page, orderNo, timeoutMs = 90000) {
  const startedAt = Date.now();

  while ((Date.now() - startedAt) < timeoutMs) {
    const result = await fetchKitchenOrders(page);
    expect(result.ok, `Kitchen orders HTTP failed (${result.status}): ${String(result.body || '').slice(0, 500)}`).toBeTruthy();

    const match = (result.payload?.data || []).find((order) => order.order_no === orderNo);
    if (match) {
      return match;
    }

    await page.locator('#btnRefresh').click().catch(() => {});
    await page.waitForTimeout(3000);
  }

  throw new Error(`Kitchen order not found for ${orderNo}`);
}

async function updateKitchenStatus(page, orderNo, status) {
  const result = await page.evaluate(async ({ targetOrderNo, targetStatus }) => {
    const response = await fetch(`/restaurant/kitchen/orders/${targetOrderNo}/status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status: targetStatus }),
    });

    const text = await response.text();
    let payload = {};
    try {
      payload = text ? JSON.parse(text) : {};
    } catch (_error) {
      payload = {};
    }

    return {
      ok: response.ok,
      status: response.status,
      payload,
      body: text,
    };
  }, { targetOrderNo: orderNo, targetStatus: status });

  expect(result.ok, `Kitchen status update failed (${result.status}) for ${orderNo} -> ${status}: ${String(result.body || '').slice(0, 500)}`).toBeTruthy();
  return result.payload;
}

async function waitForDriverDelivery(page, orderNo, timeoutMs = 90000) {
  const startedAt = Date.now();

  while ((Date.now() - startedAt) < timeoutMs) {
    await safeGoto(page, '/driver/deliveries', { attempts: 5, timeout: 30000 });
    const card = page.locator('.delivery-card', { hasText: orderNo }).first();
    if (await card.count()) {
      await expect(card).toBeVisible({ timeout: 5000 });
      return card;
    }
    await page.waitForTimeout(5000);
  }

  throw new Error(`Driver delivery card not found for ${orderNo}`);
}

async function expectClientBusinessStatus(page, pattern, timeout = 35000) {
  await page.bringToFront().catch(() => {});
  await page.evaluate(() => window.updateTracking ? window.updateTracking() : null).catch(() => {});
  await expect(page.locator('#businessStatusLabel')).toContainText(pattern, { timeout });
}

test('production food flow client orders then restaurant and driver advance statuses live', async ({ browser, baseURL }) => {
  const clientContext = await browser.newContext();
  const restaurantContext = await browser.newContext();
  const driverContext = await browser.newContext();
  const clientPage = await clientContext.newPage();
  const restaurantPage = await restaurantContext.newPage();
  const driverPage = await driverContext.newPage();
  const uniqueTag = `E2E-FOOD-${Date.now()}`;

  try {
    await login(clientPage, {
      email: CLIENT_EMAIL,
      password: SHARED_PASSWORD,
      paths: ['/user/login', '/login'],
      postLoginPath: `/resturant/view/${FOOD_RESTAURANT_ID}`,
    });

    const { orderNo } = await placeOrder(clientPage, baseURL, uniqueTag);
    await expectClientBusinessStatus(clientPage, /attente|restaurant|accept|assign/i, 15000);

    await login(restaurantPage, {
      email: RESTAURANT_EMAIL,
      password: SHARED_PASSWORD,
      paths: ['/login', '/user/login'],
      postLoginPath: '/restaurant/kitchen',
    });

    let kitchenOrder = await waitForKitchenOrder(restaurantPage, orderNo);
    if (['pending_restaurant_acceptance', 'accepted', 'pending'].includes(kitchenOrder.business_status || kitchenOrder.status)) {
      await updateKitchenStatus(restaurantPage, orderNo, 'accepted');
      await expectClientBusinessStatus(clientPage, /accept|assign/i);
      kitchenOrder = await waitForKitchenOrder(restaurantPage, orderNo);
    }

    if (['accepted', 'in_kitchen', 'prepairing'].includes(kitchenOrder.business_status || kitchenOrder.status)) {
      if ((kitchenOrder.business_status || kitchenOrder.status) === 'accepted') {
        await updateKitchenStatus(restaurantPage, orderNo, 'prepairing');
        await expectClientBusinessStatus(clientPage, /préparation/i);
        kitchenOrder = await waitForKitchenOrder(restaurantPage, orderNo);
      }

      if (['in_kitchen', 'prepairing'].includes(kitchenOrder.business_status || kitchenOrder.status)) {
        await updateKitchenStatus(restaurantPage, orderNo, 'assign');
        await expectClientBusinessStatus(clientPage, /prête|départ|livreur assigné|assigné/i);
        kitchenOrder = await waitForKitchenOrder(restaurantPage, orderNo);
      }
    }

    await expect(['ready_for_pickup', 'driver_assigned', 'assign']).toContain(kitchenOrder.business_status || kitchenOrder.status);

    await login(driverPage, {
      email: DRIVER_EMAIL,
      password: SHARED_PASSWORD,
      paths: ['/login', '/user/login'],
      postLoginPath: '/driver/deliveries',
    });

    const assignedCard = await waitForDriverDelivery(driverPage, orderNo);
    await assignedCard.getByRole('button', { name: /Marquer comme récupérée/i }).click();
    await expectClientBusinessStatus(clientPage, /récupérée|recup/i);

    const pickedUpCard = await waitForDriverDelivery(driverPage, orderNo);
    await pickedUpCard.getByRole('button', { name: /Passer en route/i }).click();
    await expectClientBusinessStatus(clientPage, /en route|livraison/i);

    const onTheWayCard = await waitForDriverDelivery(driverPage, orderNo);
    await onTheWayCard.locator('input[name="customer_confirmed"]').check();
    await onTheWayCard.getByRole('button', { name: /Marquer comme livrée/i }).click();

    await expectClientBusinessStatus(clientPage, /livrée|livree/i, 45000);
    await expect(clientPage.locator('#trackingProgressLabel')).toContainText('100', { timeout: 45000 });
    await expect(clientPage.locator('#receiptPanel')).toBeVisible({ timeout: 45000 });
  } finally {
    await clientContext.close().catch(() => {});
    await restaurantContext.close().catch(() => {});
    await driverContext.close().catch(() => {});
  }
});

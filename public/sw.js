/**
 * BantuDelice Service Worker — T1.3 Offline-first + Checkout retry
 *
 * Stratégie :
 * - Assets statiques (CSS/JS/fonts/images) → Cache First (stale-while-revalidate)
 * - Pages HTML → Network First avec fallback offline
 * - POST /checkout/api → Background Sync (retry quand réseau disponible)
 */

const CACHE_VERSION   = '20260517-1';
const CACHE_NAME      = 'bd-shell-' + CACHE_VERSION;
const OFFLINE_URL     = '/offline';
const CHECKOUT_QUEUE  = 'bd-checkout-queue';

const PRECACHE_URLS = [
    '/',
    '/offline',
    '/frontend/css/modern.css',
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS.map(u => new Request(u, { cache: 'reload' }))))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

// ── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET vers l'API checkout (gérées par Background Sync)
    if (request.method !== 'GET') {
        // Intercepter POST /checkout/api pour Background Sync
        if (url.pathname === '/checkout/api' || url.pathname.endsWith('/checkout/api')) {
            event.respondWith(handleCheckoutPost(request));
        }
        return;
    }

    // Assets statiques (CSS/JS/fonts/images) → Cache First
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Pages HTML → Network First
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirstHtml(request));
        return;
    }

    // Autres GET → Network with cache fallback
    event.respondWith(networkWithCacheFallback(request));
});

// ── Background Sync ───────────────────────────────────────────────────────────
self.addEventListener('sync', event => {
    if (event.tag === CHECKOUT_QUEUE) {
        event.waitUntil(replayPendingCheckouts());
    }
});

// ── Push Notifications ────────────────────────────────────────────────────────
self.addEventListener('push', event => {
    const data = event.data?.json() || {};
    event.waitUntil(
        self.registration.showNotification(data.title || 'BantuDelice', {
            body:    data.body  || 'Vous avez une nouvelle notification.',
            icon:    data.icon  || '/images/5-512.png',
            badge:   '/images/5-512.png',
            tag:     data.tag   || 'bd-notif',
            data:    data.url   ? { url: data.url } : {},
            vibrate: [200, 100, 200],
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(clients.openWindow(url));
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function isStaticAsset(url) {
    return /\.(css|js|woff2?|ttf|otf|eot|png|jpe?g|gif|svg|ico|webp|mp3|mp4)(\?.*)?$/.test(url.pathname);
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503 });
    }
}

async function networkFirstHtml(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        const offlinePage = await caches.match(OFFLINE_URL);
        return offlinePage || new Response('<h1>Hors ligne</h1>', {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    }
}

async function networkWithCacheFallback(request) {
    try {
        return await fetch(request);
    } catch {
        const cached = await caches.match(request);
        return cached || new Response('', { status: 503 });
    }
}

async function handleCheckoutPost(request) {
    try {
        const response = await fetch(request.clone());
        return response;
    } catch {
        // Réseau indisponible → sauvegarder dans IndexedDB et déclencher sync
        const body = await request.clone().text();
        const headers = {};
        request.headers.forEach((v, k) => { headers[k] = v; });

        await savePendingCheckout({
            url:     request.url,
            method:  request.method,
            headers,
            body,
            savedAt: Date.now(),
        });

        // Demander un Background Sync dès que le réseau revient
        if ('sync' in self.registration) {
            await self.registration.sync.register(CHECKOUT_QUEUE);
        }

        return new Response(JSON.stringify({
            offline:  true,
            queued:   true,
            message:  'Votre commande a été sauvegardée et sera envoyée dès le retour du réseau.',
        }), {
            status:  202,
            headers: { 'Content-Type': 'application/json' },
        });
    }
}

async function replayPendingCheckouts() {
    const pending = await loadPendingCheckouts();
    for (const item of pending) {
        try {
            const response = await fetch(item.url, {
                method:  item.method,
                headers: item.headers,
                body:    item.body,
            });
            if (response.ok || response.status < 500) {
                await removePendingCheckout(item.id);
                // Notifier le client du succès
                const clients = await self.clients.matchAll({ type: 'window' });
                for (const client of clients) {
                    client.postMessage({ type: 'bd:checkout-replayed', id: item.id });
                }
            }
        } catch {
            // Réseau encore indisponible, on réessaiera au prochain sync
        }
    }
}

// ── IndexedDB minimal pour stocker les checkouts en attente ──────────────────

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open('bd-offline', 1);
        req.onupgradeneeded = e => {
            e.target.result.createObjectStore('checkouts', { keyPath: 'id', autoIncrement: true });
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

async function savePendingCheckout(data) {
    const db    = await openDb();
    const tx    = db.transaction('checkouts', 'readwrite');
    const store = tx.objectStore('checkouts');
    return new Promise((resolve, reject) => {
        const req = store.add(data);
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

async function loadPendingCheckouts() {
    const db    = await openDb();
    const tx    = db.transaction('checkouts', 'readonly');
    const store = tx.objectStore('checkouts');
    return new Promise((resolve, reject) => {
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror   = () => reject(req.error);
    });
}

async function removePendingCheckout(id) {
    const db    = await openDb();
    const tx    = db.transaction('checkouts', 'readwrite');
    const store = tx.objectStore('checkouts');
    return new Promise((resolve, reject) => {
        const req = store.delete(id);
        req.onsuccess = () => resolve();
        req.onerror   = () => reject(req.error);
    });
}

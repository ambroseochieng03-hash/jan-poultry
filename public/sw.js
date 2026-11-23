/* JAN Poultry service worker.
   - Pages are always fetched from the network (carts and orders are private and must be fresh).
   - If the phone is offline, a friendly offline page is shown instead of a browser error.
   - Images, CSS and JS under /assets and /uploads are served from cache and refreshed in the background.
   Bump VERSION to force every phone to drop old caches. */
const VERSION = 'v1';
const CORE = 'jp-core-' + VERSION;
const STATIC = 'jp-static-' + VERSION;
const CORE_FILES = ['/offline.html', '/assets/icons/icon-192.png', '/assets/img/logo.svg'];
const STATIC_LIMIT = 120;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CORE).then((c) => c.addAll(CORE_FILES)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CORE && k !== STATIC).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

async function trim(cacheName, max) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - max; i++) { await cache.delete(keys[i]); }
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') { return; }
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) { return; }

    // Page loads: network only, offline page as the fallback.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // Static files: show the cached copy now, refresh it in the background.
    if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/uploads/products/')) {
        event.respondWith(
            caches.open(STATIC).then(async (cache) => {
                const cached = await cache.match(req);
                const network = fetch(req).then((res) => {
                    if (res && res.ok) { cache.put(req, res.clone()); trim(STATIC, STATIC_LIMIT); }
                    return res;
                }).catch(() => cached);
                return cached || network;
            })
        );
    }
    // Everything else (forms, JSON endpoints) goes straight to the network.
});

/* Web push: show the notification, and open the right page when it is tapped. */
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data ? event.data.text() : '' }; }
    event.waitUntil(self.registration.showNotification(data.title || 'JAN Poultry', {
        body: data.body || '',
        icon: '/assets/icons/icon-192.png',
        badge: '/assets/icons/badge-96.png',
        tag: data.tag || undefined,
        renotify: !!data.tag,
        data: { url: data.url || '/orders' }
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL((event.notification.data && event.notification.data.url) || '/orders', self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
        for (const c of list) { if (c.url === target && 'focus' in c) { return c.focus(); } }
        for (const c of list) { if ('navigate' in c && 'focus' in c) { return c.focus().then((w) => w.navigate(target)); } }
        return self.clients.openWindow(target);
    }));
});

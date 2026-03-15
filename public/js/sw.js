/* FitForFaith Service Worker — PWA offline shell caching */
const CACHE_NAME = 'fff-v1';
const STATIC_ASSETS = [
  '/public/css/app.css',
  '/public/js/app.js',
  '/public/icons/icon-192.png',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Network-first for HTML pages (always fresh content)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() =>
        caches.match('/') // fallback to cached homepage shell
      )
    );
    return;
  }

  // Cache-first for static assets
  if (STATIC_ASSETS.some(asset => url.pathname.endsWith(asset.replace('/public', '')))) {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request))
    );
    return;
  }

  // Default: network
  event.respondWith(fetch(event.request));
});

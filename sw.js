const WZ_CACHE = 'wedding-za-shell-v5';

const SHELL = [
  './offline.html',
  './assets/css/app.css?v=4.0.0',
  './assets/css/vision.css?v=4.0.4',
  './assets/css/marketplace.css?v=1.0.0',
  './assets/js/app.js?v=4.0.0',
  './assets/js/vision.js?v=4.0.4',
  './assets/js/marketplace.js?v=1.0.0',
  './assets/images/favicon.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(WZ_CACHE)
      .then((cache) => cache.addAll(SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key !== WZ_CACHE)
            .map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.origin !== self.location.origin) {
    return;
  }

  if (
    url.pathname.includes('/api/') ||
    url.pathname.includes('/crm/') ||
    url.pathname.includes('/admin/')
  ) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() =>
        caches.match('./offline.html')
      )
    );

    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }

      return fetch(request).then((response) => {
        if (
          response.ok &&
          ['style', 'script', 'image', 'font'].includes(
            request.destination
          )
        ) {
          const copy = response.clone();

          caches
            .open(WZ_CACHE)
            .then((cache) => cache.put(request, copy));
        }

        return response;
      });
    })
  );
});

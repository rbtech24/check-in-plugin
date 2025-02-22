const CACHE_NAME = 'tech-checkin-v1';
const OFFLINE_URL = '/tech-check-in-form/offline.html';

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([
        '/tech-check-in-form/',
        OFFLINE_URL,
        '/tech-check-in-form/wp-content/plugins/tech-checkin-maps/public/css/style.css',
        '/tech-check-in-form/wp-content/plugins/tech-checkin-maps/public/js/pwa-installer.js'
      ]);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        return response || fetch(event.request);
      })
      .catch(() => {
        return caches.match(OFFLINE_URL);
      })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
});
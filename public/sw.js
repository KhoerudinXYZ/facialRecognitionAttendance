// Minimal service worker - only exists so the browser considers the app
// installable (Add to Home Screen). No offline caching: this app depends
// on live server data (absensi, pengaturan) so caching responses would
// show stale state, which is worse than no offline support at all.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});

const CACHE_NAME = 'master-calendar-v1';
const DYNAMIC_CACHE_NAME = 'master-calendar-dynamic-v1';

// Install event: Skip waiting to activate immediately
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== DYNAMIC_CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Fetch event: Network First for HTML, Cache First for Assets
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip browser-sync or dev tools
    if (url.pathname.includes('browser-sync')) return;
    if (url.protocol.startsWith('chrome-extension')) return;

    // API requests: Network Only (or handled by app logic)
    // We generally want API to fail fast if offline so the app can handle it (queueing)
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // HTML / Navigation Requests: Network First, falling back to Cache
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    return caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                })
                .catch(() => {
                    return caches.match(event.request).then((cachedResponse) => {
                        if (cachedResponse) return cachedResponse;
                        // Optionally return a custom offline page here
                        // return caches.match('/offline.html');
                    });
                })
        );
        return;
    }

    // Static Assets (JS, CSS, Images, Fonts): Stale While Revalidate
    // This means we serve from cache immediately, but update the cache in background
    if (
        url.pathname.match(/\.(js|css|png|jpg|jpeg|svg|woff|woff2|ttf|eot|ico)$/) ||
        url.pathname.startsWith('/build/') // Vite build assets
    ) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                        cache.put(event.request, networkResponse.clone());
                    });
                    return networkResponse;
                });
                return cachedResponse || fetchPromise;
            })
        );
        return;
    }
});

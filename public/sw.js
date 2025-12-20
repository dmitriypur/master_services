const CACHE_NAME = 'master-calendar-v4';
const DYNAMIC_CACHE_NAME = 'master-calendar-dynamic-v4';

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
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // HTML / Navigation Requests: Network First, falling back to Cache
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    // Check if we received a valid response
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse;
                    }

                    const responseToCache = networkResponse.clone();
                    const responseToCacheClean = networkResponse.clone();

                    caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                        // 1. Cache the actual request (with params)
                        cache.put(event.request, responseToCache);

                        // 2. Cache the clean URL (without params) to ensure fallback works
                        // This handles Telegram's dynamic query parameters
                        if (url.search) {
                            const cleanUrl = new URL(event.request.url);
                            cleanUrl.search = '';
                            cache.put(cleanUrl.toString(), responseToCacheClean);
                        }
                    });

                    return networkResponse;
                })
                .catch(() => {
                    // Offline fallback
                    return caches.match(event.request, { ignoreSearch: true }).then((cachedResponse) => {
                        if (cachedResponse) return cachedResponse;

                        // Try finding the clean URL version explicitly
                        const cleanUrl = new URL(event.request.url);
                        cleanUrl.search = '';
                        return caches.match(cleanUrl.toString()).then((cleanResponse) => {
                            if (cleanResponse) return cleanResponse;

                            // Final fallback to the main entry point
                            return caches.match('/master/calendar', { ignoreSearch: true });
                        });
                    });
                })
        );
        return;
    }

    // Static Assets (JS, CSS, Images, Fonts): Stale While Revalidate
    if (
        url.pathname.match(/\.(js|css|png|jpg|jpeg|svg|woff|woff2|ttf|eot|ico)$/) ||
        url.pathname.startsWith('/build/') // Vite build assets
    ) {
        event.respondWith(
            caches.match(event.request, { ignoreSearch: true }).then((cachedResponse) => {
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    // Validate response before caching
                    if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                        caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                            cache.put(event.request, networkResponse.clone());
                        });
                    }
                    return networkResponse;
                });
                return cachedResponse || fetchPromise;
            })
        );
        return;
    }
});

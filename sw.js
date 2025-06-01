// FitGrit Service Worker
// Provides offline functionality and caching for PWA

const CACHE_NAME = 'fitgrit-v1.0.0';
const STATIC_CACHE = 'fitgrit-static-v1';
const DYNAMIC_CACHE = 'fitgrit-dynamic-v1';

// Files to cache for offline use
const STATIC_ASSETS = [
  '/',
  '/offline.html',
  '/assets/css/style.css',
  '/assets/css/mobile.css',
  '/assets/css/charts.css',
  '/assets/js/main.js',
  '/assets/js/charts.js',
  '/assets/js/pwa.js',
  '/assets/js/validation.js',
  '/assets/images/logo.png',
  '/assets/images/icons/icon-72x72.png',
  '/assets/images/icons/icon-96x96.png',
  '/assets/images/icons/icon-128x128.png',
  '/assets/images/icons/icon-144x144.png',
  '/assets/images/icons/icon-152x152.png',
  '/assets/images/icons/icon-192x192.png',
  '/assets/images/icons/icon-384x384.png',
  '/assets/images/icons/icon-512x512.png',
  '/manifest.json'
];

// URLs that should always fetch from network
const NETWORK_FIRST_URLS = [
  '/api/',
  '/logout.php',
  '/includes/',
  '/login',
  '/register'
];

// URLs that should never be cached (always fetch fresh)
const NEVER_CACHE_URLS = [
  '/logout.php',
  '/api/',
  '/includes/'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('FitGrit Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('FitGrit Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('FitGrit Service Worker: Static assets cached');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('FitGrit Service Worker: Cache installation failed', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('FitGrit Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(cacheName => cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE)
            .map(cacheName => {
              console.log('FitGrit Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('FitGrit Service Worker: Activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - handle network requests
self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);
  
  // Skip cross-origin requests
  if (requestUrl.origin !== location.origin) {
    return;
  }
  
  // Skip non-GET requests for caching
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Never cache certain URLs
  if (NEVER_CACHE_URLS.some(url => requestUrl.pathname.startsWith(url))) {
    event.respondWith(fetch(event.request, { redirect: 'follow' }));
    return;
  }
  
  // Network first for API calls and dynamic content
  if (NETWORK_FIRST_URLS.some(url => requestUrl.pathname.startsWith(url))) {
    event.respondWith(networkFirst(event.request));
    return;
  }
  
  // Cache first for static assets
  if (STATIC_ASSETS.includes(requestUrl.pathname) || 
      requestUrl.pathname.startsWith('/assets/')) {
    event.respondWith(cacheFirst(event.request));
    return;
  }
  
  // Special handling for PHP pages that might redirect
  if (requestUrl.pathname.endsWith('.php') || requestUrl.pathname === '/') {
    event.respondWith(handlePageRequest(event.request));
    return;
  }
  
  // Default to network
  event.respondWith(fetch(event.request, { redirect: 'follow' }));
});

// Handle page requests with potential redirects
async function handlePageRequest(request) {
  try {
    const networkResponse = await fetch(request, {
      redirect: 'follow'
    });
    
    // If it's a redirect (3xx status), don't cache and just return
    if (networkResponse.status >= 300 && networkResponse.status < 400) {
      return networkResponse;
    }
    
    // Only cache successful, non-redirected responses
    if (networkResponse.ok && 
        networkResponse.type === 'basic' && 
        networkResponse.url === request.url) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('FitGrit Service Worker: Page request failed, trying cache');
    
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // For navigation requests, try to return the login page
    if (request.mode === 'navigate') {
      const loginPage = await caches.match('/');
      if (loginPage) {
        return loginPage;
      }
    }
    
    return new Response('Offline - Please check your connection', {
      status: 503,
      statusText: 'Service Unavailable',
      headers: { 'Content-Type': 'text/plain' }
    });
  }
}

// Cache first strategy
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request, {
      redirect: 'follow'
    });
    
    if (networkResponse.ok && networkResponse.type === 'basic') {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('FitGrit Service Worker: Cache first failed', error);
    return new Response('Offline - Content not available', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Network first strategy
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request, {
      redirect: 'follow'
    });
    
    // Only cache successful non-redirected responses
    if (networkResponse.ok && 
        networkResponse.type === 'basic' && 
        networkResponse.status < 300 &&
        request.method === 'GET') {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('FitGrit Service Worker: Network failed, trying cache');
    
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      return caches.match('/offline.html') || 
             new Response('Offline - Please check your connection', {
               status: 503,
               statusText: 'Service Unavailable'
             });
    }
    
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Stale while revalidate strategy
async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request, {
    redirect: 'follow'
  }).then(networkResponse => {
    // Only cache successful, non-redirected responses
    if (networkResponse.ok && 
        networkResponse.type === 'basic' && 
        networkResponse.status < 300) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  }).catch(error => {
    console.log('FitGrit Service Worker: Network failed for', request.url);
    return cachedResponse || new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  });
  
  return cachedResponse || fetchPromise;
}

// Background sync for offline data
self.addEventListener('sync', event => {
  console.log('FitGrit Service Worker: Background sync triggered');
  
  if (event.tag === 'sync-data') {
    event.waitUntil(syncOfflineData());
  }
});

// Sync offline data when connection is restored
async function syncOfflineData() {
  try {
    // Get offline data from IndexedDB or localStorage
    const clients = await self.clients.matchAll();
    
    clients.forEach(client => {
      client.postMessage({
        type: 'SYNC_DATA',
        message: 'Syncing offline data...'
      });
    });
    
    console.log('FitGrit Service Worker: Data sync completed');
  } catch (error) {
    console.error('FitGrit Service Worker: Data sync failed', error);
  }
}

// Push notification handling
self.addEventListener('push', event => {
  console.log('FitGrit Service Worker: Push notification received');
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from FitGrit',
    icon: '/assets/images/icons/icon-192x192.png',
    badge: '/assets/images/icons/icon-96x96.png',
    tag: 'fitgrit-notification',
    requireInteraction: false,
    actions: [
      {
        action: 'open',
        title: 'Open FitGrit'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('FitGrit', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  console.log('FitGrit Service Worker: Notification clicked');
  
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Message handling from main thread
self.addEventListener('message', event => {
  console.log('FitGrit Service Worker: Message received', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(DYNAMIC_CACHE)
        .then(cache => cache.addAll(event.data.urls))
    );
  }
});

console.log('FitGrit Service Worker: Loaded successfully');
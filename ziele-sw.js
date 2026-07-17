// Service-Worker für das Ziele-Dashboard.
// Fängt bewusst NUR die Ziele-Dateien ab, damit die übrigen Apps
// auf ttbb-maker.github.io unberührt bleiben (kein Site-weites Caching).
var CACHE = 'ziele-v1';
var ASSETS = [
  'ziele.html',
  'ziele.webmanifest',
  'ziele-icon.svg',
  'ziele-icon-maskable.svg'
];

self.addEventListener('install', function(e){
  self.skipWaiting();
  e.waitUntil(caches.open(CACHE).then(function(c){ return c.addAll(ASSETS); }));
});

self.addEventListener('activate', function(e){
  e.waitUntil(
    caches.keys().then(function(keys){
      return Promise.all(keys.filter(function(k){ return k !== CACHE; })
        .map(function(k){ return caches.delete(k); }));
    }).then(function(){ return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function(e){
  if (e.request.method !== 'GET') return;
  var url = new URL(e.request.url);
  if (url.origin !== self.location.origin) return;
  var gehoert = ASSETS.some(function(a){
    return url.pathname === '/' + a || url.pathname.endsWith('/' + a) || url.pathname.endsWith(a);
  });
  if (!gehoert) return; // alles andere normal übers Netz laden

  // Stale-while-revalidate: sofort aus Cache, im Hintergrund aktualisieren.
  e.respondWith(
    caches.match(e.request).then(function(cached){
      var netz = fetch(e.request).then(function(res){
        if (res && res.status === 200) {
          var copy = res.clone();
          caches.open(CACHE).then(function(c){ c.put(e.request, copy); });
        }
        return res;
      }).catch(function(){ return cached; });
      return cached || netz;
    })
  );
});

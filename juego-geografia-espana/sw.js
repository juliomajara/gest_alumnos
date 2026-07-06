const CACHE_NAME = 'geoespana-v2';

const ESTATICOS = [
  'assets/css/style.css',
  'assets/js/stats.js',
  'assets/js/sound.js',
  'assets/js/map-zoom.js',
  'assets/js/game.js',
  'assets/img/favicon.svg',
  'assets/img/icon-192.png',
  'assets/img/icon-512.png',
  'assets/img/icon-maskable-192.png',
  'assets/img/icon-maskable-512.png',
  'assets/svg/mapa-provincias.svg',
  'assets/svg/mapa-ccaa.svg',
  'assets/svg/mapa-rios.svg',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function (cache) { return cache.addAll(ESTATICOS); })
      .catch(function () { /* si algún fichero falla no bloquea la instalación */ })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (nombres) {
      return Promise.all(
        nombres
          .filter(function (nombre) { return nombre !== CACHE_NAME; })
          .map(function (nombre) { return caches.delete(nombre); })
      );
    })
  );
  self.clients.claim();
});

// Solo se cachean ficheros estáticos (CSS/JS/SVG/PNG). Las páginas PHP
// (login, juego, ranking...) siempre van directas a la red: dependen de la
// sesión y de datos que cambian, y no deben servirse desde caché.
self.addEventListener('fetch', function (event) {
  const peticion = event.request;
  const url = new URL(peticion.url);

  if (peticion.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }
  if (!/\.(css|js|svg|png|jpe?g|webp|woff2?)$/.test(url.pathname)) {
    return;
  }

  event.respondWith(
    caches.match(peticion).then(function (cacheado) {
      const actualizar = fetch(peticion)
        .then(function (respuesta) {
          if (respuesta && respuesta.status === 200) {
            const copia = respuesta.clone();
            caches.open(CACHE_NAME).then(function (cache) { cache.put(peticion, copia); });
          }
          return respuesta;
        })
        .catch(function () { return cacheado; });

      return cacheado || actualizar;
    })
  );
});

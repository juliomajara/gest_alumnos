window.GeoStats = (function () {
  var PREFIX = 'geoespana:';

  function claveCompleta(k) {
    return PREFIX + k;
  }

  function obtener(k) {
    try {
      var raw = localStorage.getItem(claveCompleta(k));
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function guardar(k, datos) {
    try {
      localStorage.setItem(claveCompleta(k), JSON.stringify(datos));
    } catch (e) {
      /* localStorage no disponible: se ignora silenciosamente */
    }
  }

  function registrar(k, aciertos, total) {
    var pct = total > 0 ? Math.round((aciertos / total) * 100) : 0;
    var actual = obtener(k);
    var esRecord = !actual || aciertos > actual.aciertos || (aciertos === actual.aciertos && pct > actual.pct);
    if (esRecord) {
      guardar(k, { aciertos: aciertos, total: total, pct: pct, fecha: Date.now() });
    }
    return esRecord;
  }

  function resumen(k) {
    var d = obtener(k);
    if (!d) return 'Aún sin jugar';
    return 'Mejor: ' + d.aciertos + '/' + d.total + ' · ' + d.pct + '%';
  }

  function sonidoActivo() {
    var v = localStorage.getItem(PREFIX + 'sonido');
    return v === null ? true : v === '1';
  }

  function setSonidoActivo(activo) {
    try {
      localStorage.setItem(PREFIX + 'sonido', activo ? '1' : '0');
    } catch (e) { /* ignorar */ }
  }

  return { registrar: registrar, resumen: resumen, obtener: obtener, sonidoActivo: sonidoActivo, setSonidoActivo: setSonidoActivo };
})();

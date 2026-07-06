window.GeoStats = (function () {
  var PREFIX = 'geoespana:';

  function sonidoActivo() {
    var v = localStorage.getItem(PREFIX + 'sonido');
    return v === null ? true : v === '1';
  }

  function setSonidoActivo(activo) {
    try {
      localStorage.setItem(PREFIX + 'sonido', activo ? '1' : '0');
    } catch (e) { /* ignorar */ }
  }

  return { sonidoActivo: sonidoActivo, setSonidoActivo: setSonidoActivo };
})();

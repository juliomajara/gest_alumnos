window.GeoSound = (function () {
  var ctx = null;

  function getCtx() {
    if (!ctx) {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      ctx = new AC();
    }
    return ctx;
  }

  function tono(freq, inicio, duracion, tipo, volumen) {
    var c = getCtx();
    if (!c) return;
    var osc = c.createOscillator();
    var gain = c.createGain();
    osc.type = tipo || 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0, c.currentTime + inicio);
    gain.gain.linearRampToValueAtTime(volumen, c.currentTime + inicio + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.001, c.currentTime + inicio + duracion);
    osc.connect(gain);
    gain.connect(c.destination);
    osc.start(c.currentTime + inicio);
    osc.stop(c.currentTime + inicio + duracion + 0.02);
  }

  function activo() {
    return window.GeoStats ? GeoStats.sonidoActivo() : true;
  }

  function correcto() {
    if (!activo()) return;
    tono(523.25, 0, 0.11, 'triangle', 0.09);
    tono(783.99, 0.09, 0.16, 'triangle', 0.09);
  }

  function incorrecto() {
    if (!activo()) return;
    tono(196, 0, 0.22, 'sawtooth', 0.06);
  }

  function victoria() {
    if (!activo()) return;
    [523.25, 659.25, 783.99, 1046.5].forEach(function (f, i) {
      tono(f, i * 0.1, 0.18, 'triangle', 0.08);
    });
  }

  return { correcto: correcto, incorrecto: incorrecto, victoria: victoria };
})();

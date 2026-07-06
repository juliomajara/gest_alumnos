(function () {
  'use strict';

  function barajar(array) {
    var copia = array.slice();
    for (var i = copia.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = copia[i]; copia[i] = copia[j]; copia[j] = tmp;
    }
    return copia;
  }

  function elegirDistractores(items, correcto, cantidad) {
    var candidatos = barajar(items.filter(function (n) { return n !== correcto; }));
    return candidatos.slice(0, cantidad);
  }

  function lanzarConfeti() {
    var colores = ['#e8622c', '#f4b942', '#1f6f78', '#2fae66', '#8b5fbf'];
    for (var i = 0; i < 26; i++) {
      var pieza = document.createElement('div');
      pieza.className = 'confeti-pieza';
      pieza.style.left = (Math.random() * 100) + 'vw';
      pieza.style.background = colores[i % colores.length];
      pieza.style.animationDuration = (1.6 + Math.random() * 1.2) + 's';
      pieza.style.animationDelay = (Math.random() * 0.25) + 's';
      document.body.appendChild(pieza);
      (function (el) {
        setTimeout(function () { el.remove(); }, 3200);
      })(pieza);
    }
  }

  window.iniciarJuego = function (config) {
    var tipo = config.tipo;
    var modo = config.modo;
    var items = config.items;
    var storageKey = tipo + '-' + modo;

    var mapWrap = document.getElementById('map-wrap');
    var mapInner = document.getElementById('map-inner');
    var svg = mapInner.querySelector('svg');
    var regiones = svg.querySelectorAll('.region');

    var barraRelleno = document.getElementById('progreso-relleno');
    var puntosActualEl = document.getElementById('puntos-actual');
    var puntosTotalEl = document.getElementById('puntos-total');
    var rachaEl = document.getElementById('racha');
    var rachaValorEl = document.getElementById('racha-valor');

    var promptBanner = document.getElementById('prompt-banner');
    var promptNombre = document.getElementById('prompt-nombre');
    var opcionesGrid = document.getElementById('opciones-grid');
    var feedbackToast = document.getElementById('feedback-toast');

    var overlayFinal = document.getElementById('overlay-final');
    var finalPct = document.getElementById('final-pct');
    var finalDetalle = document.getElementById('final-detalle');
    var finalRecord = document.getElementById('final-record');
    var finalFallos = document.getElementById('final-fallos');
    var btnReintentar = document.getElementById('btn-reintentar');
    var btnSonido = document.getElementById('btn-sonido');

    var total = items.length;
    var cola = barajar(items);
    var indice = 0;
    var aciertos = 0;
    var rachaActual = 0;
    var fallos = [];
    var nombreActual = null;
    var esperandoSiguiente = false;

    var zoom = crearMapZoom(mapWrap, mapInner, {
      onTap: function (x, y) {
        if (modo !== 'tocar' || esperandoSiguiente) return;
        var el = document.elementFromPoint(x, y);
        if (!el || !el.classList || !el.classList.contains('region')) return;
        manejarRespuestaMapa(el);
      }
    });
    document.getElementById('zoom-in').addEventListener('click', zoom.zoomIn);
    document.getElementById('zoom-out').addEventListener('click', zoom.zoomOut);
    document.getElementById('zoom-reset').addEventListener('click', zoom.reset);

    function limpiarRegiones() {
      regiones.forEach(function (r) {
        r.classList.remove('es-resaltada', 'es-correcta', 'es-incorrecta', 'es-objetivo');
      });
    }

    function regionPorNombre(nombre) {
      for (var i = 0; i < regiones.length; i++) {
        if (regiones[i].dataset.name === nombre) return regiones[i];
      }
      return null;
    }

    function actualizarMarcador() {
      puntosActualEl.textContent = aciertos;
      puntosTotalEl.textContent = total;
      if (rachaActual >= 3) {
        rachaEl.classList.remove('oculta');
        rachaValorEl.textContent = rachaActual;
      } else {
        rachaEl.classList.add('oculta');
      }
    }

    function mostrarToast(texto, tipoToast) {
      feedbackToast.textContent = texto;
      feedbackToast.className = 'feedback-toast visible ' + tipoToast;
      clearTimeout(mostrarToast._t);
      mostrarToast._t = setTimeout(function () {
        feedbackToast.classList.remove('visible');
      }, 900);
    }

    function siguientePregunta() {
      esperandoSiguiente = false;
      barraRelleno.style.width = ((indice) / total * 100) + '%';

      if (cola.length === 0) {
        return finalizarJuego();
      }
      nombreActual = cola.shift();
      indice++;
      limpiarRegiones();

      if (modo === 'reconocer') {
        var objetivo = regionPorNombre(nombreActual);
        if (objetivo) objetivo.classList.add('es-resaltada');
        renderOpciones();
      } else {
        promptNombre.textContent = nombreActual;
        promptNombre.style.animation = 'none';
        void promptNombre.offsetWidth;
        promptNombre.style.animation = '';
      }
    }

    function renderOpciones() {
      var opciones = barajar([nombreActual].concat(elegirDistractores(items, nombreActual, 3)));
      opcionesGrid.innerHTML = '';
      opciones.forEach(function (nombre) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'opcion-btn';
        btn.textContent = nombre;
        btn.dataset.name = nombre;
        opcionesGrid.appendChild(btn);
      });
    }

    function registrarResultado(esCorrecta) {
      if (esCorrecta) {
        aciertos++;
        rachaActual++;
        if (rachaActual > 0 && rachaActual % 5 === 0) lanzarConfeti();
      } else {
        rachaActual = 0;
        if (fallos.indexOf(nombreActual) === -1) fallos.push(nombreActual);
      }
      actualizarMarcador();
    }

    function manejarRespuestaOpcion(btn) {
      if (esperandoSiguiente) return;
      esperandoSiguiente = true;
      var seleccion = btn.dataset.name;
      var esCorrecta = seleccion === nombreActual;

      Array.prototype.forEach.call(opcionesGrid.children, function (b) { b.disabled = true; });
      btn.classList.add(esCorrecta ? 'es-correcta' : 'es-incorrecta');
      if (!esCorrecta) {
        Array.prototype.forEach.call(opcionesGrid.children, function (b) {
          if (b.dataset.name === nombreActual) b.classList.add('es-correcta');
        });
      }

      var objetivo = regionPorNombre(nombreActual);
      if (objetivo) {
        objetivo.classList.remove('es-resaltada');
        objetivo.classList.add('es-correcta');
      }

      finalizarTurno(esCorrecta);
    }

    function manejarRespuestaMapa(el) {
      esperandoSiguiente = true;
      var seleccion = el.dataset.name;
      var esCorrecta = seleccion === nombreActual;
      el.classList.add(esCorrecta ? 'es-correcta' : 'es-incorrecta');
      if (!esCorrecta) {
        var objetivo = regionPorNombre(nombreActual);
        if (objetivo) objetivo.classList.add('es-objetivo');
      }
      finalizarTurno(esCorrecta);
    }

    function finalizarTurno(esCorrecta) {
      registrarResultado(esCorrecta);
      if (esCorrecta) {
        mostrarToast('¡Correcto! ' + nombreActual, 'correcta');
        GeoSound.correcto();
      } else {
        mostrarToast('Era ' + nombreActual, 'incorrecta');
        GeoSound.incorrecto();
      }
      setTimeout(siguientePregunta, esCorrecta ? 950 : 1350);
    }

    function finalizarJuego() {
      var pct = Math.round((aciertos / total) * 100);
      var esRecord = GeoStats.registrar(storageKey, aciertos, total);
      finalPct.textContent = pct + '%';
      finalDetalle.textContent = 'Aciertos: ' + aciertos + ' de ' + total;
      finalRecord.style.display = esRecord ? 'inline-block' : 'none';

      finalFallos.innerHTML = '';
      if (fallos.length === 0) {
        var li = document.createElement('p');
        li.className = 'resultado-detalle';
        li.textContent = '¡Puntuación perfecta, ni un fallo!';
        finalFallos.appendChild(li);
      } else {
        var titulo = document.createElement('h3');
        titulo.textContent = 'Para repasar';
        var ul = document.createElement('ul');
        fallos.forEach(function (nombre) {
          var item = document.createElement('li');
          item.textContent = nombre;
          ul.appendChild(item);
        });
        finalFallos.appendChild(titulo);
        finalFallos.appendChild(ul);
      }

      overlayFinal.classList.add('visible');
      GeoSound.victoria();
    }

    opcionesGrid.addEventListener('click', function (e) {
      var btn = e.target.closest('.opcion-btn');
      if (btn && !btn.disabled) manejarRespuestaOpcion(btn);
    });

    btnReintentar.addEventListener('click', function () {
      window.location.reload();
    });

    function actualizarIconoSonido() {
      btnSonido.textContent = GeoStats.sonidoActivo() ? '🔊' : '🔇';
    }
    btnSonido.addEventListener('click', function () {
      GeoStats.setSonidoActivo(!GeoStats.sonidoActivo());
      actualizarIconoSonido();
    });
    actualizarIconoSonido();

    if (modo === 'reconocer') {
      promptBanner.style.display = 'none';
    } else {
      opcionesGrid.style.display = 'none';
    }

    puntosTotalEl.textContent = total;
    siguientePregunta();
  };
})();

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

  var NUM_OPCIONES = 6;

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

  function formatearTiempo(ms) {
    var centesimas = Math.floor((ms % 1000) / 10);
    var segundosTotales = Math.floor(ms / 1000);
    var minutos = Math.floor(segundosTotales / 60);
    var segundos = segundosTotales % 60;
    return minutos + ':' + (segundos < 10 ? '0' : '') + segundos + '.' + (centesimas < 10 ? '0' : '') + centesimas;
  }

  window.iniciarJuego = function (config) {
    var tipo = config.tipo;
    var modo = config.modo;
    var variante = config.variante;
    var items = config.items;
    var provinciaCcaa = config.provinciaCcaa || null;
    var ccaaVecinas = config.ccaaVecinas || {};

    function construirPoolCercano(nombreActual) {
      if (tipo === 'provincias') {
        var ccaaObjetivo = provinciaCcaa[nombreActual];
        var ccaasPermitidas = [ccaaObjetivo].concat(ccaaVecinas[ccaaObjetivo] || []);
        return items.filter(function (n) {
          return n !== nombreActual && ccaasPermitidas.indexOf(provinciaCcaa[n]) !== -1;
        });
      }
      return (ccaaVecinas[nombreActual] || []).slice();
    }

    function elegirOpciones(nombreActual, cantidad) {
      var seleccion = barajar(construirPoolCercano(nombreActual)).slice(0, cantidad);
      if (seleccion.length < cantidad) {
        var usados = { };
        usados[nombreActual] = true;
        seleccion.forEach(function (n) { usados[n] = true; });
        var relleno = barajar(items.filter(function (n) { return !usados[n]; }));
        seleccion = seleccion.concat(relleno.slice(0, cantidad - seleccion.length));
      }
      return seleccion;
    }

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

    var tiraTiempo = document.getElementById('tira-tiempo');

    var overlayFinal = document.getElementById('overlay-final');
    var finalPct = document.getElementById('final-pct');
    var finalDetalle = document.getElementById('final-detalle');
    var finalCronometro = document.getElementById('final-cronometro');
    var finalAvisoAprendizaje = document.getElementById('final-aviso-aprendizaje');
    var finalLinkRanking = document.getElementById('final-link-ranking');
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

    var horaInicio = null;
    var idIntervaloReloj = null;

    function iniciarCronometro() {
      if (variante !== 'cronometrado') return;
      tiraTiempo.classList.remove('oculta');
      horaInicio = performance.now();
      idIntervaloReloj = setInterval(function () {
        tiraTiempo.textContent = '⏱ ' + formatearTiempo(performance.now() - horaInicio);
      }, 100);
    }

    function detenerCronometro() {
      if (idIntervaloReloj !== null) {
        clearInterval(idIntervaloReloj);
        idIntervaloReloj = null;
      }
      return horaInicio !== null ? Math.round(performance.now() - horaInicio) : 0;
    }

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
      var opciones = barajar([nombreActual].concat(elegirOpciones(nombreActual, NUM_OPCIONES - 1)));
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
      var tiempoMs = detenerCronometro();
      var pct = Math.round((aciertos / total) * 100);
      finalPct.textContent = pct + '%';
      finalDetalle.textContent = 'Aciertos: ' + aciertos + ' de ' + total;

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

      finalLinkRanking.href = 'ranking.php?tipo=' + tipo + '&modo=' + modo;

      if (variante === 'cronometrado') {
        finalCronometro.textContent = '⏱ Tiempo: ' + formatearTiempo(tiempoMs) + ' — guardando en el ranking…';
        finalCronometro.classList.remove('oculta');
        finalLinkRanking.classList.remove('oculta');
        fetch('guardar_puntuacion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tipo: tipo, modo: modo, aciertos: aciertos, total: total, tiempoMs: tiempoMs })
        }).then(function (r) { return r.json(); }).then(function (respuesta) {
          if (respuesta.ok) {
            finalCronometro.textContent = '⏱ Tiempo: ' + respuesta.tiempoFormateado +
              ' — Puesto ' + respuesta.posicion + ' de ' + respuesta.totalJugadores;
          } else {
            finalCronometro.textContent = '⏱ Tiempo: ' + formatearTiempo(tiempoMs) + ' — no se pudo guardar el ranking.';
          }
        }).catch(function () {
          finalCronometro.textContent = '⏱ Tiempo: ' + formatearTiempo(tiempoMs) + ' — no se pudo guardar el ranking.';
        });
      } else {
        finalAvisoAprendizaje.classList.remove('oculta');
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
    iniciarCronometro();
    siguientePregunta();
  };
})();

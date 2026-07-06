window.crearMapZoom = function (wrapEl, innerEl, opts) {
  opts = opts || {};
  var minScale = opts.minScale || 1;
  var maxScale = opts.maxScale || 5;
  var onTap = opts.onTap || function () {};

  var scale = 1, tx = 0, ty = 0;
  var pointers = new Map();
  var dragStart = null;
  var pinchStartDist = 0, pinchStartScale = 1;
  var moved = 0;
  var ultimoTap = 0;

  function apply() {
    innerEl.style.transform = 'translate(' + tx.toFixed(2) + 'px,' + ty.toFixed(2) + 'px) scale(' + scale.toFixed(4) + ')';
  }

  function clamp() {
    scale = Math.min(maxScale, Math.max(minScale, scale));
    var rect = wrapEl.getBoundingClientRect();
    var minX = rect.width - rect.width * scale;
    var minY = rect.height - rect.height * scale;
    tx = Math.min(0, Math.max(minX, tx));
    ty = Math.min(0, Math.max(minY, ty));
  }

  function reset() {
    scale = 1; tx = 0; ty = 0;
    apply();
  }

  function zoomAt(px, py, factor) {
    var nuevaScale = scale * factor;
    nuevaScale = Math.min(maxScale, Math.max(minScale, nuevaScale));
    var ratio = nuevaScale / scale;
    tx = px - (px - tx) * ratio;
    ty = py - (py - ty) * ratio;
    scale = nuevaScale;
    clamp();
    apply();
  }

  function dist(p1, p2) {
    return Math.hypot(p1.x - p2.x, p1.y - p2.y);
  }

  function centro(pts) {
    return { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 };
  }

  wrapEl.addEventListener('pointerdown', function (e) {
    wrapEl.setPointerCapture(e.pointerId);
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    moved = 0;
    if (pointers.size === 1) {
      dragStart = { x: e.clientX, y: e.clientY, tx: tx, ty: ty };
    } else if (pointers.size === 2) {
      var pts = Array.from(pointers.values());
      pinchStartDist = dist(pts[0], pts[1]);
      pinchStartScale = scale;
      dragStart = null;
    }
  });

  wrapEl.addEventListener('pointermove', function (e) {
    if (!pointers.has(e.pointerId)) return;
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

    if (pointers.size === 1 && dragStart) {
      var dx = e.clientX - dragStart.x;
      var dy = e.clientY - dragStart.y;
      moved = Math.max(moved, Math.hypot(dx, dy));
      tx = dragStart.tx + dx;
      ty = dragStart.ty + dy;
      clamp();
      apply();
    } else if (pointers.size === 2 && pinchStartDist > 0) {
      var pts = Array.from(pointers.values());
      var d = dist(pts[0], pts[1]);
      moved = 999;
      var rect = wrapEl.getBoundingClientRect();
      var c = centro(pts);
      zoomAt(c.x - rect.left, c.y - rect.top, (d / pinchStartDist) * (pinchStartScale / scale));
    }
  });

  function endPointer(e) {
    var eraSolo = pointers.size === 1;
    pointers.delete(e.pointerId);

    if (eraSolo && moved < 9) {
      var ahora = Date.now();
      if (ahora - ultimoTap < 300) {
        var rect = wrapEl.getBoundingClientRect();
        zoomAt(e.clientX - rect.left, e.clientY - rect.top, scale < 1.8 ? 2 : 1 / scale);
        ultimoTap = 0;
      } else {
        ultimoTap = ahora;
        onTap(e.clientX, e.clientY);
      }
    }
    if (pointers.size < 2) pinchStartDist = 0;
    if (pointers.size === 0) dragStart = null;
  }

  wrapEl.addEventListener('pointerup', endPointer);
  wrapEl.addEventListener('pointercancel', endPointer);

  wrapEl.addEventListener('wheel', function (e) {
    e.preventDefault();
    var rect = wrapEl.getBoundingClientRect();
    var factor = Math.exp(-e.deltaY * 0.0015);
    zoomAt(e.clientX - rect.left, e.clientY - rect.top, factor);
  }, { passive: false });

  return {
    zoomIn: function () {
      var rect = wrapEl.getBoundingClientRect();
      zoomAt(rect.width / 2, rect.height / 2, 1.4);
    },
    zoomOut: function () {
      var rect = wrapEl.getBoundingClientRect();
      zoomAt(rect.width / 2, rect.height / 2, 1 / 1.4);
    },
    reset: reset
  };
};

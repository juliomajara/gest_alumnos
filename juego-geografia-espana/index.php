<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/puntuaciones.php';

$usuario = requerir_login();

$page_title = 'GeoEspaña — Provincias, Comunidades y Ríos';
$body_class = 'pagina-menu';
require __DIR__ . '/includes/header.php';

$modos = [
    [
        'tipo' => 'provincias', 'modo' => 'reconocer',
        'icono' => '🗺️', 'color' => 'card-azul',
        'titulo' => 'Provincias · Reconoce el mapa',
        'texto' => 'Se ilumina una provincia. Adivina cuál es entre 6 opciones.',
    ],
    [
        'tipo' => 'provincias', 'modo' => 'tocar',
        'icono' => '👆', 'color' => 'card-naranja',
        'titulo' => 'Provincias · Toca el mapa',
        'texto' => 'Te decimos el nombre. Señálala tú en el mapa.',
    ],
    [
        'tipo' => 'ccaa', 'modo' => 'reconocer',
        'icono' => '🏛️', 'color' => 'card-verde',
        'titulo' => 'Comunidades · Reconoce el mapa',
        'texto' => 'Se ilumina una comunidad autónoma. Adivina cuál es.',
    ],
    [
        'tipo' => 'ccaa', 'modo' => 'tocar',
        'icono' => '📍', 'color' => 'card-morado',
        'titulo' => 'Comunidades · Toca el mapa',
        'texto' => 'Te decimos el nombre. Señálala tú en el mapa.',
    ],
    [
        'tipo' => 'rios', 'modo' => 'reconocer',
        'icono' => '🌊', 'color' => 'card-cian',
        'titulo' => 'Ríos · Reconoce el mapa',
        'texto' => 'Se ilumina un río. Adivina cuál es entre varias opciones.',
    ],
    [
        'tipo' => 'rios', 'modo' => 'tocar',
        'icono' => '🖐️', 'color' => 'card-marron',
        'titulo' => 'Ríos · Toca el mapa',
        'texto' => 'Te decimos el nombre. Señálalo tú en el mapa.',
    ],
];
?>
<div class="app-container">
  <div class="usuario-bar">
    <span>Jugando como <strong><?= h($usuario['nombre']) ?></strong></span>
    <a href="logout.php">Cerrar sesión</a>
  </div>

  <header class="portada-header">
    <div class="portada-logo" aria-hidden="true">🇪🇸</div>
    <h1>GeoEspaña</h1>
    <p class="subtitulo">Aprende jugando las provincias, comunidades autónomas y ríos de España</p>
  </header>

  <main class="menu-grid">
    <?php foreach ($modos as $m):
        $mejor = mejor_puntuacion_usuario($usuario['id'], $m['tipo'], $m['modo']);
        $mejorTexto = $mejor
            ? 'Mejor: ' . (int) $mejor['aciertos'] . '/' . (int) $mejor['total'] . ' · ' . rtrim(rtrim(number_format((float) $mejor['pct'], 1), '0'), '.') . '% · ' . formatear_tiempo((int) $mejor['tiempo_ms'])
            : 'Aún sin puntuación cronometrada';
    ?>
      <div class="modo-card <?= h($m['color']) ?>">
        <button type="button" class="modo-card-principal" data-tipo="<?= h($m['tipo']) ?>" data-modo="<?= h($m['modo']) ?>">
          <span class="modo-icono" aria-hidden="true"><?= $m['icono'] ?></span>
          <span class="modo-texto">
            <span class="modo-titulo"><?= h($m['titulo']) ?></span>
            <span class="modo-desc"><?= h($m['texto']) ?></span>
            <span class="modo-best"><?= h($mejorTexto) ?></span>
          </span>
          <span class="modo-flecha" aria-hidden="true">›</span>
        </button>
        <div class="modo-card-footer">
          <a class="modo-ranking-link" href="ranking.php?tipo=<?= h($m['tipo']) ?>&modo=<?= h($m['modo']) ?>">🏆 Ver ranking</a>
        </div>
      </div>
    <?php endforeach; ?>
  </main>

  <footer class="portada-footer">
    <p>Elige un modo, y luego juega en modo aprendizaje (sin guardar) o cronometrado (entra en el ranking).</p>
  </footer>
</div>

<div class="overlay-variante" id="overlay-variante">
  <div class="tarjeta-variante">
    <h2>¿Cómo quieres jugar?</h2>
    <p>En modo cronometrado tu resultado y tiempo se guardan en el ranking.</p>
    <a class="opcion-variante variante-aprendizaje" id="link-aprendizaje" href="#">
      <strong>📘 Modo aprendizaje</strong>
      <span>Sin cronómetro ni ranking. Ideal para practicar con calma.</span>
    </a>
    <a class="opcion-variante variante-cronometrado" id="link-cronometrado" href="#">
      <strong>⏱️ Modo cronometrado</strong>
      <span>Se mide tu tiempo y tu resultado entra en el ranking.</span>
    </a>
    <button type="button" class="cerrar-variante" id="cerrar-variante">Cancelar</button>
  </div>
</div>

<script>
  (function () {
    var overlay = document.getElementById('overlay-variante');
    var linkAprendizaje = document.getElementById('link-aprendizaje');
    var linkCronometrado = document.getElementById('link-cronometrado');

    document.querySelectorAll('.modo-card-principal').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tipo = btn.dataset.tipo, modo = btn.dataset.modo;
        linkAprendizaje.href = 'juego.php?tipo=' + tipo + '&modo=' + modo + '&variante=aprendizaje';
        linkCronometrado.href = 'juego.php?tipo=' + tipo + '&modo=' + modo + '&variante=cronometrado';
        overlay.classList.add('visible');
      });
    });

    document.getElementById('cerrar-variante').addEventListener('click', function () {
      overlay.classList.remove('visible');
    });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('visible');
    });
  })();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

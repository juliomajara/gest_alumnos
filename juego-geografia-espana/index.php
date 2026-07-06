<?php
require_once __DIR__ . '/includes/helpers.php';

$page_title = 'GeoEspaña — Provincias y Comunidades';
$body_class = 'pagina-menu';
require __DIR__ . '/includes/header.php';

$modos = [
    [
        'tipo' => 'provincias', 'modo' => 'reconocer',
        'icono' => '🗺️', 'color' => 'card-azul',
        'titulo' => 'Provincias · Reconoce el mapa',
        'texto' => 'Se ilumina una provincia. Adivina cuál es entre 4 opciones.',
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
];
?>
<div class="app-container">
  <header class="portada-header">
    <div class="portada-logo" aria-hidden="true">🇪🇸</div>
    <h1>GeoEspaña</h1>
    <p class="subtitulo">Aprende jugando las provincias y comunidades autónomas de España</p>
  </header>

  <main class="menu-grid">
    <?php foreach ($modos as $m): ?>
      <a class="modo-card <?= h($m['color']) ?>"
         href="juego.php?tipo=<?= h($m['tipo']) ?>&modo=<?= h($m['modo']) ?>"
         data-key="<?= h($m['tipo']) ?>-<?= h($m['modo']) ?>">
        <span class="modo-icono" aria-hidden="true"><?= $m['icono'] ?></span>
        <span class="modo-texto">
          <span class="modo-titulo"><?= h($m['titulo']) ?></span>
          <span class="modo-desc"><?= h($m['texto']) ?></span>
          <span class="modo-best" data-best></span>
        </span>
        <span class="modo-flecha" aria-hidden="true">›</span>
      </a>
    <?php endforeach; ?>
  </main>

  <footer class="portada-footer">
    <p>Toca un modo para empezar a jugar. Tus mejores puntuaciones se guardan en este dispositivo.</p>
  </footer>
</div>
<script src="assets/js/stats.js?v=2"></script>
<script>
  document.querySelectorAll('.modo-card').forEach(function (card) {
    var span = card.querySelector('[data-best]');
    var resumen = GeoStats.resumen(card.dataset.key);
    span.textContent = resumen;
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

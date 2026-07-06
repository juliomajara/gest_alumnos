<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/puntuaciones.php';

requerir_login();

$tipo = $_GET['tipo'] ?? '';
$modo = $_GET['modo'] ?? '';
$variante = $_GET['variante'] ?? '';

if (!in_array($tipo, TIPOS_VALIDOS, true) || !in_array($modo, MODOS_VALIDOS, true) || !in_array($variante, VARIANTES_VALIDAS, true)) {
    header('Location: index.php');
    exit;
}

$ccaa_vecinas = cargar_json(__DIR__ . '/data/ccaa_vecinas.json');

if ($tipo === 'provincias') {
    $provincias_ccaa = cargar_json(__DIR__ . '/data/provincias.json');
    $items = array_keys($provincias_ccaa);
    $provincia_a_ccaa = array_map(fn($info) => $info['ccaa'], $provincias_ccaa);
    $svg_path = __DIR__ . '/assets/svg/mapa-provincias.svg';
    $titulo_tipo = 'Provincias de España';
} else {
    $items = cargar_json(__DIR__ . '/data/ccaa.json');
    $provincia_a_ccaa = null;
    $svg_path = __DIR__ . '/assets/svg/mapa-ccaa.svg';
    $titulo_tipo = 'Comunidades Autónomas';
}

$titulo_modo = $modo === 'reconocer' ? 'Reconoce el mapa' : 'Toca el mapa';
$titulo_variante = $variante === 'cronometrado' ? 'Cronometrado' : 'Aprendizaje';
$svg_contenido = file_get_contents($svg_path);

$page_title = $titulo_tipo . ' · ' . $titulo_modo . ' — GeoEspaña';
$body_class = 'pagina-juego modo-' . $modo;
require __DIR__ . '/includes/header.php';
?>
<div class="app-container">
  <header class="juego-header">
    <a class="btn-volver" href="index.php" aria-label="Volver al menú">‹</a>
    <div class="juego-titulo">
      <h1><?= h($titulo_tipo) ?></h1>
      <p><?= h($titulo_modo) ?> · <?= h($titulo_variante) ?></p>
    </div>
    <button type="button" class="btn-sonido" id="btn-sonido" aria-label="Silenciar sonido">🔊</button>
  </header>

  <div class="barra-progreso"><div class="barra-progreso-relleno" id="progreso-relleno"></div></div>

  <div class="tira-puntuacion">
    <span>Aciertos: <span class="puntos" id="puntos-actual">0</span> / <span id="puntos-total">0</span></span>
    <span class="tira-tiempo oculta" id="tira-tiempo">⏱ 0:00.0</span>
    <span class="racha oculta" id="racha">🔥 Racha x<span id="racha-valor">0</span></span>
  </div>

  <div class="prompt-banner" id="prompt-banner">
    <span class="prompt-etiqueta">Toca en el mapa:</span>
    <span class="prompt-nombre" id="prompt-nombre">—</span>
  </div>

  <div class="map-wrap" id="map-wrap">
    <div class="map-inner" id="map-inner">
      <?= $svg_contenido ?>
    </div>
    <div class="controles-zoom">
      <button type="button" id="zoom-in" aria-label="Acercar">+</button>
      <button type="button" id="zoom-out" aria-label="Alejar">−</button>
      <button type="button" id="zoom-reset" aria-label="Restablecer zoom">⤾</button>
    </div>
  </div>

  <div class="opciones-grid" id="opciones-grid"></div>
</div>

<div class="feedback-toast" id="feedback-toast"></div>

<div class="overlay-final" id="overlay-final">
  <div class="tarjeta-final">
    <div class="trofeo">🏆</div>
    <h2>¡Partida terminada!</h2>
    <div class="resultado-pct" id="final-pct">0%</div>
    <p class="resultado-detalle" id="final-detalle"></p>
    <p class="resultado-detalle oculta" id="final-cronometro"></p>
    <p class="resultado-detalle oculta" id="final-aviso-aprendizaje">Modo aprendizaje: esta partida no se guarda en el ranking.</p>
    <div class="lista-fallos" id="final-fallos"></div>
    <div class="acciones-final">
      <a class="btn-secundario oculta" id="final-link-ranking" href="#" style="text-align:center;text-decoration:none;">Ver ranking</a>
      <button type="button" class="btn-primario" id="btn-reintentar">Jugar de nuevo</button>
      <a class="btn-secundario" href="index.php" style="text-align:center;text-decoration:none;">Menú principal</a>
    </div>
  </div>
</div>

<script src="assets/js/stats.js?v=2"></script>
<script src="assets/js/sound.js?v=1"></script>
<script src="assets/js/map-zoom.js?v=1"></script>
<script src="assets/js/game.js?v=2"></script>
<script>
  iniciarJuego({
    tipo: <?= json_encode($tipo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    modo: <?= json_encode($modo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    variante: <?= json_encode($variante, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    items: <?= json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    provinciaCcaa: <?= json_encode($provincia_a_ccaa, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    ccaaVecinas: <?= json_encode($ccaa_vecinas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

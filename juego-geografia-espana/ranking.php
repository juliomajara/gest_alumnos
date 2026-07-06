<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/puntuaciones.php';

$usuario = requerir_login();

$tipo = $_GET['tipo'] ?? '';
$modo = $_GET['modo'] ?? '';
if (!in_array($tipo, TIPOS_VALIDOS, true) || !in_array($modo, MODOS_VALIDOS, true)) {
    header('Location: index.php');
    exit;
}

$titulos_tipo = ['provincias' => 'Provincias de España', 'ccaa' => 'Comunidades Autónomas'];
$titulos_modo = ['reconocer' => 'Reconoce el mapa', 'tocar' => 'Toca el mapa'];

$pdo = db();
$filas = ranking_tipo_modo($pdo, $tipo, $modo);

$page_title = 'Ranking · ' . $titulos_tipo[$tipo] . ' — GeoEspaña';
$body_class = 'pagina-ranking';
require __DIR__ . '/includes/header.php';
?>
<div class="app-container">
  <header class="juego-header">
    <a class="btn-volver" href="index.php" aria-label="Volver al menú">‹</a>
    <div class="juego-titulo">
      <h1>Ranking</h1>
      <p><?= h($titulos_tipo[$tipo]) ?> · <?= h($titulos_modo[$modo]) ?></p>
    </div>
  </header>

  <?php if (empty($filas)): ?>
    <p class="ranking-vacio">Todavía no hay partidas cronometradas en este modo.<br>¡Sé el primero en aparecer aquí!</p>
  <?php else: ?>
    <div class="ranking-lista">
      <?php foreach ($filas as $i => $fila):
          $esActual = (int) $fila['usuario_id'] === $usuario['id'];
          $pctTexto = rtrim(rtrim(number_format((float) $fila['pct'], 1), '0'), '.');
      ?>
        <div class="ranking-fila<?= $esActual ? ' es-actual' : '' ?>">
          <span class="ranking-puesto">#<?= $i + 1 ?></span>
          <span class="ranking-nombre"><?= h($fila['nombre_usuario']) ?></span>
          <span class="ranking-datos">
            <strong><?= h($pctTexto) ?>%</strong>
            <?= h(formatear_tiempo((int) $fila['tiempo_ms'])) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

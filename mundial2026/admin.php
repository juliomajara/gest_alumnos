<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

start_session();

$admin_ok = ($_SESSION['admin_ok'] ?? false);
$error    = '';
$message  = '';

// Admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pw'])) {
    if ($_POST['admin_pw'] === ADMIN_PASSWORD) {
        $_SESSION['admin_ok'] = true; $admin_ok = true;
    } else {
        $error = 'Contraseña incorrecta';
    }
}

// Actions
if ($admin_ok && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Force cache refresh
    if (isset($_POST['refresh_cache'])) {
        $cache = DATA_DIR . '/matches.json';
        if (file_exists($cache)) unlink($cache);
        $message = 'Caché eliminada. Los partidos se recargarán automáticamente.';
    }
    // Set final positions
    if (isset($_POST['final_pos1'])) {
        $finals = [];
        foreach ([1,2,3,4] as $p) {
            $v = trim($_POST["final_pos{$p}"] ?? '');
            if ($v) $finals[$p] = $v;
        }
        write_json(DATA_DIR . '/final_positions.json', $finals);
        $message = 'Posiciones finales guardadas.';
    }
    // Manual result override
    if (isset($_POST['override_match_id'])) {
        $mid = trim($_POST['override_match_id']);
        $g1  = (int)($_POST['override_g1'] ?? 0);
        $g2  = (int)($_POST['override_g2'] ?? 0);
        if ($mid) {
            $overrides = read_json(DATA_DIR . '/results_override.json');
            $found = false;
            foreach ($overrides as &$o) {
                if ($o['id'] === $mid) { $o['score'] = ['ft' => [$g1, $g2]]; $found = true; break; }
            }
            if (!$found) $overrides[] = ['id' => $mid, 'score' => ['ft' => [$g1, $g2]]];
            write_json(DATA_DIR . '/results_override.json', $overrides);
            // Invalidate cache
            $cache = DATA_DIR . '/matches.json';
            if (file_exists($cache)) unlink($cache);
            $message = "Resultado manual guardado para partido {$mid}: {$g1}-{$g2}.";
        }
    }
    // Delete override
    if (isset($_POST['del_override_id'])) {
        $mid = trim($_POST['del_override_id']);
        $overrides = read_json(DATA_DIR . '/results_override.json');
        $overrides = array_values(array_filter($overrides, fn($o) => $o['id'] !== $mid));
        write_json(DATA_DIR . '/results_override.json', $overrides);
        $cache = DATA_DIR . '/matches.json';
        if (file_exists($cache)) unlink($cache);
        $message = "Override eliminado para partido {$mid}.";
    }
}

$matches  = $admin_ok ? get_matches() : [];
$users    = $admin_ok ? read_json(DATA_DIR . '/users.json') : [];
$preds    = $admin_ok ? read_json(DATA_DIR . '/predictions.json') : [];
$top4s    = $admin_ok ? read_json(DATA_DIR . '/top4.json') : [];
$overrides= $admin_ok ? read_json(DATA_DIR . '/results_override.json') : [];
$finals   = $admin_ok ? read_json(DATA_DIR . '/final_positions.json') : [];
$board    = $admin_ok ? get_leaderboard() : [];
$teams    = $admin_ok ? get_all_teams($matches) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · <?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  .admin-wrap { max-width: 700px; margin: 0 auto; padding: 24px 16px 60px; }
  .admin-section { background: var(--card); border: 1px solid var(--border2); border-radius: var(--radius); padding: 20px; margin-bottom: 16px; }
  .admin-section h2 { font-family: 'Montserrat',sans-serif; font-size: 1rem; font-weight: 800; margin-bottom: 14px; color: var(--primary); }
  .admin-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 8px; }
  .admin-input { padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border2); background: var(--bg2); color: var(--text); font-size: .85rem; }
  .admin-btn { padding: 8px 16px; border-radius: 8px; border: none; background: var(--primary); color: #000; font-weight: 700; cursor: pointer; font-size: .85rem; }
  .admin-btn.danger { background: var(--danger); color: #fff; }
  .admin-btn.secondary { background: var(--card2); color: var(--text); border: 1px solid var(--border2); }
  table { width: 100%; border-collapse: collapse; font-size: .8rem; }
  th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--border2); }
  th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: .7rem; }
</style>
</head>
<body>
<div class="admin-wrap">
  <div style="margin-bottom:20px">
    <h1 style="font-family:'Montserrat',sans-serif;font-size:1.3rem;font-weight:800">⚙️ Admin Panel</h1>
    <a href="partidos.php" style="font-size:.8rem;color:var(--text-muted)">← Volver a la app</a>
  </div>

  <?php if ($error):   ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($message): ?><div class="alert success"><?= h($message) ?></div><?php endif; ?>

<?php if (!$admin_ok): ?>
  <div class="admin-section">
    <h2>🔐 Acceso Admin</h2>
    <form method="post">
      <div class="admin-row">
        <input type="password" name="admin_pw" class="admin-input" placeholder="Contraseña admin">
        <button type="submit" class="admin-btn">Entrar</button>
      </div>
    </form>
  </div>

<?php else: ?>

  <!-- Stats -->
  <div class="admin-section">
    <h2>📊 Estadísticas</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:0">
      <?php foreach ([['Participantes', count($users)], ['Partidos', count($matches)], ['Predicciones', count($preds)]] as [$l, $v]): ?>
        <div style="background:var(--bg2);border-radius:8px;padding:12px;text-align:center">
          <div style="font-size:1.4rem;font-weight:800;color:var(--primary)"><?= $v ?></div>
          <div style="font-size:.72rem;color:var(--text-muted)"><?= $l ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cache -->
  <div class="admin-section">
    <h2>🔄 Caché de partidos</h2>
    <?php $cache_file = DATA_DIR . '/matches.json'; $cache_age = file_exists($cache_file) ? (time() - filemtime($cache_file)) : null; ?>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px">
      <?php if ($cache_age !== null): ?>
        Última actualización: hace <?= round($cache_age / 60) ?> minutos.
        (TTL: <?= CACHE_TTL/60 ?> min)
      <?php else: ?>
        Sin caché. Se cargará automáticamente.
      <?php endif; ?>
    </p>
    <form method="post">
      <button type="submit" name="refresh_cache" value="1" class="admin-btn">Forzar recarga de partidos</button>
    </form>
  </div>

  <!-- Final positions -->
  <div class="admin-section">
    <h2>🏆 Posiciones finales (para calcular puntos Top 4)</h2>
    <form method="post">
      <?php foreach ([1 => '🥇 Campeón', 2 => '🥈 Subcampeón', 3 => '🥉 3er puesto', 4 => '4️⃣ 4to puesto'] as $pos => $label): ?>
        <div class="admin-row">
          <label style="min-width:130px;font-size:.82rem"><?= $label ?></label>
          <select name="final_pos<?= $pos ?>" class="admin-input">
            <option value="">Sin asignar</option>
            <?php foreach ($teams as $t): ?>
              <option value="<?= h($t) ?>" <?= ($finals[$pos] ?? '') === $t ? 'selected' : '' ?>>
                <?= team_flag($t) ?> <?= h($t) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="admin-btn" style="margin-top:8px">Guardar posiciones finales</button>
    </form>
  </div>

  <!-- Manual result override -->
  <div class="admin-section">
    <h2>✏️ Resultado manual (override)</h2>
    <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:12px">
      Usa esto si un resultado no aparece aún en el feed automático.
    </p>
    <form method="post">
      <div class="admin-row">
        <input name="override_match_id" class="admin-input" placeholder="ID de partido (ej: match_1)" style="flex:1">
        <input name="override_g1" type="number" min="0" max="30" class="admin-input" style="width:55px" placeholder="G1">
        <span>-</span>
        <input name="override_g2" type="number" min="0" max="30" class="admin-input" style="width:55px" placeholder="G2">
        <button type="submit" class="admin-btn">Guardar</button>
      </div>
    </form>
    <?php if ($overrides): ?>
    <table style="margin-top:12px">
      <tr><th>Partido ID</th><th>Resultado</th><th></th></tr>
      <?php foreach ($overrides as $o): ?>
        <tr>
          <td><?= h($o['id']) ?></td>
          <td><?= $o['score']['ft'][0] ?>-<?= $o['score']['ft'][1] ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="del_override_id" value="<?= h($o['id']) ?>">
              <button type="submit" class="admin-btn danger" style="padding:3px 10px;font-size:.75rem">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <!-- Leaderboard -->
  <div class="admin-section">
    <h2>📋 Clasificación actual</h2>
    <table>
      <tr><th>#</th><th>Nombre</th><th>Partidos</th><th>Top4</th><th>Total</th><th>Exactos</th></tr>
      <?php foreach ($board as $i => $e): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= h($e['name']) ?></td>
          <td><?= $e['match_pts'] ?></td>
          <td><?= $e['top4_pts'] ?></td>
          <td><strong><?= $e['total'] ?></strong></td>
          <td><?= $e['exact'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Participants -->
  <div class="admin-section">
    <h2>👥 Participantes</h2>
    <table>
      <tr><th>Nombre</th><th>Registro</th><th>Predicciones</th><th>Top4</th></tr>
      <?php
      $pred_count_by_user = array_count_values(array_column($preds, 'user_id'));
      $top4_by_user       = array_column($top4s, null, 'user_id');
      foreach ($users as $u):
        $uid = $u['id'];
      ?>
        <tr>
          <td><?= h($u['name']) ?></td>
          <td><?= h(date('d M H:i', strtotime($u['created_at']))) ?></td>
          <td><?= $pred_count_by_user[$uid] ?? 0 ?></td>
          <td><?= isset($top4_by_user[$uid]) ? '✅' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php endif; ?>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$user    = require_login();
$matches = get_matches();
$matches_by_id = [];
foreach ($matches as $m) $matches_by_id[$m['id']] = $m;

// My predictions
$all_preds = read_json(DATA_DIR . '/predictions.json');
$my_preds  = [];
foreach ($all_preds as $p) {
    if ($p['user_id'] === $user['id']) $my_preds[$p['match_id']] = $p;
}

// My top 4
$my_top4 = get_user_top4($user['id']);

// Stats
$total_pts    = 0;
$exact_count  = 0;
$outcome_count= 0;
$wrong_count  = 0;
$pending_count= 0;

foreach ($my_preds as $mid => $pred) {
    if (!isset($matches_by_id[$mid])) continue;
    $match = $matches_by_id[$mid];
    if (!isset($match['score']['ft'])) { $pending_count++; continue; }
    $pts = calc_match_points($pred, $match);
    $total_pts += $pts;
    if ($pts === PTS_EXACT)   $exact_count++;
    elseif ($pts === PTS_OUTCOME) $outcome_count++;
    else $wrong_count++;
}

// Board rank
$board   = get_leaderboard();
$my_rank = null;
foreach ($board as $i => $e) {
    if ($e['user_id'] === $user['id']) { $my_rank = $i + 1; break; }
}

$medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];

$page_title  = 'Mi Perfil';
$active_page = 'perfil';
$assets_base = '';
include 'includes/head.php';
?>

<div class="top-bar">
  <div class="top-bar-title"><span class="ball">👤</span> Mi Perfil</div>
  <a href="logout.php" style="font-size:.8rem;color:var(--text-muted);padding:6px 12px">Salir</a>
</div>

<div class="main-content">

  <div class="profile-header">
    <div class="profile-avatar">
      <?= mb_strtoupper(mb_substr($user['name'], 0, 2)) ?>
    </div>
    <div class="profile-name"><?= h($user['name']) ?></div>
    <?php if ($my_rank): ?>
      <div style="color:var(--text-muted);font-size:.85rem">
        <?= $medals[$my_rank] ?? "#{$my_rank}" ?> en el ranking
      </div>
    <?php endif; ?>
  </div>

  <div class="profile-stats">
    <div class="stat-box">
      <div class="stat-val"><?= $total_pts + ($board[array_search($user['id'], array_column($board, 'user_id'))]['top4_pts'] ?? 0) ?></div>
      <div class="stat-lbl">Total pts</div>
    </div>
    <div class="stat-box">
      <div class="stat-val"><?= count($my_preds) ?></div>
      <div class="stat-lbl">Predicciones</div>
    </div>
    <div class="stat-box">
      <div class="stat-val"><?= $exact_count ?></div>
      <div class="stat-lbl">Exactos</div>
    </div>
  </div>

  <!-- Progress bar -->
  <?php $total_matches = count(array_filter($matches, fn($m) => $m['team1'] && $m['team2'])); ?>
  <div style="padding:0 16px 16px">
    <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);margin-bottom:4px">
      <span>Partidos predichos</span>
      <span><?= count($my_preds) ?> / <?= $total_matches ?></span>
    </div>
    <div style="height:6px;border-radius:3px;background:var(--card2);overflow:hidden">
      <div style="height:100%;width:<?= $total_matches > 0 ? round(count($my_preds) / $total_matches * 100) : 0 ?>%;
                  background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:3px;transition:width .4s">
      </div>
    </div>
  </div>

  <!-- Accuracy breakdown -->
  <?php $played = $exact_count + $outcome_count + $wrong_count; ?>
  <?php if ($played > 0): ?>
  <div style="padding:0 16px 8px">
    <div class="card" style="padding:14px">
      <div style="font-size:.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
        Aciertos (<?= $played ?> partido<?= $played > 1 ? 's' : '' ?> jugado<?= $played > 1 ? 's' : '' ?>)
      </div>
      <div style="display:flex;gap:8px">
        <?php if ($exact_count): ?>
        <div style="flex:<?= $exact_count ?>;background:rgba(0,230,118,.15);border-radius:6px;height:32px;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--success)">
          <?= $exact_count ?> exacto<?= $exact_count > 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
        <?php if ($outcome_count): ?>
        <div style="flex:<?= $outcome_count ?>;background:rgba(0,180,216,.12);border-radius:6px;height:32px;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--accent)">
          <?= $outcome_count ?> resultado
        </div>
        <?php endif; ?>
        <?php if ($wrong_count): ?>
        <div style="flex:<?= $wrong_count ?>;background:rgba(255,79,79,.1);border-radius:6px;height:32px;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--danger)">
          <?= $wrong_count ?> fallo<?= $wrong_count > 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Top 4 prediction -->
  <div class="section-header"><span class="section-title">🏆 Mi apuesta Top 4</span></div>
  <?php if ($my_top4): ?>
    <div style="padding:0 16px">
      <?php foreach ([1 => '🥇', 2 => '🥈', 3 => '🥉', 4 => '4️⃣'] as $pos => $medal): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius-sm);margin-bottom:6px">
          <span style="font-size:1.2rem"><?= $medal ?></span>
          <?php $team = $my_top4["pos{$pos}"] ?? ''; ?>
          <span style="font-size:1.1rem"><?= team_flag($team) ?></span>
          <span style="font-weight:600"><?= h($team) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="padding:0 16px">
      <div class="alert info">
        Aún no has hecho tu predicción del Top 4.
        <a href="top4.php" style="color:var(--primary);font-weight:700;margin-left:4px">Apostar ahora →</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Recent predictions -->
  <?php
  $recent = array_filter($my_preds, fn($p) => isset($matches_by_id[$p['match_id']]));
  $recent = array_values(array_reverse($recent));
  $recent = array_slice($recent, 0, 8);
  ?>
  <?php if ($recent): ?>
    <div class="section-header"><span class="section-title">⚽ Últimas predicciones</span></div>
    <div style="padding:0 16px 16px">
      <?php foreach ($recent as $pred): ?>
        <?php $m = $matches_by_id[$pred['match_id']] ?? null; if (!$m) continue; ?>
        <?php $result = $m['score']['ft'] ?? null; $pts = $result ? calc_match_points($pred, $m) : null; ?>
        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius-sm);margin-bottom:6px">
          <span><?= team_flag($m['team1'] ?? '') ?></span>
          <span style="font-size:.8rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= h($m['team1'] ?? '') ?> vs <?= h($m['team2'] ?? '') ?>
          </span>
          <span style="font-weight:700;font-variant-numeric:tabular-nums"><?= $pred['g1'] ?>-<?= $pred['g2'] ?></span>
          <?php if ($pts !== null): ?>
            <span class="pred-pts pts-<?= $pts ?>" style="min-width:36px;text-align:center">+<?= $pts ?></span>
          <?php else: ?>
            <span class="pred-pts pending" style="min-width:36px;text-align:center">?</span>
          <?php endif; ?>
          <span><?= team_flag($m['team2'] ?? '') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="text-align:center;padding:16px">
    <a href="logout.php" style="color:var(--text-muted);font-size:.82rem;text-decoration:underline">Cerrar sesión</a>
  </div>

</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

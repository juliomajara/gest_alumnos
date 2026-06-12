<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$user  = require_login();
$board = get_leaderboard();

$my_rank = null;
foreach ($board as $i => $entry) {
    if ($entry['user_id'] === $user['id']) { $my_rank = $i + 1; break; }
}

$podium = array_slice($board, 0, 3);
$rest   = array_slice($board, 3);

$page_title  = 'Ranking';
$active_page = 'clasificacion';
$assets_base = '';
include 'includes/head.php';
?>

<div class="top-bar">
  <div class="top-bar-title">
    <span class="ball">📊</span> Clasificación
  </div>
  <?php if ($my_rank): ?>
  <div class="top-bar-actions">
    <span class="top-bar-badge">Tú: <strong>#<?= $my_rank ?></strong></span>
  </div>
  <?php endif; ?>
</div>

<div class="main-content">

<?php if (empty($board)): ?>
  <div class="empty">
    <div class="empty-icon">🏁</div>
    <p>Aún no hay predicciones.<br>¡Sé el primero en apostar!</p>
  </div>
<?php else: ?>

  <!-- View all bets button -->
  <div style="padding:12px 16px 4px;display:flex;gap:8px">
    <a href="todas_apuestas.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:11px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius-sm);font-size:.85rem;font-weight:600;color:var(--text)">
      📋 Ver todas las apuestas
    </a>
  </div>

  <!-- Podium -->
  <?php if (count($podium) >= 1): ?>
  <div class="podium">
    <?php
    $podium_order = [];
    if (isset($podium[1])) $podium_order[] = [2, $podium[1]];
    $podium_order[] = [1, $podium[0]];
    if (isset($podium[2])) $podium_order[] = [3, $podium[2]];
    ?>
    <?php foreach ($podium_order as [$rank, $entry]): ?>
      <?php $is_me = $entry['user_id'] === $user['id']; ?>
      <a href="perfil_publico.php?uid=<?= urlencode($entry['user_id']) ?>" style="text-decoration:none;color:inherit">
        <div class="podium-slot rank-<?= $rank ?>">
          <div class="podium-avatar">
            <?= mb_strtoupper(mb_substr($entry['name'], 0, 2)) ?>
          </div>
          <div class="podium-medal"><?= ['🥇','🥈','🥉'][$rank-1] ?></div>
          <div class="podium-name"><?= h($entry['name']) ?><?= $is_me ? ' (tú)' : '' ?></div>
          <div class="podium-pts"><strong><?= $entry['total'] ?></strong> pts</div>
          <div class="podium-bar"></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Rest of leaderboard -->
  <?php if ($rest): ?>
  <div class="section-header" style="padding-top:8px">
    <span class="section-title">Clasificación completa</span>
  </div>
  <?php endif; ?>

  <div class="leaderboard-list">
    <?php foreach ($board as $i => $entry): ?>
      <?php $rank = $i + 1; $is_me = $entry['user_id'] === $user['id']; ?>
      <?php if ($rank <= 3 && count($podium) >= 3): continue; endif; ?>
      <a href="perfil_publico.php?uid=<?= urlencode($entry['user_id']) ?>" style="text-decoration:none;color:inherit;display:block">
        <div class="lb-row <?= $is_me ? 'me' : '' ?>">
          <span class="lb-rank">#<?= $rank ?></span>
          <div style="flex:1;min-width:0">
            <div class="lb-name"><?= h($entry['name']) ?><?= $is_me ? ' <span style="color:var(--primary);font-size:.7rem">tú</span>' : '' ?></div>
            <div class="lb-details">
              ⚽ <?= $entry['match_pts'] ?>pts · 🏆 <?= $entry['top4_pts'] ?>pts top4
              · <?= $entry['predictions'] ?> apuestas · <?= $entry['exact'] ?> exactos
            </div>
          </div>
          <span class="lb-pts"><?= $entry['total'] ?></span>
          <span style="color:var(--text-dim);font-size:.8rem">›</span>
        </div>
      </a>
    <?php endforeach; ?>

    <!-- Always show "me" at bottom if out of visible area -->
    <?php if ($my_rank && $my_rank > 3 && count($board) > 8): ?>
      <?php $my_entry = $board[$my_rank - 1]; ?>
      <?php if ($my_rank > count($rest) + 3): ?>
      <div style="text-align:center;padding:4px;color:var(--text-muted);font-size:.75rem">···</div>
      <div class="lb-row me">
        <span class="lb-rank">#<?= $my_rank ?></span>
        <div>
          <div class="lb-name"><?= h($my_entry['name']) ?> <span style="color:var(--primary);font-size:.7rem">tú</span></div>
          <div class="lb-details">⚽ <?= $my_entry['match_pts'] ?>pts · 🏆 <?= $my_entry['top4_pts'] ?>pts top4</div>
        </div>
        <span class="lb-pts"><?= $my_entry['total'] ?></span>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Points key -->
  <div class="top4-info" style="margin-top:8px">
    <h3>📖 Sistema de puntos</h3>
    <ul>
      <li>+<?= PTS_EXACT ?> por resultado exacto (ej. 2-1)</li>
      <li>+<?= PTS_OUTCOME ?> por acertar ganador/empate</li>
      <li>0 si fallas el resultado</li>
      <li>Top 4: hasta <?= (PTS_IN_TOP4 + PTS_POS[1]) + (PTS_IN_TOP4 + PTS_POS[2]) + (PTS_IN_TOP4 + PTS_POS[3]) + (PTS_IN_TOP4 + PTS_POS[4]) ?> pts</li>
    </ul>
  </div>

<?php endif; ?>
</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

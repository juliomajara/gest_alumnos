<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$me = require_login();

// Load target user
$uid  = $_GET['uid'] ?? '';
$users = read_json(DATA_DIR . '/users.json');
$target = null;
foreach ($users as $u) { if ($u['id'] === $uid) { $target = $u; break; } }
if (!$target) { header('Location: clasificacion.php'); exit; }

$is_me = $target['id'] === $me['id'];

$matches = get_matches();
$matches_by_id = [];
foreach ($matches as $m) $matches_by_id[$m['id']] = $m;

// Target's predictions
$all_preds = read_json(DATA_DIR . '/predictions.json');
$their_preds = [];
foreach ($all_preds as $p) {
    if ($p['user_id'] === $uid) $their_preds[$p['match_id']] = $p;
}

// Target's top4
$their_top4 = get_user_top4($uid);

// Stats
$total_pts = 0; $exact = 0; $outcome = 0; $wrong = 0; $pending = 0;
foreach ($their_preds as $mid => $pred) {
    if (!isset($matches_by_id[$mid])) continue;
    $m = $matches_by_id[$mid];
    if (!isset($m['score']['ft'])) { $pending++; continue; }
    $pts = calc_match_points($pred, $m);
    $total_pts += $pts;
    if ($pts === PTS_EXACT) $exact++;
    elseif ($pts === PTS_OUTCOME) $outcome++;
    else $wrong++;
}

// Rank
$board = get_leaderboard();
$rank  = null;
foreach ($board as $i => $e) { if ($e['user_id'] === $uid) { $rank = $i + 1; break; } }
$my_entry = null;
foreach ($board as $e) { if ($e['user_id'] === $uid) { $my_entry = $e; break; } }
$total_pts_all = $my_entry ? $my_entry['total'] : $total_pts;

// Group matches for display
$groups = [];
foreach ($matches as $m) {
    $key = $m['group'] ?? $m['round'] ?? 'Otros';
    $groups[$key][] = $m;
}

$medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
$page_title  = h($target['name']);
$active_page = 'clasificacion';
$assets_base = '';
include 'includes/head.php';
?>

<div class="top-bar">
  <div class="top-bar-title" style="gap:4px">
    <a href="clasificacion.php" style="color:var(--text-muted);font-size:1rem;padding-right:4px">←</a>
    <?= h($target['name']) ?>
    <?php if ($is_me): ?><span style="font-size:.72rem;color:var(--primary);font-weight:600">(tú)</span><?php endif; ?>
  </div>
  <?php if ($rank): ?>
  <div class="top-bar-actions">
    <span class="top-bar-badge"><?= $medals[$rank] ?? "#$rank" ?> <?= $rank <= 3 ? '' : "#{$rank}" ?></span>
  </div>
  <?php endif; ?>
</div>

<div class="main-content">

  <!-- Avatar + stats -->
  <div class="profile-header">
    <div class="profile-avatar"><?= mb_strtoupper(mb_substr($target['name'], 0, 2)) ?></div>
    <div class="profile-name"><?= h($target['name']) ?></div>
    <?php if ($rank): ?>
      <div style="color:var(--text-muted);font-size:.85rem"><?= $medals[$rank] ?? "#{$rank}" ?> en el ranking</div>
    <?php endif; ?>
  </div>

  <div class="profile-stats">
    <div class="stat-box"><div class="stat-val"><?= $total_pts_all ?></div><div class="stat-lbl">Total pts</div></div>
    <div class="stat-box"><div class="stat-val"><?= count($their_preds) ?></div><div class="stat-lbl">Apuestas</div></div>
    <div class="stat-box"><div class="stat-val"><?= $exact ?></div><div class="stat-lbl">Exactos</div></div>
  </div>

  <!-- Accuracy bar -->
  <?php $played = $exact + $outcome + $wrong; if ($played > 0): ?>
  <div style="padding:0 16px 8px">
    <div class="card" style="padding:12px">
      <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px">Aciertos sobre <?= $played ?> partido<?= $played > 1 ? 's' : '' ?> jugado<?= $played > 1 ? 's' : '' ?></div>
      <div style="display:flex;gap:6px">
        <?php if ($exact):   ?><div style="flex:<?= $exact   ?>;background:rgba(0,230,118,.15);border-radius:5px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--success)"><?= $exact ?>✓</div><?php endif; ?>
        <?php if ($outcome): ?><div style="flex:<?= $outcome ?>;background:rgba(0,180,216,.12);border-radius:5px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--accent)"><?= $outcome ?>~</div><?php endif; ?>
        <?php if ($wrong):   ?><div style="flex:<?= $wrong   ?>;background:rgba(255,79,79,.1);border-radius:5px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--danger)"><?= $wrong ?>✗</div><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Top 4 prediction -->
  <div class="section-header"><span class="section-title">🏆 Su apuesta Top 4</span></div>
  <?php if ($their_top4): ?>
    <div style="padding:0 16px 8px">
      <?php foreach ([1 => '🥇', 2 => '🥈', 3 => '🥉', 4 => '4️⃣'] as $pos => $medal): ?>
        <?php $team = $their_top4["pos{$pos}"] ?? ''; ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius-sm);margin-bottom:6px">
          <span style="font-size:1.1rem"><?= $medal ?></span>
          <span style="font-size:1.05rem"><?= team_flag($team) ?></span>
          <span style="font-weight:600"><?= h($team) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="padding:0 16px 8px"><div class="alert info">No ha hecho predicción del Top 4.</div></div>
  <?php endif; ?>

  <!-- Match predictions by group -->
  <div class="section-header"><span class="section-title">⚽ Sus predicciones por partido</span></div>

  <?php if (empty($their_preds)): ?>
    <div class="empty"><div class="empty-icon">🤫</div><p>Todavía no ha hecho ninguna apuesta.</p></div>
  <?php else: ?>

    <?php foreach ($groups as $group_name => $group_matches): ?>
      <?php
      $group_preds = array_filter($group_matches, fn($m) => isset($their_preds[$m['id']]));
      if (empty($group_preds)) continue;
      $is_group = preg_match('/^group/i', $group_name);
      ?>
      <div style="padding:0 16px">
        <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;padding:10px 0 6px">
          <?= $is_group ? '🏟️' : '⚡' ?> <?= h($group_name) ?>
        </div>
        <?php foreach ($group_matches as $m): ?>
          <?php
          $pred = $their_preds[$m['id']] ?? null;
          if (!$pred || !$m['team1'] || !$m['team2']) continue;
          $result = $m['score']['ft'] ?? null;
          $pts    = $result ? calc_match_points($pred, $m) : null;
          ?>
          <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius-sm);margin-bottom:5px">
            <!-- Teams -->
            <span style="font-size:.95rem"><?= team_flag($m['team1']) ?></span>
            <span style="font-size:.75rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted)">
              <?= h($m['team1']) ?> - <?= h($m['team2']) ?>
            </span>
            <span style="font-size:.95rem"><?= team_flag($m['team2']) ?></span>
            <!-- Prediction -->
            <span style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:.9rem;min-width:36px;text-align:center">
              <?= $pred['g1'] ?>-<?= $pred['g2'] ?>
            </span>
            <!-- Result -->
            <?php if ($result): ?>
              <span style="font-size:.72rem;color:var(--text-muted)">(<?= $result[0] ?>-<?= $result[1] ?>)</span>
            <?php endif; ?>
            <!-- Points badge -->
            <?php if ($pts !== null): ?>
              <span class="pred-pts pts-<?= $pts ?>" style="min-width:34px;text-align:center">+<?= $pts ?></span>
            <?php elseif ($result === null): ?>
              <span class="pred-pts pending" style="min-width:34px;text-align:center">?</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="height:16px"></div>
</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

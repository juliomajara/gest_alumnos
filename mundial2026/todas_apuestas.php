<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$me      = require_login();
$matches = get_matches();
$users   = read_json(DATA_DIR . '/users.json');
$preds   = read_json(DATA_DIR . '/predictions.json');

// Index predictions: [match_id][user_id] = pred
$pred_map = [];
foreach ($preds as $p) {
    $pred_map[$p['match_id']][$p['user_id']] = $p;
}

// Only show users who have at least one prediction
$active_uids = array_unique(array_column($preds, 'user_id'));
$active_users = array_filter($users, fn($u) => in_array($u['id'], $active_uids));
usort($active_users, fn($a,$b) => strcmp($a['name'], $b['name']));

// Group matches
$groups = [];
foreach ($matches as $m) {
    if (!$m['team1'] || !$m['team2']) continue; // Skip TBD
    $key = $m['group'] ?? $m['round'] ?? 'Otros';
    $groups[$key][] = $m;
}

$page_title  = 'Todas las apuestas';
$active_page = 'clasificacion';
$assets_base = '';
include 'includes/head.php';
?>
<style>
.apuestas-table-wrap { overflow-x: auto; padding: 0 16px 16px; -webkit-overflow-scrolling: touch; }
.apuestas-table { border-collapse: collapse; width: 100%; min-width: 320px; font-size: .8rem; }
.apuestas-table th, .apuestas-table td { padding: 7px 9px; border-bottom: 1px solid var(--border2); white-space: nowrap; }
.apuestas-table th { color: var(--text-muted); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; background: var(--bg2); position: sticky; top: 0; z-index: 2; }
.apuestas-table td.match-col { font-weight: 600; color: var(--text); min-width: 130px; }
.apuestas-table td.match-col .flags { font-size: 1rem; }
.apuestas-table td.result-col { font-family: 'Montserrat', sans-serif; font-weight: 800; color: var(--primary); }
.pred-cell { text-align: center; font-family: 'Montserrat', sans-serif; font-weight: 700; }
.pred-cell.pts-3 { color: var(--success); }
.pred-cell.pts-1 { color: var(--accent); }
.pred-cell.pts-0 { color: var(--danger); }
.pred-cell.no-pred { color: var(--text-dim); font-weight: 400; }
.pred-cell.pending { color: var(--text-muted); }
.group-header { background: rgba(255,215,0,.05); }
.group-header td { color: var(--primary); font-weight: 700; font-size: .72rem; text-transform: uppercase; letter-spacing: .8px; padding: 10px 9px 6px; }
.user-th { max-width: 70px; overflow: hidden; text-overflow: ellipsis; }
.me-col { background: rgba(255,215,0,.04); }
</style>

<div class="top-bar">
  <div class="top-bar-title">
    <a href="clasificacion.php" style="color:var(--text-muted);padding-right:6px">←</a>
    📋 Todas las apuestas
  </div>
</div>

<div class="main-content">

<?php if (empty($active_users)): ?>
  <div class="empty"><div class="empty-icon">🤫</div><p>Aún no hay predicciones.</p></div>
<?php else: ?>

  <div style="padding:8px 16px 4px;font-size:.78rem;color:var(--text-muted)">
    <?= count($active_users) ?> participante<?= count($active_users) > 1 ? 's' : '' ?> ·
    <span style="color:var(--success)">■</span> Exacto (+3) &nbsp;
    <span style="color:var(--accent)">■</span> Resultado (+1) &nbsp;
    <span style="color:var(--danger)">■</span> Fallo (0) &nbsp;
    <span style="color:var(--text-muted)">—</span> Sin apuesta
  </div>

  <?php foreach ($groups as $group_name => $group_matches): ?>
    <?php $is_group = preg_match('/^group/i', $group_name); ?>
    <div class="section-header">
      <span class="section-title"><?= $is_group ? '🏟️' : '⚡' ?> <?= h($group_name) ?></span>
    </div>

    <div class="apuestas-table-wrap">
      <table class="apuestas-table">
        <thead>
          <tr>
            <th>Partido</th>
            <th>Res.</th>
            <?php foreach ($active_users as $u): ?>
              <th class="user-th <?= $u['id'] === $me['id'] ? 'me-col' : '' ?>">
                <?= team_flag('') ?> <?= h(mb_substr($u['name'], 0, 7)) ?><?= mb_strlen($u['name']) > 7 ? '…' : '' ?>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($group_matches as $m): ?>
            <?php
            $result = $m['score']['ft'] ?? null;
            $mid    = $m['id'];
            ?>
            <tr>
              <!-- Match -->
              <td class="match-col">
                <span class="flags"><?= team_flag($m['team1']) ?> <?= team_flag($m['team2']) ?></span>
                <span style="font-size:.72rem;color:var(--text-muted);display:block;margin-top:1px">
                  <?= h(mb_substr($m['team1'], 0, 8)) ?>
                  <span style="color:var(--text-dim)"> v </span>
                  <?= h(mb_substr($m['team2'], 0, 8)) ?>
                </span>
              </td>
              <!-- Real result -->
              <td class="result-col">
                <?php if ($result): ?>
                  <?= $result[0] ?>-<?= $result[1] ?>
                <?php elseif (is_locked($m)): ?>
                  <span style="color:var(--text-dim)">?</span>
                <?php else: ?>
                  <span style="color:var(--text-dim);font-weight:400;font-family:inherit">–</span>
                <?php endif; ?>
              </td>
              <!-- Each user's prediction -->
              <?php foreach ($active_users as $u): ?>
                <?php
                $pred = $pred_map[$mid][$u['id']] ?? null;
                $is_me_col = $u['id'] === $me['id'];
                if ($pred) {
                    if ($result) {
                        $pts = calc_match_points($pred, $m);
                        $cls = "pts-{$pts}";
                        $label = $pred['g1'] . '-' . $pred['g2'];
                    } else {
                        $cls = 'pending';
                        $label = $pred['g1'] . '-' . $pred['g2'];
                    }
                } else {
                    $cls = 'no-pred'; $label = '—';
                }
                ?>
                <td class="pred-cell <?= $cls ?> <?= $is_me_col ? 'me-col' : '' ?>">
                  <?= h($label) ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>

<?php endif; ?>
</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

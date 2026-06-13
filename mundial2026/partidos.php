<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$user = require_login();
$matches = get_matches();

$all_preds = read_json(DATA_DIR . '/predictions.json');
$my_preds  = [];
foreach ($all_preds as $p) {
    if ($p['user_id'] === $user['id']) $my_preds[$p['match_id']] = $p;
}

// Group matches by round/group, preserving chronological order within each group
$groups = [];
foreach ($matches as $m) {
    $key = $m['group'] ?? $m['round'] ?? 'Otros';
    $groups[$key][] = $m;
}
// Sort groups by the kickoff of their earliest match
uasort($groups, function ($a, $b) {
    $ea = min(array_map(fn($m) => $m['kickoff_utc'] ?? '9999', $a));
    $eb = min(array_map(fn($m) => $m['kickoff_utc'] ?? '9999', $b));
    return strcmp($ea, $eb);
});

$my_count = count($my_preds);
$total    = count($matches);

$page_title  = 'Partidos';
$active_page = 'partidos';
$assets_base = '';
include 'includes/head.php';
?>

<div class="top-bar">
  <div class="top-bar-title">
    <span class="ball">⚽</span> Mundial 2026
  </div>
  <div class="top-bar-actions">
    <span class="top-bar-badge"><?= $my_count ?>/<?= $total ?> predichos</span>
  </div>
</div>

<div class="main-content">

<?php if (empty($matches)): ?>
  <div class="empty">
    <div class="empty-icon">📡</div>
    <p>Cargando partidos…<br>
       <button onclick="location.reload()" style="margin-top:12px;padding:8px 16px;border-radius:8px;border:1px solid var(--border2);background:var(--card2);color:var(--text);cursor:pointer">
         Reintentar
       </button>
    </p>
  </div>
<?php else: ?>

<div class="filter-tabs">
  <button class="tab-btn active" data-filter="all">Todos</button>
  <button class="tab-btn" data-filter="my">Mis apuestas</button>
  <button class="tab-btn" data-filter="groups">Grupos</button>
  <button class="tab-btn" data-filter="knockout">Eliminatorias</button>
</div>

<?php foreach ($groups as $group_name => $group_matches): ?>
  <?php
  $is_group = preg_match('/^group/i', $group_name);
  $has_pred_count = count(array_filter($group_matches, fn($m) => isset($my_preds[$m['id']])));
  ?>
  <div class="match-group" data-group="<?= h($group_name) ?>">
    <div class="section-header">
      <span class="section-title"><?= $is_group ? '🏟️' : '⚡' ?> <?= h($group_name) ?></span>
      <?php if ($has_pred_count): ?>
        <span class="section-badge"><?= $has_pred_count ?> apostado<?= $has_pred_count > 1 ? 's' : '' ?></span>
      <?php endif; ?>
    </div>

    <?php foreach ($group_matches as $m): ?>
      <?php
      $locked   = is_locked($m);
      $has_both = $m['team1'] && $m['team2'];
      $pred     = $my_preds[$m['id']] ?? null;
      $result   = $m['score']['ft'] ?? null;
      $pts      = ($pred && $result) ? calc_match_points($pred, $m) : null;
      $card_cls = implode(' ', array_filter(['match-card', $locked ? 'locked' : 'open', $pred ? 'has-pred' : '']));
      ?>
      <div class="<?= $card_cls ?>">

        <div class="match-meta">
          <span class="match-round"><?= $is_group ? h($group_name) : h($m['round']) ?></span>
          <?php if ($m['kickoff_utc']): ?>
            <span class="match-time" data-utc="<?= h($m['kickoff_utc']) ?>"><?= h(format_match_time($m['kickoff_utc'])) ?></span>
          <?php endif; ?>
          <?php if ($result): ?>
            <span class="match-status live">Finalizado</span>
          <?php elseif ($locked): ?>
            <span class="match-status locked">🔒 Cerrado</span>
          <?php else: ?>
            <span class="match-status open">Abierto</span>
          <?php endif; ?>
        </div>

        <?php if ($has_both): ?>

          <?php if (!$locked && !$result): ?>
            <!-- OPEN: form wraps teams + footer together -->
            <form class="predict-form" data-match-id="<?= h($m['id']) ?>">
              <div class="match-teams">
                <div class="team">
                  <span class="team-flag"><?= team_flag($m['team1']) ?></span>
                  <span class="team-name <?= mb_strlen($m['team1']) > 10 ? 'long' : '' ?>"><?= h($m['team1']) ?></span>
                </div>
                <div class="score-area">
                  <input class="score-input" type="number" name="g1" min="0" max="30"
                         inputmode="numeric"
                         value="<?= $pred ? $pred['g1'] : '' ?>" placeholder="0">
                  <span class="score-sep">-</span>
                  <input class="score-input" type="number" name="g2" min="0" max="30"
                         inputmode="numeric"
                         value="<?= $pred ? $pred['g2'] : '' ?>" placeholder="0">
                </div>
                <div class="team">
                  <span class="team-flag"><?= team_flag($m['team2']) ?></span>
                  <span class="team-name <?= mb_strlen($m['team2']) > 10 ? 'long' : '' ?>"><?= h($m['team2']) ?></span>
                </div>
              </div>
              <div class="match-footer">
                <div class="pred-info">
                  <?php if ($pred): ?>
                    <span class="text-muted" style="font-size:.75rem">Tu predicción:</span>
                    <span class="pred-score"><?= $pred['g1'] ?> - <?= $pred['g2'] ?></span>
                  <?php else: ?>
                    <span class="text-muted" style="font-size:.75rem">Sin predicción</span>
                  <?php endif; ?>
                </div>
                <button type="submit" class="save-btn"><?= $pred ? 'Cambiar' : 'Guardar' ?></button>
              </div>
            </form>

          <?php else: ?>
            <!-- LOCKED / FINISHED: no form -->
            <div class="match-teams">
              <div class="team">
                <span class="team-flag"><?= team_flag($m['team1']) ?></span>
                <span class="team-name <?= mb_strlen($m['team1']) > 10 ? 'long' : '' ?>"><?= h($m['team1']) ?></span>
              </div>
              <div class="score-area">
                <?php if ($result): ?>
                  <div class="result-score">
                    <span><?= $result[0] ?></span>
                    <span class="sep">-</span>
                    <span><?= $result[1] ?></span>
                  </div>
                <?php else: ?>
                  <div class="result-score">
                    <span style="color:var(--text-dim)">?</span>
                    <span class="sep">-</span>
                    <span style="color:var(--text-dim)">?</span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="team">
                <span class="team-flag"><?= team_flag($m['team2']) ?></span>
                <span class="team-name <?= mb_strlen($m['team2']) > 10 ? 'long' : '' ?>"><?= h($m['team2']) ?></span>
              </div>
            </div>
            <div class="match-footer">
              <?php if ($pred): ?>
                <div class="pred-info">
                  <span class="text-muted" style="font-size:.75rem">Tu apuesta:</span>
                  <span class="pred-score"><?= $pred['g1'] ?> - <?= $pred['g2'] ?></span>
                </div>
                <?php if ($pts !== null): ?>
                  <span class="pred-pts pts-<?= $pts ?>">+<?= $pts ?> pts</span>
                <?php else: ?>
                  <span class="pred-pts pending">Pendiente</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted" style="font-size:.75rem">Sin predicción</span>
                <?php if ($result): ?>
                  <span style="font-size:.78rem;color:var(--text-dim)">0 pts</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        <?php else: /* Teams TBD */ ?>
          <div class="match-teams">
            <div class="team">
              <span class="team-flag">🏳️</span>
              <span class="team-name" style="color:var(--text-muted)"><?= h($m['team1_ref'] ?? 'Por det.') ?></span>
            </div>
            <div class="score-area">
              <div class="result-score">
                <span style="color:var(--text-dim)">?</span>
                <span class="sep">-</span>
                <span style="color:var(--text-dim)">?</span>
              </div>
            </div>
            <div class="team">
              <span class="team-flag">🏳️</span>
              <span class="team-name" style="color:var(--text-muted)"><?= h($m['team2_ref'] ?? 'Por det.') ?></span>
            </div>
          </div>
          <div class="match-footer">
            <span class="text-muted" style="font-size:.75rem">⏳ Equipos por determinar</span>
          </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php endif; ?>
</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

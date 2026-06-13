<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$user = require_login();
$matches = get_matches(); // already sorted by kickoff_utc

$all_preds = read_json(DATA_DIR . '/predictions.json');
$my_preds  = [];
foreach ($all_preds as $p) {
    if ($p['user_id'] === $user['id']) $my_preds[$p['match_id']] = $p;
}

// Group by date (for date-separator headers)
$by_date = [];
foreach ($matches as $m) {
    $key = $m['date'] ?: 'tbd';
    $by_date[$key][] = $m;
}

// Find first open match (for scroll anchor)
$first_open_id = null;
foreach ($matches as $m) {
    if (!is_locked($m) && $m['team1'] && $m['team2']) {
        $first_open_id = $m['id'];
        break;
    }
}

$my_count = count($my_preds);
$total    = count($matches);

// Date header formatter
function date_header(string $date): string {
    if ($date === 'tbd') return 'Fecha por determinar';
    $ts = @strtotime($date);
    if (!$ts) return $date;
    $days   = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb'];
    $months = ['Jan'=>'Ene','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Abr','May'=>'May','Jun'=>'Jun',
               'Jul'=>'Jul','Aug'=>'Ago','Sep'=>'Sep','Oct'=>'Oct','Nov'=>'Nov','Dec'=>'Dic'];
    $d = $days[date('D',$ts)] ?? date('D',$ts);
    $m = $months[date('M',$ts)] ?? date('M',$ts);
    return $d . ' ' . date('j', $ts) . ' ' . $m;
}

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
  <button class="tab-btn" data-filter="open">Abiertos</button>
  <button class="tab-btn" data-filter="done">Finalizados</button>
</div>

<div id="matches-list" style="padding: 0 16px">
<?php foreach ($by_date as $date_key => $day_matches): ?>

  <div class="date-group" data-date="<?= h($date_key) ?>">
    <div class="date-header-row">
      <?= h(date_header($date_key)) ?>
    </div>

    <?php foreach ($day_matches as $m): ?>
      <?php
      $locked     = is_locked($m);
      $has_both   = $m['team1'] && $m['team2'];
      $pred       = $my_preds[$m['id']] ?? null;
      $result     = $m['score']['ft'] ?? null;
      $pts        = ($pred && $result) ? calc_match_points($pred, $m) : null;
      $is_group   = !empty($m['group']);

      $classes = ['match-card'];
      if ($locked)  $classes[] = 'locked';  else $classes[] = 'open';
      if ($pred)    $classes[] = 'has-pred';
      if ($result)  $classes[] = 'has-result';
      $card_cls = implode(' ', $classes);

      $is_anchor = ($first_open_id && $m['id'] === $first_open_id);
      ?>
      <div class="<?= $card_cls ?>"<?= $is_anchor ? ' id="primer-abierto"' : '' ?>>

        <div class="match-meta">
          <span class="match-round"><?= h($is_group ? $m['group'] : $m['round']) ?></span>
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
            <form class="predict-form" data-match-id="<?= h($m['id']) ?>">
              <div class="match-teams">
                <div class="team">
                  <span class="team-flag"><?= team_flag($m['team1']) ?></span>
                  <span class="team-name <?= mb_strlen($m['team1']) > 10 ? 'long' : '' ?>"><?= h($m['team1']) ?></span>
                </div>
                <div class="score-area">
                  <input class="score-input" type="number" name="g1" min="0" max="30"
                         inputmode="numeric" value="<?= $pred ? $pred['g1'] : '' ?>" placeholder="0">
                  <span class="score-sep">-</span>
                  <input class="score-input" type="number" name="g2" min="0" max="30"
                         inputmode="numeric" value="<?= $pred ? $pred['g2'] : '' ?>" placeholder="0">
                </div>
                <div class="team">
                  <span class="team-flag"><?= team_flag($m['team2']) ?></span>
                  <span class="team-name <?= mb_strlen($m['team2']) > 10 ? 'long' : '' ?>"><?= h($m['team2']) ?></span>
                </div>
              </div>
              <div class="match-footer">
                <div class="pred-info">
                  <?php if ($pred): ?>
                    <span class="text-muted" style="font-size:.75rem">Tu apuesta:</span>
                    <span class="pred-score"><?= $pred['g1'] ?> - <?= $pred['g2'] ?></span>
                  <?php else: ?>
                    <span class="text-muted" style="font-size:.75rem">Sin predicción</span>
                  <?php endif; ?>
                </div>
                <button type="submit" class="save-btn <?= $pred ? 'saved' : '' ?>"><?= $pred ? 'Cambiar' : 'Guardar' ?></button>
              </div>
            </form>

          <?php else: ?>
            <div class="match-teams">
              <div class="team">
                <span class="team-flag"><?= team_flag($m['team1']) ?></span>
                <span class="team-name <?= mb_strlen($m['team1']) > 10 ? 'long' : '' ?>"><?= h($m['team1']) ?></span>
              </div>
              <div class="score-area">
                <?php if ($result): ?>
                  <div class="result-score">
                    <span><?= $result[0] ?></span><span class="sep">-</span><span><?= $result[1] ?></span>
                  </div>
                <?php else: ?>
                  <div class="result-score">
                    <span style="color:var(--text-dim)">?</span><span class="sep">-</span><span style="color:var(--text-dim)">?</span>
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

        <?php else: ?>
          <div class="match-teams">
            <div class="team">
              <span class="team-flag">🏳️</span>
              <span class="team-name" style="color:var(--text-muted)"><?= h($m['team1_ref'] ?? 'Por det.') ?></span>
            </div>
            <div class="score-area">
              <div class="result-score">
                <span style="color:var(--text-dim)">?</span><span class="sep">-</span><span style="color:var(--text-dim)">?</span>
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
</div>

<?php endif; ?>
</div>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

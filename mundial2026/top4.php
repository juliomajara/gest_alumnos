<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$user    = require_login();
$matches = get_matches();
$teams   = get_all_teams($matches);
$locked  = is_qf_started($matches);
$my_top4 = get_user_top4($user['id']);

// Find first QF kickoff for countdown
$qf_kickoff = null;
foreach ($matches as $m) {
    $round_lc = strtolower($m['round']);
    foreach (QF_ROUND_KEYWORDS as $kw) {
        if (str_contains($round_lc, $kw) && $m['kickoff_utc']) {
            if (!$qf_kickoff || strtotime($m['kickoff_utc']) < strtotime($qf_kickoff)) {
                $qf_kickoff = $m['kickoff_utc'];
            }
        }
    }
}

$medals   = [1 => '🥇', 2 => '🥈', 3 => '🥉', 4 => '4️⃣'];
$pos_name = [1 => 'Campeón', 2 => 'Subcampeón', 3 => 'Tercer puesto', 4 => 'Cuarto puesto'];
$pos_pts  = PTS_POS;
$pts_base = PTS_IN_TOP4;

$page_title  = 'Top 4';
$active_page = 'top4';
$assets_base = '';
include 'includes/head.php';
?>

<div class="top-bar">
  <div class="top-bar-title">
    <span class="ball">🏆</span> Top 4
  </div>
  <div class="top-bar-actions">
    <?php if ($locked): ?>
      <span class="top-bar-badge" style="color:var(--danger)">🔒 Cerrado</span>
    <?php elseif ($my_top4): ?>
      <span class="top-bar-badge" style="color:var(--success)">✅ Guardado</span>
    <?php endif; ?>
  </div>
</div>

<div class="main-content">

  <div style="padding: 16px 0 0">
    <div class="top4-info">
      <h3>🎯 ¿Cómo funciona?</h3>
      <ul>
        <li>Elige qué 4 selecciones quedarán en el Top 4</li>
        <li>+<?= $pts_base ?> punto por cada equipo que acierte en el top 4 (sin importar posición)</li>
        <li>+<?= $pos_pts[1] ?> puntos extra si aciertas el campeón</li>
        <li>+<?= $pos_pts[2] ?> puntos extra si aciertas el subcampeón</li>
        <li>+<?= $pos_pts[3] ?> puntos extra si aciertas el tercer puesto</li>
        <li>+<?= $pos_pts[4] ?> puntos extra si aciertas el cuarto puesto</li>
        <li>Máximo: <?= ($pts_base + $pos_pts[1]) + ($pts_base + $pos_pts[2]) + ($pts_base + $pos_pts[3]) + ($pts_base + $pos_pts[4]) ?> puntos en total</li>
      </ul>
    </div>
  </div>

  <?php if ($locked): ?>
    <div class="locked-badge">🔒 Los cuartos de final ya han comenzado. El plazo de apuestas está cerrado.</div>
  <?php elseif ($qf_kickoff): ?>
    <div class="deadline-badge">
      ⏳ Plazo: antes del primer cuarto de final —
      <strong id="countdown-qf" data-countdown="<?= h($qf_kickoff) ?>">…</strong>
    </div>
  <?php endif; ?>

  <div id="top4-success" class="alert success" style="display:none;margin:0 16px 8px"></div>
  <div id="top4-error"   class="alert error"   style="display:none;margin:0 16px 8px"></div>

  <form id="top4-form">
    <div class="top4-grid">
      <?php foreach ([1, 2, 3, 4] as $pos): ?>
        <div class="pos-card pos-<?= $pos ?>">
          <div class="pos-header">
            <span class="pos-medal"><?= $medals[$pos] ?></span>
            <span class="pos-title"><?= h($pos_name[$pos]) ?></span>
            <span class="pos-pts">+<?= $pts_base + $pos_pts[$pos] ?> pts</span>
          </div>
          <select class="pos-select" name="pos<?= $pos ?>" <?= $locked ? 'disabled' : '' ?> required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($teams as $team): ?>
              <option value="<?= h($team) ?>"
                <?= (isset($my_top4["pos{$pos}"]) && $my_top4["pos{$pos}"] === $team) ? 'selected' : '' ?>>
                <?= team_flag($team) ?> <?= h($team) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (!$locked): ?>
      <button type="submit" class="top4-submit">
        <?= $my_top4 ? '🔄 Actualizar mi Top 4' : '✅ Guardar mi Top 4' ?>
      </button>
    <?php elseif ($my_top4): ?>
      <div style="margin:16px;padding:14px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius)">
        <p style="font-size:.82rem;color:var(--text-muted);text-align:center">
          Tu apuesta fue guardada el <?= h(date('d M H:i', strtotime($my_top4['updated_at'] ?? $my_top4['created_at'] ?? ''))) ?>
        </p>
      </div>
    <?php else: ?>
      <div class="locked-badge" style="margin-top:0">No enviaste tu predicción antes del plazo.</div>
    <?php endif; ?>
  </form>

  <?php if (empty($teams)): ?>
    <div class="empty">
      <div class="empty-icon">📡</div>
      <p>Cargando equipos… <button onclick="location.reload()" style="margin-top:8px;padding:6px 14px;border-radius:8px;border:1px solid var(--border2);background:var(--card2);color:var(--text);cursor:pointer">Reintentar</button></p>
    </div>
  <?php endif; ?>

</div>

<script>
document.getElementById('top4-form')?.addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const err  = document.getElementById('top4-error');
  const ok   = document.getElementById('top4-success');
  const btn  = form.querySelector('.top4-submit');
  err.style.display = 'none'; ok.style.display = 'none';

  const fd = new FormData(form);
  const vals = ['pos1','pos2','pos3','pos4'].map(k => fd.get(k)).filter(Boolean);
  if (vals.length < 4) { err.textContent = 'Selecciona las 4 posiciones'; err.style.display = ''; return; }
  if (new Set(vals).size < 4) { err.textContent = 'No puedes repetir equipo'; err.style.display = ''; return; }

  if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

  try {
    const res  = await fetch('api/top4_save.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      ok.textContent = '✅ Tu predicción del Top 4 ha sido guardada.';
      ok.style.display = '';
      if (btn) { btn.textContent = '🔄 Actualizar mi Top 4'; btn.disabled = false; }
    } else {
      err.textContent = data.error || 'Error al guardar';
      err.style.display = '';
      if (btn) { btn.disabled = false; btn.textContent = '✅ Guardar mi Top 4'; }
    }
  } catch {
    err.textContent = 'Error de conexión';
    err.style.display = '';
    if (btn) { btn.disabled = false; btn.textContent = '✅ Guardar mi Top 4'; }
  }
});
</script>

<?php include 'includes/nav.php'; ?>
<?php include 'includes/footer.php'; ?>

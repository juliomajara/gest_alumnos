<?php $u = current_user(); ?>
<nav class="bottom-nav">
  <a href="partidos.php" class="nav-item <?= ($active_page ?? '') === 'partidos' ? 'active' : '' ?>">
    <span class="nav-icon">⚽</span>
    <span class="nav-label">Partidos</span>
  </a>
  <a href="top4.php" class="nav-item <?= ($active_page ?? '') === 'top4' ? 'active' : '' ?>">
    <span class="nav-icon">🏆</span>
    <span class="nav-label">Top 4</span>
  </a>
  <a href="clasificacion.php" class="nav-item <?= ($active_page ?? '') === 'clasificacion' ? 'active' : '' ?>">
    <span class="nav-icon">📊</span>
    <span class="nav-label">Ranking</span>
  </a>
  <a href="mi_perfil.php" class="nav-item <?= ($active_page ?? '') === 'perfil' ? 'active' : '' ?>">
    <span class="nav-icon">👤</span>
    <span class="nav-label"><?= $u ? h(mb_substr($u['name'], 0, 6)) : 'Perfil' ?></span>
  </a>
</nav>

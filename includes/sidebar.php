<?php
$active_page = $active_page ?? '';
$nav_items = [
  [
    'key' => 'dashboard',
    'label' => 'Dashboard',
    'href' => 'index.php',
  ],
  [
    'key' => 'alumnos',
    'label' => 'Alumnos',
    'href' => 'alumnos.php',
  ],
  [
    'key' => 'modulos',
    'label' => 'Módulos',
    'href' => 'modulos.php',
  ],
  [
    'key' => 'empresas',
    'label' => 'Empresas',
    'href' => 'empresas.php',
  ],
  [
    'key' => 'practicas',
    'label' => 'Prácticas',
    'href' => 'practicas.php',
  ],
  [
    'key' => 'calendario',
    'label' => 'Calendario',
    'href' => 'calendario.php',
  ],
  [
    'key' => 'configuracion',
    'label' => 'Configuración',
    'href' => 'configuracion.php',
  ],
];
?>
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-header">
    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-expanded="true" aria-label="Colapsar sidebar">
      <span class="sidebar-toggle-icon" id="sidebarToggleIcon" aria-hidden="true">‹</span>
      <span class="sr-only" id="sidebarToggleLabel">Colapsar sidebar</span>
    </button>
  </div>
  <nav class="nav">
    <?php foreach ($nav_items as $item): ?>
      <a
        class="nav-link<?php echo $item['key'] === $active_page ? ' active' : ''; ?>"
        href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
      >
        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
<script>
  (function () {
    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const toggleIcon = document.getElementById('sidebarToggleIcon');

    if (!sidebar || !toggle || !toggleIcon) {
      return;
    }

    const page = sidebar.closest('.page');
    const storageKey = 'sidebarCollapsed';
    let isCollapsed = localStorage.getItem(storageKey) === 'true';

    const updateSidebar = () => {
      const isExpanded = !isCollapsed;
      const ariaLabel = isCollapsed ? 'Expandir sidebar' : 'Colapsar sidebar';

      sidebar.classList.toggle('collapsed', isCollapsed);

      if (page) {
        page.classList.toggle('sidebar-collapsed', isCollapsed);
      }

      toggle.setAttribute('aria-expanded', String(isExpanded));
      toggle.setAttribute('aria-label', ariaLabel);
      toggleIcon.textContent = isCollapsed ? '›' : '‹';
    };

    updateSidebar();

    toggle.addEventListener('click', () => {
      isCollapsed = !isCollapsed;
      localStorage.setItem(storageKey, String(isCollapsed));
      updateSidebar();
    });
  })();
</script>

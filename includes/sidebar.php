<?php
$active_page = $active_page ?? '';

$current_page = basename((string) ($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? ''));

$section_pages = [
  'utilidades' => [
    'utilidades.php',
    'empresas_importar.php',
    'dump_db.php',
    'practicas_ras.php',
    'enviar_correos.php',
  ],
  'configuracion' => [
    'configuracion.php',
    'datos_centro.php',
    'calendario.php',
  ],
];

$detected_active_page = '';
foreach ($section_pages as $section_key => $pages) {
  if (in_array($current_page, $pages, true)) {
    $detected_active_page = $section_key;
    break;
  }
}

if ($detected_active_page !== '') {
  $active_page = $detected_active_page;
}

$nav_items = [
  [
    'key' => 'dashboard',
    'label' => 'Dashboard',
    'href' => 'index.php',
    'icon' => 'assets/icons/dashboard.svg',
  ],
  [
    'key' => 'alumnos',
    'label' => 'Alumnos',
    'href' => 'alumnos.php',
    'icon' => 'assets/icons/alumnos.svg',
  ],
  [
    'key' => 'calificaciones',
    'label' => 'Calificaciones',
    'href' => 'calificaciones.php',
    'icon' => 'assets/icons/evaluacion.svg',
  ],
  [
    'key' => 'modulos',
    'label' => 'Módulos',
    'href' => 'modulos.php',
    'icon' => 'assets/icons/modulos.svg',
  ],
  [
    'key' => 'empresas',
    'label' => 'Empresas',
    'href' => 'empresas.php',
    'icon' => 'assets/icons/empresas.svg',
  ],
  [
    'key' => 'practicas',
    'label' => 'Prácticas',
    'href' => 'practicas.php',
    'icon' => 'assets/icons/practicas.svg',
  ],
  [
    'key' => 'profesores',
    'label' => 'Profesores',
    'href' => 'profesores.php',
    'icon' => 'assets/icons/profesores.svg',
  ],
  [
    'key' => 'utilidades',
    'label' => 'Utilidades',
    'href' => 'utilidades.php',
    'icon' => 'assets/icons/utilidades.svg',
  ],
  [
    'key' => 'configuracion',
    'label' => 'Configuración',
    'href' => 'configuracion.php',
    'icon' => 'assets/icons/configuracion.svg',
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
      <?php $icon_key = pathinfo((string) $item['icon'], PATHINFO_FILENAME); ?>
      <a
        class="nav-link<?php echo $item['key'] === $active_page ? ' active' : ''; ?>"
        href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
      >
        <span class="nav-icon" aria-hidden="true">
          <span
            class="nav-icon-image"
            data-icon="<?php echo htmlspecialchars((string) $icon_key, ENT_QUOTES, 'UTF-8'); ?>"
          ></span>
        </span>
        <span class="nav-text"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <button class="sidebar-scroll-top is-hidden" id="sidebarScrollTop" type="button">Volver arriba</button>
</aside>
<script>
  (function () {
    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const toggleIcon = document.getElementById('sidebarToggleIcon');
    const toggleLabel = document.getElementById('sidebarToggleLabel');
    const scrollTopButton = document.getElementById('sidebarScrollTop');

    if (!sidebar || !toggle || !toggleIcon || !toggleLabel || !scrollTopButton) {
      return;
    }

    const page = sidebar.closest('.page');
    const storageKey = 'sidebar_collapsed';
    let isCollapsed = localStorage.getItem(storageKey) === '1';

    const updateSidebar = () => {
      const isExpanded = !isCollapsed;
      const ariaLabel = isCollapsed ? 'Expandir sidebar' : 'Colapsar sidebar';

      if (page) {
        page.classList.toggle('sidebar-collapsed', isCollapsed);
      }

      sidebar.classList.toggle('collapsed', isCollapsed);
      toggle.setAttribute('aria-expanded', String(isExpanded));
      toggle.setAttribute('aria-label', ariaLabel);
      toggleLabel.textContent = ariaLabel;
      toggleIcon.textContent = isCollapsed ? '›' : '‹';
    };

    const updateScrollTopVisibility = () => {
      scrollTopButton.classList.toggle('is-hidden', window.scrollY === 0);
    };

    updateSidebar();
    updateScrollTopVisibility();

    toggle.addEventListener('click', () => {
      isCollapsed = !isCollapsed;
      localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
      updateSidebar();
    });

    scrollTopButton.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', updateScrollTopVisibility, { passive: true });
  })();
</script>


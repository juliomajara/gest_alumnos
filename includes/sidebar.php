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
    'key' => 'empresas',
    'label' => 'Empresas',
    'href' => '#',
  ],
  [
    'key' => 'practicas',
    'label' => 'Prácticas',
    'href' => '#',
  ],
  [
    'key' => 'profesores',
    'label' => 'Profesores',
    'href' => '#',
  ],
  [
    'key' => 'calendario',
    'label' => 'Calendario',
    'href' => 'calendario.php',
  ],
  [
    'key' => 'configuracion',
    'label' => 'Configuración',
    'href' => '#',
  ],
];
?>
<aside class="sidebar">
  <div class="brand">
    <div class="brand-icon">GA</div>
    <div>
      <p class="brand-title">Gestor de Alumnos</p>
      <p class="brand-subtitle">Panel central</p>
    </div>
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

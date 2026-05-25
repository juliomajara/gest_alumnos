<?php
$page_title = 'Configuración | Gestor de Alumnos';
$active_page = 'configuracion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="page">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
      <header class="header">
        <div>
          <h1>Configuración</h1>
          <p class="subheading">Página de configuración del sistema.</p>
        </div>
      </header>

      <div class="util-grid">
        <a class="util-card" href="datos_centro.php">
          <span class="util-card-icon">
            <img src="assets/icons/datos_centro.svg" alt="">
          </span>
          <span class="util-card-title">Datos del centro</span>
          <span class="util-card-desc">Configuración de la información general del centro educativo.</span>
        </a>
        <a class="util-card" href="calendario.php">
          <span class="util-card-icon">
            <img src="assets/icons/calendario.svg" alt="">
          </span>
          <span class="util-card-title">Calendario</span>
          <span class="util-card-desc">Gestión del calendario académico y días no lectivos.</span>
        </a>
        <a class="util-card" href="alumnos_importar.php">
          <span class="util-card-icon">
            <img src="assets/icons/importar_alumnos.svg" alt="">
          </span>
          <span class="util-card-title">Importar alumnos</span>
          <span class="util-card-desc">Importación de alumnos desde archivos externos.</span>
        </a>
        <a class="util-card" href="practicas_ras.php">
          <span class="util-card-icon">
            <img src="assets/icons/porcentaje_ra.svg" alt="">
          </span>
          <span class="util-card-title">Porcentaje RA/CE en empresa</span>
          <span class="util-card-desc">Configuración del reparto de resultados de aprendizaje y criterios de evaluación en empresa.</span>
        </a>
      </div>
    </main>
  </div>
</body>
</html>

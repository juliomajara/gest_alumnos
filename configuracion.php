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

      <section class="panel">
        <div class="panel-header">
          <h3>Configuración</h3>
          <p>Página de configuración del sistema.</p>
        </div>
        <div class="panel-grid">
          <a class="panel-link" href="datos_centro.php"><span>Datos del centro</span></a> <?php // MODIFICADO ?>
          <a class="panel-link" href="calendario.php"><span>Calendario</span></a> <?php // MODIFICADO ?>
          <a class="panel-link" href="alumnos_importar.php"><span>Importar alumnos</span></a>
          <a class="panel-link" href="practicas_ras.php"><span>Porcentaje RA/CE en empresa</span></a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

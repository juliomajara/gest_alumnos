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
          <h1>Utilidades</h1>
          <p class="subheading">Administra importaciones y accesos clave desde un único panel.</p>
        </div>
      </header>

      <div class="util-grid">
        <a class="util-card" href="asistencia_importar.php">
          <span class="util-card-icon">
            <img src="assets/icons/asistencia.svg" alt="">
          </span>
          <span class="util-card-title">Importar asistencia</span>
          <span class="util-card-desc">Importa la asistencia desde el CSV correspondiente.</span>
        </a>
        <a class="util-card" href="importar_calificaciones.php">
          <span class="util-card-icon">
            <img src="assets/icons/calificaciones.svg" alt="">
          </span>
          <span class="util-card-title">Importar calificaciones</span>
          <span class="util-card-desc">Importa notas desde CSV para un contexto académico concreto.</span>
        </a>

<a class="util-card" href="enviar_correos.php">
          <span class="util-card-icon">
            <img src="assets/icons/enviar_correos.svg" alt="">
          </span>
          <span class="util-card-title">Envío de correos</span>
          <span class="util-card-desc">Envía comunicaciones masivas desde la plataforma.</span>
        </a>
        <a class="util-card" href="dump_db.php">
          <span class="util-card-icon">
            <img src="assets/icons/backup.svg" alt="">
          </span>
          <span class="util-card-title">Backup de base de datos</span>
          <span class="util-card-desc">Genera y gestiona copias de seguridad completas de la base de datos.</span>
        </a>
        <a class="util-card util-card--disabled" href="#" aria-disabled="true" tabindex="-1">
          <span class="util-card-icon">
            <img src="assets/icons/acceso_ffe.svg" alt="">
          </span>
          <span class="util-card-title">Acceso a FFE</span>
          <span class="util-card-desc">Próximamente (enlace en preparación).</span>
        </a>
      </div>
    </main>
  </div>
</body>
</html>

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

      <section class="grid">
        <article class="panel">
          <div class="panel-header">
            <h3>Panel de opciones</h3>
            <p>Accesos rápidos para la gestión de importaciones y utilidades.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link" href="asistencia_importar.php">
              <span>Importar asistencia</span>
              <small>Importa la asistencia desde el CSV correspondiente.</small>
            </a>
            <a class="panel-link panel-link-with-icon" href="importar_calificaciones.php">
              <span class="nav-icon" aria-hidden="true">
                <img class="nav-icon-image" src="assets/icons/calificaciones.svg" alt="">
              </span>
              <span class="panel-link-text">
                <span>Importar calificaciones</span>
                <small>Importa notas desde CSV para un contexto académico concreto.</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="practicas_dias.php">
              <span class="nav-icon" aria-hidden="true">
                <img class="nav-icon-image" src="assets/icons/dias_practicas.svg" alt="">
              </span>
              <span class="panel-link-text">
                <span>Días de prácticas por alumno</span>
                <small>Consulta el resumen mensual y las horas realizadas.</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="practicas_documentacion.php">
              <span class="nav-icon" aria-hidden="true">
                <img class="nav-icon-image" src="assets/icons/documentacion.svg" alt="">
              </span>
              <span class="panel-link-text">
                <span>Documentación de prácticas</span>
                <small>Genera programas formativos y calendarios del curso actual.</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="enviar_correos.php">
              <span class="nav-icon" aria-hidden="true">
                <img class="nav-icon-image" src="assets/icons/enviar_correos.svg" alt="">
              </span>
              <span class="panel-link-text">
                <span>Envío de correos</span>
                <small>Envía comunicaciones masivas desde la plataforma.</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="#" aria-disabled="true">
              <span class="nav-icon" aria-hidden="true">
                <img class="nav-icon-image" src="assets/icons/acceso_ffe.svg" alt="">
              </span>
              <span class="panel-link-text">
                <span>Acceso a FFE</span>
                <small>Próximamente (enlace en preparación).</small>
              </span>
            </a>
          </div>
        </article>

        <article class="panel">
          <div class="panel-header">
            <h3>Más opciones</h3>
            <p>Espacio preparado para nuevos accesos de configuración.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link" href="#" aria-disabled="true">
              <span>Reservado para futuras opciones</span>
              <small>Añade aquí nuevos botones cuando estén disponibles.</small>
            </a>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>

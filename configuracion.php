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
          <p class="eyebrow">Configuración</p>
          <h1>Acciones rápidas</h1>
          <p class="subheading">Gestiona importaciones y genera documentos clave desde este panel.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Procesos disponibles</h3>
          <p>Selecciona la acción que quieres ejecutar. Próximamente añadiremos más botones.</p>
        </div>

        <div class="panel-grid">
          <button class="primary-button" type="button">Importar alumnos</button>
          <button class="primary-button" type="button">Importar empresas</button>
          <button class="primary-button" type="button">Generar anexos de acceso a FFE</button>
          <p class="subheading">Espacio reservado para nuevas acciones.</p>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

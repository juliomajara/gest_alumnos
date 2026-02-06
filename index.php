<?php
$page_title = 'Dashboard | Gestor de Alumnos';
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
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">GA</div>
        <div>
          <p class="brand-title">Gestor de Alumnos</p>
          <p class="brand-subtitle">Panel central</p>
        </div>
      </div>
      <nav class="nav">
        <a class="nav-link active" href="index.php">Dashboard</a>
        <a class="nav-link" href="#">Alumnos</a>
        <a class="nav-link" href="#">Cursos</a>
        <a class="nav-link" href="#">Profesores</a>
        <a class="nav-link" href="#">Evaluaciones</a>
        <a class="nav-link" href="#">Configuración</a>
      </nav>
      <div class="sidebar-footer">
        <p>Acceso rápido a todos los módulos.</p>
        <button class="primary-button" type="button">Nueva entrada</button>
      </div>
    </aside>

    <main class="content">
      <header class="header">
        <div>
          <p class="eyebrow">Bienvenido</p>
          <h1>Dashboard general</h1>
          <p class="subheading">Visualiza el estado de tu base de datos y accede a cada sección con un clic.</p>
        </div>
        <div class="header-actions">
          <button class="ghost-button" type="button">Exportar</button>
          <button class="primary-button" type="button">Crear reporte</button>
        </div>
      </header>

      <section class="cards">
        <article class="card highlight">
          <div>
            <p class="card-label">Registros totales</p>
            <h2>2,450</h2>
            <p class="card-note">+12% este mes</p>
          </div>
          <span class="card-badge">Resumen</span>
        </article>
        <article class="card">
          <div>
            <p class="card-label">Alumnos activos</p>
            <h2>1,835</h2>
            <p class="card-note">98% asistencia media</p>
          </div>
          <span class="card-badge">Academia</span>
        </article>
        <article class="card">
          <div>
            <p class="card-label">Cursos abiertos</p>
            <h2>42</h2>
            <p class="card-note">15 nuevos esta semana</p>
          </div>
          <span class="card-badge">Oferta</span>
        </article>
        <article class="card">
          <div>
            <p class="card-label">Solicitudes pendientes</p>
            <h2>18</h2>
            <p class="card-note">Requieren revisión</p>
          </div>
          <span class="card-badge">Alertas</span>
        </article>
      </section>

      <section class="grid">
        <article class="panel">
          <div class="panel-header">
            <h3>Accesos directos</h3>
            <p>Accede a cada módulo de la base de datos.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link" href="#">
              <span>Gestión de alumnos</span>
              <small>Altas, bajas y seguimiento.</small>
            </a>
            <a class="panel-link" href="#">
              <span>Catálogo de cursos</span>
              <small>Programación y horarios.</small>
            </a>
            <a class="panel-link" href="#">
              <span>Docentes</span>
              <small>Asignaciones y disponibilidad.</small>
            </a>
            <a class="panel-link" href="#">
              <span>Evaluaciones</span>
              <small>Notas y observaciones.</small>
            </a>
          </div>
        </article>

        <article class="panel">
          <div class="panel-header">
            <h3>Actividad reciente</h3>
            <p>Resumen de los últimos movimientos.</p>
          </div>
          <ul class="activity">
            <li>
              <div>
                <strong>Nuevo alumno registrado</strong>
                <p>María Hernández · 10:32</p>
              </div>
              <span class="status">Completado</span>
            </li>
            <li>
              <div>
                <strong>Curso actualizado</strong>
                <p>Matemáticas · 09:18</p>
              </div>
              <span class="status warning">Pendiente</span>
            </li>
            <li>
              <div>
                <strong>Reporte generado</strong>
                <p>Alumnos activos · Ayer</p>
              </div>
              <span class="status">Completado</span>
            </li>
          </ul>
        </article>
      </section>
    </main>
  </div>
</body>
</html>

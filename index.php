<?php
$page_title = 'Dashboard | Gestor de Alumnos';
$active_page = 'dashboard';
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
      <div class="topbar">
        <div class="topbar-search">
          <span class="search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"></circle>
              <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
            </svg>
          </span>
          <input type="search" placeholder="Buscar alumnos, empresas, profesores" aria-label="Buscar alumnos, empresas y profesores">
        </div>
        <div class="topbar-actions">
          <button class="ghost-button" type="button">Notificaciones</button>
          <button class="primary-button" type="button">Nuevo registro</button>
        </div>
      </div>

      <header class="header">
        <div>
          <p class="eyebrow">Bienvenido</p>
          <h1>Dashboard general</h1>
          <p class="subheading">Visualiza el estado de tu base de datos y accede a cada sección con un clic.</p>
        </div>
        <div class="header-actions">
          <button class="ghost-button" type="button">Exportar</button>
          <button class="edit-toggle" type="button" id="editToggle">Modo edición</button>
        </div>
      </header>

      <section class="cards">
        <article class="card highlight">
          <div>
            <p class="card-label">Alumnos totales</p>
            <h2>1,284</h2>
            <p class="card-note">Censo completo actualizado</p>
          </div>
          <span class="card-badge">General</span>
        </article>
        <article class="card">
          <div>
            <p class="card-label">Alumnos de tutoría</p>
            <h2>326</h2>
            <p class="card-note">Seguimiento con tutores asignados</p>
          </div>
          <span class="card-badge">Tutorías</span>
        </article>
        <article class="card">
          <div>
            <p class="card-label">Alumnos con empresa asignada</p>
            <h2>892</h2>
            <p class="card-note">Prácticas gestionadas activamente</p>
          </div>
          <span class="card-badge">Empresas</span>
        </article>
      </section>

      <section class="calendar-panel">
        <div class="calendar-header">
          <div>
            <p class="eyebrow">Calendario</p>
            <h2>Planificación de 4 meses</h2>
            <p class="subheading">Consulta hitos, tutorías y entregas previstas en el calendario.</p>
          </div>
          <div class="calendar-actions">
            <button class="ghost-button" type="button">Hoy</button>
          </div>
        </div>
        <div class="calendar-grid">
          <article class="month-card">
            <div class="month-header">
              <h3>Febrero de 2026</h3>
            </div>
            <div class="month-weekdays">
              <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="month-days">
              <span class="day muted">26</span><span class="day muted">27</span><span class="day muted">28</span><span class="day muted">29</span><span class="day muted">30</span><span class="day muted">31</span>
              <span class="day weekend">1</span>
              <span class="day">2</span><span class="day">3</span><span class="day">4</span><span class="day">5</span><span class="day today">6</span><span class="day">7</span><span class="day weekend">8</span>
              <span class="day">9</span><span class="day">10</span><span class="day">11</span><span class="day">12</span><span class="day">13</span><span class="day event">14</span><span class="day weekend">15</span>
              <span class="day">16</span><span class="day">17</span><span class="day">18</span><span class="day">19</span><span class="day">20</span><span class="day event">21</span><span class="day weekend">22</span>
              <span class="day">23</span><span class="day">24</span><span class="day">25</span><span class="day">26</span><span class="day">27</span><span class="day">28</span><span class="day weekend muted">1</span>
            </div>
          </article>
          <article class="month-card">
            <div class="month-header">
              <h3>Marzo de 2026</h3>
            </div>
            <div class="month-weekdays">
              <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="month-days">
              <span class="day muted">23</span><span class="day muted">24</span><span class="day muted">25</span><span class="day muted">26</span><span class="day muted">27</span><span class="day muted">28</span>
              <span class="day weekend">1</span>
              <span class="day">2</span><span class="day">3</span><span class="day">4</span><span class="day">5</span><span class="day">6</span><span class="day event">7</span><span class="day weekend">8</span>
              <span class="day">9</span><span class="day">10</span><span class="day">11</span><span class="day">12</span><span class="day">13</span><span class="day event">14</span><span class="day weekend">15</span>
              <span class="day">16</span><span class="day">17</span><span class="day">18</span><span class="day">19</span><span class="day">20</span><span class="day event">21</span><span class="day weekend">22</span>
              <span class="day">23</span><span class="day">24</span><span class="day">25</span><span class="day">26</span><span class="day event">27</span><span class="day">28</span><span class="day weekend">29</span>
              <span class="day">30</span><span class="day">31</span><span class="day muted">1</span><span class="day muted">2</span><span class="day muted">3</span><span class="day muted">4</span><span class="day weekend muted">5</span>
            </div>
          </article>
          <article class="month-card">
            <div class="month-header">
              <h3>Abril de 2026</h3>
            </div>
            <div class="month-weekdays">
              <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="month-days">
              <span class="day muted">30</span><span class="day muted">31</span>
              <span class="day">1</span><span class="day">2</span><span class="day">3</span><span class="day">4</span><span class="day weekend">5</span>
              <span class="day">6</span><span class="day event">7</span><span class="day">8</span><span class="day">9</span><span class="day">10</span><span class="day">11</span><span class="day weekend">12</span>
              <span class="day">13</span><span class="day">14</span><span class="day">15</span><span class="day event">16</span><span class="day">17</span><span class="day">18</span><span class="day weekend">19</span>
              <span class="day">20</span><span class="day">21</span><span class="day">22</span><span class="day">23</span><span class="day">24</span><span class="day event">25</span><span class="day weekend">26</span>
              <span class="day">27</span><span class="day">28</span><span class="day">29</span><span class="day">30</span><span class="day muted">1</span><span class="day muted">2</span><span class="day weekend muted">3</span>
            </div>
          </article>
          <article class="month-card">
            <div class="month-header">
              <h3>Mayo de 2026</h3>
            </div>
            <div class="month-weekdays">
              <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="month-days">
              <span class="day muted">27</span><span class="day muted">28</span><span class="day muted">29</span><span class="day muted">30</span>
              <span class="day">1</span><span class="day">2</span><span class="day weekend">3</span>
              <span class="day">4</span><span class="day">5</span><span class="day">6</span><span class="day">7</span><span class="day">8</span><span class="day event">9</span><span class="day weekend">10</span>
              <span class="day">11</span><span class="day">12</span><span class="day">13</span><span class="day">14</span><span class="day event">15</span><span class="day">16</span><span class="day weekend">17</span>
              <span class="day">18</span><span class="day">19</span><span class="day">20</span><span class="day">21</span><span class="day">22</span><span class="day event">23</span><span class="day weekend">24</span>
              <span class="day">25</span><span class="day">26</span><span class="day">27</span><span class="day">28</span><span class="day">29</span><span class="day">30</span><span class="day weekend">31</span>
            </div>
          </article>
        </div>
        <div class="calendar-legend">
          <span><span class="legend-dot today"></span>Hoy</span>
          <span><span class="legend-dot event"></span>Día con hitos</span>
          <span><span class="legend-dot weekend"></span>Fin de semana</span>
          <span><span class="legend-dot muted"></span>Otro mes</span>
        </div>
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
  <script>
    const editToggle = document.getElementById('editToggle');
    const storageKey = 'calendarEditMode';
    let editMode = localStorage.getItem(storageKey) === 'true';

    const updateEditButton = () => {
      editToggle.classList.toggle('is-active', editMode);
      editToggle.textContent = editMode ? 'Modo edición activado' : 'Modo edición';
    };

    updateEditButton();

    editToggle.addEventListener('click', () => {
      editMode = !editMode;
      localStorage.setItem(storageKey, String(editMode));
      updateEditButton();
    });
  </script>
</body>
</html>

<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'];

  if ($action === 'toggle_non_lectivo') {
    try {
      $date = $_POST['date'] ?? '';
      $date_obj = DateTime::createFromFormat('Y-m-d', $date);
      $valid_date = $date_obj && $date_obj->format('Y-m-d') === $date;

      if (!$valid_date) {
        echo json_encode(['ok' => false, 'message' => 'Fecha inválida.']);
        exit;
      }

      $stmt = $pdo->prepare('SELECT id FROM no_lectivos WHERE fecha = ?');
      $stmt->execute([$date]);
      $existing = $stmt->fetchColumn();

      if ($existing) {
        $delete = $pdo->prepare('DELETE FROM no_lectivos WHERE fecha = ?');
        $delete->execute([$date]);
        echo json_encode(['ok' => true, 'selected' => false]);
        exit;
      }

      $insert = $pdo->prepare('INSERT INTO no_lectivos (fecha) VALUES (?)');
      $insert->execute([$date]);
      echo json_encode(['ok' => true, 'selected' => true]);
      exit;
    } catch (Throwable $exception) {
      echo json_encode(['ok' => false, 'message' => 'Error al actualizar día no lectivo: ' . $exception->getMessage()]);
      exit;
    }
  }

  // MODIFICADO
  if ($action === 'toggle_tutoria') {
    try {
      $date = $_POST['date'] ?? '';
      $date_obj = DateTime::createFromFormat('Y-m-d', $date);
      $valid_date = $date_obj && $date_obj->format('Y-m-d') === $date;

      if (!$valid_date) {
        echo json_encode(['ok' => false, 'message' => 'Fecha inválida.']);
        exit;
      }

      $stmt = $pdo->prepare('SELECT id_tutoria FROM tutorias WHERE fecha = ?');
      $stmt->execute([$date]);
      $existing = $stmt->fetchColumn();

      if ($existing) {
        $delete = $pdo->prepare('DELETE FROM tutorias WHERE fecha = ?');
        $delete->execute([$date]);
        echo json_encode(['ok' => true, 'selected' => false]);
        exit;
      }

      $insert = $pdo->prepare('INSERT INTO tutorias (fecha) VALUES (?)');
      $insert->execute([$date]);
      echo json_encode(['ok' => true, 'selected' => true]);
      exit;
    } catch (Throwable $exception) {
      echo json_encode(['ok' => false, 'message' => 'Error al actualizar tutoría: ' . $exception->getMessage()]);
      exit;
    }
  }

  echo json_encode(['ok' => false, 'message' => 'Acción no permitida.']);
  exit;
}

$courses = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, curso_escolar DESC')->fetchAll();
$active_course = $courses[0] ?? null;

$selected_course_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
$selected_course = null;
foreach ($courses as $course) {
  if ($course['id_curso_escolar'] === $selected_course_id) {
    $selected_course = $course;
    break;
  }
}

if (!$selected_course) {
  $selected_course = $active_course;
  $selected_course_id = $selected_course['id_curso_escolar'] ?? 0;
}

$course_label = $selected_course['curso_escolar'] ?? 'Curso activo';
$years = array_map('intval', explode('/', $course_label));
$start_year = $years[0] ?? (int) date('Y');
$end_year = $years[1] ?? ($start_year + 1);

$months = $pdo->query('SELECT id_mes, mes FROM meses ORDER BY orden')->fetchAll();
$no_lectivos = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN);
$no_lectivos_lookup = array_fill_keys($no_lectivos, true);
// MODIFICADO
$tutorias = $pdo->query('SELECT fecha FROM tutorias')->fetchAll(PDO::FETCH_COLUMN);
// MODIFICADO
$tutorias_lookup = array_fill_keys($tutorias, true);

function course_year_for_month(int $month_number, int $start_year, int $end_year): int {
  return $month_number >= 9 ? $start_year : $end_year;
}

$page_title = 'Calendario | Gestor de Alumnos';
$active_page = 'calendario';
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
          <h1>Calendario del curso <?php echo htmlspecialchars($course_label, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Selecciona un curso escolar y marca los días no lectivos para ajustar el calendario.</p>
          <form method="get" class="calendar-select-form">
            <label class="calendar-select">
              <select name="curso_id" onchange="this.form.submit()">
                <?php foreach ($courses as $course): ?>
                  <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </form>
        </div>
        <div class="header-actions">
          <button class="edit-toggle" type="button" id="editToggle">Activar modo edición</button>
          <!-- MODIFICADO -->
          <button class="edit-toggle edit-toggle-success" type="button" id="modeNonLectivo" hidden>No lectivo</button>
          <!-- MODIFICADO -->
          <button class="edit-toggle" type="button" id="modeTutoria" hidden>Tutoría</button>
        </div>
      </header>

      <section class="calendar-grid">
        <?php foreach ($months as $month): ?>
          <?php
            $month_number = (int) $month['id_mes'];
            $month_year = course_year_for_month($month_number, $start_year, $end_year);
            $first_day = new DateTime(sprintf('%04d-%02d-01', $month_year, $month_number));
            $days_in_month = (int) $first_day->format('t');
            $weekday_start = (int) $first_day->format('N');
          ?>
          <article class="calendar-month">
            <header class="calendar-month-title">
              <h3><?php echo htmlspecialchars($month['mes'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <span><?php echo $month_year; ?></span>
            </header>
            <div class="calendar-weekdays">
              <span>Lun</span>
              <span>Mar</span>
              <span>Mié</span>
              <span>Jue</span>
              <span>Vie</span>
              <span>Sáb</span>
              <span>Dom</span>
            </div>
            <div class="calendar-days">
              <?php for ($i = 1; $i < $weekday_start; $i++): ?>
                <div class="calendar-day is-empty"></div>
              <?php endfor; ?>
              <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                <?php
                  $date_value = sprintf('%04d-%02d-%02d', $month_year, $month_number, $day);
                  $day_of_week = (int) (new DateTime($date_value))->format('N');
                  $is_weekend = $day_of_week >= 6;
                  $is_no_lectivo = isset($no_lectivos_lookup[$date_value]);
                  // MODIFICADO
                  $is_tutoria = isset($tutorias_lookup[$date_value]);
                  $classes = ['calendar-day'];
                  if ($is_weekend) {
                    $classes[] = 'is-weekend';
                  }
                  // MODIFICADO
                  if ($is_no_lectivo && !$is_tutoria) {
                    $classes[] = 'is-nonlectivo';
                  }
                  // MODIFICADO
                  if ($is_tutoria) {
                    $classes[] = 'is-tutoria';
                  }
                ?>
                <button
                  class="<?php echo implode(' ', $classes); ?>"
                  type="button"
                  data-date="<?php echo $date_value; ?>"
                  data-nonlectivo="<?php echo $is_no_lectivo ? '1' : '0'; ?>"
                  data-tutoria="<?php echo $is_tutoria ? '1' : '0'; ?>"
                  aria-label="<?php echo $date_value; ?>"
                >
                  <?php echo $day; ?>
                </button>
              <?php endfor; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    </main>
  </div>

  <script>
    const editToggle = document.getElementById('editToggle');
    // MODIFICADO
    const modeNonLectivo = document.getElementById('modeNonLectivo');
    // MODIFICADO
    const modeTutoria = document.getElementById('modeTutoria');
    const storageKey = 'calendarEditMode';
    let editMode = localStorage.getItem(storageKey) === 'true';
    // MODIFICADO
    let markMode = 'nonlectivo';

    // MODIFICADO
    const updateModeButtons = () => {
      modeNonLectivo.classList.toggle('edit-toggle-success', markMode === 'nonlectivo');
      modeTutoria.classList.toggle('edit-toggle-success', markMode === 'tutoria');
    };

    const updateEditButton = () => {
      editToggle.classList.toggle('is-active', editMode);
      editToggle.textContent = editMode ? 'Desactivar modo edición' : 'Activar modo edición';
      modeNonLectivo.hidden = !editMode;
      modeTutoria.hidden = !editMode;
    };

    updateEditButton();
    // MODIFICADO
    updateModeButtons();

    editToggle.addEventListener('click', () => {
      editMode = !editMode;
      localStorage.setItem(storageKey, String(editMode));
      updateEditButton();
    });

    // MODIFICADO
    modeNonLectivo.addEventListener('click', () => {
      markMode = 'nonlectivo';
      updateModeButtons();
    });

    // MODIFICADO
    modeTutoria.addEventListener('click', () => {
      markMode = 'tutoria';
      updateModeButtons();
    });

    document.querySelectorAll('.calendar-day[data-date]').forEach((day) => {
      day.addEventListener('click', async () => {
        if (!editMode) return;
        const date = day.dataset.date;
        // MODIFICADO
        const action = markMode === 'tutoria' ? 'toggle_tutoria' : 'toggle_non_lectivo';
        const formData = new FormData();
        formData.append('action', action);
        formData.append('date', date);

        try {
          const response = await fetch('calendario.php', {
            method: 'POST',
            body: formData,
          });
          const data = await response.json();
          if (!data.ok) {
            alert(data.message || 'No se pudo guardar el cambio.');
            return;
          }
          if (markMode === 'tutoria') {
            day.dataset.tutoria = data.selected ? '1' : '0';
            day.classList.toggle('is-tutoria', data.selected);
            if (data.selected) {
              if (day.dataset.nonlectivo === '1') {
                const nonLectivoData = new FormData();
                nonLectivoData.append('action', 'toggle_non_lectivo');
                nonLectivoData.append('date', date);
                const nonLectivoResponse = await fetch('calendario.php', {
                  method: 'POST',
                  body: nonLectivoData,
                });
                const nonLectivoResult = await nonLectivoResponse.json();
                if (nonLectivoResult.ok) {
                  day.dataset.nonlectivo = '0';
                }
              }
              day.classList.remove('is-nonlectivo');
            }
          } else {
            day.dataset.nonlectivo = data.selected ? '1' : '0';
            day.classList.toggle('is-nonlectivo', data.selected);
            if (data.selected && day.dataset.tutoria === '1') {
              const tutoriaData = new FormData();
              tutoriaData.append('action', 'toggle_tutoria');
              tutoriaData.append('date', date);
              const tutoriaResponse = await fetch('calendario.php', {
                method: 'POST',
                body: tutoriaData,
              });
              const tutoriaResult = await tutoriaResponse.json();
              if (tutoriaResult.ok) {
                day.dataset.tutoria = '0';
                day.classList.remove('is-tutoria');
              }
            }
          }
        } catch (error) {
          console.error(error);
          alert('No se pudo guardar el cambio.');
        }
      });
    });
  </script>
</body>
</html>

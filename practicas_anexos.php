<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function normalize_text(string $value): string
{
  $value = trim($value);
  $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
  $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

  return $value;
}

function resolve_anexo_code(string $value): string
{
  $normalized = normalize_text($value);
  if (preg_match('/2\s*\.\s*1/u', $normalized) === 1) {
    return '2.1';
  }
  if (preg_match('/2\s*\.\s*2/u', $normalized) === 1) {
    return '2.2';
  }
  if (preg_match('/(^|\D)3(\D|$)/u', $normalized) === 1) {
    return '3';
  }
  if (preg_match('/(^|\D)4(\D|$)/u', $normalized) === 1) {
    return '4';
  }
  if (preg_match('/(^|\D)7(\D|$)/u', $normalized) === 1) {
    return '7';
  }
  if (preg_match('/(^|\D)8(\D|$)/u', $normalized) === 1) {
    return '8';
  }

  return '';
}

function calculate_practice_status(array $practice): string
{
  if ((int) ($practice['cancelada'] ?? 0) === 1) {
    return 'Cancelada';
  }

  $fecha_inicio = (string) ($practice['fecha_inicio'] ?? '');
  $fecha_fin_extra = (string) ($practice['fecha_fin_extra'] ?? '');

  if ($fecha_inicio === '' || $fecha_fin_extra === '') {
    return 'No disponible';
  }

  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  if ($today < $fecha_inicio) {
    return 'En espera';
  }

  if ($today <= $fecha_fin_extra) {
    return 'En curso';
  }

  return 'Finalizada';
}

function render_practicas_anexos_lista(array $practices, array $anexos_definiciones, array $marcados): string
{
  ob_start();
  ?>
  <div class="practicas-anexos-lista" data-practicas-anexos-list>
    <?php if ($practices === []): ?>
      <p>No hay prácticas para los filtros seleccionados.</p>
    <?php endif; ?>

    <?php foreach ($practices as $practice): ?>
      <?php
        $id_practica = (int) $practice['id_practica'];
        $alumno_apellidos = trim(implode(' ', array_filter([
          (string) ($practice['alumno_apellido1'] ?? ''),
          (string) ($practice['alumno_apellido2'] ?? ''),
        ], static fn($value): bool => trim($value) !== '')));
        $alumno_nombre = trim((string) ($practice['alumno_nombre'] ?? ''));
        $alumno = trim($alumno_apellidos . ', ' . $alumno_nombre, ' ,');
        if ($alumno === '') {
          $alumno = 'No disponible';
        }

        $empresa_nombre = trim(implode(' ', array_filter([
          (string) ($practice['empresa_nombre'] ?? ''),
          (string) ($practice['empresa_apellido1'] ?? ''),
          (string) ($practice['empresa_apellido2'] ?? ''),
        ], static fn($value): bool => trim($value) !== '')));
        $empresa_nombre_comercial = trim((string) ($practice['empresa_nombre_comercial'] ?? ''));
        $empresa = $empresa_nombre_comercial !== '' ? $empresa_nombre_comercial : ($empresa_nombre !== '' ? $empresa_nombre : 'No disponible');
        $estado_practica = calculate_practice_status($practice);

        $anexos_mostrar = ['2.1', '2.2', '3', '7', '8'];
        if ((int) ($practice['circ_excep'] ?? 0) === 1) {
          $anexos_mostrar[] = '4';
        }

        $anexos_mostrar = array_values(array_filter($anexos_mostrar, static fn(string $code): bool => isset($anexos_definiciones[$code])));
        $global_total = 0;
        $global_completados = 0;
      ?>

      <article class="practicas-anexos-practica" data-practice-card>
        <header class="practicas-anexos-practica-head">
          <div>
            <h4><?php echo htmlspecialchars($alumno, ENT_QUOTES, 'UTF-8'); ?> <span class="practicas-anexos-empresa"><?php echo htmlspecialchars('(' . $empresa . ')', ENT_QUOTES, 'UTF-8'); ?></span></h4>
          </div>
          <div class="practicas-anexos-practica-meta">
            <?php if (in_array('7', $anexos_mostrar, true)): ?>
              <button
                type="button"
                class="primary-button practicas-anexos-add-tracking"
                data-add-anexo7
                data-id-practica="<?php echo $id_practica; ?>"
                data-id-practicas-anexo="<?php echo (int) $anexos_definiciones['7']['id']; ?>"
                data-anexo7-fases='<?php echo htmlspecialchars((string) json_encode($anexos_definiciones['7']['fases'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'
              >
                Añadir seguimiento Anexo 7
              </button>
            <?php endif; ?>
            <span class="practicas-anexos-practica-estado"><?php echo htmlspecialchars($estado_practica, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </header>

        <div class="practicas-anexos-tarjetas" data-practice-anexos>
          <?php foreach ($anexos_mostrar as $code): ?>
            <?php
              $anexo_data = $anexos_definiciones[$code];
              $id_anexo = (int) $anexo_data['id'];
              $fases = $anexo_data['fases'];
              $seguimientos = [1];

              if ($code === '7') {
                $seguimientos = [];
                if (isset($marcados[$id_practica][$id_anexo]) && is_array($marcados[$id_practica][$id_anexo])) {
                  $seguimientos = array_keys($marcados[$id_practica][$id_anexo]);
                  sort($seguimientos);
                }
                if ($seguimientos === []) {
                  $seguimientos = [1];
                }
              }
            ?>

            <?php foreach ($seguimientos as $numero_seguimiento): ?>
              <?php
                $numero_seguimiento = (int) $numero_seguimiento;
                $total_fases = count($fases);
                $completadas = 0;
                foreach ($fases as $fase) {
                  $id_fase = (int) $fase['id'];
                  if (isset($marcados[$id_practica][$id_anexo][$numero_seguimiento][$id_fase])) {
                    $completadas++;
                  }
                }

                $global_total += $total_fases;
                $global_completados += $completadas;

                $percent = $total_fases > 0 ? (int) round(($completadas / $total_fases) * 100) : 0;
                $is_done = $total_fases > 0 && $completadas === $total_fases;
                $card_id = 'anexo-card-' . $id_practica . '-' . $id_anexo . '-' . $numero_seguimiento;
                $panel_id = $card_id . '-panel';
                $title = 'Anexo ' . $code;
                if ($code === '7') {
                  $title .= ' - Seg. ' . $numero_seguimiento;
                }
              ?>
              <section
                class="practicas-anexos-card<?php echo $is_done ? ' is-complete' : ''; ?>"
                data-anexo-card
                data-id-practica="<?php echo $id_practica; ?>"
                data-id-practicas-anexo="<?php echo $id_anexo; ?>"
                data-numero-seguimiento="<?php echo $numero_seguimiento; ?>"
                data-total-steps="<?php echo $total_fases; ?>"
              >
                <button class="practicas-anexos-card-head" type="button" aria-expanded="false" aria-controls="<?php echo htmlspecialchars($panel_id, ENT_QUOTES, 'UTF-8'); ?>" data-accordion-toggle>
                  <span class="practicas-anexos-card-title-wrap">
                    <strong><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="practicas-anexos-card-percent anexo-percent" data-progress-text>(<?php echo $percent; ?>%)</span>
                    <small data-card-summary hidden><?php echo $completadas; ?> de <?php echo $total_fases; ?> pasos completados</small>
                  </span>
                  <span class="practicas-anexos-card-icons">
                    <span class="practicas-anexos-complete-icon" data-complete-icon <?php echo $is_done ? '' : 'hidden'; ?>>✓</span>
                  </span>
                </button>
              </section>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <div class="practicas-anexos-detalles">
          <?php foreach ($anexos_mostrar as $code): ?>
            <?php
              $anexo_data = $anexos_definiciones[$code];
              $id_anexo = (int) $anexo_data['id'];
              $fases = $anexo_data['fases'];
              $seguimientos = [1];

              if ($code === '7') {
                $seguimientos = [];
                if (isset($marcados[$id_practica][$id_anexo]) && is_array($marcados[$id_practica][$id_anexo])) {
                  $seguimientos = array_keys($marcados[$id_practica][$id_anexo]);
                  sort($seguimientos);
                }
                if ($seguimientos === []) {
                  $seguimientos = [1];
                }
              }
            ?>
            <?php foreach ($seguimientos as $numero_seguimiento): ?>
              <?php
                $numero_seguimiento = (int) $numero_seguimiento;
                $card_id = 'anexo-card-' . $id_practica . '-' . $id_anexo . '-' . $numero_seguimiento;
                $panel_id = $card_id . '-panel';
              ?>
              <div
                class="practicas-anexos-card-panel"
                id="<?php echo htmlspecialchars($panel_id, ENT_QUOTES, 'UTF-8'); ?>"
                data-id-practica="<?php echo $id_practica; ?>"
                data-id-practicas-anexo="<?php echo $id_anexo; ?>"
                data-numero-seguimiento="<?php echo $numero_seguimiento; ?>"
                hidden
              >
                <ul class="practicas-anexos-steps">
                  <?php foreach ($fases as $fase): ?>
                    <?php
                      $id_fase = (int) $fase['id'];
                      $checked = isset($marcados[$id_practica][$id_anexo][$numero_seguimiento][$id_fase]);
                    ?>
                    <li>
                      <label>
                        <input
                          type="checkbox"
                          <?php echo $checked ? 'checked' : ''; ?>
                          data-ajax-step
                          data-id-practica="<?php echo $id_practica; ?>"
                          data-id-practicas-anexo="<?php echo $id_anexo; ?>"
                          data-id-practicas-anexo-estado="<?php echo $id_fase; ?>"
                          data-numero-seguimiento="<?php echo $numero_seguimiento; ?>"
                        >
                        <span><?php echo htmlspecialchars((string) $fase['estado'], ENT_QUOTES, 'UTF-8'); ?></span>
                      </label>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <?php
          $global_percent = $global_total > 0 ? (int) round(($global_completados / $global_total) * 100) : 0;
        ?>
        <div class="practicas-anexos-practica-total" data-initial-completed="<?php echo $global_completados; ?>" data-initial-total="<?php echo $global_total; ?>" data-initial-percent="<?php echo $global_percent; ?>"></div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php
  return (string) ob_get_clean();
}

$anexos_estados_permitidos = [
  '2.1' => [
    'datos solicitados',
    'enviado a firmar por la empresa',
    'firmado por la empresa',
    'enviado a firmar por el director',
    'firmado por el director',
    'devuelto a la empresa',
  ],
  '2.2' => [
    'enviado a firmar por el director',
    'firmado por el director',
  ],
  '3' => [
    'enviado a firmar por la empresa',
    'firmado por la empresa',
    'devuelto a la empresa',
  ],
  '4' => [
    'enviado a la dat',
    'autorizado',
  ],
  '7' => [
    'enviado a firmar por la empresa',
    'firmado por la empresa',
  ],
  '8' => [
    'enviado a firmar por la empresa',
    'firmado por la empresa',
  ],
];

$anexos_titulos = [
  '2.1' => 'Anexo 2.1',
  '2.2' => 'Anexo 2.2',
  '3' => 'Anexo 3',
  '4' => 'Anexo 4',
  '7' => 'Anexo 7',
  '8' => 'Anexo 8',
];

$anexos_stmt = $pdo->query(
  'SELECT id_practicas_anexo, anexo, descripcion
   FROM practicas_anexos
   ORDER BY id_practicas_anexo ASC'
);
$anexos_catalog = $anexos_stmt->fetchAll();
$anexos_por_codigo = [];
foreach ($anexos_catalog as $anexo_row) {
  $code = resolve_anexo_code((string) ($anexo_row['anexo'] ?? ''));
  if ($code === '') {
    continue;
  }
  $anexos_por_codigo[$code] = [
    'id' => (int) $anexo_row['id_practicas_anexo'],
    'codigo' => (string) ($anexo_row['anexo'] ?? ''),
    'descripcion' => trim((string) ($anexo_row['descripcion'] ?? '')),
  ];
}

$fases_stmt = $pdo->query(
  'SELECT id_practicas_anexo_estado, estado
   FROM practicas_anexos_estados
   ORDER BY id_practicas_anexo_estado ASC'
);
$fases_catalog = $fases_stmt->fetchAll();
$fases_por_clave = [];
foreach ($fases_catalog as $fase_row) {
  $estado = trim((string) ($fase_row['estado'] ?? ''));
  if ($estado === '') {
    continue;
  }
  $fases_por_clave[normalize_text($estado)] = [
    'id' => (int) $fase_row['id_practicas_anexo_estado'],
    'estado' => $estado,
  ];
}

$anexos_definiciones = [];
foreach ($anexos_estados_permitidos as $codigo => $estados_permitidos) {
  if (!isset($anexos_por_codigo[$codigo])) {
    continue;
  }

  $fases = [];
  foreach ($estados_permitidos as $estado_permitido) {
    $clave = normalize_text($estado_permitido);
    if (!isset($fases_por_clave[$clave])) {
      continue;
    }

    $fases[] = [
      'id' => (int) $fases_por_clave[$clave]['id'],
      'estado' => (string) $fases_por_clave[$clave]['estado'],
    ];
  }

  $anexos_definiciones[$codigo] = [
    'id' => (int) $anexos_por_codigo[$codigo]['id'],
    'codigo' => $codigo,
    'titulo' => $anexos_titulos[$codigo] ?? ('Anexo ' . $codigo),
    'descripcion' => $anexos_por_codigo[$codigo]['descripcion'] !== '' ? $anexos_por_codigo[$codigo]['descripcion'] : ($anexos_por_codigo[$codigo]['codigo'] ?? ''),
    'fases' => $fases,
  ];
}

if (($_POST['ajax'] ?? '') === '1') {
  header('Content-Type: application/json; charset=UTF-8');

  $action = (string) ($_POST['action'] ?? '');
  $id_practica = filter_var($_POST['id_practica'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

  if ($id_practica === false || $id_practica === null) {
    echo json_encode(['ok' => false, 'message' => 'Práctica no válida.']);
    exit;
  }

  if ($action === 'toggle_step') {
    $id_anexo = filter_var($_POST['id_practicas_anexo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_estado = filter_var($_POST['id_practicas_anexo_estado'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $numero_seguimiento = filter_var($_POST['numero_seguimiento'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $checked = (string) ($_POST['checked'] ?? '') === '1';

    if ($id_anexo === false || $id_anexo === null || $id_estado === false || $id_estado === null || $numero_seguimiento === false || $numero_seguimiento === null) {
      echo json_encode(['ok' => false, 'message' => 'Datos no válidos.']);
      exit;
    }

    if ($checked) {
      $exists_stmt = $pdo->prepare(
        'SELECT id_practica_anexo_seguimiento
         FROM practicas_anexos_seguimiento
         WHERE id_practica = :id_practica
           AND id_practicas_anexo = :id_practicas_anexo
           AND id_practicas_anexo_estado = :id_practicas_anexo_estado
           AND numero_seguimiento = :numero_seguimiento
         LIMIT 1'
      );
      $exists_stmt->execute([
        'id_practica' => $id_practica,
        'id_practicas_anexo' => $id_anexo,
        'id_practicas_anexo_estado' => $id_estado,
        'numero_seguimiento' => $numero_seguimiento,
      ]);

      if ($exists_stmt->fetchColumn() === false) {
        $insert_stmt = $pdo->prepare(
          'INSERT INTO practicas_anexos_seguimiento
             (id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion)
           VALUES
             (:id_practica, :id_practicas_anexo, :id_practicas_anexo_estado, :numero_seguimiento, NOW(), NOW())'
        );
        $insert_stmt->execute([
          'id_practica' => $id_practica,
          'id_practicas_anexo' => $id_anexo,
          'id_practicas_anexo_estado' => $id_estado,
          'numero_seguimiento' => $numero_seguimiento,
        ]);
      }
    } else {
      $delete_stmt = $pdo->prepare(
        'DELETE FROM practicas_anexos_seguimiento
         WHERE id_practica = :id_practica
           AND id_practicas_anexo = :id_practicas_anexo
           AND id_practicas_anexo_estado = :id_practicas_anexo_estado
           AND numero_seguimiento = :numero_seguimiento'
      );
      $delete_stmt->execute([
        'id_practica' => $id_practica,
        'id_practicas_anexo' => $id_anexo,
        'id_practicas_anexo_estado' => $id_estado,
        'numero_seguimiento' => $numero_seguimiento,
      ]);
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'add_anexo7_tracking') {
    $id_anexo = filter_var($_POST['id_practicas_anexo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $numero_base = filter_var($_POST['numero_seguimiento_base'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($id_anexo === false || $id_anexo === null || $numero_base === false || $numero_base === null) {
      echo json_encode(['ok' => false, 'message' => 'Datos no válidos.']);
      exit;
    }

    $max_stmt = $pdo->prepare(
      'SELECT MAX(numero_seguimiento)
       FROM practicas_anexos_seguimiento
       WHERE id_practica = :id_practica
         AND id_practicas_anexo = :id_practicas_anexo'
    );
    $max_stmt->execute([
      'id_practica' => $id_practica,
      'id_practicas_anexo' => $id_anexo,
    ]);
    $max_db = (int) ($max_stmt->fetchColumn() ?: 0);
    $next = max($max_db, (int) $numero_base) + 1;

    echo json_encode([
      'ok' => true,
      'numero_seguimiento' => $next,
      'id_practica' => (int) $id_practica,
      'id_practicas_anexo' => (int) $id_anexo,
    ]);
    exit;
  }

  echo json_encode(['ok' => false, 'message' => 'Acción no válida.']);
  exit;
}

$search_term = trim((string) ($_GET['q'] ?? ''));
$params = [];
$where = [];

if ($search_term !== '') {
  $where[] = '(
    a.nombre LIKE :q_nombre
    OR a.apellido1 LIKE :q_apellido1
    OR a.apellido2 LIKE :q_apellido2
    OR e.nombre LIKE :q_empresa_nombre
    OR e.apellido1 LIKE :q_empresa_apellido1
    OR e.apellido2 LIKE :q_empresa_apellido2
    OR e.nombre_comercial LIKE :q_empresa_comercial
  )';
  $query_like = '%' . $search_term . '%';
  $params['q_nombre'] = $query_like;
  $params['q_apellido1'] = $query_like;
  $params['q_apellido2'] = $query_like;
  $params['q_empresa_nombre'] = $query_like;
  $params['q_empresa_apellido1'] = $query_like;
  $params['q_empresa_apellido2'] = $query_like;
  $params['q_empresa_comercial'] = $query_like;
}

$where_sql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';

$practices_stmt = $pdo->prepare(
  'SELECT
    p.id_practica,
    p.id_alumno,
    p.id_empresa,
    p.circ_excep,
    p.cancelada,
    p.fecha_inicio,
    p.fecha_fin_extra,
    a.nombre AS alumno_nombre,
    a.apellido1 AS alumno_apellido1,
    a.apellido2 AS alumno_apellido2,
    e.nombre AS empresa_nombre,
    e.apellido1 AS empresa_apellido1,
    e.apellido2 AS empresa_apellido2,
    e.nombre_comercial AS empresa_nombre_comercial
   FROM practicas p
   INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
   INNER JOIN empresas e ON e.id_empresa = p.id_empresa
   ' . $where_sql . '
   ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica DESC'
);
$practices_stmt->execute($params);
$practices = $practices_stmt->fetchAll();

$practice_ids = array_map(static fn(array $row): int => (int) $row['id_practica'], $practices);
$seguimiento_rows = [];
if ($practice_ids !== []) {
  $in_placeholders = [];
  $seguimiento_params = [];
  foreach ($practice_ids as $index => $practice_id) {
    $key = ':id' . $index;
    $in_placeholders[] = $key;
    $seguimiento_params['id' . $index] = $practice_id;
  }

  $seguimiento_stmt = $pdo->prepare(
    'SELECT id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento
     FROM practicas_anexos_seguimiento
     WHERE id_practica IN (' . implode(',', $in_placeholders) . ')
     ORDER BY id_practica ASC, id_practicas_anexo ASC, numero_seguimiento ASC, id_practicas_anexo_estado ASC'
  );
  $seguimiento_stmt->execute($seguimiento_params);
  $seguimiento_rows = $seguimiento_stmt->fetchAll();
}

$marcados = [];
$anexo7_max_seguimiento = [];
foreach ($seguimiento_rows as $row) {
  $id_practica = (int) $row['id_practica'];
  $id_anexo = (int) $row['id_practicas_anexo'];
  $id_estado = (int) $row['id_practicas_anexo_estado'];
  $numero = (int) $row['numero_seguimiento'];

  $marcados[$id_practica][$id_anexo][$numero][$id_estado] = true;

  if (!isset($anexo7_max_seguimiento[$id_practica]) || $numero > $anexo7_max_seguimiento[$id_practica]) {
    $anexo7_max_seguimiento[$id_practica] = $numero;
  }
}

$listado_html = render_practicas_anexos_lista($practices, $anexos_definiciones, $marcados);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $listado_html;
  exit;
}

$page_title = 'Prácticas anexos | Gestor de Alumnos';
$active_page = 'practicas';
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
          <h1>Seguimiento de anexos</h1>
          <p class="subheading">Consulta y actualiza el estado de los anexos por práctica, alumno y empresa.</p>
        </div>
      </header>

      <form class="topbar" method="get">
        <div class="topbar-search">
          <span class="search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"></circle>
              <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
            </svg>
          </span>
          <input
            type="search"
            name="q"
            placeholder="Buscar por alumno o empresa"
            aria-label="Buscar por alumno o empresa"
            value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>
        <div class="topbar-actions"></div>
      </form>

      <section class="panel practicas-anexos-panel">
        <div class="panel-header">
          <h3>Listado de anexos por práctica</h3>
          <p>Marca cada fase del anexo y guarda los cambios automáticamente.</p>
        </div>

        <?php echo $listado_html; ?>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const getCardPanel = (card) => {
        const toggle = card ? card.querySelector('[data-accordion-toggle]') : null;
        const panelId = toggle ? toggle.getAttribute('aria-controls') : null;
        return panelId ? document.getElementById(panelId) : null;
      };

      const findCardFromInput = (input) => {
        const practiceCard = input.closest('[data-practice-card]');
        if (!practiceCard) return null;

        return practiceCard.querySelector(
          '[data-anexo-card][data-id-practica="' + (input.dataset.idPractica || '') + '"][data-id-practicas-anexo="' + (input.dataset.idPracticasAnexo || '') + '"][data-numero-seguimiento="' + (input.dataset.numeroSeguimiento || '1') + '"]'
        );
      };

      const refreshPracticeSummary = (practiceCard) => {
        const panels = practiceCard.querySelectorAll('.practicas-anexos-card-panel');
        let total = 0;
        let completed = 0;

        panels.forEach((panel) => {
          const inputs = panel.querySelectorAll('input[type="checkbox"][data-ajax-step]');
          total += inputs.length;
          inputs.forEach((input) => {
            if (input.checked) completed += 1;
          });
        });

        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        const summaryText = practiceCard.querySelector('[data-practice-summary-text]');
        const summaryPercent = practiceCard.querySelector('[data-practice-summary-percent]');

        if (summaryText) {
          summaryText.textContent = `${completed} de ${total} pasos completados`;
        }
        if (summaryPercent) {
          summaryPercent.textContent = `${percent}%`;
        }
      };

      const refreshCard = (card) => {
        const panel = getCardPanel(card);
        const checkboxes = panel ? panel.querySelectorAll('input[type="checkbox"][data-ajax-step]') : [];
        const done = panel ? panel.querySelectorAll('input[type="checkbox"][data-ajax-step]:checked').length : 0;
        const total = checkboxes.length;
        const percent = total > 0 ? Math.round((done / total) * 100) : 0;
        const summary = card.querySelector('[data-card-summary]');
        const progressText = card.querySelector('[data-progress-text]');
        const completeIcon = card.querySelector('[data-complete-icon]');

        if (summary) {
          summary.textContent = `${done} de ${total} pasos completados`;
        }

        if (progressText) {
          progressText.textContent = `(${percent}%)`;
        }

        if (completeIcon) {
          completeIcon.hidden = done !== total || total === 0;
        }

        card.classList.toggle('is-complete', done === total && total > 0);

        const practiceCard = card.closest('[data-practice-card]');
        if (practiceCard) {
          refreshPracticeSummary(practiceCard);
        }
      };

      const bindAccordion = (toggleButton) => {
        toggleButton.addEventListener('click', () => {
          const panelId = toggleButton.getAttribute('aria-controls');
          const panel = panelId ? document.getElementById(panelId) : null;
          if (!panel) return;

          const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
          const practiceCard = toggleButton.closest('[data-practice-card]');
          if (!expanded && practiceCard) {
            const openToggles = practiceCard.querySelectorAll('[data-accordion-toggle][aria-expanded="true"]');
            openToggles.forEach((openToggle) => {
              if (openToggle === toggleButton) return;
              openToggle.setAttribute('aria-expanded', 'false');
              const openPanelId = openToggle.getAttribute('aria-controls');
              const openPanel = openPanelId ? document.getElementById(openPanelId) : null;
              if (openPanel) {
                openPanel.hidden = true;
              }
            });
          }

          toggleButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
          panel.hidden = expanded;

        });
      };

      const bindStepInput = (input) => {
        input.addEventListener('change', async () => {
          const previous = !input.checked;
          const payload = new URLSearchParams();
          payload.set('ajax', '1');
          payload.set('action', 'toggle_step');
          payload.set('id_practica', input.dataset.idPractica || '');
          payload.set('id_practicas_anexo', input.dataset.idPracticasAnexo || '');
          payload.set('id_practicas_anexo_estado', input.dataset.idPracticasAnexoEstado || '');
          payload.set('numero_seguimiento', input.dataset.numeroSeguimiento || '1');
          payload.set('checked', input.checked ? '1' : '0');

          try {
            const response = await fetch('practicas_anexos.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
              body: payload.toString(),
            });
            const data = await response.json();

            if (!data.ok) {
              input.checked = previous;
            }
          } catch (error) {
            input.checked = previous;
          }

          const card = findCardFromInput(input);
          if (card) {
            refreshCard(card);
          }
        });
      };

      const createAnexo7Card = (practiceCard, idPractica, idAnexo, numeroSeguimiento, fases) => {
        const panelContainer = practiceCard.querySelector('[data-practice-anexos]');
        if (!panelContainer) return null;

        const card = document.createElement('section');
        card.className = 'practicas-anexos-card';
        card.setAttribute('data-anexo-card', '1');
        card.dataset.idPractica = idPractica;
        card.dataset.idPracticasAnexo = idAnexo;
        card.dataset.numeroSeguimiento = String(numeroSeguimiento);
        card.dataset.totalSteps = String(fases.length);

        const panelId = `anexo-card-${idPractica}-${idAnexo}-${numeroSeguimiento}-panel`;

        card.innerHTML = `
          <button class="practicas-anexos-card-head" type="button" aria-expanded="false" aria-controls="${panelId}" data-accordion-toggle>
            <span class="practicas-anexos-card-title-wrap">
              <strong>Anexo 7 - Seg. ${numeroSeguimiento}</strong>
              <span class="practicas-anexos-card-percent anexo-percent" data-progress-text>(0%)</span>
              <small data-card-summary hidden>0 de ${fases.length} pasos completados</small>
            </span>
            <span class="practicas-anexos-card-icons">
              <span class="practicas-anexos-complete-icon" data-complete-icon hidden>✓</span>
            </span>
          </button>
        `;

        const panel = document.createElement('div');
        panel.className = 'practicas-anexos-card-panel';
        panel.id = panelId;
        panel.dataset.idPractica = String(idPractica);
        panel.dataset.idPracticasAnexo = String(idAnexo);
        panel.dataset.numeroSeguimiento = String(numeroSeguimiento);
        panel.hidden = true;
        panel.innerHTML = '<ul class="practicas-anexos-steps"></ul>';
        const list = panel.querySelector('.practicas-anexos-steps');
        fases.forEach((fase) => {
          const li = document.createElement('li');
          li.innerHTML = `
            <label>
              <input type="checkbox" data-ajax-step data-id-practica="${idPractica}" data-id-practicas-anexo="${idAnexo}" data-id-practicas-anexo-estado="${fase.id}" data-numero-seguimiento="${numeroSeguimiento}">
              <span>${fase.estado}</span>
            </label>
          `;
          list.appendChild(li);
        });

        panelContainer.appendChild(card);
        const detailsContainer = practiceCard.querySelector('.practicas-anexos-detalles');
        if (detailsContainer) {
          detailsContainer.appendChild(panel);
        }

        const toggle = card.querySelector('[data-accordion-toggle]');
        if (toggle) bindAccordion(toggle);

        panel.querySelectorAll('input[data-ajax-step]').forEach(bindStepInput);
        refreshCard(card);
        refreshPracticeSummary(practiceCard);

        return card;
      };

      const bindAddAnexo7Button = (button) => {
        if (button.dataset.boundAnexo7 === '1') return;
        button.dataset.boundAnexo7 = '1';

        button.addEventListener('click', async () => {
          const practiceCard = button.closest('[data-practice-card]');
          if (!practiceCard) return;

          const existing = Array.from(practiceCard.querySelectorAll('[data-anexo-card][data-id-practicas-anexo="' + button.dataset.idPracticasAnexo + '"]'));
          let maxLocal = 0;
          existing.forEach((card) => {
            const n = Number(card.dataset.numeroSeguimiento || '0');
            if (n > maxLocal) maxLocal = n;
          });

          const payload = new URLSearchParams();
          payload.set('ajax', '1');
          payload.set('action', 'add_anexo7_tracking');
          payload.set('id_practica', button.dataset.idPractica || '');
          payload.set('id_practicas_anexo', button.dataset.idPracticasAnexo || '');
          payload.set('numero_seguimiento_base', String(maxLocal));

          try {
            const response = await fetch('practicas_anexos.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
              body: payload.toString(),
            });
            const data = await response.json();
            if (!data.ok) return;

            const fases = JSON.parse(button.dataset.anexo7Fases || '[]');
            createAnexo7Card(practiceCard, data.id_practica, data.id_practicas_anexo, data.numero_seguimiento, fases);
          } catch (error) {
          }
        });
      };

      const initAnexosEvents = (scope) => {
        const root = scope || document;
        root.querySelectorAll('[data-accordion-toggle]').forEach(bindAccordion);
        root.querySelectorAll('input[data-ajax-step]').forEach(bindStepInput);
        root.querySelectorAll('[data-anexo-card]').forEach(refreshCard);
        root.querySelectorAll('[data-practice-card]').forEach(refreshPracticeSummary);
        root.querySelectorAll('[data-add-anexo7]').forEach(bindAddAnexo7Button);
      };

      initAnexosEvents(document);

      const searchForm = document.querySelector('form.topbar');
      const searchInput = document.querySelector('input[name="q"]');
      let searchDebounceTimer = null;

      const fetchPracticas = async () => {
        if (!searchInput) return;
        const url = new URL('practicas_anexos.php', window.location.href);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('q', searchInput.value || '');

        try {
          const response = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
          });
          const html = await response.text();
          const currentList = document.querySelector('[data-practicas-anexos-list]');
          if (!currentList) return;

          const wrapper = document.createElement('div');
          wrapper.innerHTML = html.trim();
          const newList = wrapper.querySelector('[data-practicas-anexos-list]');
          if (!newList) return;

          currentList.replaceWith(newList);
          initAnexosEvents(newList);
        } catch (error) {
        }
      };

      if (searchForm) {
        searchForm.addEventListener('submit', (event) => {
          event.preventDefault();
          fetchPracticas();
        });
      }

      if (searchInput) {
        searchInput.addEventListener('input', () => {
          window.clearTimeout(searchDebounceTimer);
          searchDebounceTimer = window.setTimeout(fetchPracticas, 250);
        });
      }
    })();
  </script>
</body>
</html>

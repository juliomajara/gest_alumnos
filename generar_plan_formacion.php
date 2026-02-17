<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';
require_once __DIR__ . '/includes/practicas_pdf_helpers.php';

use Mpdf\Mpdf;

$id_practica = filter_var($_GET['id_practica'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id_practica === false || $id_practica === null) {
  practicas_redirect_to_detail(0, null, 'Plan: No se ha indicado un identificador de práctica válido.');
}

try {
  $pdo = db();
  $sql = <<<'SQL'
SELECT p.*, p.fecha_fin, p.fecha_fin_real, p.dias_extra, a.nombre AS alumno_nombre, a.apellido1 AS alumno_apellido1, a.apellido2 AS alumno_apellido2,
  e.convenio AS empresa_convenio, e.nombre AS empresa_nombre, et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2,
  d.id_provincia AS direccion_id_provincia, d.nombre_via AS direccion_nombre_via, d.numero AS direccion_numero, d.bloque AS direccion_bloque, d.escalera AS direccion_escalera,
  d.planta AS direccion_planta, d.puerta AS direccion_puerta, d.otros AS direccion_otros, d.cp AS direccion_cp, v.via AS direccion_via_tipo, ld.nombre AS direccion_localidad,
  pd.nombre AS direccion_provincia, pa.pais AS direccion_pais, ac.id_curso_escolar, ac.id_curso, ce.curso_escolar, c.curso AS curso_ordinal, ac.id_grupo, g.id_ciclo,
  ci.ciclo AS ciclo_nombre, ci.codigo AS ciclo_codigo,
  CONCAT_WS(CHAR(32), pc.apellido1, pc.apellido2, pc.nombre) AS tutor_centro,
  (SELECT c1.direccion_correo FROM correos c1 WHERE c1.entidad_tipo = 'alumno' AND c1.id_entidad = a.id_alumno ORDER BY c1.id_correo ASC LIMIT 1) AS alumno_email,
  (SELECT t1.telefono FROM telefonos t1 WHERE t1.entidad_tipo = 'alumno' AND t1.id_entidad = a.id_alumno ORDER BY t1.id_telefono ASC LIMIT 1) AS alumno_telefono,
  (SELECT c2.direccion_correo FROM correos c2 WHERE c2.entidad_tipo = 'empresa' AND c2.id_entidad = e.id_empresa ORDER BY c2.id_correo ASC LIMIT 1) AS empresa_email,
  (SELECT t2.telefono FROM telefonos t2 WHERE t2.entidad_tipo = 'empresa' AND t2.id_entidad = e.id_empresa ORDER BY t2.id_telefono ASC LIMIT 1) AS empresa_telefono,
  (SELECT c3.direccion_correo FROM correos c3 WHERE c3.entidad_tipo = 'empresa_tutor' AND c3.id_entidad = et.id_empresa_tutor ORDER BY c3.id_correo ASC LIMIT 1) AS tutor_empresa_email,
  (SELECT t3.telefono FROM telefonos t3 WHERE t3.entidad_tipo = 'empresa_tutor' AND t3.id_entidad = et.id_empresa_tutor ORDER BY t3.id_telefono ASC LIMIT 1) AS tutor_empresa_telefono,
  (SELECT c4.direccion_correo FROM correos c4 WHERE c4.entidad_tipo = 'profesor' AND c4.id_entidad = pc.id_profesor ORDER BY c4.id_correo ASC LIMIT 1) AS tutor_centro_email,
  (SELECT t4.telefono FROM telefonos t4 WHERE t4.entidad_tipo = 'profesor' AND t4.id_entidad = pc.id_profesor ORDER BY t4.id_telefono ASC LIMIT 1) AS tutor_centro_telefono
FROM practicas p
LEFT JOIN alumnos a ON a.id_alumno = p.id_alumno
LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
LEFT JOIN empresas_tutores et ON et.id_empresa_tutor = p.id_empresa_tutor
LEFT JOIN direcciones d ON d.id_direccion = p.id_direccion
LEFT JOIN vias v ON v.id_via = d.id_via
LEFT JOIN localidades ld ON ld.id_localidad = d.id_localidad
LEFT JOIN provincias pd ON pd.id_provincia = d.id_provincia
LEFT JOIN paises pa ON pa.id_pais = d.id_pais
LEFT JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno AND ac.id_curso_escolar = (SELECT MAX(ac2.id_curso_escolar) FROM alumno_curso ac2 WHERE ac2.id_alumno = p.id_alumno)
LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
LEFT JOIN cursos c ON c.id_curso = ac.id_curso
LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
LEFT JOIN ciclos ci ON ci.id_ciclo = g.id_ciclo
LEFT JOIN grupos_tutores gt ON gt.id_grupo = ac.id_grupo AND gt.id_curso_escolar = ac.id_curso_escolar
LEFT JOIN profesores pc ON pc.id_profesor = gt.id_profesor
WHERE p.id_practica = :id_practica LIMIT 1
SQL;
  $practice_stmt = $pdo->prepare($sql);

  $practice_stmt->execute(['id_practica' => $id_practica]);
  $practice = $practice_stmt->fetch();
  if (!$practice) {
    practicas_redirect_to_detail((int) $id_practica, null, 'Plan: No se encontró la práctica solicitada.');
  }

  $schedule_by_day = [];
  $schedule_stmt = $pdo->prepare('SELECT id_practicas_horario, dia_semana, hora_entrada, hora_salida FROM practicas_horario WHERE id_practica = :id_practica ORDER BY dia_semana ASC, hora_entrada ASC');
  $schedule_stmt->execute(['id_practica' => $id_practica]);
  foreach ($schedule_stmt->fetchAll() as $row) {
    $day = (int) $row['dia_semana'];
    $schedule_by_day[$day][] = $row;
  }

  $requiredFields = [
    'alumno_nombre' => 'nombre del alumno', 'alumno_apellido1' => 'primer apellido del alumno', 'empresa_nombre' => 'nombre de la empresa',
    'empresa_convenio' => 'número de convenio', 'anexo' => 'número de anexo', 'fecha_inicio' => 'fecha de inicio',
  ];
  $missing = [];
  foreach ($requiredFields as $field => $label) {
    if (trim((string) ($practice[$field] ?? '')) === '') {
      $missing[] = $label;
    }
  }
  $endDateRaw = trim((string) ($practice['fecha_fin_real'] ?? ''));
  if ($endDateRaw === '') {
    $endDateRaw = trim((string) ($practice['fecha_fin'] ?? ''));
  }
  if ($endDateRaw === '') {
    $missing[] = 'fecha de fin';
  }
  if ($missing !== []) {
    practicas_redirect_to_detail((int) $id_practica, null, 'Plan: Faltan datos esenciales para generar el PDF: ' . implode(', ', $missing) . '.');
  }

  $paths = practicas_get_document_paths($practice);
  $plan_rows = fetch_plan_formacion_rows($pdo, $practice);
  $pdfHtml = build_plan_formacion_html($practice, $schedule_by_day, $plan_rows, $pdo);
  $plan_html_path = $paths['plan_directory'] . '/' . pathinfo($paths['plan_file_name'], PATHINFO_FILENAME) . '.html';
  if (file_put_contents($plan_html_path, $pdfHtml) === false) {
    throw new RuntimeException('No se pudo guardar el HTML generado del plan de formación.');
  }

  $mpdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10,
    'margin_top' => 10, 'margin_bottom' => 10, 'default_font_size' => 12,
    'shrink_tables_to_fit' => 0, 'tempDir' => ensure_mpdf_temp_dir(), 'keep_table_proportions' => true, 'use_kwt' => true,
  ]);
  $mpdf->setBasePath(__DIR__ . '/docs/');
  $mpdf->showImageErrors = true;
  $mpdf->WriteHTML($pdfHtml);
  $mpdf->Output($paths['plan_file_path'], \Mpdf\Output\Destination::FILE);

  practicas_redirect_to_detail((int) $id_practica, 'plan_generated', null);
} catch (Throwable $planPdfError) {
  $errorMessage = $planPdfError->getMessage();
  if ($errorMessage === '') {
    $errorMessage = 'No se pudo generar el PDF del plan de formación en este momento.';
  }
  practicas_redirect_to_detail((int) ($id_practica ?: 0), null, 'Plan: ' . $errorMessage);
}

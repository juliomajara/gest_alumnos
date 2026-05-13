<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$page_title = 'Ficha de seguimiento periódico | Gestor de Alumnos';
$active_page = 'utilidades';

const MAX_UPLOAD_BYTES = 10485760;

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function normalize_date(?string $v): string {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) return $v;
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $v);
  return $d ? $d->format('d/m/Y') : $v;
}
function ensure_dir(string $path): void {
  if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) throw new RuntimeException('No se pudo crear carpeta temporal.');
  if (!is_writable($path)) throw new RuntimeException('La carpeta temporal no tiene permisos de escritura.');
}
function extract_pdf_fields_pdftk(string $pdf): array {
  if (!function_exists('exec')) throw new RuntimeException('No se puede analizar el PDF: exec está desactivado en PHP.');

  $pdftkCandidates = [
    'C:\Program Files (x86)\PDFtk Server\bin\pdftk.exe',
    'C:\Program Files\PDFtk Server\bin\pdftk.exe',
    'pdftk',
  ];

  $pdftk = '';
  foreach ($pdftkCandidates as $candidate) {
    if ($candidate !== 'pdftk' && (!is_file($candidate) || !is_readable($candidate))) continue;
    $versionCmd = escapeshellarg($candidate) . ' --version 2>&1';
    $versionOutput = []; $versionCode = 1; exec($versionCmd, $versionOutput, $versionCode);
    if ($versionCode === 0 && stripos(implode("
", $versionOutput), 'pdftk') !== false) { $pdftk = $candidate; break; }
  }

  if ($pdftk === '') {
    throw new RuntimeException(
      "No se puede analizar el PDF: no se ha encontrado pdftk. Rutas probadas:
- " . implode("
- ", $pdftkCandidates)
    );
  }

  $cmd = escapeshellarg($pdftk) . ' ' . escapeshellarg($pdf) . ' dump_data_fields_utf8 2>&1';
  $output = []; $code = 1; exec($cmd, $output, $code);
  if ($code !== 0) {
    throw new RuntimeException(
      'No se pudieron leer campos del PDF con pdftk. Comando ejecutado: ' . $cmd . '. Salida: ' . implode("
", $output)
    );
  }

  $fields = []; $name = '';
  foreach ($output as $line) {
    if (str_starts_with($line, 'FieldName:')) { $name = trim(substr($line, 10)); continue; }
    if (str_starts_with($line, 'FieldValue:') && $name !== '') { $fields[$name] = trim(substr($line, 11)); }
  }
  return $fields;
}
function find_alumno(PDO $pdo, string $nia, string $dni): array {
  $byNia = null; $byDni = null;
  if ($nia !== '') { $st=$pdo->prepare('SELECT * FROM alumnos WHERE nia=:nia LIMIT 1'); $st->execute(['nia'=>$nia]); $byNia=$st->fetch(PDO::FETCH_ASSOC) ?: null; }
  if ($dni !== '') { $st=$pdo->prepare('SELECT * FROM alumnos WHERE dni=:dni LIMIT 1'); $st->execute(['dni'=>$dni]); $byDni=$st->fetch(PDO::FETCH_ASSOC) ?: null; }
  if ($byNia && $byDni && (int)$byNia['id_alumno'] !== (int)$byDni['id_alumno']) throw new RuntimeException('Conflicto: NIA y DNI apuntan a alumnos distintos.');
  return $byNia ?: ($byDni ?: []);
}
function active_school_year(PDO $pdo): string {
  try {
    $st = $pdo->query('SELECT * FROM cursos_escolares WHERE activo = 1 LIMIT 1');
    $row = $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: []) : [];
    if (!$row) return '';
    foreach (['nombre','curso','descripcion','anyo','curso_escolar','curso_academico'] as $c) {
      if (isset($row[$c]) && trim((string)$row[$c]) !== '') return trim((string)$row[$c]);
    }
    return '';
  } catch (Throwable $e) {
    return '';
  }
}
function alumno_email(PDO $pdo, int $id): string {
  $st = $pdo->prepare('SELECT direccion_correo FROM correos WHERE entidad_tipo = "alumno" AND id_entidad=:id ORDER BY CASE WHEN LOWER(TRIM(COALESCE(direccion_correo, ""))) LIKE "%@educa.madrid.org" THEN 0 WHEN TRIM(COALESCE(etiqueta, "")) = "EducaMadrid" THEN 1 WHEN TRIM(COALESCE(etiqueta, "")) = "Personal" THEN 2 ELSE 3 END, id_correo DESC LIMIT 1');
  try { $st->execute(['id'=>$id]); $v=$st->fetchColumn(); return is_string($v)?$v:''; } catch (Throwable $e) { return ''; }
}
function tutor_email(PDO $pdo, int $id): string {
  $st = $pdo->prepare('SELECT direccion_correo FROM correos WHERE entidad_tipo = "empresa_tutor" AND id_entidad=:id ORDER BY CASE WHEN LOWER(TRIM(COALESCE(direccion_correo, ""))) LIKE "%@educa.madrid.org" THEN 0 ELSE 1 END, id_correo DESC LIMIT 1');
  try { $st->execute(['id'=>$id]); $v=$st->fetchColumn(); return is_string($v)?$v:''; } catch (Throwable $e) { return ''; }
}
function find_practica(PDO $pdo, int $idAlumno): array {
  $st = $pdo->prepare('SELECT p.*, e.nombre_comercial, e.nombre, e.convenio AS num_convenio, p.anexo AS num_anexo_relacion, et.id_empresas_tutor, et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2
  FROM practicas p LEFT JOIN empresas e ON e.id_empresa=p.id_empresa LEFT JOIN empresas_tutores et ON et.id_empresas_tutor=p.id_empresa_tutor WHERE p.id_alumno=:id ORDER BY p.cancelada ASC, (p.fecha_fin_extra IS NULL) ASC, p.fecha_fin_extra DESC, (p.fecha_fin IS NULL) ASC, p.fecha_fin DESC LIMIT 1');
  $st->execute(['id'=>$idAlumno]); return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
$activityMap = [
  'aplicaciones_web_ra4'=>['modulo'=>'Aplicaciones Web','ra'=>'4'], 'digitalizacion_ra1'=>['modulo'=>'Digitalización','ra'=>'1'], 'sostenibilidad_ra6'=>['modulo'=>'Sostenibilidad','ra'=>'6'],
  'seguridad_informatica_ra2'=>['modulo'=>'Seguridad Informática','ra'=>'2'], 'seguridad_informatica_ra3'=>['modulo'=>'Seguridad Informática','ra'=>'3'], 'sistemas_operativos_en_red_ra1'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'1'],
  'sistemas_operativos_en_red_ra2'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'2'], 'sistemas_operativos_en_red_ra4'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'4'], 'sistemas_operativos_en_red_ra6'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'6'],
  'servicios_en_red_ra6'=>['modulo'=>'Servicios en Red','ra'=>'6'],
];
$errors = []; $warnings=[]; $formData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'analizar_pdf') {
  try {
    if (!isset($_FILES['pdf_alumno']) || (int)$_FILES['pdf_alumno']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Debes subir un PDF válido.');
    $f = $_FILES['pdf_alumno'];
    if ((int)$f['size'] <= 0 || (int)$f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('El PDF excede 10MB o está vacío.');
    $name = (string)$f['name']; if (strtolower((string)pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') throw new RuntimeException('La extensión debe ser .pdf');
    $mime = function_exists('mime_content_type') ? (string) mime_content_type((string)$f['tmp_name']) : '';
    if ($mime !== '' && !in_array($mime, ['application/pdf','application/x-pdf'], true)) throw new RuntimeException('El archivo no parece un PDF válido.');
    $tmpDir = __DIR__ . '/docs/tmp_fichas_seguimiento'; ensure_dir($tmpDir);
    $tmpPdf = $tmpDir . '/subido_' . bin2hex(random_bytes(8)) . '.pdf';
    if (!move_uploaded_file((string)$f['tmp_name'], $tmpPdf)) throw new RuntimeException('No se pudo guardar temporalmente el PDF.');
    $fields = extract_pdf_fields_pdftk($tmpPdf);
    @unlink($tmpPdf);
    $dni = trim((string)($fields['dni'] ?? '')); $nia = trim((string)($fields['nia'] ?? ''));
    if ($dni === '' && $nia === '') throw new RuntimeException('No se han podido extraer DNI/NIA del PDF.');
    $pdo = db();
    $alumno = find_alumno($pdo, $nia, $dni);
    $practica = [];
    if ($alumno) $practica = find_practica($pdo, (int)$alumno['id_alumno']);
    if (!$alumno) $warnings[] = 'No se ha vinculado automáticamente el alumno.';
    $rows=[];
    foreach ($activityMap as $key=>$meta) {
      $actividad = trim((string)($fields[$key] ?? ''));
      $codigo='';
      try { $st=$pdo->prepare('SELECT codigo FROM modulos WHERE LOWER(materia_general)=LOWER(:m) OR LOWER(materia_propia)=LOWER(:m) LIMIT 1'); $st->execute(['m'=>$meta['modulo']]); $codigo=(string)($st->fetchColumn() ?: ''); } catch(Throwable $e) {}
      $rows[]=['actividad'=>$actividad,'codigo_modulo'=>$codigo,'ra'=>$meta['ra'],'estado'=>'en_proceso','observaciones'=>''];
    }
    $cursoActivo = active_school_year($pdo);
    $tutorEmail = isset($practica['id_empresas_tutor']) ? tutor_email($pdo, (int)$practica['id_empresas_tutor']) : '';
    $formData = [
      'curso_academico'=>$cursoActivo, 'num_convenio'=>(string)($practica['num_convenio'] ?? ''), 'num_anexo_relacion'=>(string)($practica['num_anexo_relacion'] ?? ''),
      'alumno_apellidos'=>trim((string)($alumno['apellido1']??'').' '.(string)($alumno['apellido2']??'')) ?: trim((string)($fields['apellidos']??'')),
      'alumno_nombre'=>(string)($alumno['nombre'] ?? $fields['nombre'] ?? ''), 'alumno_email'=>$alumno ? alumno_email($pdo,(int)$alumno['id_alumno']) : '',
      'centro_trabajo_denominacion'=>(string)($practica['nombre_comercial'] ?: $practica['nombre'] ?? ''),
      'tutor_empresa_apellidos'=>trim((string)($practica['tutor_apellido1']??'').' '.(string)($practica['tutor_apellido2']??'')), 'tutor_empresa_nombre'=>(string)($practica['tutor_nombre'] ?? ''), 'tutor_empresa_email'=>$tutorEmail,
      'fecha_inicio'=>normalize_date((string)($fields['fecha_inicio'] ?? $practica['fecha_inicio'] ?? '')),
      'fecha_fin'=>normalize_date((string)($fields['fecha_fin'] ?? $practica['fecha_fin_extra'] ?? $practica['fecha_fin'] ?? '')),
      'fecha_firma'=>(new DateTimeImmutable('today'))->format('d/m/Y'), 'filas'=>$rows,
    ];
  } catch (Throwable $e) { $errors[]=$e->getMessage(); }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo h($page_title); ?></title><link rel="stylesheet" href="assets/styles.css"><style>.bloque-alumno-apellidos,.bloque-centro-trabajo,.seccion-actividades{margin-top:1rem;}</style></head><body><div class="page"><?php require __DIR__ . '/includes/sidebar.php'; ?><main class="content"><header class="header"><div><h1>Ficha de seguimiento periódico</h1><p class="subheading">Sube el PDF rellenado por el alumno, revisa los datos y genera la ficha final.</p></div></header>
<?php if($errors): ?><section class="panel"><ul class="form-errors"><?php foreach($errors as $er): ?><li><?php echo h($er); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php if($warnings): ?><section class="panel"><ul class="form-errors"><?php foreach($warnings as $wr): ?><li><?php echo h($wr); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php if($formData===null): ?>
<section class="panel"><form method="post" enctype="multipart/form-data" class="entity-form"><input type="hidden" name="accion" value="analizar_pdf"><label>PDF del alumno (máx. 10MB)<input type="file" name="pdf_alumno" accept="application/pdf,.pdf" required></label><button class="primary-button" type="submit">Analizar PDF</button></form></section>
<?php else: ?>
<section class="panel"><form method="post" action="includes/generar_ficha_seguimiento_periodico.php" class="entity-form">
<div class="entity-grid entity-grid--6">
<label>Curso académico<input type="text" name="curso_academico" value="<?php echo h((string)$formData['curso_academico']); ?>"></label>
<label>Número de convenio<input type="text" name="num_convenio" value="<?php echo h((string)$formData['num_convenio']); ?>"></label>
<label>Número de anexo de relación de alumnos<input type="text" name="num_anexo_relacion" value="<?php echo h((string)$formData['num_anexo_relacion']); ?>"></label>
<label>Fecha inicio<input type="text" name="fecha_inicio" value="<?php echo h((string)$formData['fecha_inicio']); ?>"></label>
<label>Fecha fin<input type="text" name="fecha_fin" value="<?php echo h((string)$formData['fecha_fin']); ?>"></label>
<label>Fecha firma<input type="text" name="fecha_firma" value="<?php echo h((string)$formData['fecha_firma']); ?>"></label>
</div>
<div class="entity-grid entity-grid--3 bloque-alumno-apellidos">
<label>Alumno apellidos<input type="text" name="alumno_apellidos" value="<?php echo h((string)$formData['alumno_apellidos']); ?>"></label>
<label>Alumno nombre<input type="text" name="alumno_nombre" value="<?php echo h((string)$formData['alumno_nombre']); ?>"></label>
<label>Alumno e-mail<input type="text" name="alumno_email" value="<?php echo h((string)$formData['alumno_email']); ?>"></label>
</div>
<div class="entity-grid entity-grid--4 bloque-centro-trabajo">
<label>Centro trabajo denominación<input type="text" name="centro_trabajo_denominacion" value="<?php echo h((string)$formData['centro_trabajo_denominacion']); ?>"></label>
<label>Tutor empresa apellidos<input type="text" name="tutor_empresa_apellidos" value="<?php echo h((string)$formData['tutor_empresa_apellidos']); ?>"></label>
<label>Tutor empresa nombre<input type="text" name="tutor_empresa_nombre" value="<?php echo h((string)$formData['tutor_empresa_nombre']); ?>"></label>
<label>Tutor empresa email<input type="text" name="tutor_empresa_email" value="<?php echo h((string)$formData['tutor_empresa_email']); ?>"></label>
</div>
<h3 class="seccion-actividades">Actividades</h3>
<div class="panel-grid"><table class="ficha-seguimiento-table"><thead><tr><th class="col-actividad">Actividad</th><th class="col-codigo">Código módulo</th><th class="col-ra">RA</th><th class="col-estado">Estado</th><th class="col-observaciones">Observaciones</th></tr></thead><tbody>
<?php foreach($formData['filas'] as $i=>$fila): ?>
<tr><td><input type="text" name="filas[<?php echo $i; ?>][actividad]" value="<?php echo h($fila['actividad']); ?>"></td><td><input type="text" name="filas[<?php echo $i; ?>][codigo_modulo]" value="<?php echo h($fila['codigo_modulo']); ?>"></td><td><input type="text" name="filas[<?php echo $i; ?>][ra]" value="<?php echo h($fila['ra']); ?>"></td><td><select name="filas[<?php echo $i; ?>][estado]"><option value="no_superado" <?php echo $fila['estado']==='no_superado'?'selected':''; ?>>No superada</option><option value="en_proceso" <?php echo $fila['estado']==='en_proceso'?'selected':''; ?>>En proceso</option><option value="superado" <?php echo $fila['estado']==='superado'?'selected':''; ?>>Superado</option></select></td><td><input type="text" name="filas[<?php echo $i; ?>][observaciones]" value="<?php echo h($fila['observaciones']); ?>"></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<button class="primary-button" type="submit">Generar ficha de seguimiento</button></form></section>
<?php endif; ?>
</main></div></body></html>

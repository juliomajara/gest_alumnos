<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$page_title = 'Ficha de seguimiento periódico | Gestor de Alumnos';
$active_page = 'utilidades';

const MAX_UPLOAD_BYTES = 10485760;

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function normalize_dni(string $dni): string { return strtoupper(str_replace([' ', '-'], '', trim($dni))); }
function normalize_nia(string $nia): string { return str_replace(' ', '', trim($nia)); }
function normalize_ra(string $ra): string {
  $ra = strtoupper(trim($ra));
  if (preg_match('/^RA\s*([0-9]+)$/', $ra, $m)) return $m[1];
  return preg_replace('/\s+/', '', $ra);
}
function normalize_date(?string $v): string {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) return $v;
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $v);
  return $d ? $d->format('d/m/Y') : $v;
}
function to_ymd(?string $v): string {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
  $d = DateTimeImmutable::createFromFormat('d/m/Y', $v);
  return $d ? $d->format('Y-m-d') : '';
}
function get_no_lectivos_lookup(PDO $pdo): array {
  try { $rows = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN); return array_fill_keys((array)$rows, true); } catch (Throwable $e) { return []; }
}
function compute_months_in_range(string $inicio, string $fin): array {
  $d1 = DateTimeImmutable::createFromFormat('Y-m-d', $inicio);
  $d2 = DateTimeImmutable::createFromFormat('Y-m-d', $fin);
  if (!$d1 || !$d2 || $d1 > $d2) return [];
  $months = []; $cursor = $d1->modify('first day of this month');
  $finMes = $d2->modify('first day of this month');
  while ($cursor <= $finMes) { $months[] = ['year'=>(int)$cursor->format('Y'),'month'=>(int)$cursor->format('n'),'value'=>$cursor->format('Y-m')]; $cursor = $cursor->modify('+1 month'); }
  return $months;
}
function first_lectivo_day_in_month(int $year, int $month, string $practicaInicio, array $noLectivos): string {
  $daysInMonth = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
  for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
    if ($dateStr < $practicaInicio) continue;
    $dow = (int)(new DateTimeImmutable($dateStr))->format('N');
    if ($dow >= 6) continue;
    if (isset($noLectivos[$dateStr])) continue;
    return $dateStr;
  }
  return '';
}
function last_lectivo_day_in_month(int $year, int $month, string $practicaFin, array $noLectivos): string {
  $daysInMonth = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
  for ($d = $daysInMonth; $d >= 1; $d--) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
    if ($dateStr > $practicaFin) continue;
    $dow = (int)(new DateTimeImmutable($dateStr))->format('N');
    if ($dow >= 6) continue;
    if (isset($noLectivos[$dateStr])) continue;
    return $dateStr;
  }
  return '';
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
function mes_espanol_to_num(string $mes): int {
  static $map=['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
  return $map[strtolower(trim($mes))] ?? 0;
}
function parse_json_file(string $contenido, PDO $pdo, array $noLectivos): array {
  $json=json_decode($contenido,true);
  if(json_last_error()!==JSON_ERROR_NONE||!is_array($json)) throw new RuntimeException('El JSON no es válido.');
  if(!array_key_exists('actividades',$json)||!is_array($json['actividades'])) throw new RuntimeException('Falta el campo actividades o no es un array.');
  if(empty($json['mes'])) throw new RuntimeException('Falta el campo mes en el JSON.');
  $mesNum=mes_espanol_to_num((string)$json['mes']);
  if($mesNum===0) throw new RuntimeException('Mes no reconocido: '.(string)$json['mes']);
  $year=(int)date('Y');
  if(!empty($json['fechaGuardado'])){try{$dt=new DateTimeImmutable((string)$json['fechaGuardado']);$refYear=(int)$dt->format('Y');$refMonth=(int)$dt->format('n');$year=($mesNum<=$refMonth)?$refYear:$refYear-1;}catch(Throwable $e){}}
  $dni=isset($json['dni'])?normalize_dni((string)$json['dni']):'';
  $nia=isset($json['nia'])?normalize_nia((string)$json['nia']):'';
  if($dni===''&&$nia==='') throw new RuntimeException('El JSON debe incluir dni o nia.');
  $alumno=find_alumno($pdo,$nia,$dni);
  if(!$alumno) throw new RuntimeException('No se ha encontrado al alumno con DNI/NIA indicado.');
  $practica=find_practica($pdo,(int)$alumno['id_alumno']);
  $practicaInicioRaw=to_ymd((string)($practica['fecha_inicio']??''));
  $practicaFinRaw=to_ymd((string)($practica['fecha_fin_extra']??$practica['fecha_fin']??''));
  if($practicaInicioRaw===''||$practicaFinRaw==='') throw new RuntimeException('No se encontraron fechas de prácticas para el alumno.');
  $fechaInicioYmd=first_lectivo_day_in_month($year,$mesNum,$practicaInicioRaw,$noLectivos);
  $fechaFinYmd=last_lectivo_day_in_month($year,$mesNum,$practicaFinRaw,$noLectivos);
  if($fechaInicioYmd===''||$fechaFinYmd==='') throw new RuntimeException('El mes indicado no coincide con el período de prácticas del alumno.');
  $cursoActivo=active_school_year($pdo);
  $tutorEmail=isset($practica['id_empresas_tutor'])?tutor_email($pdo,(int)$practica['id_empresas_tutor']):'';
  $rows=[];
  foreach($json['actividades'] as $actividad){
    if(!is_array($actividad)) continue;
    $actividadTexto=trim((string)($actividad['actividadEmpresa']??''));
    $rows[]=['actividad'=>$actividadTexto,'codigo_modulo'=>trim((string)($actividad['codigoModulo']??'')),'ra'=>normalize_ra((string)($actividad['ra']??'')),'estado'=>$actividadTexto!==''?'superado':'en_proceso','observaciones'=>''];
  }
  return [
    'curso_academico'=>$cursoActivo,'num_convenio'=>(string)($practica['num_convenio']??''),'num_anexo_relacion'=>(string)($practica['num_anexo_relacion']??''),
    'alumno_apellidos'=>trim((string)($alumno['apellido1']??'').' '.(string)($alumno['apellido2']??'')),
    'alumno_nombre'=>(string)($alumno['nombre']??''),'alumno_email'=>alumno_email($pdo,(int)$alumno['id_alumno']),
    'centro_trabajo_denominacion'=>(string)($practica['nombre_comercial']?:$practica['nombre']??''),
    'tutor_empresa_apellidos'=>trim((string)($practica['tutor_apellido1']??'').' '.(string)($practica['tutor_apellido2']??'')),'tutor_empresa_nombre'=>(string)($practica['tutor_nombre']??''),'tutor_empresa_email'=>$tutorEmail,
    'fecha_inicio'=>normalize_date($fechaInicioYmd),'fecha_fin'=>normalize_date($fechaFinYmd),'fecha_firma'=>normalize_date($fechaFinYmd),'filas'=>$rows,
  ];
}
$activityMap = [
  'aplicaciones_web_ra4'=>['modulo'=>'Aplicaciones Web','ra'=>'4'], 'digitalizacion_ra1'=>['modulo'=>'Digitalización','ra'=>'1'], 'sostenibilidad_ra6'=>['modulo'=>'Sostenibilidad','ra'=>'6'],
  'seguridad_informatica_ra2'=>['modulo'=>'Seguridad Informática','ra'=>'2'], 'seguridad_informatica_ra3'=>['modulo'=>'Seguridad Informática','ra'=>'3'], 'sistemas_operativos_en_red_ra1'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'1'],
  'sistemas_operativos_en_red_ra2'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'2'], 'sistemas_operativos_en_red_ra4'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'4'], 'sistemas_operativos_en_red_ra6'=>['modulo'=>'Sistemas Operativos en Red','ra'=>'6'],
  'servicios_en_red_ra6'=>['modulo'=>'Servicios en Red','ra'=>'6'],
];
$errors = []; $warnings=[]; $formData = null; $batchResults = null; $mesesDisponibles = null; $mesSeleccionado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'analizar_pdf') {
  try {
    $uploadedFiles = $_FILES['pdf_alumno'] ?? null;
    if (!$uploadedFiles || !isset($uploadedFiles['name']) || (is_array($uploadedFiles['name']) && count(array_filter((array)$uploadedFiles['name'])) === 0) || (!is_array($uploadedFiles['name']) && $uploadedFiles['name'] === '')) throw new RuntimeException('Debes subir al menos un archivo PDF, JSON o ZIP.');
    $fileList = [];
    if (is_array($uploadedFiles['name'])) {
      foreach ($uploadedFiles['name'] as $i => $nm) { $fileList[] = ['name'=>(string)$nm,'tmp_name'=>(string)$uploadedFiles['tmp_name'][$i],'error'=>(int)$uploadedFiles['error'][$i],'size'=>(int)$uploadedFiles['size'][$i]]; }
    } else {
      $fileList[] = ['name'=>(string)$uploadedFiles['name'],'tmp_name'=>(string)$uploadedFiles['tmp_name'],'error'=>(int)$uploadedFiles['error'],'size'=>(int)$uploadedFiles['size']];
    }
    $pdfFile = null; $jsonContents = [];
    foreach ($fileList as $f) {
      if ($f['error'] !== UPLOAD_ERR_OK) { $errors[] = $f['name'] . ': error al subir el archivo.'; continue; }
      if ($f['size'] <= 0 || $f['size'] > MAX_UPLOAD_BYTES) { $errors[] = $f['name'] . ': excede 10MB o está vacío.'; continue; }
      $ext = strtolower((string)pathinfo($f['name'], PATHINFO_EXTENSION));
      $mime = function_exists('mime_content_type') ? (string)mime_content_type($f['tmp_name']) : '';
      if ($ext === 'pdf' || in_array($mime, ['application/pdf','application/x-pdf'], true)) {
        if ($pdfFile === null) $pdfFile = $f; else $warnings[] = 'Solo se procesa un PDF a la vez; se ignora: ' . $f['name'];
      } elseif ($ext === 'json' || in_array($mime, ['application/json','text/json','text/plain'], true)) {
        $jsonContents[] = ['filename'=>$f['name'],'content'=>(string)file_get_contents($f['tmp_name'])];
      } elseif ($ext === 'zip' || in_array($mime, ['application/zip','application/x-zip-compressed','application/octet-stream'], true)) {
        if (!class_exists('ZipArchive')) { $errors[] = $f['name'] . ': ZipArchive no está disponible en este servidor.'; continue; }
        $zip = new ZipArchive();
        if ($zip->open($f['tmp_name']) !== true) { $errors[] = $f['name'] . ': no se pudo abrir el ZIP.'; continue; }
        for ($zi = 0; $zi < $zip->numFiles; $zi++) {
          $zipEntry = (string)$zip->getNameIndex($zi);
          if (strtolower(pathinfo($zipEntry, PATHINFO_EXTENSION)) === 'json') {
            $zipContent = $zip->getFromIndex($zi);
            if ($zipContent !== false) $jsonContents[] = ['filename'=>basename($zipEntry),'content'=>$zipContent];
          }
        }
        $zip->close();
      } else {
        $errors[] = $f['name'] . ': tipo de archivo no soportado (usa PDF, JSON o ZIP).';
      }
    }
    if (!empty($jsonContents)) {
      $pdo = db(); $noLectivos = get_no_lectivos_lookup($pdo); $results = [];
      foreach ($jsonContents as $jf) {
        try { $results[] = ['status'=>'ok','filename'=>$jf['filename'],'formData'=>parse_json_file($jf['content'], $pdo, $noLectivos)]; }
        catch (Throwable $e) { $results[] = ['status'=>'error','filename'=>$jf['filename'],'error'=>$e->getMessage()]; }
      }
      if (count($results) === 1 && $results[0]['status'] === 'ok') { $formData = $results[0]['formData']; }
      elseif (count($results) === 1 && $results[0]['status'] === 'error') { $errors[] = $results[0]['filename'] . ': ' . $results[0]['error']; }
      else { $batchResults = $results; }
    } elseif ($pdfFile !== null) {
      $f = $pdfFile;
      $pdo = db(); $fields = []; $rows = [];
      $tmpDir = __DIR__ . '/docs/tmp_fichas_seguimiento'; ensure_dir($tmpDir);
      $tmpPdf = $tmpDir . '/subido_' . bin2hex(random_bytes(8)) . '.pdf';
      if (!move_uploaded_file($f['tmp_name'], $tmpPdf)) throw new RuntimeException('No se pudo guardar temporalmente el PDF.');
      $fields = extract_pdf_fields_pdftk($tmpPdf);
      @unlink($tmpPdf);
      $dni = trim((string)($fields['dni'] ?? '')); $nia = trim((string)($fields['nia'] ?? ''));
      if ($dni === '' && $nia === '') throw new RuntimeException('No se han podido extraer DNI/NIA del PDF.');
      foreach ($activityMap as $key=>$meta) {
        $actividad = trim((string)($fields[$key] ?? ''));
        $codigo='';
        try { $st=$pdo->prepare('SELECT codigo FROM modulos WHERE LOWER(materia_general)=LOWER(:m) OR LOWER(materia_propia)=LOWER(:m) LIMIT 1'); $st->execute(['m'=>$meta['modulo']]); $codigo=(string)($st->fetchColumn() ?: ''); } catch(Throwable $e) {}
        $rows[]=['actividad'=>$actividad,'codigo_modulo'=>$codigo,'ra'=>$meta['ra'],'estado'=>$actividad !== '' ? 'superado' : 'en_proceso','observaciones'=>''];
      }
      $alumno = find_alumno($pdo, $nia, $dni);
      if (!$alumno) throw new RuntimeException('No se ha encontrado al alumno indicado.');
      $practica = find_practica($pdo, (int)$alumno['id_alumno']);
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
        'fecha_firma'=>normalize_date((string)($fields['fecha_fin'] ?? $practica['fecha_fin_extra'] ?? $practica['fecha_fin'] ?? '')), 'filas'=>$rows,
      ];
      $practicaInicioRaw = to_ymd((string)($practica['fecha_inicio'] ?? ''));
      $practicaFinRaw = to_ymd((string)($practica['fecha_fin_extra'] ?? $practica['fecha_fin'] ?? ''));
      if ($practicaInicioRaw !== '' && $practicaFinRaw !== '') {
        $calculatedMonths = compute_months_in_range($practicaInicioRaw, $practicaFinRaw);
        if (!empty($calculatedMonths)) { $mesesDisponibles = $calculatedMonths; $formData['_practica_inicio_raw'] = $practicaInicioRaw; $formData['_practica_fin_raw'] = $practicaFinRaw; }
      }
    } elseif (empty($errors)) {
      throw new RuntimeException('Debes subir al menos un archivo PDF, JSON o ZIP.');
    }
  } catch (Throwable $e) { $errors[]=$e->getMessage(); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'seleccionar_mes') {
  try {
    $mesVal = trim((string)($_POST['mes_seguimiento'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}$/', $mesVal)) throw new RuntimeException('Mes no válido.');
    [$yearStr, $monthStr] = explode('-', $mesVal);
    $year = (int)$yearStr; $month = (int)$monthStr;
    $practicaInicioRaw = trim((string)($_POST['practica_inicio_raw'] ?? ''));
    $practicaFinRaw = trim((string)($_POST['practica_fin_raw'] ?? ''));
    if ($practicaInicioRaw === '' || $practicaFinRaw === '') throw new RuntimeException('Faltan fechas de prácticas.');
    $pdo = db();
    $noLectivos = get_no_lectivos_lookup($pdo);
    $fechaInicioYmd = first_lectivo_day_in_month($year, $month, $practicaInicioRaw, $noLectivos);
    $fechaFinYmd = last_lectivo_day_in_month($year, $month, $practicaFinRaw, $noLectivos);
    $filasJson = trim((string)($_POST['filas_json'] ?? ''));
    $filas = json_decode($filasJson, true);
    if (!is_array($filas)) $filas = [];
    $formData = [
      'curso_academico'=>trim((string)($_POST['curso_academico']??'')), 'num_convenio'=>trim((string)($_POST['num_convenio']??'')), 'num_anexo_relacion'=>trim((string)($_POST['num_anexo_relacion']??'')),
      'alumno_apellidos'=>trim((string)($_POST['alumno_apellidos']??'')), 'alumno_nombre'=>trim((string)($_POST['alumno_nombre']??'')), 'alumno_email'=>trim((string)($_POST['alumno_email']??'')),
      'centro_trabajo_denominacion'=>trim((string)($_POST['centro_trabajo_denominacion']??'')),
      'tutor_empresa_apellidos'=>trim((string)($_POST['tutor_empresa_apellidos']??'')), 'tutor_empresa_nombre'=>trim((string)($_POST['tutor_empresa_nombre']??'')), 'tutor_empresa_email'=>trim((string)($_POST['tutor_empresa_email']??'')),
      'fecha_inicio'=>normalize_date($fechaInicioYmd), 'fecha_fin'=>normalize_date($fechaFinYmd), 'fecha_firma'=>normalize_date($fechaFinYmd),
      'filas'=>$filas,
    ];
    $mesSeleccionado = $mesVal;
  } catch (Throwable $e) { $errors[] = $e->getMessage(); }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo h($page_title); ?></title><link rel="stylesheet" href="assets/styles.css"><style>.bloque-alumno-apellidos,.bloque-centro-trabajo,.seccion-actividades{margin-top:1rem;}.acciones-analizar-pdf{margin-top:1rem;}.acciones-ficha{margin-top:1.5rem;}.tabla-actividades{width:100%;table-layout:fixed;}.tabla-actividades .col-actividad{width:55%;}.tabla-actividades .col-codigo-modulo{width:10%;}.tabla-actividades .col-ra{width:5%;}.tabla-actividades .col-estado{width:10%;}.tabla-actividades .col-observaciones{width:20%;}.tabla-actividades input,.tabla-actividades select,.tabla-actividades textarea{width:100%;box-sizing:border-box;}table.tabla-actividades tbody tr,table.tabla-actividades tbody tr:nth-child(odd),table.tabla-actividades tbody tr:nth-child(even),table.tabla-actividades tbody tr:nth-of-type(odd),table.tabla-actividades tbody tr:nth-of-type(even),table.tabla-actividades tbody tr:hover,table.tabla-actividades tbody td,table.tabla-actividades tbody th{background:#ffffff !important;background-color:#ffffff !important;}</style></head><body><div class="page"><?php require __DIR__ . '/includes/sidebar.php'; ?><main class="content"><header class="header"><div><h1>Ficha de seguimiento periódico</h1><p class="subheading">Sube el PDF rellenado por el alumno, revisa los datos y genera la ficha final.</p></div></header>
<?php if($errors): ?><section class="panel"><ul class="form-errors"><?php foreach($errors as $er): ?><li><?php echo h($er); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php if($warnings): ?><section class="panel"><ul class="form-errors"><?php foreach($warnings as $wr): ?><li><?php echo h($wr); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php if($batchResults !== null): ?>
<section class="panel">
<?php $okCount=count(array_filter($batchResults,fn($r)=>$r['status']==='ok')); $errCount=count($batchResults)-$okCount; ?>
<p><?php echo h($okCount.' fichero(s) procesado(s) correctamente'.($errCount>0?', '.$errCount.' con error':'').'.'); ?></p>
<?php if($okCount>0): ?><div style="margin-bottom:1.5rem;"><button type="button" id="btn-generar-todas" class="primary-button" onclick="generarTodasLasFichas()">Generar todas</button></div><?php endif; ?>
<?php foreach($batchResults as $bIdx=>$bItem): ?>
<details open style="margin-bottom:1.5rem;border:1px solid #ddd;border-radius:4px;padding:0 1rem;">
<summary style="cursor:pointer;font-weight:bold;padding:0.75rem 0;"><?php echo h($bItem['filename']); ?><?php if($bItem['status']==='ok'): ?> <span style="color:green;font-weight:normal;">— OK</span><?php else: ?> <span style="color:#c00;font-weight:normal;">— Error</span><?php endif; ?></summary>
<?php if($bItem['status']==='error'): ?>
<p style="color:#c00;padding-bottom:0.75rem;"><?php echo h($bItem['error']); ?></p>
<?php else: $bfd=$bItem['formData']; ?>
<form method="post" action="includes/generar_ficha_seguimiento_periodico.php" class="entity-form batch-form" style="padding-bottom:1rem;">
<div class="entity-grid entity-grid--6">
<label>Curso académico<input type="text" name="curso_academico" value="<?php echo h((string)$bfd['curso_academico']); ?>"></label>
<label>Nº de convenio<input type="text" name="num_convenio" value="<?php echo h((string)$bfd['num_convenio']); ?>"></label>
<label>Nº relación de alumnos<input type="text" name="num_anexo_relacion" value="<?php echo h((string)$bfd['num_anexo_relacion']); ?>"></label>
<label>Fecha inicio<input type="text" name="fecha_inicio" value="<?php echo h((string)$bfd['fecha_inicio']); ?>"></label>
<label>Fecha fin<input type="text" name="fecha_fin" value="<?php echo h((string)$bfd['fecha_fin']); ?>"></label>
<label>Fecha firma<input type="text" name="fecha_firma" value="<?php echo h((string)$bfd['fecha_firma']); ?>"></label>
</div>
<div class="entity-grid entity-grid--3 bloque-alumno-apellidos">
<label>Alumno apellidos<input type="text" name="alumno_apellidos" value="<?php echo h((string)$bfd['alumno_apellidos']); ?>"></label>
<label>Alumno nombre<input type="text" name="alumno_nombre" value="<?php echo h((string)$bfd['alumno_nombre']); ?>"></label>
<label>Alumno e-mail<input type="text" name="alumno_email" value="<?php echo h((string)$bfd['alumno_email']); ?>"></label>
</div>
<div class="entity-grid entity-grid--4 bloque-centro-trabajo">
<label>Centro trabajo denominación<input type="text" name="centro_trabajo_denominacion" value="<?php echo h((string)$bfd['centro_trabajo_denominacion']); ?>"></label>
<label>Tutor empresa apellidos<input type="text" name="tutor_empresa_apellidos" value="<?php echo h((string)$bfd['tutor_empresa_apellidos']); ?>"></label>
<label>Tutor empresa nombre<input type="text" name="tutor_empresa_nombre" value="<?php echo h((string)$bfd['tutor_empresa_nombre']); ?>"></label>
<label>Tutor empresa email<input type="text" name="tutor_empresa_email" value="<?php echo h((string)$bfd['tutor_empresa_email']); ?>"></label>
</div>
<h3 class="seccion-actividades">Actividades</h3>
<div class="panel-grid"><table class="ficha-seguimiento-table tabla-actividades"><thead><tr><th class="col-actividad">Actividad</th><th class="col-codigo-modulo">Código módulo</th><th class="col-ra">RA</th><th class="col-estado">Estado</th><th class="col-observaciones">Observaciones</th></tr></thead><tbody>
<?php foreach($bfd['filas'] as $i=>$fila): ?>
<tr><td class="col-actividad"><input type="text" name="filas[<?php echo $i; ?>][actividad]" value="<?php echo h($fila['actividad']); ?>"></td><td class="col-codigo-modulo"><input type="text" name="filas[<?php echo $i; ?>][codigo_modulo]" value="<?php echo h($fila['codigo_modulo']); ?>"></td><td class="col-ra"><input type="text" name="filas[<?php echo $i; ?>][ra]" value="<?php echo h($fila['ra']); ?>"></td><td class="col-estado"><select name="filas[<?php echo $i; ?>][estado]"><option value="no_superado" <?php echo $fila['estado']==='no_superado'?'selected':''; ?>>No superada</option><option value="en_proceso" <?php echo $fila['estado']==='en_proceso'?'selected':''; ?>>En proceso</option><option value="superado" <?php echo $fila['estado']==='superado'?'selected':''; ?>>Superado</option></select></td><td class="col-observaciones"><input type="text" name="filas[<?php echo $i; ?>][observaciones]" value="<?php echo h($fila['observaciones']); ?>"></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="acciones-ficha"><button class="primary-button" type="submit">Generar ficha de seguimiento</button></div>
</form>
<?php endif; ?>
</details>
<?php endforeach; ?>
</section>
<?php elseif($formData===null): ?>
<section class="panel"><form method="post" enctype="multipart/form-data" class="entity-form"><input type="hidden" name="accion" value="analizar_pdf"><div class="file-field"><span class="file-field-label">PDF, JSON o ZIP (máx. 10MB; puedes seleccionar varios)</span><div class="file-field-control"><label class="file-field-btn" for="pdfAlumnoInput"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Seleccionar archivo</label><span class="file-field-name" id="pdfAlumnoName">Ningún archivo seleccionado</span><input type="file" id="pdfAlumnoInput" name="pdf_alumno[]" accept="application/pdf,.pdf,application/json,.json,application/zip,.zip" required class="file-field-input" multiple></div></div><script>(function(){var inp=document.getElementById('pdfAlumnoInput');var nm=document.getElementById('pdfAlumnoName');if(inp&&nm){inp.addEventListener('change',function(){if(!this.files||this.files.length===0){nm.textContent='Ningún archivo seleccionado';}else if(this.files.length===1){nm.textContent=this.files[0].name;}else{nm.textContent=this.files.length+' archivos seleccionados';}});}})();</script><div class="acciones-analizar-pdf"><button class="primary-button" type="submit">Analizar archivo</button></div></form></section>
<?php elseif($mesesDisponibles !== null && $mesSeleccionado === null): ?>
<section class="panel"><form method="post" class="entity-form">
<input type="hidden" name="accion" value="seleccionar_mes">
<input type="hidden" name="practica_inicio_raw" value="<?php echo h((string)($formData['_practica_inicio_raw'] ?? '')); ?>">
<input type="hidden" name="practica_fin_raw" value="<?php echo h((string)($formData['_practica_fin_raw'] ?? '')); ?>">
<input type="hidden" name="curso_academico" value="<?php echo h((string)$formData['curso_academico']); ?>">
<input type="hidden" name="num_convenio" value="<?php echo h((string)$formData['num_convenio']); ?>">
<input type="hidden" name="num_anexo_relacion" value="<?php echo h((string)$formData['num_anexo_relacion']); ?>">
<input type="hidden" name="alumno_apellidos" value="<?php echo h((string)$formData['alumno_apellidos']); ?>">
<input type="hidden" name="alumno_nombre" value="<?php echo h((string)$formData['alumno_nombre']); ?>">
<input type="hidden" name="alumno_email" value="<?php echo h((string)$formData['alumno_email']); ?>">
<input type="hidden" name="centro_trabajo_denominacion" value="<?php echo h((string)$formData['centro_trabajo_denominacion']); ?>">
<input type="hidden" name="tutor_empresa_apellidos" value="<?php echo h((string)$formData['tutor_empresa_apellidos']); ?>">
<input type="hidden" name="tutor_empresa_nombre" value="<?php echo h((string)$formData['tutor_empresa_nombre']); ?>">
<input type="hidden" name="tutor_empresa_email" value="<?php echo h((string)$formData['tutor_empresa_email']); ?>">
<input type="hidden" name="filas_json" value="<?php echo h((string)(json_encode($formData['filas']) ?: '[]')); ?>">
<div class="entity-grid entity-grid--3"><?php $meses_es=[1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre']; ?>
<label>Mes del seguimiento<select name="mes_seguimiento" required><option value="">-- Selecciona un mes --</option><?php foreach($mesesDisponibles as $mes): ?><option value="<?php echo h($mes['value']); ?>"><?php echo h(ucfirst($meses_es[$mes['month']]).' '.$mes['year']); ?></option><?php endforeach; ?></select></label>
</div>
<div class="acciones-analizar-pdf"><button class="primary-button" type="submit">Seleccionar mes</button></div>
</form></section>
<?php else: ?>
<section class="panel"><form method="post" action="includes/generar_ficha_seguimiento_periodico.php" class="entity-form">
<div class="entity-grid entity-grid--6">
<label>Curso académico<input type="text" name="curso_academico" value="<?php echo h((string)$formData['curso_academico']); ?>"></label>
<label>Nº de convenio<input type="text" name="num_convenio" value="<?php echo h((string)$formData['num_convenio']); ?>"></label>
<label>Nº relación de alumnos<input type="text" name="num_anexo_relacion" value="<?php echo h((string)$formData['num_anexo_relacion']); ?>"></label>
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
<div class="panel-grid"><table class="ficha-seguimiento-table tabla-actividades"><thead><tr><th class="col-actividad">Actividad</th><th class="col-codigo-modulo">Código módulo</th><th class="col-ra">RA</th><th class="col-estado">Estado</th><th class="col-observaciones">Observaciones</th></tr></thead><tbody>
<?php foreach($formData['filas'] as $i=>$fila): ?>
<tr><td class="col-actividad"><input type="text" name="filas[<?php echo $i; ?>][actividad]" value="<?php echo h($fila['actividad']); ?>"></td><td class="col-codigo-modulo"><input type="text" name="filas[<?php echo $i; ?>][codigo_modulo]" value="<?php echo h($fila['codigo_modulo']); ?>"></td><td class="col-ra"><input type="text" name="filas[<?php echo $i; ?>][ra]" value="<?php echo h($fila['ra']); ?>"></td><td class="col-estado"><select name="filas[<?php echo $i; ?>][estado]"><option value="no_superado" <?php echo $fila['estado']==='no_superado'?'selected':''; ?>>No superada</option><option value="en_proceso" <?php echo $fila['estado']==='en_proceso'?'selected':''; ?>>En proceso</option><option value="superado" <?php echo $fila['estado']==='superado'?'selected':''; ?>>Superado</option></select></td><td class="col-observaciones"><input type="text" name="filas[<?php echo $i; ?>][observaciones]" value="<?php echo h($fila['observaciones']); ?>"></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="acciones-ficha"><button class="primary-button" type="submit">Generar ficha de seguimiento</button></div></form></section>
<?php endif; ?>
</main></div><script>
(function () {
  const form = document.querySelector('form[action="includes/generar_ficha_seguimiento_periodico.php"]');
  if (!form) return;
  const fechaFin = form.querySelector('input[name="fecha_fin"]');
  const fechaFirma = form.querySelector('input[name="fecha_firma"]');
  if (!fechaFin || !fechaFirma) return;
  const syncFechaFirma = function () { fechaFirma.value = fechaFin.value; };
  syncFechaFirma();
  fechaFin.addEventListener('input', syncFechaFirma);
  fechaFin.addEventListener('change', syncFechaFirma);
  form.addEventListener('submit', syncFechaFirma);
})();
async function generarTodasLasFichas() {
  var btn = document.getElementById('btn-generar-todas');
  var forms = document.querySelectorAll('form.batch-form');
  if (!forms.length) return;
  btn.disabled = true;
  btn.textContent = 'Generando ZIP…';
  var sanitize = function(s) {
    return s.normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^\w]/g,'_').replace(/_+/g,'_').replace(/^_+|_+$/g,'');
  };
  var fichas = [];
  for (var i = 0; i < forms.length; i++) {
    var fd = new FormData(forms[i]);
    var obj = {}; var filas = {};
    for (var pair of fd.entries()) {
      var k = pair[0]; var v = pair[1].toString();
      var m = k.match(/^filas\[(\d+)\]\[(\w+)\]$/);
      if (m) { if (!filas[m[1]]) filas[m[1]] = {}; filas[m[1]][m[2]] = v; }
      else { obj[k] = v; }
    }
    obj.filas = Object.values(filas);
    var convenio = sanitize((obj.num_convenio || '').trim());
    var anexo = sanitize((obj.num_anexo_relacion || '').trim());
    var apellido1 = sanitize(((obj.alumno_apellidos || '').trim().split(/\s+/)[0]) || '');
    var nombre = sanitize((obj.alumno_nombre || '').trim());
    var partesFecha = (obj.fecha_inicio || '').trim().split('/');
    var mes = partesFecha.length >= 2 ? String(parseInt(partesFecha[1], 10)) : '0';
    obj._filename = [convenio, anexo, apellido1, nombre, mes].filter(Boolean).join('_') + '.pdf';
    fichas.push(obj);
  }
  try {
    var resp = await fetch('includes/generar_fichas_zip.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(fichas)});
    if (!resp.ok) { var txt = await resp.text(); throw new Error(txt || 'HTTP ' + resp.status); }
    var blob = await resp.blob();
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a'); a.href = url; a.download = 'fichas_seguimiento.zip';
    document.body.appendChild(a); a.click();
    setTimeout(function(){ document.body.removeChild(a); URL.revokeObjectURL(url); }, 2000);
  } catch(err) {
    alert('No se pudo generar el ZIP: ' + err.message);
  }
  btn.disabled = false;
  btn.textContent = 'Generar todas';
}
</script></body></html>

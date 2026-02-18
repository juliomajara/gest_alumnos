<?php
declare(strict_types=1);

function practicas_redirect_to_detail(int $practiceId, ?string $documentStatus = null, ?string $documentError = null): void {
  $params = ['id_practica=' . $practiceId];
  if ($documentStatus !== null) {
    $params[] = 'doc_status=' . rawurlencode($documentStatus);
  }
  if ($documentError !== null) {
    $params[] = 'doc_error=' . rawurlencode($documentError);
  }

  header('Location: practica_detalle.php?' . implode('&', $params));
  exit;
}

function practicas_sanitize_filename_component(?string $value, int $maxLength = 60): string {
  $value = trim((string) $value);
  if ($value === '') {
    return 'NA';
  }

  $value = preg_replace('/[\\\\\/:*?"<>|]+/u', '', $value) ?? '';
  $value = preg_replace('/\s+/u', '_', $value) ?? '';
  $value = preg_replace('/_+/u', '_', $value) ?? '';
  $value = trim($value, '._-');

  if ($value === '') {
    return 'NA';
  }

  return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function practicas_build_calendar_pdf_filename(array $practice): string {
  $parts = [
    practicas_sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
    practicas_sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
    practicas_sanitize_filename_component((string) ($practice['alumno_nombre'] ?? ''), 40),
    practicas_sanitize_filename_component((string) ($practice['alumno_apellido1'] ?? ''), 40),
    practicas_sanitize_filename_component((string) ($practice['empresa_nombre'] ?? ''), 70),
  ];

  $baseName = implode('_', $parts);
  $baseName = function_exists('mb_substr') ? mb_substr($baseName, 0, 200) : substr($baseName, 0, 200);

  return $baseName . '.pdf';
}

function practicas_build_plan_pdf_filename(array $practice): string {
  $sanitize = static function (?string $value, int $maxLength = 80): string {
    $value = trim((string) $value);
    if ($value === '') {
      return 'NA';
    }

    if (function_exists('iconv')) {
      $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
      if (is_string($converted) && $converted !== '') {
        $value = $converted;
      }
    }

    $value = preg_replace('/[\x00-\x1F\x7F]+/', '', $value) ?? '';
    $value = preg_replace('/[\/\\:*?"<>|]+/', '', $value) ?? '';
    $value = preg_replace('/[^A-Za-z0-9]+/', '', $value) ?? '';
    $value = preg_replace('/_+/', '_', $value) ?? '';
    $value = trim($value, '._-');

    if ($value === '') {
      return 'NA';
    }

    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
  };

  $baseName = implode('_', [
    'PF',
    $sanitize((string) ($practice['anexo'] ?? ''), 20),
    $sanitize((string) ($practice['empresa_convenio'] ?? ''), 20),
    $sanitize((string) ($practice['alumno_nombre'] ?? '') . (string) ($practice['alumno_apellido1'] ?? ''), 40),
    $sanitize((string) ($practice['empresa_nombre'] ?? ''), 40),
  ]);

  $baseName = preg_replace('/_+/', '_', $baseName) ?? 'PF';
  $baseName = trim($baseName, '._-');
  if ($baseName === '') {
    $baseName = 'PF';
  }

  $maxBaseLength = 80 - 4;
  if (function_exists('mb_substr')) {
    $baseName = mb_substr($baseName, 0, $maxBaseLength);
  } else {
    $baseName = substr($baseName, 0, $maxBaseLength);
  }
  $baseName = rtrim($baseName, '._-');
  if ($baseName === '') {
    $baseName = 'PF';
  }

  return $baseName . '.pdf';
}

function practicas_get_document_paths(array $practice): array {
  $calendarDirectory = __DIR__ . '/../docs/practicas_info';
  if (!is_dir($calendarDirectory) && !mkdir($calendarDirectory, 0755, true) && !is_dir($calendarDirectory)) {
    throw new RuntimeException('No se pudo crear el directorio de calendarios.');
  }

  $planDirectory = __DIR__ . '/../docs/practicas_plan_formacion';
  if (!is_dir($planDirectory) && !mkdir($planDirectory, 0755, true) && !is_dir($planDirectory)) {
    throw new RuntimeException('No se pudo crear el directorio de planes de formación.');
  }

  $calendarFileName = practicas_build_calendar_pdf_filename($practice);
  $planFileName = practicas_build_plan_pdf_filename($practice);

  return [
    'calendar_directory' => $calendarDirectory,
    'calendar_file_name' => $calendarFileName,
    'calendar_file_path' => $calendarDirectory . '/' . $calendarFileName,
    'plan_directory' => $planDirectory,
    'plan_file_name' => $planFileName,
    'plan_file_path' => $planDirectory . '/' . $planFileName,
  ];
}

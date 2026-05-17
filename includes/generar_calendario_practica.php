<?php
declare(strict_types=1);

function gestionar_generacion_calendario_practica(array $document_paths, string $action, int $id_practica): array {
  $calendarDirectory = $document_paths['calendar_directory'];
  $calendar_file_name = $document_paths['calendar_file_name'];
  $calendar_file_path = $document_paths['calendar_file_path'];
  $calendar_error = null;

  if ($action === 'descargar_calendario') {
    $realBase = realpath($calendarDirectory);
    $realFile = $calendar_file_path !== null && file_exists($calendar_file_path) ? realpath($calendar_file_path) : false;

    if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_readable($realFile)) {
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="calendario_practicas.pdf"; filename*=UTF-8\'\'' . rawurlencode($calendar_file_name));
      header('Content-Length: ' . (string) filesize($realFile));
      header('X-Content-Type-Options: nosniff');
      readfile($realFile);
      exit;
    }

    $calendar_error = 'El calendario no existe o no está disponible para descarga.';
  }

  if ($action === 'generar_calendario') {
    header('Location: generar_calendario_practica.php?id_practica=' . (int) $id_practica);
    exit;
  }

  return [
    'calendar_file_name' => $calendar_file_name,
    'calendar_file_path' => $calendar_file_path,
    'calendar_error' => $calendar_error,
  ];
}

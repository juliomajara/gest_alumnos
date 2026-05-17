<?php
declare(strict_types=1);

function calcularClaveValidacion($dni, $nia)
{
  $dni = strtoupper(str_replace([' ', '-'], '', trim((string) $dni)));
  $nia = trim((string) $nia);

  $dniSoloNumeros = preg_replace('/\D/', '', $dni);

  if (!preg_match('/^\d{8}$/', $dniSoloNumeros)) {
    return null;
  }

  if (!preg_match('/^\d+$/', $nia)) {
    return null;
  }

  $d = $dniSoloNumeros;
  $n = $nia;

  $sumaDni = array_sum(array_map('intval', str_split($d)));
  $sumaNia = array_sum(array_map('intval', str_split($n)));

  $a = $sumaDni + $sumaNia;
  $b = abs($sumaDni - $sumaNia);

  $ponderadoDni = 0;
  foreach (str_split($d) as $i => $digito) {
    $factor = $i + 2;
    $ponderadoDni += intval($digito) * ($factor ** 3);
  }

  $ponderadoNia = 0;
  foreach (str_split($n) as $i => $digito) {
    $factor = $i + 3;
    $ponderadoNia += intval($digito) * ($factor ** 3);
  }

  $c = abs($ponderadoDni - $ponderadoNia);

  $dInvertido = intval(strrev($d));
  $nInvertido = intval(strrev($n));

  $mezcla =
    intval($d) * 97 +
    intval($n) * 31 +
    $dInvertido * 17 +
    $nInvertido * 13 +
    $c * 7 +
    $a * 1009 +
    $b * 917;

  return (string) (10000000000000 + ($mezcla % 90000000000000));
}

function generarClavesJsonDesdeAlumnos(array $students): void
{
  $claves = [];

  foreach ($students as $student) {
    $clave = calcularClaveValidacion((string) ($student['dni'] ?? ''), (string) ($student['nia'] ?? ''));
    if ($clave !== null) {
      $claves[] = $clave;
    }
  }

  $claves = array_values(array_unique($claves));

  if (ob_get_level() > 0) {
    ob_clean();
  }

  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="claves.json"');
  echo json_encode($claves, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  exit;
}

<?php
declare(strict_types=1);

function es_telefono_movil_espanol(string $telefono): bool
{
  $telefono_limpio = preg_replace('/[\s\-.]/', '', $telefono) ?? '';

  return (bool) preg_match('/^(?:\+34|0034)?[67]\d{8}$/', $telefono_limpio);
}

function es_correo_valido(string $correo): bool
{
  $correo_limpio = trim($correo);

  return (bool) preg_match('/^(?!\.)[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i', $correo_limpio);
}

function es_dni_nie_valido(string $documento): bool
{
  $documento_limpio = strtoupper(trim($documento));

  return (bool) preg_match('/^(?:\d{8}[A-Z]|[XYZ]\d{7}[A-Z])$/', $documento_limpio);
}

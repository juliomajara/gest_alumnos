<?php

declare(strict_types=1);

define('RUTA_DATOS_ALMACEN', __DIR__ . '/../data/almacen');

function ruta_almacen(string $archivo): string
{
    return RUTA_DATOS_ALMACEN . '/' . $archivo;
}

/**
 * Lectura simple (bloqueo compartido) para cuando no hace falta modificar el fichero.
 */
function leer_json(string $ruta, array $porDefecto): array
{
    if (!file_exists($ruta)) {
        return $porDefecto;
    }
    $fp = fopen($ruta, 'r');
    if ($fp === false) {
        return $porDefecto;
    }
    flock($fp, LOCK_SH);
    $contenido = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $datos = $contenido !== '' ? json_decode($contenido, true) : null;
    return is_array($datos) ? $datos : $porDefecto;
}

/**
 * Abre el fichero con bloqueo exclusivo durante toda la operación de
 * lectura + modificación + escritura, para que dos peticiones simultáneas
 * (dos alumnos guardando una puntuación a la vez, por ejemplo) no se pisen.
 *
 * $transformar recibe los datos actuales y debe devolver
 * ['datos' => <datos a guardar>, 'valor' => <lo que se quiera devolver>].
 */
function actualizar_json(string $ruta, array $porDefecto, callable $transformar)
{
    $carpeta = dirname($ruta);
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0775, true);
    }

    $fp = fopen($ruta, 'c+');
    if ($fp === false) {
        throw new RuntimeException('No se pudo abrir ' . $ruta);
    }

    flock($fp, LOCK_EX);
    $tamano = filesize($ruta) ?: 0;
    $contenido = $tamano > 0 ? fread($fp, $tamano) : '';
    $datos = $contenido !== '' ? json_decode($contenido, true) : null;
    if (!is_array($datos)) {
        $datos = $porDefecto;
    }

    $resultado = $transformar($datos);

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($resultado['datos'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $resultado['valor'] ?? null;
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/almacen.php';
require_once __DIR__ . '/usuarios.php';

const TIPOS_VALIDOS = ['provincias', 'ccaa', 'rios'];
const MODOS_VALIDOS = ['reconocer', 'tocar'];
const VARIANTES_VALIDAS = ['aprendizaje', 'cronometrado'];

function ruta_puntuaciones(): string
{
    return ruta_almacen('puntuaciones.json');
}

function puntuaciones_todas(): array
{
    $datos = leer_json(ruta_puntuaciones(), ['siguiente_id' => 1, 'puntuaciones' => []]);
    return $datos['puntuaciones'] ?? [];
}

/** ¿$a es mejor puntuación que $b? (mayor % y, en empate, menor tiempo) */
function es_mejor_puntuacion(array $a, array $b): bool
{
    if ($a['pct'] != $b['pct']) {
        return $a['pct'] > $b['pct'];
    }
    return $a['tiempo_ms'] < $b['tiempo_ms'];
}

/** Mejor puntuación de cada usuario para un tipo/modo: [usuario_id => registro] */
function mejores_por_usuario(string $tipo, string $modo): array
{
    $mejores = [];
    foreach (puntuaciones_todas() as $p) {
        if ($p['tipo'] !== $tipo || $p['modo'] !== $modo) {
            continue;
        }
        $uid = (int) $p['usuario_id'];
        if (!isset($mejores[$uid]) || es_mejor_puntuacion($p, $mejores[$uid])) {
            $mejores[$uid] = $p;
        }
    }
    return $mejores;
}

function mejor_puntuacion_usuario(int $usuarioId, string $tipo, string $modo): ?array
{
    return mejores_por_usuario($tipo, $modo)[$usuarioId] ?? null;
}

function ranking_tipo_modo(string $tipo, string $modo, int $limite = 100): array
{
    $filas = [];
    foreach (mejores_por_usuario($tipo, $modo) as $uid => $p) {
        $usuario = usuario_por_id($uid);
        if ($usuario === null) {
            continue;
        }
        $filas[] = [
            'nombre_usuario' => $usuario['nombre_usuario'],
            'usuario_id' => $uid,
            'aciertos' => $p['aciertos'],
            'total' => $p['total'],
            'pct' => $p['pct'],
            'tiempo_ms' => $p['tiempo_ms'],
            'creado_en' => $p['creado_en'],
        ];
    }

    usort($filas, function (array $a, array $b): int {
        if ($a['pct'] != $b['pct']) {
            return $b['pct'] <=> $a['pct'];
        }
        return $a['tiempo_ms'] <=> $b['tiempo_ms'];
    });

    return array_slice($filas, 0, $limite);
}

function posicion_en_ranking(string $tipo, string $modo, float $pct, int $tiempoMs): int
{
    $candidato = ['pct' => $pct, 'tiempo_ms' => $tiempoMs];
    $mejoresQueYo = 0;
    foreach (mejores_por_usuario($tipo, $modo) as $p) {
        if (es_mejor_puntuacion($p, $candidato)) {
            $mejoresQueYo++;
        }
    }
    return $mejoresQueYo + 1;
}

function total_jugadores(string $tipo, string $modo): int
{
    return count(mejores_por_usuario($tipo, $modo));
}

function guardar_puntuacion(int $usuarioId, string $tipo, string $modo, int $aciertos, int $total, int $tiempoMs): void
{
    $pct = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0.0;

    actualizar_json(ruta_puntuaciones(), ['siguiente_id' => 1, 'puntuaciones' => []], function (array $datos) use ($usuarioId, $tipo, $modo, $aciertos, $total, $pct, $tiempoMs) {
        $id = $datos['siguiente_id'] ?? 1;
        $datos['puntuaciones'][] = [
            'id' => $id,
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'modo' => $modo,
            'aciertos' => $aciertos,
            'total' => $total,
            'pct' => $pct,
            'tiempo_ms' => $tiempoMs,
            'creado_en' => date('Y-m-d H:i:s'),
        ];
        $datos['siguiente_id'] = $id + 1;

        return ['datos' => $datos, 'valor' => null];
    });
}

function formatear_tiempo(int $tiempoMs): string
{
    $centesimas = intdiv($tiempoMs % 1000, 10);
    $segundosTotales = intdiv($tiempoMs, 1000);
    $minutos = intdiv($segundosTotales, 60);
    $segundos = $segundosTotales % 60;
    return sprintf('%d:%02d.%02d', $minutos, $segundos, $centesimas);
}

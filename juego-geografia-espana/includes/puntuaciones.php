<?php
declare(strict_types=1);

const TIPOS_VALIDOS = ['provincias', 'ccaa'];
const MODOS_VALIDOS = ['reconocer', 'tocar'];
const VARIANTES_VALIDAS = ['aprendizaje', 'cronometrado'];

/**
 * Mejor puntuación (cronometrada) de un usuario para un tipo/modo:
 * mayor porcentaje y, en caso de empate, menor tiempo.
 */
function mejor_puntuacion_usuario(PDO $pdo, int $usuarioId, string $tipo, string $modo): ?array
{
    $sql = 'SELECT p.aciertos, p.total, p.pct, p.tiempo_ms
            FROM puntuaciones p
            LEFT JOIN puntuaciones mejor
              ON mejor.usuario_id = p.usuario_id
             AND mejor.tipo = p.tipo AND mejor.modo = p.modo
             AND (mejor.pct > p.pct OR (mejor.pct = p.pct AND mejor.tiempo_ms < p.tiempo_ms))
            WHERE p.usuario_id = ? AND p.tipo = ? AND p.modo = ? AND mejor.id IS NULL
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuarioId, $tipo, $modo]);
    $fila = $stmt->fetch();
    return $fila ?: null;
}

/**
 * Ranking (mejor puntuación por usuario) para un tipo/modo, ordenado por
 * porcentaje descendente y tiempo ascendente.
 */
function ranking_tipo_modo(PDO $pdo, string $tipo, string $modo, int $limite = 100): array
{
    $sql = 'SELECT u.nombre_usuario, p.usuario_id, p.aciertos, p.total, p.pct, p.tiempo_ms, p.creado_en
            FROM puntuaciones p
            JOIN usuarios u ON u.id = p.usuario_id
            LEFT JOIN puntuaciones mejor
              ON mejor.usuario_id = p.usuario_id
             AND mejor.tipo = p.tipo AND mejor.modo = p.modo
             AND (mejor.pct > p.pct OR (mejor.pct = p.pct AND mejor.tiempo_ms < p.tiempo_ms))
            WHERE p.tipo = ? AND p.modo = ? AND mejor.id IS NULL
            ORDER BY p.pct DESC, p.tiempo_ms ASC
            LIMIT ' . (int) $limite;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipo, $modo]);
    return $stmt->fetchAll();
}

/**
 * Posición (1-indexada) que ocuparía una puntuación dada dentro del ranking.
 */
function posicion_en_ranking(PDO $pdo, string $tipo, string $modo, float $pct, int $tiempoMs): int
{
    $sql = 'SELECT COUNT(DISTINCT p.usuario_id) AS mejores
            FROM puntuaciones p
            WHERE p.tipo = ? AND p.modo = ?
              AND (p.pct > ? OR (p.pct = ? AND p.tiempo_ms < ?))';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipo, $modo, $pct, $pct, $tiempoMs]);
    return (int) $stmt->fetchColumn() + 1;
}

function guardar_puntuacion(PDO $pdo, int $usuarioId, string $tipo, string $modo, int $aciertos, int $total, int $tiempoMs): void
{
    $pct = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0.0;
    $stmt = $pdo->prepare(
        'INSERT INTO puntuaciones (usuario_id, tipo, modo, aciertos, total, pct, tiempo_ms)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $tipo, $modo, $aciertos, $total, $pct, $tiempoMs]);
}

function formatear_tiempo(int $tiempoMs): string
{
    $centesimas = intdiv($tiempoMs % 1000, 10);
    $segundosTotales = intdiv($tiempoMs, 1000);
    $minutos = intdiv($segundosTotales, 60);
    $segundos = $segundosTotales % 60;
    return sprintf('%d:%02d.%02d', $minutos, $segundos, $centesimas);
}

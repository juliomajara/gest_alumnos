<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Comprobación de acceso básico.
 * Permite ejecutar el backup si existe la constante ADMIN=true
 * o si hay una sesión activa con un indicador común de autenticación.
 */
function usuario_autorizado_para_backup(): bool
{
    if (defined('ADMIN') && ADMIN === true) {
        return true;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $claves_autenticacion = [
        'user_id',
        'usuario_id',
        'admin',
        'is_admin',
        'authenticated',
        'logged_in',
    ];

    foreach ($claves_autenticacion as $clave) {
        if (!empty($_SESSION[$clave])) {
            return true;
        }
    }

    return false;
}

/**
 * Obtiene todas las tablas base de la base de datos actual.
 */
function obtener_tablas(PDO $pdo): array
{
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $filas = $stmt->fetchAll(PDO::FETCH_NUM) ?: [];

    return array_map(static fn(array $fila): string => (string) $fila[0], $filas);
}

/**
 * Crea un orden de exportación por dependencias FK (padres primero).
 */
function ordenar_tablas_por_dependencias(PDO $pdo, array $tablas): array
{
    if ($tablas === []) {
        return [];
    }

    $dependencias = [];
    $dependientes = [];

    foreach ($tablas as $tabla) {
        $dependencias[$tabla] = [];
        $dependientes[$tabla] = [];
    }

    $stmt = $pdo->query(
        'SELECT TABLE_NAME, REFERENCED_TABLE_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND REFERENCED_TABLE_NAME IS NOT NULL'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $relacion) {
        $hija = (string) $relacion['TABLE_NAME'];
        $padre = (string) $relacion['REFERENCED_TABLE_NAME'];

        if (!isset($dependencias[$hija], $dependencias[$padre])) {
            continue;
        }

        $dependencias[$hija][$padre] = true;
        $dependientes[$padre][$hija] = true;
    }

    $cola = [];
    foreach ($dependencias as $tabla => $deps) {
        if (count($deps) === 0) {
            $cola[] = $tabla;
        }
    }

    sort($cola);

    $orden = [];
    while ($cola !== []) {
        $tabla = array_shift($cola);
        $orden[] = $tabla;

        foreach (array_keys($dependientes[$tabla]) as $tabla_hija) {
            unset($dependencias[$tabla_hija][$tabla]);
            if (count($dependencias[$tabla_hija]) === 0) {
                $cola[] = $tabla_hija;
            }
        }

        sort($cola);
    }

    // Si hay ciclos extraños, añadimos las tablas restantes al final.
    if (count($orden) < count($tablas)) {
        $faltantes = array_values(array_diff($tablas, $orden));
        sort($faltantes);
        $orden = array_merge($orden, $faltantes);
    }

    return $orden;
}

/**
 * Devuelve el SQL CREATE TABLE de una tabla.
 */
function obtener_create_table(PDO $pdo, string $tabla): string
{
    $stmt = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $tabla) . '`');
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!is_array($fila) || !isset($fila['Create Table'])) {
        throw new RuntimeException('No se pudo obtener la estructura de una tabla.');
    }

    return (string) $fila['Create Table'];
}

/**
 * Convierte un valor PHP a literal SQL seguro.
 */
function valor_sql(PDO $pdo, mixed $valor): string
{
    if ($valor === null) {
        return 'NULL';
    }

    if (is_bool($valor)) {
        return $valor ? '1' : '0';
    }

    if (is_int($valor) || is_float($valor)) {
        return (string) $valor;
    }

    return $pdo->quote((string) $valor);
}

/**
 * Genera los INSERT INTO de una tabla.
 */
function obtener_inserts_tabla(PDO $pdo, string $tabla): string
{
    $sql = '';
    $tabla_escapada = '`' . str_replace('`', '``', $tabla) . '`';

    $stmtColumnas = $pdo->query('SHOW COLUMNS FROM ' . $tabla_escapada);
    $columnas = $stmtColumnas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($columnas === []) {
        return $sql;
    }

    $nombresColumnas = array_map(
        static fn(array $col): string => '`' . str_replace('`', '``', (string) $col['Field']) . '`',
        $columnas
    );

    $stmtDatos = $pdo->query('SELECT * FROM ' . $tabla_escapada);

    while (($fila = $stmtDatos->fetch(PDO::FETCH_ASSOC)) !== false) {
        $valores = [];

        foreach ($columnas as $columna) {
            $nombre = (string) $columna['Field'];
            $valores[] = valor_sql($pdo, $fila[$nombre] ?? null);
        }

        $sql .= 'INSERT INTO ' . $tabla_escapada . ' (' . implode(', ', $nombresColumnas) . ') VALUES (' . implode(', ', $valores) . ');' . PHP_EOL;
    }

    return $sql;
}

if (!usuario_autorizado_para_backup()) {
    http_response_code(403);
    echo 'Acceso no autorizado.';
    exit;
}

$mensaje = 'No se pudo generar la copia de seguridad.';
$ok = false;

try {
    $pdo = db();
    $pdo->exec('SET SESSION group_concat_max_len = 1000000');

    $tablas = obtener_tablas($pdo);
    $tablas_ordenadas = ordenar_tablas_por_dependencias($pdo, $tablas);

    $backupDir = __DIR__ . '/docs/backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('No se pudo crear el directorio de backups.');
    }

    $nombreArchivo = 'backup_gestion_alumnos_' . date('Ymd_His') . '.sql';
    $rutaArchivo = $backupDir . '/' . $nombreArchivo;

    $contenido = "-- Backup de base de datos\n";
    $contenido .= '-- Generado: ' . date('Y-m-d H:i:s') . "\n";
    $contenido .= '-- Aplicación: Gestor de Alumnos' . "\n\n";
    $contenido .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $contenido .= "SET AUTOCOMMIT = 0;\n";
    $contenido .= "START TRANSACTION;\n";
    $contenido .= "SET time_zone = \"+00:00\";\n";
    $contenido .= "SET NAMES utf8mb4;\n";
    $contenido .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tablas_ordenadas as $tabla) {
        $createTable = obtener_create_table($pdo, $tabla);

        $contenido .= '-- ----------------------------' . "\n";
        $contenido .= '-- Estructura de tabla `' . $tabla . '`' . "\n";
        $contenido .= '-- ----------------------------' . "\n";
        $contenido .= 'DROP TABLE IF EXISTS `' . str_replace('`', '``', $tabla) . '`;' . "\n";
        $contenido .= $createTable . ';' . "\n\n";

        $contenido .= '-- ----------------------------' . "\n";
        $contenido .= '-- Datos de tabla `' . $tabla . '`' . "\n";
        $contenido .= '-- ----------------------------' . "\n";
        $contenido .= obtener_inserts_tabla($pdo, $tabla);
        $contenido .= "\n";
    }

    $contenido .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $contenido .= "COMMIT;\n";

    if (file_put_contents($rutaArchivo, $contenido) === false) {
        throw new RuntimeException('No se pudo escribir el archivo SQL de backup.');
    }

    $ok = true;
    $mensaje = 'Backup generado correctamente: ' . $nombreArchivo;
} catch (Throwable $exception) {
    http_response_code(500);
    $ok = false;
    $mensaje = 'No se pudo generar la copia de seguridad.';
}

$page_title = 'Backup de base de datos | Gestor de Alumnos';
$active_page = 'configuracion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="page">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
      <header class="header">
        <div>
          <h1>Backup de base de datos</h1>
          <p class="subheading"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </header>

      <?php if (!$ok): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Estado</h3>
            <p>Ha ocurrido un error al generar la copia de seguridad.</p>
          </div>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>

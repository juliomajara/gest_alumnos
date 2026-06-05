<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Comprobación de acceso básico.
 */
function usuario_autorizado_para_backup(): bool
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }

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

// ---------------------------------------------------------------------------

if (!usuario_autorizado_para_backup()) {
    http_response_code(403);
    echo 'Acceso no autorizado.';
    exit;
}

$backupDir = __DIR__ . '/database/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    http_response_code(500);
    echo 'No se pudo crear el directorio de backups.';
    exit;
}

$action = (string) ($_REQUEST['action'] ?? '');

// --- Descarga ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'descargar') {
    $archivo = basename((string) ($_GET['archivo'] ?? ''));
    $ruta = $backupDir . '/' . $archivo;
    if (preg_match('/^backup_gestion_alumnos_\d{8}_\d{6}\.sql$/', $archivo) && is_file($ruta)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
    http_response_code(404);
    echo 'Archivo no encontrado.';
    exit;
}

// --- Eliminar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar') {
    $archivo = basename((string) ($_POST['archivo'] ?? ''));
    $ruta = $backupDir . '/' . $archivo;
    if (preg_match('/^backup_gestion_alumnos_\d{8}_\d{6}\.sql$/', $archivo) && is_file($ruta)) {
        unlink($ruta);
    }
    header('Location: dump_db.php');
    exit;
}

// --- Crear ---
$alerta_creacion = '';
$exito_creacion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
    try {
        $pdo = db();
        $pdo->exec('SET SESSION group_concat_max_len = 1000000');
        set_time_limit(120);

        $tablas = obtener_tablas($pdo);
        $tablas_ordenadas = ordenar_tablas_por_dependencias($pdo, $tablas);

        $nombreArchivo = 'backup_gestion_alumnos_' . date('Ymd_His') . '.sql';
        $rutaArchivo   = $backupDir . '/' . $nombreArchivo;

        $contenido  = "-- Backup de base de datos\n";
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
            throw new RuntimeException('No se pudo escribir el archivo SQL.');
        }

        $exito_creacion = 'Backup generado correctamente: ' . htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8');
    } catch (Throwable $e) {
        $alerta_creacion = 'Error al generar el backup: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// --- Lista de backups ---
$backups = [];
$archivos_raw = glob($backupDir . '/backup_gestion_alumnos_*.sql') ?: [];
rsort($archivos_raw);
foreach ($archivos_raw as $ruta) {
    $ts = (int) filemtime($ruta);
    $backups[] = [
        'nombre' => basename($ruta),
        'fecha'  => $ts > 0 ? date('d/m/Y H:i', $ts) : '—',
        'ts'     => $ts,
        'bytes'  => (int) filesize($ruta),
    ];
}

$ultimo_ts       = $backups !== [] ? $backups[0]['ts'] : 0;
$dias_sin_backup = $ultimo_ts > 0 ? (int) floor((time() - $ultimo_ts) / 86400) : -1;

$page_title  = 'Backup de base de datos | Gestor de Alumnos';
$active_page = 'utilidades';
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
          <p class="subheading">Genera y gestiona copias de seguridad completas de la base de datos.</p>
        </div>
        <div class="header-actions">
          <form method="post">
            <input type="hidden" name="action" value="crear">
            <button type="submit" class="primary-button">Crear backup ahora</button>
          </form>
        </div>
      </header>

      <?php if ($exito_creacion !== ''): ?>
        <div class="backup-notice backup-notice--ok"><?php echo $exito_creacion; ?></div>
      <?php endif; ?>
      <?php if ($alerta_creacion !== ''): ?>
        <div class="backup-notice backup-notice--error"><?php echo $alerta_creacion; ?></div>
      <?php endif; ?>

      <?php if ($backups === []): ?>
        <div class="backup-notice backup-notice--warn">No hay ningún backup guardado. Crea el primero ahora.</div>
      <?php elseif ($dias_sin_backup >= 7): ?>
        <div class="backup-notice backup-notice--warn">El último backup tiene <?php echo $dias_sin_backup; ?> días de antigüedad. Se recomienda crear uno nuevo.</div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <h3>Copias de seguridad guardadas</h3>
          <p>Se almacenan en <code>database/backups/</code> dentro del proyecto. Descarga el archivo SQL para importarlo desde phpMyAdmin.</p>
        </div>

        <div class="panel-grid">
          <?php if ($backups === []): ?>
            <p style="padding: 1rem 1.25rem; color: var(--muted);">Aún no hay backups.</p>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Archivo</th>
                  <th>Fecha</th>
                  <th>Tamaño</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($backups as $b): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($b['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($b['bytes'] / 1024, 0, ',', '.') . ' KB'; ?></td>
                    <td class="backup-actions">
                      <a class="primary-button" href="dump_db.php?action=descargar&amp;archivo=<?php echo urlencode($b['nombre']); ?>">Descargar</a>
                      <form method="post" onsubmit="return confirm('¿Eliminar este backup?');">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="danger-button">Eliminar</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h3>Automatización</h3>
          <p>Para ejecutar el backup diariamente de forma automática sin intervención manual.</p>
        </div>
        <div class="panel-grid" style="padding: 1rem 1.25rem;">
          <p style="color: var(--muted); font-size: 0.875rem; margin-bottom: 0.75rem;">Crea una tarea en el <strong>Programador de tareas de Windows</strong> que ejecute este comando cada día:</p>
          <code class="backup-code">php "<?php echo htmlspecialchars(__FILE__, ENT_QUOTES, 'UTF-8'); ?>"</code>
          <p style="color: var(--muted); font-size: 0.875rem; margin-top: 0.75rem;">O desde línea de comandos con curl (si el servidor está en marcha):</p>
          <code class="backup-code">curl -s -X POST "http://localhost/gest_alumnos/dump_db.php" -d "action=crear"</code>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

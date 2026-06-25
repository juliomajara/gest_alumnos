<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function pe_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function pe_format_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if ($date !== false && $date->format('Y-m-d') === $value) {
        return $date->format('d/m/Y');
    }
    return $value;
}

function pe_calculate_practice_status(array $practice): string
{
    if ((int) ($practice['cancelada'] ?? 0) === 1) {
        return 'Cancelada';
    }

    $fecha_inicio = $practice['fecha_inicio'] ?? null;
    $fecha_fin_efectiva = $practice['fecha_fin_extra'] ?? $practice['fecha_fin'] ?? null;

    if ($fecha_inicio === null || $fecha_fin_efectiva === null) {
        return 'No disponible';
    }

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    if ($today < $fecha_inicio) {
        return 'En espera';
    }

    if ($today <= $fecha_fin_efectiva) {
        return 'En curso';
    }

    return 'Finalizada';
}

$pdo = db();
$formato = ($_GET['formato'] ?? 'excel') === 'pdf' ? 'pdf' : 'excel';

$search_term = trim((string) ($_GET['q'] ?? ''));

$allowed_orders = ['alumno_asc', 'alumno_desc', 'empresa_asc', 'empresa_desc', 'fecha_inicio_asc', 'fecha_inicio_desc', 'fecha_fin_asc', 'fecha_fin_desc', 'anexo_asc', 'anexo_desc', 'estado_asc', 'estado_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
$_last_us = strrpos($current_order, '_');
$sort_col = $_last_us !== false ? substr($current_order, 0, $_last_us) : 'alumno';
$sort_dir = $_last_us !== false ? substr($current_order, $_last_us + 1) : 'asc';

$filters = [];
$params = [];

if ($search_term !== '') {
    $filters[] = '(
        a.nombre LIKE :search_term_alumno_nombre
        OR a.apellido1 LIKE :search_term_alumno_apellido1
        OR a.apellido2 LIKE :search_term_alumno_apellido2
        OR a.dni LIKE :search_term_alumno_dni
        OR e.nombre LIKE :search_term_empresa_nombre
        OR e.apellido1 LIKE :search_term_empresa_apellido1
        OR e.apellido2 LIKE :search_term_empresa_apellido2
        OR e.cif LIKE :search_term_empresa_cif
        OR CAST(e.convenio AS CHAR) LIKE :search_term_empresa_convenio
    )';
    $search_like = '%' . $search_term . '%';
    $params['search_term_alumno_nombre'] = $search_like;
    $params['search_term_alumno_apellido1'] = $search_like;
    $params['search_term_alumno_apellido2'] = $search_like;
    $params['search_term_alumno_dni'] = $search_like;
    $params['search_term_empresa_nombre'] = $search_like;
    $params['search_term_empresa_apellido1'] = $search_like;
    $params['search_term_empresa_apellido2'] = $search_like;
    $params['search_term_empresa_cif'] = $search_like;
    $params['search_term_empresa_convenio'] = $search_like;
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
$order_clause = match ($sort_col) {
    'empresa'      => "ORDER BY e.nombre $dir, e.apellido1 $dir, e.apellido2 $dir, p.id_practica DESC",
    'fecha_inicio' => "ORDER BY p.fecha_inicio $dir, p.id_practica DESC",
    'fecha_fin'    => "ORDER BY COALESCE(p.fecha_fin_extra, p.fecha_fin) $dir, p.id_practica DESC",
    'anexo'        => "ORDER BY CAST(p.anexo AS UNSIGNED) $dir, p.id_practica DESC",
    'estado'       => "ORDER BY CASE WHEN p.cancelada = 1 THEN 1 WHEN p.fecha_inicio IS NULL OR COALESCE(p.fecha_fin_extra, p.fecha_fin) IS NULL THEN 2 WHEN CURDATE() < p.fecha_inicio THEN 3 WHEN CURDATE() <= COALESCE(p.fecha_fin_extra, p.fecha_fin) THEN 4 ELSE 5 END $dir, p.id_practica DESC",
    default        => "ORDER BY a.apellido1 $dir, a.apellido2 $dir, a.nombre $dir, p.id_practica DESC",
};

$stmt = $pdo->prepare(
    'SELECT
        p.anexo,
        p.fecha_inicio,
        p.fecha_fin,
        p.fecha_fin_extra,
        p.fecha_fin_real,
        p.cancelada,
        p.motivo_exclusion,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        e.nombre AS empresa_nombre,
        e.nombre_comercial AS empresa_nombre_comercial,
        e.apellido1 AS empresa_apellido1,
        e.apellido2 AS empresa_apellido2,
        e.convenio
    FROM practicas p
    INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
    INNER JOIN empresas e ON e.id_empresa = p.id_empresa
    ' . $where_clause . '
    ' . $order_clause
);
$stmt->execute($params);
$practices = $stmt->fetchAll();

$filename = 'practicas_' . date('Y-m-d');

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Prácticas</title>
<style>
body { font-family: Arial, sans-serif; font-size: 10px; color: #1f2a44; margin: 16px; }
h1 { font-size: 15px; margin: 0 0 4px; }
p.meta { font-size: 9px; color: #6b7280; margin: 0 0 14px; }
table { border-collapse: collapse; width: 100%; }
th { background: #eef0f7; color: #374151; font-weight: bold; padding: 4px 6px; border: 1px solid #d1d5db; font-size: 9px; text-align: left; white-space: nowrap; }
td { padding: 3px 6px; border: 1px solid #e5e7eb; font-size: 9px; vertical-align: middle; }
tr:nth-child(even) td { background: #f9fafb; }
</style>
</head>
<body>
<h1>Prácticas</h1>
<p class="meta">Generado el <?php echo date('d/m/Y H:i'); ?></p>

<table>
  <thead>
    <tr>
      <th>Alumno</th>
      <th>Empresa</th>
      <th>Fecha inicio</th>
      <th>Fecha prevista fin</th>
      <th>Fecha fin real</th>
      <th>Estado</th>
      <th>Motivo de cancelación</th>
      <th>Convenio/Anexo</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($practices === []): ?>
    <tr><td colspan="8">No hay prácticas para los filtros seleccionados.</td></tr>
    <?php else: ?>
    <?php foreach ($practices as $p):
        $alumno_apellido2 = trim((string) ($p['alumno_apellido2'] ?? ''));
        $alumno_apellidos = trim(implode(' ', array_filter([
            $p['alumno_apellido1'] ?? '',
            $alumno_apellido2,
        ], static fn ($v) => trim((string) $v) !== '')));
        $alumno_nombre = trim((string) ($p['alumno_nombre'] ?? ''));
        $alumno = $alumno_apellidos !== '' || $alumno_nombre !== ''
            ? trim($alumno_apellidos . ', ' . $alumno_nombre, ' ,')
            : 'No disponible';

        $empresa_nombre_completo = trim(implode(' ', array_filter([
            $p['empresa_nombre'] ?? '',
            $p['empresa_apellido1'] ?? '',
            $p['empresa_apellido2'] ?? '',
        ], static fn ($v) => trim((string) $v) !== '')));
        $empresa_nombre_comercial = trim((string) ($p['empresa_nombre_comercial'] ?? ''));
        $empresa_nombre_apellido1 = trim(implode(' ', array_filter([
            $p['empresa_nombre'] ?? '',
            $p['empresa_apellido1'] ?? '',
        ], static fn ($v) => trim((string) $v) !== '')));
        $empresa = $empresa_nombre_completo !== '' ? $empresa_nombre_completo : 'No disponible';
        if ($empresa_nombre_comercial !== '' && $empresa_nombre_comercial !== $empresa_nombre_completo) {
            $empresa = $empresa_nombre_comercial;
            if ($empresa_nombre_apellido1 !== '') {
                $empresa .= ' (' . $empresa_nombre_apellido1 . ')';
            }
        }

        $fecha_inicio      = pe_format_date($p['fecha_inicio'] ?? null);
        $fecha_prevista_fin = pe_format_date($p['fecha_fin_extra'] ?? $p['fecha_fin'] ?? null);
        $estado            = pe_calculate_practice_status($p);
        $fecha_fin_real_raw = $p['fecha_fin_real'] ?? null;
        if ($estado === 'Finalizada') {
            $fecha_fin_real = ($fecha_fin_real_raw !== null && $fecha_fin_real_raw !== '')
                ? pe_format_date($fecha_fin_real_raw)
                : 'Fecha prevista';
        } elseif ($estado === 'Cancelada') {
            $fecha_fin_real = pe_format_date($fecha_fin_real_raw);
        } else {
            $fecha_fin_real = '';
        }
        $motivo_cancelacion = ($p['motivo_exclusion'] !== null && $p['motivo_exclusion'] !== '') ? (string) $p['motivo_exclusion'] : '';
        $convenio     = ($p['convenio'] !== null && $p['convenio'] !== '') ? (string) $p['convenio'] : '';
        $anexo_val    = ($p['anexo'] !== null && $p['anexo'] !== '') ? (string) $p['anexo'] : '';
        $anexo        = $convenio . ' / ' . $anexo_val;
    ?>
    <tr>
      <td><?php echo pe_h($alumno); ?></td>
      <td><?php echo pe_h($empresa); ?></td>
      <td><?php echo pe_h($fecha_inicio); ?></td>
      <td><?php echo pe_h($fecha_prevista_fin); ?></td>
      <td><?php echo pe_h($fecha_fin_real); ?></td>
      <td><?php echo pe_h($estado); ?></td>
      <td><?php echo pe_h($motivo_cancelacion); ?></td>
      <td><?php echo pe_h($anexo); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
<?php
$html_content = (string) ob_get_clean();

if ($formato === 'pdf') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $mpdf_temp = __DIR__ . '/../docs/tmp_mpdf';
    if (!is_dir($mpdf_temp) && !mkdir($mpdf_temp, 0775, true) && !is_dir($mpdf_temp)) {
        http_response_code(500);
        exit('No se pudo crear el directorio temporal para PDF.');
    }
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_left'   => 8,
        'margin_right'  => 8,
        'margin_top'    => 12,
        'margin_bottom' => 10,
        'tempDir'       => $mpdf_temp,
    ]);
    $mpdf->WriteHTML($html_content);
    $mpdf->Output($filename . '.pdf', 'D');
} else {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";
    echo $html_content;
}
exit;

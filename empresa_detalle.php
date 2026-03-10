<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$id_empresa = isset($_GET['id_empresa']) ? (int) $_GET['id_empresa'] : 0;

function format_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (string) $value;
}

function format_date(?string $value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if ($date && $date->format('Y-m-d') === $value) {
    return $date->format('d/m/Y');
  }

  return $value;
}

function format_bool(?int $value, string $fallback = 'No disponible'): string {
  if ($value === null) {
    return $fallback;
  }

  return $value === 1 ? 'Sí' : 'No';
}

function calculate_practica_estado(array $practica): string {
  if ((int) ($practica['cancelada'] ?? 0) === 1) {
    return 'Cancelada';
  }

  $fecha_inicio = $practica['fecha_inicio'] ?? null;
  $fecha_fin_extra = $practica['fecha_fin_extra'] ?? null;

  if (!$fecha_inicio || !$fecha_fin_extra) {
    return 'No disponible';
  }

  $inicio = DateTime::createFromFormat('Y-m-d', (string) $fecha_inicio);
  $fin_real = DateTime::createFromFormat('Y-m-d', (string) $fecha_fin_extra);

  if (!$inicio || !$fin_real) {
    return 'No disponible';
  }

  $hoy = new DateTime('today');

  if ($hoy < $inicio) {
    return 'En espera';
  }

  if ($hoy > $fin_real) {
    return 'Finalizada';
  }

  return 'En curso';
}

function pick_contact_value(array $items, string $value_key, array $preferred_labels): ?string {
  foreach ($preferred_labels as $label) {
    foreach ($items as $item) {
      if (!isset($item['etiqueta'])) {
        continue;
      }
      if (strtolower((string) $item['etiqueta']) === strtolower($label)) {
        return $item[$value_key] ?? null;
      }
    }
  }

  if (!$items) {
    return null;
  }

  $first = $items[0];
  return $first[$value_key] ?? null;
}

$company = null;
$contacts = [];
$tutors = [];
$addresses = [];
$phones = [];
$emails = [];
$practicas = [];
$horarios = [];

if ($id_empresa > 0) {
  $company_stmt = $pdo->prepare(
    'SELECT e.*
     FROM empresas e
     WHERE e.id_empresa = :id_empresa'
  );
  $company_stmt->execute(['id_empresa' => $id_empresa]);
  $company = $company_stmt->fetch();
}

if ($company) {
  $contacts_stmt = $pdo->prepare(
    'SELECT apellido1,
            apellido2,
            nombre,
            cargo,
            comentarios
     FROM empresas_contactos
     WHERE id_empresa = :id_empresa
     ORDER BY apellido1, apellido2, nombre'
  );
  $contacts_stmt->execute(['id_empresa' => $id_empresa]);
  $contacts = $contacts_stmt->fetchAll();

  $tutors_stmt = $pdo->prepare(
    'SELECT apellido1,
            apellido2,
            nombre,
            dni,
            comentarios
     FROM empresas_tutores
     WHERE id_empresa = :id_empresa
     ORDER BY apellido1, apellido2, nombre'
  );
  $tutors_stmt->execute(['id_empresa' => $id_empresa]);
  $tutors = $tutors_stmt->fetchAll();

  $address_stmt = $pdo->prepare(
    'SELECT d.id_direccion,
            d.id_empresa,
            d.id_pais,
            d.id_provincia,
            d.id_localidad,
            d.id_via,
            d.nombre_via,
            d.numero,
            d.bloque,
            d.escalera,
            d.planta,
            d.puerta,
            d.otros,
            d.etiqueta,
            d.cp,
            d.principal,
            v.via AS via_tipo,
            p.nombre AS provincia,
            l.nombre AS localidad,
            pa.pais
     FROM direcciones d
     LEFT JOIN vias v ON v.id_via = d.id_via
     LEFT JOIN provincias p ON p.id_provincia = d.id_provincia
     LEFT JOIN localidades l ON l.id_localidad = d.id_localidad
     LEFT JOIN paises pa ON pa.id_pais = d.id_pais
     WHERE d.id_empresa = :id_empresa
     ORDER BY d.principal DESC, d.etiqueta, d.id_direccion'
  );
  $address_stmt->execute(['id_empresa' => $id_empresa]);
  $addresses = $address_stmt->fetchAll();

  $phones_stmt = $pdo->prepare(
    'SELECT telefono, etiqueta
     FROM telefonos
     WHERE entidad_tipo = :tipo AND id_entidad = :id_empresa
     ORDER BY etiqueta, telefono'
  );
  $phones_stmt->execute(['tipo' => 'empresa', 'id_empresa' => $id_empresa]);
  $phones = $phones_stmt->fetchAll();

  $emails_stmt = $pdo->prepare(
    'SELECT direccion_correo, etiqueta
     FROM correos
     WHERE entidad_tipo = :tipo AND id_entidad = :id_empresa
     ORDER BY etiqueta, direccion_correo'
  );
  $emails_stmt->execute(['tipo' => 'empresa', 'id_empresa' => $id_empresa]);
  $emails = $emails_stmt->fetchAll();

  $practicas_stmt = $pdo->prepare(
    'SELECT pr.id_practica,
            pr.fecha_inicio,
            pr.fecha_fin,
            pr.fecha_fin_extra,
            pr.cancelada,
            pr.horas,
            pr.observaciones,
            et.nombre AS tutor_nombre,
            et.apellido1 AS tutor_apellido1,
            et.apellido2 AS tutor_apellido2,
            et.dni AS tutor_dni,
            a.nombre AS alumno_nombre,
            a.apellido1 AS alumno_apellido1,
            a.apellido2 AS alumno_apellido2,
            a.nia AS alumno_nia,
            a.dni AS alumno_dni,
            d.nombre_via,
            d.numero,
            d.bloque,
            d.escalera,
            d.planta,
            d.puerta,
            d.otros,
            d.cp AS direccion_cp,
            d.etiqueta AS direccion_etiqueta,
            v.via AS via_tipo,
            lp.nombre AS direccion_provincia,
            ll.nombre AS direccion_localidad,
            pa.pais AS direccion_pais
     FROM practicas pr
     LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = pr.id_empresa_tutor
     LEFT JOIN alumnos a ON a.id_alumno = pr.id_alumno
     LEFT JOIN direcciones d ON d.id_direccion = pr.id_direccion
     LEFT JOIN vias v ON v.id_via = d.id_via
     LEFT JOIN provincias lp ON lp.id_provincia = d.id_provincia
     LEFT JOIN localidades ll ON ll.id_localidad = d.id_localidad
     LEFT JOIN paises pa ON pa.id_pais = d.id_pais
     WHERE pr.id_empresa = :id_empresa
     ORDER BY pr.fecha_inicio DESC, pr.id_practica DESC'
  );
  $practicas_stmt->execute(['id_empresa' => $id_empresa]);
  $practicas = $practicas_stmt->fetchAll();

  $horario_stmt = $pdo->prepare(
    'SELECT ph.id_practica,
            ph.dia_semana,
            ph.hora_entrada,
            ph.hora_salida,
            a.nombre AS alumno_nombre,
            a.apellido1 AS alumno_apellido1,
            a.apellido2 AS alumno_apellido2
     FROM practicas_horario ph
     INNER JOIN practicas pr ON pr.id_practica = ph.id_practica
     INNER JOIN alumnos a ON a.id_alumno = pr.id_alumno
     WHERE pr.id_empresa = :id_empresa
     ORDER BY pr.id_practica, ph.dia_semana, ph.hora_entrada'
  );
  $horario_stmt->execute(['id_empresa' => $id_empresa]);
  $horarios = $horario_stmt->fetchAll();
}

$telefono_principal = pick_contact_value($phones, 'telefono', ['principal', 'empresa']);
$correo_principal = pick_contact_value($emails, 'direccion_correo', ['principal', 'empresa']);

$contacto_principal = $contacts[0] ?? null;

$direccion_principal = $addresses[0] ?? null;
$direccion_partes = [];
if ($direccion_principal) {
  $direccion_partes = array_filter([
    $direccion_principal['via_tipo'] ? $direccion_principal['via_tipo'] : null,
    $direccion_principal['nombre_via'] ? $direccion_principal['nombre_via'] : null,
    $direccion_principal['numero'] ? 'Nº ' . $direccion_principal['numero'] : null,
    $direccion_principal['bloque'] ? 'Bloque ' . $direccion_principal['bloque'] : null,
    $direccion_principal['escalera'] ? 'Esc. ' . $direccion_principal['escalera'] : null,
    $direccion_principal['planta'] ? 'Planta ' . $direccion_principal['planta'] : null,
    $direccion_principal['puerta'] ? 'Puerta ' . $direccion_principal['puerta'] : null,
    $direccion_principal['otros'] ? $direccion_principal['otros'] : null,
    $direccion_principal['cp'] ? 'CP ' . $direccion_principal['cp'] : null,
    $direccion_principal['localidad'] ? $direccion_principal['localidad'] : null,
    $direccion_principal['provincia'] ? $direccion_principal['provincia'] : null,
    $direccion_principal['pais'] ? $direccion_principal['pais'] : null,
  ]);
}

$nombre_completo = $company
  ? trim(sprintf(
    '%s %s %s',
    $company['nombre'],
    $company['apellido1'] ?? '',
    $company['apellido2'] ?? ''
  ))
  : 'Empresa no encontrada';

$convenio = $company ? trim((string) ($company['convenio'] ?? '')) : '';
$nombre_titulo = $nombre_completo;
if ($convenio !== '') {
  $nombre_titulo .= ' (' . $convenio . ')';
}

$nombre_comercial = $company ? trim((string) ($company['nombre_comercial'] ?? '')) : '';
$nombre_base_corto = $company
  ? trim(sprintf('%s %s', $company['nombre'] ?? '', $company['apellido1'] ?? ''))
  : '';
$nombre_empresa_mostrar = $nombre_completo;
if ($nombre_comercial !== '' && $nombre_comercial !== $nombre_completo) {
  $nombre_empresa_mostrar = $nombre_base_corto !== ''
    ? $nombre_comercial . ' (' . $nombre_base_corto . ')'
    : $nombre_comercial;
}

$direcciones_principales = [];
$direcciones_centro_trabajo = [];
foreach ($addresses as $address) {
  $direccion_texto = implode(' · ', array_filter([
    $address['via_tipo'] ? $address['via_tipo'] : null,
    $address['nombre_via'] ? $address['nombre_via'] : null,
    $address['numero'] ? 'Nº ' . $address['numero'] : null,
    $address['bloque'] ? 'Bloque ' . $address['bloque'] : null,
    $address['escalera'] ? 'Esc. ' . $address['escalera'] : null,
    $address['planta'] ? 'Planta ' . $address['planta'] : null,
    $address['puerta'] ? 'Puerta ' . $address['puerta'] : null,
    $address['otros'] ? $address['otros'] : null,
    $address['cp'] ? 'CP ' . $address['cp'] : null,
    $address['localidad'] ? $address['localidad'] : null,
    $address['provincia'] ? $address['provincia'] : null,
    $address['pais'] ? $address['pais'] : null,
  ]));

  if ($direccion_texto === '') {
    continue;
  }

  if ((int) ($address['principal'] ?? 0) === 1) {
    $direcciones_principales[] = $direccion_texto;
  }

  if (strpos(strtolower((string) ($address['etiqueta'] ?? '')), 'centro') !== false) {
    $direcciones_centro_trabajo[] = $direccion_texto;
  }
}

$page_title = $company
  ? sprintf('Ficha de %s | Gestor de Alumnos', $nombre_completo)
  : 'Empresa no encontrada | Gestor de Alumnos';
$active_page = 'empresas';

$dias_semana = [
  1 => 'Lunes',
  2 => 'Martes',
  3 => 'Miércoles',
  4 => 'Jueves',
  5 => 'Viernes',
  6 => 'Sábado',
  7 => 'Domingo',
];
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
          <p class="eyebrow">Ficha de empresa</p>
          <h1><?php echo htmlspecialchars($nombre_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Consulta la información general, contactos y prácticas asociadas a la empresa.</p>
        </div>
        <div class="header-actions">
          <a class="primary-button" href="empresa_editar.php?id_empresa=<?= (int)$id_empresa ?>">Editar empresa</a> <!-- // MODIFICADO -->
          <a class="ghost-button" href="empresas.php">Volver al listado</a>
        </div>
      </header>

      <?php if (!$company): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Empresa no encontrada</h3>
            <p>No hemos podido localizar la empresa solicitada. Comprueba el enlace o vuelve al listado.</p>
          </div>
        </section>
      <?php else: ?>
        <div class="practica-detalle-grid practica-detalle-grid--fila-1">
          <section class="panel practica-detalle-bloque">
            <div class="panel-header">
              <h3>Datos de la empresa</h3>
              <p>Información básica de identificación y contacto corporativo.</p>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">CIF</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($company['cif']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Empresa</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($nombre_empresa_mostrar), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Teléfono principal</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($telefono_principal), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Correo principal</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($correo_principal), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Teléfonos registrados</span>
                <span class="practica-detalle-campo-valor">
                  <?php
                    $telefono_items = [];
                    foreach ($phones as $phone) {
                      $etiqueta = format_value($phone['etiqueta'], '');
                      $telefono_label = $etiqueta ? $etiqueta . ': ' : '';
                      $telefono_items[] = $telefono_label . $phone['telefono'];
                    }
                  ?>
                  <?php echo htmlspecialchars($telefono_items ? implode(' · ', $telefono_items) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Correos registrados</span>
                <span class="practica-detalle-campo-valor">
                  <?php
                    $correo_items = [];
                    foreach ($emails as $email) {
                      $etiqueta = format_value($email['etiqueta'], '');
                      $correo_label = $etiqueta ? $etiqueta . ': ' : '';
                      $correo_items[] = $correo_label . $email['direccion_correo'];
                    }
                  ?>
                  <?php echo htmlspecialchars($correo_items ? implode(' · ', $correo_items) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </div>
            </div>
          </section>

          <section class="panel practica-detalle-bloque">
            <div class="panel-header">
              <h3>Contactos principales</h3>
              <p>Personas de referencia registradas en la empresa.</p>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Contacto 1</span>
                <span class="practica-detalle-campo-valor">
                  <?php
                    $contacto_nombre = $contacto_principal
                      ? trim(sprintf(
                        '%s %s %s',
                        $contacto_principal['nombre'] ?? '',
                        $contacto_principal['apellido1'] ?? '',
                        $contacto_principal['apellido2'] ?? ''
                      ))
                      : null;
                  ?>
                  <div><?php echo htmlspecialchars(format_value($contacto_nombre), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div>Cargo: <?php echo htmlspecialchars(format_value($contacto_principal['cargo'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div>Teléfono: <?php echo htmlspecialchars(format_value($telefono_principal), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div>Email: <?php echo htmlspecialchars(format_value($correo_principal), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div>Comentarios: <?php echo htmlspecialchars(format_value($contacto_principal['comentarios'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                </span>
              </div>
            </div>
          </section>
        </div>

        <div class="practica-detalle-grid practica-detalle-grid--fila-2">
          <section class="panel practica-detalle-bloque">
            <div class="panel-header">
              <h3>Dirección y localización</h3>
              <p>Dirección principal registrada para la empresa.</p>
            </div>
            <div class="practica-detalle-campos">
              <?php if ($direcciones_principales): ?>
                <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Principal</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(implode(' | ', $direcciones_principales), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php endif; ?>
              <?php if ($direcciones_centro_trabajo): ?>
                <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Centro de trabajo</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(implode(' | ', $direcciones_centro_trabajo), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php endif; ?>
              <?php if (!$direcciones_principales && !$direcciones_centro_trabajo): ?>
                <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Dirección</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars($direccion_partes ? implode(' · ', $direccion_partes) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php endif; ?>
            </div>
          </section>

          <section class="panel practica-detalle-bloque">
            <div class="panel-header">
              <h3>Notas</h3>
              <p>Comentarios internos de la empresa.</p>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Notas</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($company['notas']), ENT_QUOTES, 'UTF-8'); ?></span></div>
            </div>
          </section>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Contactos de empresa</h3>
            <p>Personas de contacto registradas en la empresa.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Apellido 1</th>
                  <th>Apellido 2</th>
                  <th>Cargo</th>
                  <th>Comentarios</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$contacts): ?>
                  <tr>
                    <td colspan="5">No hay contactos registrados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($contacts as $contact): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($contact['nombre']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($contact['apellido1']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($contact['apellido2']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($contact['cargo']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($contact['comentarios']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Tutores de empresa</h3>
            <p>Personas tutoras asignadas para el seguimiento de prácticas.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Apellido 1</th>
                  <th>Apellido 2</th>
                  <th>DNI</th>
                  <th>Comentarios</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$tutors): ?>
                  <tr>
                    <td colspan="5">No hay tutores registrados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($tutors as $tutor): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($tutor['nombre']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($tutor['apellido1']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($tutor['apellido2']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($tutor['dni']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($tutor['comentarios']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Direcciones registradas</h3>
            <p>Histórico de direcciones vinculadas a la empresa.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Etiqueta</th>
                  <th>Dirección</th>
                  <th>Código postal</th>
                  <th>Localidad</th>
                  <th>Provincia</th>
                  <th>País</th>
                  <th>Principal</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$addresses): ?>
                  <tr>
                    <td colspan="7">No hay direcciones registradas.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($addresses as $address): ?>
                    <?php
                      $direccion_detalle = array_filter([
                        $address['via_tipo'] ? $address['via_tipo'] : null,
                        $address['nombre_via'] ? $address['nombre_via'] : null,
                        $address['numero'] ? 'Nº ' . $address['numero'] : null,
                        $address['bloque'] ? 'Bloque ' . $address['bloque'] : null,
                        $address['escalera'] ? 'Esc. ' . $address['escalera'] : null,
                        $address['planta'] ? 'Planta ' . $address['planta'] : null,
                        $address['puerta'] ? 'Puerta ' . $address['puerta'] : null,
                        $address['otros'] ? $address['otros'] : null,
                      ]);
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($address['etiqueta']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($direccion_detalle ? implode(' · ', $direccion_detalle) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($address['cp']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($address['localidad']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($address['provincia']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($address['pais']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_bool($address['principal']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Prácticas</h3>
            <p>Alumnos, fechas, estado y horario de las prácticas en esta empresa.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Estado</th>
                  <th>Fechas</th>
                  <th>Horas</th>
                  <th>Tutor empresa</th>
                  <th>Dirección</th>
                  <th>Observaciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$practicas): ?>
                  <tr>
                    <td colspan="7">No hay prácticas registradas.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practicas as $practica): ?>
                    <?php
                      $fechas = trim(
                        sprintf(
                          '%s - %s',
                          format_date($practica['fecha_inicio'], 'Sin inicio'),
                          format_date($practica['fecha_fin'], 'Sin fin')
                        )
                      );
                      $tutor_nombre = trim(
                        sprintf(
                          '%s %s %s',
                          $practica['tutor_nombre'] ?? '',
                          $practica['tutor_apellido1'] ?? '',
                          $practica['tutor_apellido2'] ?? ''
                        )
                      );
                      $alumno_nombre = trim(
                        sprintf(
                          '%s %s %s',
                          $practica['alumno_nombre'] ?? '',
                          $practica['alumno_apellido1'] ?? '',
                          $practica['alumno_apellido2'] ?? ''
                        )
                      );
                      $alumno_detalle = $alumno_nombre ?: 'No disponible';
                      if ($practica['alumno_nia']) {
                        $alumno_detalle .= ' · NIA ' . $practica['alumno_nia'];
                      }
                      if ($practica['alumno_dni']) {
                        $alumno_detalle .= ' · DNI ' . $practica['alumno_dni'];
                      }
                      $direccion_partes = array_filter([
                        $practica['via_tipo'] ? $practica['via_tipo'] : null,
                        $practica['nombre_via'] ? $practica['nombre_via'] : null,
                        $practica['numero'] ? 'Nº ' . $practica['numero'] : null,
                        $practica['bloque'] ? 'Bloque ' . $practica['bloque'] : null,
                        $practica['escalera'] ? 'Esc. ' . $practica['escalera'] : null,
                        $practica['planta'] ? 'Planta ' . $practica['planta'] : null,
                        $practica['puerta'] ? 'Puerta ' . $practica['puerta'] : null,
                        $practica['otros'] ? $practica['otros'] : null,
                        $practica['direccion_cp'] ? 'CP ' . $practica['direccion_cp'] : null,
                        $practica['direccion_localidad'] ? $practica['direccion_localidad'] : null,
                        $practica['direccion_provincia'] ? $practica['direccion_provincia'] : null,
                        $practica['direccion_pais'] ? $practica['direccion_pais'] : null,
                      ]);
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($alumno_detalle, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(calculate_practica_estado($practica), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($fechas, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $practica['horas'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <?php echo htmlspecialchars($tutor_nombre ?: 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($practica['tutor_dni']): ?>
                          (<?php echo htmlspecialchars($practica['tutor_dni'], ENT_QUOTES, 'UTF-8'); ?>)
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($direccion_partes ? implode(' · ', $direccion_partes) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($practica['observaciones']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>ID práctica</th>
                  <th>Alumno</th>
                  <th>Día</th>
                  <th>Hora entrada</th>
                  <th>Hora salida</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$horarios): ?>
                  <tr>
                    <td colspan="5">No hay horarios de prácticas registrados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($horarios as $horario): ?>
                    <?php
                      $alumno_horario = trim(
                        sprintf(
                          '%s %s %s',
                          $horario['alumno_nombre'] ?? '',
                          $horario['alumno_apellido1'] ?? '',
                          $horario['alumno_apellido2'] ?? ''
                        )
                      );
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $horario['id_practica'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($alumno_horario ?: 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($dias_semana[(int) $horario['dia_semana']] ?? 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($horario['hora_entrada'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($horario['hora_salida'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>

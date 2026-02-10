<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function normalize_text($value): ?string {
  if ($value === null) {
    return null;
  }

  $trimmed = trim((string) $value);
  return $trimmed === '' ? null : $trimmed;
}

function sanitize_phone_entries(array $items): array {
  $clean = [];

  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }

    $telefono = normalize_text($item['telefono'] ?? null);
    $etiqueta = normalize_text($item['etiqueta'] ?? null);

    if ($telefono === null) {
      continue;
    }

    $clean[] = [
      'telefono' => $telefono,
      'etiqueta' => $etiqueta,
    ];
  }

  return $clean;
}

function sanitize_email_entries(array $items): array {
  $clean = [];

  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }

    $correo = normalize_text($item['correo'] ?? null);
    $etiqueta = normalize_text($item['etiqueta'] ?? null);

    if ($correo === null) {
      continue;
    }

    $clean[] = [
      'correo' => $correo,
      'etiqueta' => $etiqueta,
    ];
  }

  return $clean;
}

function normalize_entity_list($value): array {
  return is_array($value) ? $value : [];
}

$via_options = $pdo->query('SELECT id_via, via FROM vias ORDER BY via')->fetchAll();
$pais_options = $pdo->query('SELECT id_pais, pais FROM paises ORDER BY pais')->fetchAll();
$provincia_options = $pdo->query('SELECT id_provincia, nombre FROM provincias ORDER BY nombre')->fetchAll();
$localidad_options = $pdo->query('SELECT id_localidad, nombre FROM localidades ORDER BY nombre')->fetchAll();

$errors = [];
$form_values = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $empresa = [
    'cif' => normalize_text($_POST['empresa']['cif'] ?? null),
    'nombre' => normalize_text($_POST['empresa']['nombre'] ?? null),
    'apellido1' => normalize_text($_POST['empresa']['apellido1'] ?? null),
    'apellido2' => normalize_text($_POST['empresa']['apellido2'] ?? null),
    'numero_convenio' => normalize_text($_POST['empresa']['numero_convenio'] ?? null),
  ];

  if ($empresa['nombre'] === null) {
    $errors[] = 'El nombre de la empresa es obligatorio.';
  }

  if ($empresa['numero_convenio'] !== null && !ctype_digit($empresa['numero_convenio'])) {
    $errors[] = 'El número de convenio debe ser numérico.';
  }

  $telefonos_empresa = sanitize_phone_entries(normalize_entity_list($_POST['telefonos_empresa'] ?? []));
  $correos_empresa = sanitize_email_entries(normalize_entity_list($_POST['correos_empresa'] ?? []));

  $direcciones = [];
  foreach (normalize_entity_list($_POST['direcciones'] ?? []) as $direccion) {
    if (!is_array($direccion)) {
      continue;
    }

    $entry = [
      'etiqueta' => normalize_text($direccion['etiqueta'] ?? null),
      'id_via' => normalize_text($direccion['id_via'] ?? null),
      'nombre_via' => normalize_text($direccion['nombre_via'] ?? null),
      'numero' => normalize_text($direccion['numero'] ?? null),
      'bloque' => normalize_text($direccion['bloque'] ?? null),
      'escalera' => normalize_text($direccion['escalera'] ?? null),
      'planta' => normalize_text($direccion['planta'] ?? null),
      'puerta' => normalize_text($direccion['puerta'] ?? null),
      'cp' => normalize_text($direccion['cp'] ?? null),
      'id_pais' => normalize_text($direccion['id_pais'] ?? null),
      'id_provincia' => normalize_text($direccion['id_provincia'] ?? null),
      'id_localidad' => normalize_text($direccion['id_localidad'] ?? null),
    ];

    $has_any_value = false;
    foreach ($entry as $value) {
      if ($value !== null) {
        $has_any_value = true;
        break;
      }
    }

    if (!$has_any_value) {
      continue;
    }

    $direcciones[] = $entry;
  }

  $contactos = [];
  foreach (normalize_entity_list($_POST['contactos'] ?? []) as $contacto) {
    if (!is_array($contacto)) {
      continue;
    }

    $entry = [
      'nombre' => normalize_text($contacto['nombre'] ?? null),
      'apellido1' => normalize_text($contacto['apellido1'] ?? null),
      'apellido2' => normalize_text($contacto['apellido2'] ?? null),
      'cargo' => normalize_text($contacto['cargo'] ?? null),
      'telefonos' => sanitize_phone_entries(normalize_entity_list($contacto['telefonos'] ?? [])),
      'correos' => sanitize_email_entries(normalize_entity_list($contacto['correos'] ?? [])),
    ];

    if ($entry['nombre'] === null && $entry['apellido1'] === null && $entry['apellido2'] === null && $entry['cargo'] === null && !$entry['telefonos'] && !$entry['correos']) {
      continue;
    }

    if ($entry['nombre'] === null || $entry['apellido1'] === null) {
      $errors[] = 'Cada persona de contacto debe tener al menos nombre y apellido1.';
    }

    $contactos[] = $entry;
  }

  $tutores = [];
  foreach (normalize_entity_list($_POST['tutores'] ?? []) as $tutor) {
    if (!is_array($tutor)) {
      continue;
    }

    $entry = [
      'nombre' => normalize_text($tutor['nombre'] ?? null),
      'apellido1' => normalize_text($tutor['apellido1'] ?? null),
      'apellido2' => normalize_text($tutor['apellido2'] ?? null),
      'dni' => normalize_text($tutor['dni'] ?? null),
      'telefonos' => sanitize_phone_entries(normalize_entity_list($tutor['telefonos'] ?? [])),
      'correos' => sanitize_email_entries(normalize_entity_list($tutor['correos'] ?? [])),
    ];

    if ($entry['nombre'] === null && $entry['apellido1'] === null && $entry['apellido2'] === null && $entry['dni'] === null && !$entry['telefonos'] && !$entry['correos']) {
      continue;
    }

    if ($entry['nombre'] === null || $entry['apellido1'] === null) {
      $errors[] = 'Cada tutor debe tener al menos nombre y apellido1.';
    }

    $tutores[] = $entry;
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $insert_empresa = $pdo->prepare(
        'INSERT INTO empresas (cif, nombre, apellido1, apellido2, convenio)
         VALUES (:cif, :nombre, :apellido1, :apellido2, :convenio)'
      );
      $insert_empresa->execute([
        'cif' => $empresa['cif'],
        'nombre' => $empresa['nombre'],
        'apellido1' => $empresa['apellido1'],
        'apellido2' => $empresa['apellido2'],
        'convenio' => $empresa['numero_convenio'] !== null ? (int) $empresa['numero_convenio'] : null,
      ]);

      $id_empresa = (int) $pdo->lastInsertId();

      $insert_telefono = $pdo->prepare(
        'INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta)
         VALUES (:entidad_tipo, :id_entidad, :telefono, :etiqueta)'
      );

      foreach ($telefonos_empresa as $telefono) {
        $insert_telefono->execute([
          'entidad_tipo' => 'empresa',
          'id_entidad' => $id_empresa,
          'telefono' => $telefono['telefono'],
          'etiqueta' => $telefono['etiqueta'],
        ]);
      }

      $insert_correo = $pdo->prepare(
        'INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta)
         VALUES (:entidad_tipo, :id_entidad, :direccion_correo, :etiqueta)'
      );

      foreach ($correos_empresa as $correo) {
        $insert_correo->execute([
          'entidad_tipo' => 'empresa',
          'id_entidad' => $id_empresa,
          'direccion_correo' => $correo['correo'],
          'etiqueta' => $correo['etiqueta'],
        ]);
      }

      $insert_direccion = $pdo->prepare(
        'INSERT INTO direcciones (
          id_empresa, id_pais, id_provincia, id_localidad, id_via, nombre_via, numero, bloque, escalera,
          planta, puerta, etiqueta, cp, principal
        ) VALUES (
          :id_empresa, :id_pais, :id_provincia, :id_localidad, :id_via, :nombre_via, :numero, :bloque, :escalera,
          :planta, :puerta, :etiqueta, :cp, :principal
        )'
      );

      foreach ($direcciones as $direccion) {
        $insert_direccion->execute([
          'id_empresa' => $id_empresa,
          'id_pais' => $direccion['id_pais'] !== null ? (int) $direccion['id_pais'] : null,
          'id_provincia' => $direccion['id_provincia'] !== null ? (int) $direccion['id_provincia'] : null,
          'id_localidad' => $direccion['id_localidad'] !== null ? (int) $direccion['id_localidad'] : null,
          'id_via' => $direccion['id_via'] !== null ? (int) $direccion['id_via'] : null,
          'nombre_via' => $direccion['nombre_via'],
          'numero' => $direccion['numero'],
          'bloque' => $direccion['bloque'],
          'escalera' => $direccion['escalera'],
          'planta' => $direccion['planta'],
          'puerta' => $direccion['puerta'],
          'etiqueta' => $direccion['etiqueta'],
          'cp' => $direccion['cp'],
          'principal' => strtolower((string) ($direccion['etiqueta'] ?? '')) === 'principal' ? 1 : 0,
        ]);
      }

      $insert_contacto = $pdo->prepare(
        'INSERT INTO empresas_contactos (id_empresa, apellido1, apellido2, nombre, cargo)
         VALUES (:id_empresa, :apellido1, :apellido2, :nombre, :cargo)'
      );

      foreach ($contactos as $contacto) {
        $insert_contacto->execute([
          'id_empresa' => $id_empresa,
          'apellido1' => $contacto['apellido1'] ?? '',
          'apellido2' => $contacto['apellido2'],
          'nombre' => $contacto['nombre'] ?? '',
          'cargo' => $contacto['cargo'],
        ]);

        $id_contacto = (int) $pdo->lastInsertId();

        foreach ($contacto['telefonos'] as $telefono) {
          $insert_telefono->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $id_contacto,
            'telefono' => $telefono['telefono'],
            'etiqueta' => $telefono['etiqueta'],
          ]);
        }

        foreach ($contacto['correos'] as $correo) {
          $insert_correo->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $id_contacto,
            'direccion_correo' => $correo['correo'],
            'etiqueta' => $correo['etiqueta'],
          ]);
        }
      }

      $insert_tutor = $pdo->prepare(
        'INSERT INTO empresas_tutores (id_empresa, apellido1, apellido2, nombre, dni)
         VALUES (:id_empresa, :apellido1, :apellido2, :nombre, :dni)'
      );

      foreach ($tutores as $tutor) {
        $insert_tutor->execute([
          'id_empresa' => $id_empresa,
          'apellido1' => $tutor['apellido1'] ?? '',
          'apellido2' => $tutor['apellido2'],
          'nombre' => $tutor['nombre'] ?? '',
          'dni' => $tutor['dni'],
        ]);

        $id_tutor = (int) $pdo->lastInsertId();

        foreach ($tutor['telefonos'] as $telefono) {
          $insert_telefono->execute([
            'entidad_tipo' => 'empresa_tutor',
            'id_entidad' => $id_tutor,
            'telefono' => $telefono['telefono'],
            'etiqueta' => $telefono['etiqueta'],
          ]);
        }

        foreach ($tutor['correos'] as $correo) {
          $insert_correo->execute([
            'entidad_tipo' => 'empresa_tutor',
            'id_entidad' => $id_tutor,
            'direccion_correo' => $correo['correo'],
            'etiqueta' => $correo['etiqueta'],
          ]);
        }
      }

      $pdo->commit();
      header('Location: empresa_detalle.php?id_empresa=' . $id_empresa);
      exit;
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errors[] = 'No se pudo guardar la empresa. Revisa los datos e inténtalo de nuevo.';
    }
  }
}

$page_title = 'Nueva empresa | Gestor de Alumnos';
$active_page = 'empresas';
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
          <h1>Nueva empresa</h1>
          <p class="subheading">Alta de empresas con teléfonos, correos, direcciones, personas de contacto y tutores.</p>
        </div>
        <div class="header-actions">
          <a class="ghost-button" href="empresas.php">Volver al listado</a>
        </div>
      </header>

      <?php if ($errors): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Revisa los datos del formulario</h3>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <form method="post" class="panel entity-form empresa-form" id="empresaForm">
        <div class="empresa-form-grid">
          <section class="panel empresa-block empresa-block-main">
            <div class="panel-header">
              <h3>Datos de la empresa</h3>
              <p>Información identificativa y administrativa principal.</p>
            </div>

            <div class="entity-grid empresa-datos-grid">
              <label>
                Nombre *
                <input type="text" name="empresa[nombre]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                CIF
                <input type="text" name="empresa[cif]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['cif'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Apellido 1
                <input type="text" name="empresa[apellido1]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['apellido1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Apellido 2
                <input type="text" name="empresa[apellido2]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['apellido2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Número de convenio
                <input type="text" name="empresa[numero_convenio]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['numero_convenio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
            </div>

            <section class="entity-section empresa-subsection" id="mediosContactoSection">
              <div class="entity-section-header">
                <h3>Medios de contacto</h3>
              </div>
              <div class="grid empresa-medios-grid">
                <section class="entity-nested-section" id="telefonosEmpresaSection">
                  <div class="entity-section-header">
                    <h4>Teléfonos</h4>
                    <button type="button" class="edit-toggle" data-add-item="#telefonosEmpresaList" data-template="#telefonoEmpresaTemplate">Añadir teléfono</button>
                  </div>
                  <div class="entity-stack" id="telefonosEmpresaList"></div>
                </section>

                <section class="entity-nested-section" id="correosEmpresaSection">
                  <div class="entity-section-header">
                    <h4>Correos</h4>
                    <button type="button" class="edit-toggle" data-add-item="#correosEmpresaList" data-template="#correoEmpresaTemplate">Añadir correo</button>
                  </div>
                  <div class="entity-stack" id="correosEmpresaList"></div>
                </section>
              </div>
            </section>
          </section>

          <section class="panel empresa-block empresa-block-direcciones">
            <div class="panel-header">
              <h3>Direcciones</h3>
              <p>Direcciones asociadas a la empresa.</p>
            </div>

            <div class="entity-stack" id="direccionesList"></div>
            <div>
              <button type="button" class="edit-toggle" data-add-item="#direccionesList" data-template="#direccionTemplate">Añadir dirección</button>
            </div>
          </section>

          <section class="panel empresa-block empresa-block-contactos">
            <div class="panel-header">
              <h3>Personas de contacto</h3>
              <p>Personas con las que se gestiona la comunicación habitual.</p>
            </div>
            <div class="entity-section-header">
              <button type="button" class="edit-toggle" id="addContactoButton">Añadir persona de contacto</button>
            </div>
            <div class="entity-stack" id="contactosList"></div>
          </section>

          <section class="panel empresa-block empresa-block-tutores">
            <div class="panel-header">
              <h3>Tutores de la empresa</h3>
              <p>Tutores vinculados para el seguimiento de prácticas.</p>
            </div>
            <div class="entity-section-header">
              <button type="button" class="edit-toggle" id="addTutorButton">Añadir tutor</button>
            </div>
            <div class="entity-stack" id="tutoresList"></div>
          </section>
        </div>

        <template id="telefonoEmpresaTemplate">
          <div class="entity-repeatable-item empresa-inline-item">
            <div class="entity-inline-grid empresa-contact-item-grid">
              <label>
                Número
                <input type="text" name="telefonos_empresa[__INDEX__][telefono]">
              </label>
              <label>
                Etiqueta
                <select name="telefonos_empresa[__INDEX__][etiqueta]">
                  <option value="Trabajo">Trabajo</option>
                  <option value="Personal">Personal</option>
                  <option value="Otro">Otro</option>
                </select>
              </label>
              <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
            </div>
          </div>
        </template>

        <template id="correoEmpresaTemplate">
          <div class="entity-repeatable-item empresa-inline-item">
            <div class="entity-inline-grid empresa-contact-item-grid">
              <label>
                Correo
                <input type="email" name="correos_empresa[__INDEX__][correo]">
              </label>
              <label>
                Etiqueta
                <select name="correos_empresa[__INDEX__][etiqueta]">
                  <option value="Trabajo">Trabajo</option>
                  <option value="Personal">Personal</option>
                  <option value="Otro">Otro</option>
                </select>
              </label>
              <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
            </div>
          </div>
        </template>

        <template id="direccionTemplate">
          <div class="entity-repeatable-item empresa-direccion-item">
            <div class="entity-grid empresa-direccion-grid">
              <label>
                Etiqueta
                <select name="direcciones[__INDEX__][etiqueta]">
                  <option value="Principal">Principal</option>
                  <option value="Centro de Trabajo">Centro de Trabajo</option>
                </select>
              </label>
              <label>
                Tipo de vía
                <select name="direcciones[__INDEX__][id_via]">
                  <option value="">Selecciona</option>
                  <?php foreach ($via_options as $via): ?>
                    <option value="<?php echo (int) $via['id_via']; ?>"><?php echo htmlspecialchars($via['via'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Nombre de la vía
                <input type="text" name="direcciones[__INDEX__][nombre_via]">
              </label>
              <label>
                Número
                <input type="text" name="direcciones[__INDEX__][numero]">
              </label>
              <label>
                Bloque
                <input type="text" name="direcciones[__INDEX__][bloque]">
              </label>
              <label>
                Escalera
                <input type="text" name="direcciones[__INDEX__][escalera]">
              </label>
              <label>
                Planta
                <input type="text" name="direcciones[__INDEX__][planta]">
              </label>
              <label>
                Puerta
                <input type="text" name="direcciones[__INDEX__][puerta]">
              </label>
              <label>
                País
                <select name="direcciones[__INDEX__][id_pais]">
                  <option value="">Selecciona</option>
                  <?php foreach ($pais_options as $pais): ?>
                    <option value="<?php echo (int) $pais['id_pais']; ?>"><?php echo htmlspecialchars($pais['pais'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Código postal
                <input type="text" name="direcciones[__INDEX__][cp]">
              </label>
              <label>
                Provincia
                <select name="direcciones[__INDEX__][id_provincia]">
                  <option value="">Selecciona</option>
                  <?php foreach ($provincia_options as $provincia): ?>
                    <option value="<?php echo (int) $provincia['id_provincia']; ?>"><?php echo htmlspecialchars($provincia['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Localidad
                <select name="direcciones[__INDEX__][id_localidad]">
                  <option value="">Selecciona</option>
                  <?php foreach ($localidad_options as $localidad): ?>
                    <option value="<?php echo (int) $localidad['id_localidad']; ?>"><?php echo htmlspecialchars($localidad['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <button type="button" class="ghost-button" data-remove-item>Eliminar dirección</button>
          </div>
        </template>

        <div class="form-actions">
          <button type="submit" class="primary-button">Guardar</button>
          <a class="ghost-button" href="empresas.php">Cancelar</a>
        </div>
      </form>
    </main>
  </div>

  <script>
    const normalizeText = (value) => (value || '')
      .toString()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();

    const countryNameToCode = {
      espana: 'ES',
      espanya: 'ES',
      spain: 'ES',
      portugal: 'PT',
      francia: 'FR',
      france: 'FR',
      alemania: 'DE',
      germany: 'DE',
      italia: 'IT',
      italy: 'IT',
      'reino unido': 'GB',
      uk: 'GB',
      usa: 'US',
      'estados unidos': 'US',
      mexico: 'MX',
      argentina: 'AR',
      colombia: 'CO',
      chile: 'CL',
      peru: 'PE',
      uruguay: 'UY',
      paraguay: 'PY',
      brasil: 'BR',
      brazil: 'BR',
      andorra: 'AD',
      belgica: 'BE',
      belgium: 'BE',
      suiza: 'CH',
      switzerland: 'CH',
      austria: 'AT',
      holanda: 'NL',
      'paises bajos': 'NL',
      'paises bajos (holanda)': 'NL',
      irlanda: 'IE',
    };

    const resolveCountryCode = (paisSelect) => {
      if (!paisSelect || !paisSelect.value) {
        return '';
      }

      const option = paisSelect.options[paisSelect.selectedIndex];
      const optionText = (option?.textContent || '').trim();

      if (/^[A-Za-z]{2}$/.test(optionText)) {
        return optionText.toUpperCase();
      }

      const normalized = normalizeText(optionText);
      return countryNameToCode[normalized] || '';
    };

    const setSelectByText = (select, text) => {
      if (!select || !text) {
        return false;
      }

      const normalizedTarget = normalizeText(text);
      const option = Array.from(select.options).find((item) => normalizeText(item.textContent) === normalizedTarget);
      if (!option) {
        return false;
      }

      select.value = option.value;
      return true;
    };

    const fetchAddressFromPostalCode = async (direccionItem) => {
      if (!direccionItem) {
        return;
      }

      const paisSelect = direccionItem.querySelector('select[name*="[id_pais]"]');
      const cpInput = direccionItem.querySelector('input[name*="[cp]"]');
      const provinciaSelect = direccionItem.querySelector('select[name*="[id_provincia]"]');
      const localidadSelect = direccionItem.querySelector('select[name*="[id_localidad]"]');

      const countryCode = resolveCountryCode(paisSelect);
      const postalCode = (cpInput?.value || '').trim();

      if (!countryCode || !postalCode || postalCode.length < 2) {
        return;
      }

      direccionItem.dataset.lookupKey = `${countryCode}-${postalCode}`;

      try {
        const response = await fetch(`https://api.zippopotam.us/${encodeURIComponent(countryCode)}/${encodeURIComponent(postalCode)}`);
        if (!response.ok) {
          return;
        }

        const data = await response.json();
        const place = Array.isArray(data.places) && data.places.length > 0 ? data.places[0] : null;
        if (!place) {
          return;
        }

        const currentLookupKey = `${countryCode}-${postalCode}`;
        if (direccionItem.dataset.lookupKey !== currentLookupKey) {
          return;
        }

        setSelectByText(provinciaSelect, place.state || place['state abbreviation'] || '');
        setSelectByText(localidadSelect, place['place name'] || '');
      } catch (_) {
        // Silencio intencional para no mostrar errores técnicos.
      }
    };

    const replaceIndex = (value, index) => value.replaceAll('__INDEX__', String(index));

    const addItemFromTemplate = (listSelector, templateSelector) => {
      const list = document.querySelector(listSelector);
      const template = document.querySelector(templateSelector);
      if (!list || !template) {
        return null;
      }

      const itemIndex = Number(list.dataset.nextIndex || 0);
      const html = replaceIndex(template.innerHTML, itemIndex);
      list.insertAdjacentHTML('beforeend', html);
      list.dataset.nextIndex = String(itemIndex + 1);
      return list.lastElementChild;
    };

    document.addEventListener('click', (event) => {
      const addButton = event.target.closest('[data-add-item]');
      if (addButton) {
        addItemFromTemplate(addButton.dataset.addItem, addButton.dataset.template);
        return;
      }

      const removeButton = event.target.closest('[data-remove-item]');
      if (removeButton) {
        const wrapper = removeButton.closest('.entity-repeatable-item');
        if (wrapper) {
          wrapper.remove();
        }
      }

    });

    document.addEventListener('blur', (event) => {
      const cpInput = event.target.closest('.empresa-direccion-item input[name*="[cp]"]');
      if (cpInput) {
        fetchAddressFromPostalCode(cpInput.closest('.empresa-direccion-item'));
      }
    }, true);

    const createPhoneBlock = (namePrefix) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'entity-repeatable-item';
      wrapper.innerHTML = `
        <div class="entity-inline-grid">
          <label>
            Teléfono
            <input type="text" name="${namePrefix}[telefonos][__INDEX__][telefono]">
          </label>
          <label>
            Etiqueta
            <select name="${namePrefix}[telefonos][__INDEX__][etiqueta]">
              <option value="Trabajo">Trabajo</option>
              <option value="Personal">Personal</option>
              <option value="Otro">Otro</option>
            </select>
          </label>
        </div>
        <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
      `;
      return wrapper;
    };

    const createEmailBlock = (namePrefix) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'entity-repeatable-item';
      wrapper.innerHTML = `
        <div class="entity-inline-grid">
          <label>
            Correo
            <input type="email" name="${namePrefix}[correos][__INDEX__][correo]">
          </label>
          <label>
            Etiqueta
            <select name="${namePrefix}[correos][__INDEX__][etiqueta]">
              <option value="Trabajo">Trabajo</option>
              <option value="Personal">Personal</option>
              <option value="Otro">Otro</option>
            </select>
          </label>
        </div>
        <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
      `;
      return wrapper;
    };

    const attachNestedControls = (entityCard, entityType, entityIndex) => {
      const phoneList = entityCard.querySelector('.nested-phone-list');
      const emailList = entityCard.querySelector('.nested-email-list');
      const addPhone = entityCard.querySelector('.nested-add-phone');
      const addEmail = entityCard.querySelector('.nested-add-email');

      const entityPrefix = `${entityType}[${entityIndex}]`;

      const addNestedBlock = (list, blockFactory) => {
        const blockIndex = Number(list.dataset.nextIndex || 0);
        const block = blockFactory(entityPrefix);
        block.innerHTML = replaceIndex(block.innerHTML, blockIndex);
        list.appendChild(block);
        list.dataset.nextIndex = String(blockIndex + 1);
      };

      addPhone.addEventListener('click', () => addNestedBlock(phoneList, createPhoneBlock));
      addEmail.addEventListener('click', () => addNestedBlock(emailList, createEmailBlock));

      addNestedBlock(phoneList, createPhoneBlock);
      addNestedBlock(emailList, createEmailBlock);
    };

    const createEntityCard = (entityType, entityIndex, extraFieldLabel, extraFieldName) => {
      const card = document.createElement('div');
      card.className = 'entity-repeatable-item entity-card';
      card.innerHTML = `
        <div class="entity-section-header">
          <h4>${entityType === 'contactos' ? 'Persona de contacto' : 'Tutor de empresa'} #${entityIndex + 1}</h4>
          <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
        </div>
        <div class="entity-grid">
          <label>
            Nombre
            <input type="text" name="${entityType}[${entityIndex}][nombre]">
          </label>
          <label>
            Apellido 1
            <input type="text" name="${entityType}[${entityIndex}][apellido1]">
          </label>
          <label>
            Apellido 2
            <input type="text" name="${entityType}[${entityIndex}][apellido2]">
          </label>
          <label>
            ${extraFieldLabel}
            <input type="text" name="${entityType}[${entityIndex}][${extraFieldName}]">
          </label>
        </div>
        <div class="entity-nested-section">
          <div class="entity-section-header">
            <h4>Teléfonos</h4>
            <button type="button" class="edit-toggle nested-add-phone">Añadir teléfono</button>
          </div>
          <div class="entity-stack nested-phone-list"></div>
        </div>
        <div class="entity-nested-section">
          <div class="entity-section-header">
            <h4>Correos</h4>
            <button type="button" class="edit-toggle nested-add-email">Añadir correo</button>
          </div>
          <div class="entity-stack nested-email-list"></div>
        </div>
      `;
      return card;
    };

    const addContacto = () => {
      const list = document.getElementById('contactosList');
      const entityIndex = Number(list.dataset.nextIndex || 0);
      const card = createEntityCard('contactos', entityIndex, 'Cargo', 'cargo');
      list.appendChild(card);
      list.dataset.nextIndex = String(entityIndex + 1);
      attachNestedControls(card, 'contactos', entityIndex);
    };

    const addTutor = () => {
      const list = document.getElementById('tutoresList');
      const entityIndex = Number(list.dataset.nextIndex || 0);
      const card = createEntityCard('tutores', entityIndex, 'DNI', 'dni');
      list.appendChild(card);
      list.dataset.nextIndex = String(entityIndex + 1);
      attachNestedControls(card, 'tutores', entityIndex);
    };

    document.getElementById('addContactoButton').addEventListener('click', addContacto);
    document.getElementById('addTutorButton').addEventListener('click', addTutor);

    addItemFromTemplate('#telefonosEmpresaList', '#telefonoEmpresaTemplate');
    addItemFromTemplate('#correosEmpresaList', '#correoEmpresaTemplate');
    addItemFromTemplate('#direccionesList', '#direccionTemplate');
    addContacto();
    addTutor();
  </script>
</body>
</html>

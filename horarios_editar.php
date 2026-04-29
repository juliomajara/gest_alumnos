<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function json_response(array $payload): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function fetch_schedule_data(PDO $pdo, int $courseId, int $groupId): array {
  $days = [1, 2, 3, 4, 5];

  $tramosStmt = $pdo->query('SELECT id_horario_tramo, numero_tramo, hora_inicio, hora_fin FROM horarios_tramos ORDER BY numero_tramo');
  $tramos = $tramosStmt->fetchAll(PDO::FETCH_ASSOC);

  $modulesStmt = $pdo->prepare(
    'SELECT DISTINCT
      m.id_modulo,
      m.codigo,
      m.abreviatura,
      COALESCE(NULLIF(m.materia_propia, ""), NULLIF(m.materia_general, ""), m.abreviatura, m.codigo, CONCAT("Módulo ", m.id_modulo)) AS nombre,
      COALESCE(m.horas_semanales, 0) AS horas_semanales,
      p.nombre AS profesor_nombre,
      TRIM(CONCAT_WS(" ", p.apellido1, p.apellido2)) AS profesor_apellidos,
      mp.id_profesor
    FROM alumno_curso ac
    INNER JOIN alumno_modulo am ON am.id_alumno = ac.id_alumno
    INNER JOIN modulos m ON m.id_modulo = am.id_modulo
    LEFT JOIN modulos_profesores mp ON mp.id_modulo = m.id_modulo AND mp.id_curso_escolar = ac.id_curso_escolar
    LEFT JOIN profesores p ON p.id_profesor = mp.id_profesor
    WHERE ac.id_curso_escolar = :id_curso_escolar
      AND ac.id_grupo = :id_grupo
    GROUP BY m.id_modulo, m.codigo, m.abreviatura, m.materia_propia, m.materia_general, m.horas_semanales, mp.id_profesor
    ORDER BY m.codigo, nombre'
  );
  $modulesStmt->execute(['id_curso_escolar' => $courseId, 'id_grupo' => $groupId]);
  $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

  $scheduleStmt = $pdo->prepare(
    'SELECT hg.dia_semana, hg.id_horario_tramo, hg.id_modulo, hg.id_profesor,
            COALESCE(NULLIF(m.materia_propia, ""), NULLIF(m.materia_general, ""), m.abreviatura, m.codigo, CONCAT("Módulo ", m.id_modulo)) AS nombre,
            m.codigo, m.abreviatura
     FROM horarios_grupos hg
     INNER JOIN modulos m ON m.id_modulo = hg.id_modulo
     WHERE hg.id_curso_escolar = :id_curso_escolar AND hg.id_grupo = :id_grupo'
  );
  $scheduleStmt->execute(['id_curso_escolar' => $courseId, 'id_grupo' => $groupId]);

  $schedule = [];
  foreach ($scheduleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $key = (int) $row['dia_semana'] . '-' . (int) $row['id_horario_tramo'];
    $schedule[$key] = $row;
  }

  $countsStmt = $pdo->prepare(
    'SELECT id_modulo, COUNT(*) AS usados
     FROM horarios_grupos
     WHERE id_curso_escolar = :id_curso_escolar AND id_grupo = :id_grupo
     GROUP BY id_modulo'
  );
  $countsStmt->execute(['id_curso_escolar' => $courseId, 'id_grupo' => $groupId]);
  $moduleCounts = [];
  foreach ($countsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $moduleCounts[(int) $row['id_modulo']] = (int) $row['usados'];
  }

  return ['days' => $days, 'tramos' => $tramos, 'modules' => $modules, 'schedule' => $schedule, 'module_counts' => $moduleCounts];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = (string) $_POST['action'];

  try {
    if ($action === 'load') {
      $courseId = (int) ($_POST['id_curso_escolar'] ?? 0);
      $groupId = (int) ($_POST['id_grupo'] ?? 0);
      if ($courseId <= 0 || $groupId <= 0) {
        json_response(['ok' => false, 'message' => 'Curso o grupo inválido.']);
      }
      json_response(['ok' => true, 'message' => 'Horario cargado.', 'data' => fetch_schedule_data($pdo, $courseId, $groupId)]);
    }

    if ($action === 'save_cell' || $action === 'clear_cell') {
      $stage = 'start';
      $courseId = (int) ($_POST['id_curso_escolar'] ?? 0);
      $groupId = (int) ($_POST['id_grupo'] ?? 0);
      $day = (int) ($_POST['dia_semana'] ?? 0);
      $tramoId = (int) ($_POST['id_horario_tramo'] ?? 0);
      $moduleId = (int) ($_POST['id_modulo'] ?? 0);
      $profesorId = null;
      if (isset($_POST['id_profesor'])) {
        $rawProfesorId = trim((string) $_POST['id_profesor']);
        if ($rawProfesorId !== '' && ctype_digit($rawProfesorId)) {
          $parsedProfesorId = (int) $rawProfesorId;
          if ($parsedProfesorId > 0) {
            $profesorId = $parsedProfesorId;
          }
        }
      }
      $sourceDay = (int) ($_POST['source_dia_semana'] ?? 0);
      $sourceTramoId = (int) ($_POST['source_id_horario_tramo'] ?? 0);

      if ($courseId <= 0) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta id_curso_escolar válido.', 'post' => $_POST, 'stage' => 'validate_id_curso_escolar']);
      if ($groupId <= 0) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta id_grupo válido.', 'post' => $_POST, 'stage' => 'validate_id_grupo']);
      if ($day < 1 || $day > 5) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta dia_semana válido.', 'post' => $_POST, 'stage' => 'validate_dia_semana']);
      if ($tramoId <= 0) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta id_horario_tramo válido.', 'post' => $_POST, 'stage' => 'validate_id_horario_tramo']);
      if ($action === 'save_cell' && $moduleId <= 0) {
        json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta id_modulo válido.', 'post' => $_POST, 'stage' => 'validate_id_modulo']);
      }

      $stage = 'validate_course_fk';
      $validCheck = $pdo->prepare('SELECT 1 FROM cursos_escolares WHERE id_curso_escolar=:id');
      $validCheck->execute(['id' => $courseId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'id_curso_escolar no existe.', 'post' => $_POST, 'stage' => $stage]);

      $stage = 'validate_group_fk';
      $validCheck = $pdo->prepare('SELECT 1 FROM grupos WHERE id_grupo=:id');
      $validCheck->execute(['id' => $groupId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'id_grupo no existe.', 'post' => $_POST, 'stage' => $stage]);

      $stage = 'validate_tramo_fk';
      $validCheck = $pdo->prepare('SELECT 1 FROM horarios_tramos WHERE id_horario_tramo=:id');
      $validCheck->execute(['id' => $tramoId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'id_horario_tramo no existe.', 'post' => $_POST, 'stage' => $stage]);

      if ($action === 'clear_cell') {
        $stage = 'clear_cell_delete';
        $del = $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t');
        $del->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);
        json_response(['ok' => true, 'message' => 'Celda vaciada.']);
      }

      $stage = 'validate_module_fk';
      $validCheck = $pdo->prepare('SELECT horas_semanales, COALESCE(horas_semanales, 0) AS limite FROM modulos WHERE id_modulo=:id');
      $validCheck->execute(['id' => $moduleId]);
      $moduleRow = $validCheck->fetch(PDO::FETCH_ASSOC);
      if (!$moduleRow) json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'id_modulo no existe.', 'post' => $_POST, 'stage' => $stage]);

      $isMove = $sourceDay >= 1 && $sourceDay <= 5 && $sourceTramoId > 0;

      $stage = 'inspect_columns';
      $columnsStmt = $pdo->query('SHOW COLUMNS FROM horarios_grupos');
      $tableColumns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
      $columnMap = [];
      foreach ($tableColumns as $col) {
        $columnMap[(string) $col['Field']] = $col;
      }
      $requiredMissing = [];
      foreach ($tableColumns as $col) {
        $field = (string) $col['Field'];
        $nullable = strtoupper((string) ($col['Null'] ?? 'YES')) === 'YES';
        $default = $col['Default'] ?? null;
        $extra = strtolower((string) ($col['Extra'] ?? ''));
        if (!$nullable && $default === null && strpos($extra, 'auto_increment') === false && !in_array($field, ['id_curso_escolar', 'id_grupo', 'dia_semana', 'id_horario_tramo', 'id_modulo', 'id_profesor'], true)) {
          $requiredMissing[] = $field;
        }
      }
      if ($requiredMissing !== []) {
        json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Faltan columnas obligatorias en INSERT: ' . implode(', ', $requiredMissing), 'post' => $_POST, 'stage' => $stage]);
      }

      $hasProfesorColumn = isset($columnMap['id_profesor']);
      $allowNullProfesor = true;
      if ($hasProfesorColumn) {
        $column = $columnMap['id_profesor'];
        $allowNullProfesor = isset($column['Null']) && strtoupper((string) $column['Null']) === 'YES';

        if ($profesorId !== null) {
          $profesorExistsStmt = $pdo->prepare('SELECT 1 FROM profesores WHERE id_profesor = :id LIMIT 1');
          $profesorExistsStmt->execute(['id' => $profesorId]);
          $profesorExists = (bool) $profesorExistsStmt->fetchColumn();
          if (!$profesorExists) {
            if ($allowNullProfesor) {
              $profesorId = null;
            } else {
              json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'El id_profesor recibido no existe y la columna id_profesor no permite NULL.', 'post' => $_POST, 'stage' => 'validate_profesor_fk']);
            }
          }
        }

        if ($profesorId === null && !$allowNullProfesor) {
          json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => 'Falta profesor válido y la columna id_profesor no permite NULL.', 'post' => $_POST, 'stage' => 'validate_profesor_required']);
        }
      }

      $stage = 'begin_transaction';
      $pdo->beginTransaction();
      $stage = 'select_current_slot';
      $currentStmt = $pdo->prepare('SELECT id_modulo FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t LIMIT 1');
      $currentStmt->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);
      $currentModule = (int) ($currentStmt->fetchColumn() ?: 0);

      if ($isMove) {
        $stage = 'validate_source_slot';
        $sourceStmt = $pdo->prepare('SELECT id_modulo FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t LIMIT 1');
        $sourceStmt->execute(['c' => $courseId, 'g' => $groupId, 'd' => $sourceDay, 't' => $sourceTramoId]);
        $sourceModule = (int) ($sourceStmt->fetchColumn() ?: 0);
        if ($sourceModule !== $moduleId) {
          $pdo->rollBack();
          json_response(['ok' => false, 'message' => 'Movimiento no válido.']);
        }
      }

      $stage = 'count_module_hours';
      $countStmt = $pdo->prepare('SELECT COUNT(*) FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND id_modulo=:m');
      $countStmt->execute(['c' => $courseId, 'g' => $groupId, 'm' => $moduleId]);
      $used = (int) $countStmt->fetchColumn();
      $limit = (int) ($moduleRow['limite'] ?? 0);

      if ($limit <= 0) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'El módulo no tiene horas semanales asignables.']);
      }

      $addsNewOccurrence = $currentModule !== $moduleId && !$isMove;
      if ($addsNewOccurrence && $used >= $limit) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'El módulo ya alcanzó sus horas semanales.', 'used_hours' => $used, 'limit_hours' => $limit]);
      }

      if ($isMove) {
        $stage = 'delete_source_slot';
        $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t')
          ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $sourceDay, 't' => $sourceTramoId]);
      }

      $stage = 'delete_target_slot';
      $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t')
        ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);

      $stage = 'insert_target_slot';
      if ($hasProfesorColumn) {
        $pdo->prepare('INSERT INTO horarios_grupos (id_curso_escolar,id_grupo,dia_semana,id_horario_tramo,id_modulo,id_profesor) VALUES (:c,:g,:d,:t,:m,:p)')
          ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId, 'm' => $moduleId, 'p' => $profesorId]);
      } else {
        $pdo->prepare('INSERT INTO horarios_grupos (id_curso_escolar,id_grupo,dia_semana,id_horario_tramo,id_modulo) VALUES (:c,:g,:d,:t,:m)')
          ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId, 'm' => $moduleId]);
      }

      $stage = 'commit_transaction';
      $pdo->commit();
      json_response(['ok' => true, 'message' => 'Horario guardado correctamente.']);
    }

    json_response(['ok' => false, 'message' => 'Acción no permitida.']);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response([
      'ok' => false,
      'message' => 'No se pudo guardar el horario.',
      'detail' => $e->getMessage(),
      'stage' => $stage ?? 'unknown',
      'error_type' => get_class($e),
      'error_code' => (string) $e->getCode(),
      'post' => $_POST,
    ]);
  }
}

$courses = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$selectedCourse = isset($_GET['id_curso_escolar']) ? (int) $_GET['id_curso_escolar'] : (int) ($courses[0]['id_curso_escolar'] ?? 0);
$selectedGroup = isset($_GET['id_grupo']) ? (int) $_GET['id_grupo'] : (int) ($groups[0]['id_grupo'] ?? 0);

$page_title = 'Editar horarios | Gestor de Alumnos';
$active_page = 'horarios';
?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/styles.css"></head>
<body><div class="page"><?php require __DIR__ . '/includes/sidebar.php'; ?><main class="content">
<header class="header"><div><h1>Editar horarios de grupos</h1><p class="subheading">Selecciona curso y grupo para configurar la cuadrícula semanal.</p></div></header>
<section class="panel"><div class="panel-header"><h3>Configuración</h3></div><div class="entity-form horarios-configuracion"><label>Curso escolar<select id="id_curso_escolar"><?php foreach ($courses as $c): ?><option value="<?php echo (int)$c['id_curso_escolar']; ?>" <?php echo (int)$c['id_curso_escolar']===$selectedCourse?'selected':''; ?>><?php echo htmlspecialchars((string)$c['curso_escolar'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label><label>Grupo<select id="id_grupo"><?php foreach ($groups as $g): ?><option value="<?php echo (int)$g['id_grupo']; ?>" <?php echo (int)$g['id_grupo']===$selectedGroup?'selected':''; ?>><?php echo htmlspecialchars((string)$g['grupo'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
<div class="horarios-editor"><div class="panel-grid horarios-grid-wrap"><p class="horarios-help">Arrastra los módulos al horario. Para quitar un módulo colocado, haz clic derecho sobre él.</p><table class="horarios-grid" id="horariosGrid"><thead><tr><th>Tramo</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr></thead><tbody></tbody></table></div><section class="panel horarios-modulos"><h4>Módulos disponibles</h4><p class="subheading" id="modulosEmpty" hidden>No hay módulos disponibles para este grupo.</p><div id="modulosList" class="horarios-modulos-list"></div></section></div></section>
</main></div>
<script>
const dayNames={1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie'};
const modulePalette=['#fff100','#e81123','#68217a','#00bcf2','#009e49','#ff8c00','#ec008c','#00188f','#00b294','#bad80a'];
let state={tramos:[],modules:[],schedule:{},counts:{}};
let moduleColorMap={};
const courseSel=document.getElementById('id_curso_escolar'); const groupSel=document.getElementById('id_grupo');

function formatApiError(res, fallback){
  if(!res) return fallback;
  const parts=[];
  if(res.message) parts.push(res.message);
  if(res.detail) parts.push(`Detalle: ${res.detail}`);
  if(res.stage) parts.push(`Paso: ${res.stage}`);
  if(res.error_type||res.error_code) parts.push(`Error técnico: ${(res.error_type||'desconocido')} (${res.error_code||'sin código'})`);
  if(Array.isArray(res.missing_fields)&&res.missing_fields.length) parts.push(`Campos faltantes: ${res.missing_fields.join(', ')}`);
  return parts.join('\n');
}
function toTitleCase(value){
  const text=String(value||'');
  return text.split(/(\s+)/).map((token)=>{
    if(!token.trim()) return token;
    const hasLetters=/\p{L}/u.test(token);
    if(hasLetters&&token===token.toLocaleUpperCase('es-ES')) return token;
    const lower=token.toLocaleLowerCase('es-ES');
    return lower.replace(/^\p{L}/u,(char)=>char.toLocaleUpperCase('es-ES'));
  }).join('');
}
async function post(action,payload={}){const fd=new FormData(); fd.append('action',action); Object.entries(payload).forEach(([k,v])=>fd.append(k,v)); const r=await fetch('horarios_editar.php',{method:'POST',body:fd}); let data; try{data=await r.json();}catch(_e){const text=await r.text(); console.error('Respuesta no JSON:',text); alert(text); throw new Error('Respuesta no JSON');} if(!r.ok){console.error('HTTP error:',data); alert(JSON.stringify(data,null,2));} return data;}
function render(){const list=document.getElementById('modulosList'); const empty=document.getElementById('modulosEmpty');
moduleColorMap={}; let paletteIndex=0;
state.modules.forEach(m=>{const limit=parseInt(m.horas_semanales,10); if(Number.isInteger(limit)&&limit>0){moduleColorMap[m.id_modulo]=`module-color-${(paletteIndex%modulePalette.length)+1}`; paletteIndex++;}});

const tb=document.querySelector('#horariosGrid tbody'); tb.innerHTML='';
state.tramos.forEach(t=>{const tr=document.createElement('tr'); const label=(t.hora_inicio&&t.hora_fin)?`${t.numero_tramo}ª (${t.hora_inicio.slice(0,5)}-${t.hora_fin.slice(0,5)})`:`${t.numero_tramo}ª`; tr.innerHTML=`<td>${label}</td>`;
for(let d=1;d<=5;d++){const td=document.createElement('td'); td.className='horario-dropzone'; td.dataset.day=d; td.dataset.tramo=t.id_horario_tramo; td.addEventListener('dragover',e=>e.preventDefault()); td.addEventListener('drop',onDrop);
const key=`${d}-${t.id_horario_tramo}`; if(state.schedule[key]) td.appendChild(cellModule(state.schedule[key],d,t.id_horario_tramo,true)); tr.appendChild(td);} tb.appendChild(tr);});
list.innerHTML=''; empty.hidden=state.modules.length>0;
state.modules.forEach(m=>{const used=state.counts[m.id_modulo]||0; const rawLimit=parseInt(m.horas_semanales,10); const limit=Number.isInteger(rawLimit)?rawLimit:0; const noHours=limit<=0; const completed=!noHours&&used>=limit; const disabled=noHours||completed; const item=document.createElement('div'); const colorClass=getModuleColorClass(m.id_modulo); const moduleName=toTitleCase(m.nombre); item.className='horarios-modulo-item '+colorClass+(noHours?' is-zero-hours':'')+(completed?' is-completed':'')+(disabled?' is-disabled':''); item.draggable=!disabled; item.dataset.module=m.id_modulo; item.dataset.origin='list'; item.dataset.profesor=m.id_profesor||''; const profesorNombre=[m.profesor_nombre,m.profesor_apellidos].filter(Boolean).join(' ').trim(); const codigoInfo=[m.codigo,m.abreviatura].filter(Boolean).join(' · '); item.title=profesorNombre?`${moduleName} — Profesor: ${profesorNombre}${codigoInfo?` — ${codigoInfo}`:''}`:`${moduleName}${codigoInfo?` — ${codigoInfo}`:''}`; item.innerHTML=`<strong>${moduleName}</strong><small>${used}/${limit>0?limit:0} horas</small>`; if(!disabled)item.addEventListener('dragstart',onDragStart); list.appendChild(item);});}
function cellModule(m,day,tramo,fromGrid){const d=document.createElement('div'); d.className='horario-cell-module '+getModuleColorClass(m.id_modulo); d.draggable=true; d.dataset.module=m.id_modulo; d.dataset.origin='grid'; d.dataset.day=day; d.dataset.tramo=tramo; if(m.id_profesor!==undefined)d.dataset.profesor=m.id_profesor||''; const moduleName=toTitleCase(m.nombre); d.title=m.codigo||m.abreviatura?`${moduleName} — ${[m.codigo,m.abreviatura].filter(Boolean).join(' · ')}`:moduleName; d.innerHTML=`<span>${moduleName}</span>`; d.addEventListener('dragstart',onDragStart); d.addEventListener('contextmenu',e=>{e.preventDefault(); clearCell(day,tramo);}); return d;}
function getModuleColorClass(moduleId){return moduleColorMap[moduleId]||'module-color-disabled';}
function onDragStart(e){if(e.currentTarget.draggable===false||e.currentTarget.classList.contains('is-disabled')){e.preventDefault(); return;} e.dataTransfer.setData('text/plain',JSON.stringify(e.currentTarget.dataset));}
async function onDrop(e){e.preventDefault(); const data=JSON.parse(e.dataTransfer.getData('text/plain')||'{}'); const day=e.currentTarget.dataset.day; const tramo=e.currentTarget.dataset.tramo; if(!data.module) return; if(data.origin==='list'){const moduleData=state.modules.find(m=>String(m.id_modulo)===String(data.module)); const rawLimit=moduleData?parseInt(moduleData.horas_semanales,10):0; const limit=Number.isInteger(rawLimit)?rawLimit:0; const used=state.counts[data.module]||0; if(limit<=0||used>=limit) return;}
try{const res=await post('save_cell',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value,dia_semana:day,id_horario_tramo:tramo,id_modulo:data.module,id_profesor:data.profesor||'',source_dia_semana:data.day||'',source_id_horario_tramo:data.tramo||''});
if(!res.ok){console.error('Error guardando horario:',res); alert(formatApiError(res,'No se pudo guardar el horario.')); return;} await loadData();}catch(err){console.error('Fallo de fetch/save_cell:',err); alert(String(err));}}
async function clearCell(day,tramo){const res=await post('clear_cell',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value,dia_semana:day,id_horario_tramo:tramo}); if(!res.ok){alert(formatApiError(res,'No se pudo borrar la celda.'));return;} await loadData();}
async function loadData(){const res=await post('load',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value}); if(!res.ok){alert(formatApiError(res,'Error de carga de horario.'));return;} state={tramos:res.data.tramos,modules:res.data.modules,schedule:res.data.schedule,counts:res.data.module_counts}; render();}
courseSel.addEventListener('change',loadData); groupSel.addEventListener('change',loadData); loadData();
</script></body></html>

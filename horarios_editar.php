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
      p.apellidos AS profesor_apellidos
    FROM alumno_curso ac
    INNER JOIN alumno_modulo am ON am.id_alumno = ac.id_alumno
    INNER JOIN modulos m ON m.id_modulo = am.id_modulo
    LEFT JOIN modulos_profesores mp ON mp.id_modulo = m.id_modulo AND mp.id_curso_escolar = ac.id_curso_escolar
    LEFT JOIN profesores p ON p.id_profesor = mp.id_profesor
    WHERE ac.id_curso_escolar = :id_curso_escolar
      AND ac.id_grupo = :id_grupo
    GROUP BY m.id_modulo, m.codigo, m.abreviatura, m.materia_propia, m.materia_general, m.horas_semanales
    ORDER BY nombre'
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
      $courseId = (int) ($_POST['id_curso_escolar'] ?? 0);
      $groupId = (int) ($_POST['id_grupo'] ?? 0);
      $day = (int) ($_POST['dia_semana'] ?? 0);
      $tramoId = (int) ($_POST['id_horario_tramo'] ?? 0);
      $moduleId = (int) ($_POST['id_modulo'] ?? 0);
      $sourceDay = (int) ($_POST['source_dia_semana'] ?? 0);
      $sourceTramoId = (int) ($_POST['source_id_horario_tramo'] ?? 0);

      if ($courseId <= 0 || $groupId <= 0 || $day < 1 || $day > 5 || $tramoId <= 0) {
        json_response(['ok' => false, 'message' => 'Datos inválidos.']);
      }

      $validCheck = $pdo->prepare('SELECT 1 FROM cursos_escolares WHERE id_curso_escolar=:id');
      $validCheck->execute(['id' => $courseId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'Curso inválido.']);

      $validCheck = $pdo->prepare('SELECT 1 FROM grupos WHERE id_grupo=:id');
      $validCheck->execute(['id' => $groupId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'Grupo inválido.']);

      $validCheck = $pdo->prepare('SELECT 1 FROM horarios_tramos WHERE id_horario_tramo=:id');
      $validCheck->execute(['id' => $tramoId]);
      if (!$validCheck->fetchColumn()) json_response(['ok' => false, 'message' => 'Tramo inválido.']);

      if ($action === 'clear_cell') {
        $del = $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t');
        $del->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);
        json_response(['ok' => true, 'message' => 'Celda vaciada.']);
      }

      $validCheck = $pdo->prepare('SELECT horas_semanales, COALESCE(horas_semanales, 0) AS limite FROM modulos WHERE id_modulo=:id');
      $validCheck->execute(['id' => $moduleId]);
      $moduleRow = $validCheck->fetch(PDO::FETCH_ASSOC);
      if (!$moduleRow) json_response(['ok' => false, 'message' => 'Módulo inválido.']);

      $isMove = $sourceDay >= 1 && $sourceDay <= 5 && $sourceTramoId > 0;

      $pdo->beginTransaction();
      $currentStmt = $pdo->prepare('SELECT id_modulo FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t LIMIT 1');
      $currentStmt->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);
      $currentModule = (int) ($currentStmt->fetchColumn() ?: 0);

      if ($isMove) {
        $sourceStmt = $pdo->prepare('SELECT id_modulo FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t LIMIT 1');
        $sourceStmt->execute(['c' => $courseId, 'g' => $groupId, 'd' => $sourceDay, 't' => $sourceTramoId]);
        $sourceModule = (int) ($sourceStmt->fetchColumn() ?: 0);
        if ($sourceModule !== $moduleId) {
          $pdo->rollBack();
          json_response(['ok' => false, 'message' => 'Movimiento no válido.']);
        }
      }

      $countStmt = $pdo->prepare('SELECT COUNT(*) FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND id_modulo=:m');
      $countStmt->execute(['c' => $courseId, 'g' => $groupId, 'm' => $moduleId]);
      $used = (int) $countStmt->fetchColumn();
      $limit = (int) ($moduleRow['limite'] ?? 0);

      $addsNewOccurrence = $currentModule !== $moduleId && !$isMove;
      if ($limit > 0 && $addsNewOccurrence && $used >= $limit) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'El módulo ya alcanzó sus horas semanales.', 'used_hours' => $used, 'limit_hours' => $limit]);
      }

      if ($isMove) {
        $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t')
          ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $sourceDay, 't' => $sourceTramoId]);
      }

      $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar=:c AND id_grupo=:g AND dia_semana=:d AND id_horario_tramo=:t')
        ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId]);

      $pdo->prepare('INSERT INTO horarios_grupos (id_curso_escolar,id_grupo,dia_semana,id_horario_tramo,id_modulo) VALUES (:c,:g,:d,:t,:m)')
        ->execute(['c' => $courseId, 'g' => $groupId, 'd' => $day, 't' => $tramoId, 'm' => $moduleId]);

      $pdo->commit();
      json_response(['ok' => true, 'message' => 'Horario actualizado.']);
    }

    json_response(['ok' => false, 'message' => 'Acción no permitida.']);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['ok' => false, 'message' => 'No se pudo guardar el horario.', 'detail' => $e->getMessage()]);
  }
}

$courses = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$selectedCourse = isset($_GET['id_curso_escolar']) ? (int) $_GET['id_curso_escolar'] : (int) ($courses[0]['id_curso_escolar'] ?? 0);
$selectedGroup = isset($_GET['id_grupo']) ? (int) $_GET['id_grupo'] : (int) ($groups[0]['id_grupo'] ?? 0);

$page_title = 'Editar horarios | Gestor de Alumnos';
$active_page = 'calendario';
?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/styles.css"></head>
<body><div class="page"><?php require __DIR__ . '/includes/sidebar.php'; ?><main class="content">
<header class="header"><div><h1>Editar horarios de grupos</h1><p class="subheading">Selecciona curso y grupo para configurar la cuadrícula semanal.</p></div></header>
<section class="panel"><div class="panel-header"><h3>Configuración</h3></div><div class="entity-form horarios-configuracion"><label>Curso escolar<select id="id_curso_escolar"><?php foreach ($courses as $c): ?><option value="<?php echo (int)$c['id_curso_escolar']; ?>" <?php echo (int)$c['id_curso_escolar']===$selectedCourse?'selected':''; ?>><?php echo htmlspecialchars((string)$c['curso_escolar'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label><label>Grupo<select id="id_grupo"><?php foreach ($groups as $g): ?><option value="<?php echo (int)$g['id_grupo']; ?>" <?php echo (int)$g['id_grupo']===$selectedGroup?'selected':''; ?>><?php echo htmlspecialchars((string)$g['grupo'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
<div class="horarios-editor"><div class="panel-grid"><table class="horarios-grid" id="horariosGrid"><thead><tr><th>Tramo</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr></thead><tbody></tbody></table></div><aside class="panel horarios-modulos"><h4>Módulos disponibles</h4><p class="subheading" id="modulosEmpty" hidden>No hay módulos disponibles para este grupo.</p><div id="modulosList" class="horarios-modulos-list"></div></aside></div></section>
</main></div>
<script>
const dayNames={1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie'};
let state={tramos:[],modules:[],schedule:{},counts:{}};
const courseSel=document.getElementById('id_curso_escolar'); const groupSel=document.getElementById('id_grupo');
async function post(action,payload={}){const fd=new FormData(); fd.append('action',action); Object.entries(payload).forEach(([k,v])=>fd.append(k,v)); const r=await fetch('horarios_editar.php',{method:'POST',body:fd}); return r.json();}
function render(){const tb=document.querySelector('#horariosGrid tbody'); tb.innerHTML='';
state.tramos.forEach(t=>{const tr=document.createElement('tr'); const label=(t.hora_inicio&&t.hora_fin)?`${t.numero_tramo}ª (${t.hora_inicio.slice(0,5)}-${t.hora_fin.slice(0,5)})`:`${t.numero_tramo}ª`; tr.innerHTML=`<td>${label}</td>`;
for(let d=1;d<=5;d++){const td=document.createElement('td'); td.className='horario-dropzone'; td.dataset.day=d; td.dataset.tramo=t.id_horario_tramo; td.addEventListener('dragover',e=>e.preventDefault()); td.addEventListener('drop',onDrop);
const key=`${d}-${t.id_horario_tramo}`; if(state.schedule[key]) td.appendChild(cellModule(state.schedule[key],d,t.id_horario_tramo,true)); tr.appendChild(td);} tb.appendChild(tr);});
const list=document.getElementById('modulosList'); list.innerHTML=''; const empty=document.getElementById('modulosEmpty'); empty.hidden=state.modules.length>0;
state.modules.forEach(m=>{const used=state.counts[m.id_modulo]||0; const limit=parseInt(m.horas_semanales||0,10)||0; const disabled=limit>0&&used>=limit; const item=document.createElement('div'); item.className='horarios-modulo-item'+(disabled?' is-disabled':''); item.draggable=!disabled; item.dataset.module=m.id_modulo; item.dataset.origin='list'; const profesorNombre=[m.profesor_nombre,m.profesor_apellidos].filter(Boolean).join(' ').trim(); item.title=profesorNombre?`${m.nombre} — Profesor: ${profesorNombre}`:m.nombre; item.innerHTML=`<strong>${m.abreviatura||m.nombre}${m.codigo?` (${m.codigo})`:''}</strong><small>${used}/${limit>0?limit:'-' } horas</small>`; if(!disabled)item.addEventListener('dragstart',onDragStart); list.appendChild(item);});}
function cellModule(m,day,tramo,fromGrid){const d=document.createElement('div'); d.className='horario-cell-module'; d.draggable=true; d.dataset.module=m.id_modulo; d.dataset.origin='grid'; d.dataset.day=day; d.dataset.tramo=tramo; d.innerHTML=`<span>${m.abreviatura||m.nombre}</span><button type='button' aria-label='Quitar'>×</button>`; d.addEventListener('dragstart',onDragStart); d.querySelector('button').addEventListener('click',()=>clearCell(day,tramo)); return d;}
function onDragStart(e){e.dataTransfer.setData('text/plain',JSON.stringify(e.currentTarget.dataset));}
async function onDrop(e){e.preventDefault(); const data=JSON.parse(e.dataTransfer.getData('text/plain')||'{}'); const day=e.currentTarget.dataset.day; const tramo=e.currentTarget.dataset.tramo; if(!data.module) return;
const res=await post('save_cell',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value,dia_semana:day,id_horario_tramo:tramo,id_modulo:data.module,source_dia_semana:data.day||'',source_id_horario_tramo:data.tramo||''});
if(!res.ok){alert(res.detail?`${res.message}\n${res.detail}`:(res.message||'No se pudo guardar'));return;} await loadData();}
async function clearCell(day,tramo){const res=await post('clear_cell',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value,dia_semana:day,id_horario_tramo:tramo}); if(!res.ok){alert(res.message||'No se pudo borrar');return;} await loadData();}
async function loadData(){const res=await post('load',{id_curso_escolar:courseSel.value,id_grupo:groupSel.value}); if(!res.ok){alert(res.message||'Error de carga');return;} state={tramos:res.data.tramos,modules:res.data.modules,schedule:res.data.schedule,counts:res.data.module_counts}; render();}
courseSel.addEventListener('change',loadData); groupSel.addEventListener('change',loadData); loadData();
</script></body></html>

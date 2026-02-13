<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$sql = <<<'SQL'
SELECT
  a.id_alumno,
  a.nia,
  a.dni,
  a.seg_soc,
  a.apellido1,
  a.apellido2,
  a.nombre,
  a.fecha_nacimiento,
  a.horas_ffe_aprobadas,
  a.id_provincia,
  prov.nombre AS provincia,
  a.id_localidad,
  loc.nombre AS localidad,
  a.cp,
  a.repite_curso,
  a.nombre_tutor1,
  a.telefono_tutor1,
  a.correo_tutor1,
  a.nombre_tutor2,
  a.telefono_tutor2,
  a.correo_tutor2,
  a.faltas_10_dia,
  a.faltas_10_cantidad,
  a.faltas_15_dia,
  a.faltas_15_cantidad,
  a.comentarios,

  ac.id_curso_escolar,
  ce.curso_escolar,
  ce.activo AS curso_escolar_activo,
  ac.id_nivel AS ac_id_nivel,
  n_ac.nivel AS ac_nivel,
  ac.id_ciclo AS ac_id_ciclo,
  c_ac.ciclo AS ac_ciclo,
  c_ac.abreviatura AS ac_ciclo_abreviatura,
  ac.id_curso AS ac_id_curso,
  cu_ac.curso AS ac_curso,
  ac.id_grupo,
  g.grupo,
  g.id_nivel AS grupo_id_nivel,
  n_g.nivel AS grupo_nivel,
  g.id_ciclo AS grupo_id_ciclo,
  c_g.ciclo AS grupo_ciclo,
  c_g.abreviatura AS grupo_ciclo_abreviatura,
  g.id_curso AS grupo_id_curso,
  cu_g.curso AS grupo_curso,

  am.id_modulo,
  m.id_nivel AS modulo_id_nivel,
  n_m.nivel AS modulo_nivel,
  m.id_ciclo AS modulo_id_ciclo,
  c_m.ciclo AS modulo_ciclo,
  c_m.abreviatura AS modulo_ciclo_abreviatura,
  m.id_curso AS modulo_id_curso,
  cu_m.curso AS modulo_curso,
  m.codigo AS modulo_codigo,
  m.abreviatura AS modulo_abreviatura,
  m.tipo AS modulo_tipo,
  m.materia_general,
  m.materia_propia,
  m.horas_semanales,
  m.horas_totales,

  mp.id_modulo_profesor,
  mp.id_curso_escolar AS mp_id_curso_escolar,
  p.id_profesor,
  p.apellido1 AS profesor_apellido1,
  p.apellido2 AS profesor_apellido2,
  p.nombre AS profesor_nombre,
  p.dni AS profesor_dni,

  t.id_telefono,
  t.telefono,
  t.etiqueta AS telefono_etiqueta,

  co.id_correo,
  co.direccion_correo,
  co.etiqueta AS correo_etiqueta
FROM alumnos a
LEFT JOIN provincias prov
  ON prov.id_provincia = a.id_provincia
LEFT JOIN localidades loc
  ON loc.id_localidad = a.id_localidad
LEFT JOIN alumno_curso ac
  ON ac.id_alumno = a.id_alumno
LEFT JOIN cursos_escolares ce
  ON ce.id_curso_escolar = ac.id_curso_escolar
LEFT JOIN niveles n_ac
  ON n_ac.id_nivel = ac.id_nivel
LEFT JOIN ciclos c_ac
  ON c_ac.id_ciclo = ac.id_ciclo
LEFT JOIN cursos cu_ac
  ON cu_ac.id_curso = ac.id_curso
LEFT JOIN grupos g
  ON g.id_grupo = ac.id_grupo
LEFT JOIN niveles n_g
  ON n_g.id_nivel = g.id_nivel
LEFT JOIN ciclos c_g
  ON c_g.id_ciclo = g.id_ciclo
LEFT JOIN cursos cu_g
  ON cu_g.id_curso = g.id_curso
LEFT JOIN alumno_modulo am
  ON am.id_alumno = a.id_alumno
LEFT JOIN modulos m
  ON m.id_modulo = am.id_modulo
LEFT JOIN niveles n_m
  ON n_m.id_nivel = m.id_nivel
LEFT JOIN ciclos c_m
  ON c_m.id_ciclo = m.id_ciclo
LEFT JOIN cursos cu_m
  ON cu_m.id_curso = m.id_curso
LEFT JOIN modulos_profesores mp
  ON mp.id_modulo = m.id_modulo
  AND mp.id_curso_escolar = ac.id_curso_escolar
LEFT JOIN profesores p
  ON p.id_profesor = mp.id_profesor
LEFT JOIN telefonos t
  ON t.entidad_tipo = 'alumno'
  AND t.id_entidad = a.id_alumno
LEFT JOIN correos co
  ON co.entidad_tipo = 'alumno'
  AND co.id_entidad = a.id_alumno
ORDER BY
  a.id_alumno,
  ac.id_curso_escolar,
  am.id_modulo,
  mp.id_modulo_profesor,
  t.id_telefono,
  co.id_correo
SQL;

$stmt = $pdo->query($sql);

$filename = sprintf('alumnos_exportacion_%s.csv', date('Ymd_His'));
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'wb');
if ($output === false) {
  http_response_code(500);
  exit('No se pudo generar la exportación.');
}

$headerWritten = false;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  if (!$headerWritten) {
    fputcsv($output, array_keys($row));
    $headerWritten = true;
  }

  fputcsv($output, $row);
}

if (!$headerWritten) {
  fputcsv($output, [
    'id_alumno',
    'nia',
    'dni',
    'seg_soc',
    'apellido1',
    'apellido2',
    'nombre',
    'fecha_nacimiento',
    'horas_ffe_aprobadas',
    'id_provincia',
    'provincia',
    'id_localidad',
    'localidad',
    'cp',
    'repite_curso',
    'nombre_tutor1',
    'telefono_tutor1',
    'correo_tutor1',
    'nombre_tutor2',
    'telefono_tutor2',
    'correo_tutor2',
    'faltas_10_dia',
    'faltas_10_cantidad',
    'faltas_15_dia',
    'faltas_15_cantidad',
    'comentarios',
    'id_curso_escolar',
    'curso_escolar',
    'curso_escolar_activo',
    'ac_id_nivel',
    'ac_nivel',
    'ac_id_ciclo',
    'ac_ciclo',
    'ac_ciclo_abreviatura',
    'ac_id_curso',
    'ac_curso',
    'id_grupo',
    'grupo',
    'grupo_id_nivel',
    'grupo_nivel',
    'grupo_id_ciclo',
    'grupo_ciclo',
    'grupo_ciclo_abreviatura',
    'grupo_id_curso',
    'grupo_curso',
    'id_modulo',
    'modulo_id_nivel',
    'modulo_nivel',
    'modulo_id_ciclo',
    'modulo_ciclo',
    'modulo_ciclo_abreviatura',
    'modulo_id_curso',
    'modulo_curso',
    'modulo_codigo',
    'modulo_abreviatura',
    'modulo_tipo',
    'materia_general',
    'materia_propia',
    'horas_semanales',
    'horas_totales',
    'id_modulo_profesor',
    'mp_id_curso_escolar',
    'id_profesor',
    'profesor_apellido1',
    'profesor_apellido2',
    'profesor_nombre',
    'profesor_dni',
    'id_telefono',
    'telefono',
    'telefono_etiqueta',
    'id_correo',
    'direccion_correo',
    'correo_etiqueta',
  ]);
}

fclose($output);

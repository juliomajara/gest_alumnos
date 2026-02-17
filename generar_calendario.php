<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

use Mpdf\Mpdf;

const CALENDAR_COLOR_HEADER = '#d9d9d9';
const CALENDAR_COLOR_ATTENDANCE = '#cce5ff';
const CALENDAR_COLOR_START = '#4a90e2';
const CALENDAR_COLOR_END = '#ccffcc';
const CALENDAR_COLOR_NO_ATTENDANCE = '#ffcccc';

function format_value($value, string $fallback = 'No disponible'): string { return ($value === null || $value === '') ? $fallback : (string) $value; }
function format_date(?string $value, string $fallback = 'No disponible'): string { if ($value===null||$value==='') return $fallback; $d=DateTime::createFromFormat('Y-m-d',$value); return ($d&&$d->format('Y-m-d')===$value)?$d->format('d/m/Y'):$value; }
function format_time(?string $value, string $fallback = '—'): string { if ($value===null||$value==='') return $fallback; $t=DateTime::createFromFormat('H:i:s',$value); return ($t&&$t->format('H:i:s')===$value)?$t->format('H:i'):$value; }
function full_name(array $row, string $prefix): string { $parts=array_filter([trim((string)($row[$prefix.'_apellido1']??'')),trim((string)($row[$prefix.'_apellido2']??''))],fn($v)=>$v!==''); $name=trim((string)($row[$prefix.'_nombre']??'')); return ($parts===[]&&$name==='')?'No disponible':trim(implode(' ',$parts).', '.$name,' ,'); }
function build_address(array $practice): string { $parts=array_filter([trim((string)($practice['direccion_via_tipo']??'')),trim((string)($practice['direccion_nombre_via']??'')),trim((string)($practice['direccion_numero']??'')),($practice['direccion_bloque']??'')!==''?'Bloque '.$practice['direccion_bloque']:'',($practice['direccion_escalera']??'')!==''?'Esc. '.$practice['direccion_escalera']:'',($practice['direccion_planta']??'')!==''?'Planta '.$practice['direccion_planta']:'',($practice['direccion_puerta']??'')!==''?'Puerta '.$practice['direccion_puerta']:'',trim((string)($practice['direccion_otros']??''))],fn($v)=>$v!==''); $cp=trim(implode(' ',array_filter([trim((string)($practice['direccion_cp']??'')),trim((string)($practice['direccion_localidad']??''))],fn($v)=>$v!==''))); if($cp!=='')$parts[]=$cp; foreach(['direccion_provincia','direccion_pais'] as $k){$x=trim((string)($practice[$k]??'')); if($x!=='')$parts[]=$x;} return $parts?implode(', ',$parts):'No disponible'; }
function ensure_mpdf_temp_dir(): string { $tmp=__DIR__.'/docs/.mpdf_tmp'; if(!is_dir($tmp)&&!mkdir($tmp,0755,true)&&!is_dir($tmp)) throw new RuntimeException('No se pudo crear el directorio temporal para PDFs.'); if(!is_writable($tmp)) throw new RuntimeException('El directorio temporal para PDFs no tiene permisos de escritura.'); return $tmp; }
function month_name_es(int $m): string { return [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'][$m]??''; }
function time_to_seconds(?string $v): ?int { if($v===null||$v==='') return null; $p=explode(':',$v); if(count($p)<2)return null; return ((int)$p[0])*3600+((int)$p[1])*60+((int)($p[2]??0)); }
function build_weekly_schedule_pdf_table(array $scheduleByDay, array $diasSemana): string { $html='<table width="100%" border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; font-size: 10pt; margin-bottom: 14px;"><thead><tr><th rowspan="2" style="background-color:#d9d9d9; text-align:left;">Día</th><th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario Mañana</th><th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario tarde</th><th rowspan="2" style="background-color:#d9d9d9; text-align:center;">Total</th></tr><tr><th style="background-color:#d9d9d9; text-align:center;">Inicio</th><th style="background-color:#d9d9d9; text-align:center;">Fin</th><th style="background-color:#d9d9d9; text-align:center;">Inicio</th><th style="background-color:#d9d9d9; text-align:center;">Fin</th></tr></thead><tbody>'; foreach($diasSemana as $d=>$name){$se=$scheduleByDay[$d]??[];$m=$se[0]??null;$a=$se[1]??null;$ms=$m?format_time($m['hora_entrada']??null,'--:--'):'--:--';$me=$m?format_time($m['hora_salida']??null,'--:--'):'--:--';$as=$a?format_time($a['hora_entrada']??null,'--:--'):'--:--';$ae=$a?format_time($a['hora_salida']??null,'--:--'):'--:--';$tot=0;foreach($se as $sg){$e=time_to_seconds((string)($sg['hora_entrada']??''));$s=time_to_seconds((string)($sg['hora_salida']??''));if($e!==null&&$s!==null&&$s>$e)$tot+=($s-$e);} $label='--'; if($tot>0){$h=intdiv($tot,3600);$mi=intdiv($tot%3600,60);$label=$mi>0?sprintf('%d:%02d horas',$h,$mi):$h.' horas';}$html.='<tr><td>'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'</td><td style="text-align:center;">'.htmlspecialchars($ms,ENT_QUOTES,'UTF-8').'</td><td style="text-align:center;">'.htmlspecialchars($me,ENT_QUOTES,'UTF-8').'</td><td style="text-align:center;">'.htmlspecialchars($as,ENT_QUOTES,'UTF-8').'</td><td style="text-align:center;">'.htmlspecialchars($ae,ENT_QUOTES,'UTF-8').'</td><td style="text-align:center;">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</td></tr>'; } return $html.'</tbody></table>'; }
function is_no_attendance_day(DateTimeImmutable $d, array $nonSchoolDays, array $scheduleByDay): bool { $k=$d->format('Y-m-d'); if(isset($nonSchoolDays[$k])) return true; return !isset($scheduleByDay[(int)$d->format('N')]) || $scheduleByDay[(int)$d->format('N')]===[]; }
function build_calendar_html(DateTimeImmutable $startDate, DateTimeImmutable $endDate, array $nonSchoolDays, array $scheduleByDay): string { $start=new DateTimeImmutable($startDate->format('Y-m-01')); $endMonth=(new DateTimeImmutable($endDate->format('Y-m-01')))->modify('first day of next month'); $months=[]; for($c=$start;$c<$endMonth;$c=$c->modify('+1 month')){ $y=(int)$c->format('Y');$m=(int)$c->format('n');$dim=(int)$c->format('t');$fd=(int)$c->format('N'); $h='<table width="100%" border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse; margin-bottom:10px; font-size:9pt;"><thead><tr><th colspan="7" style="background-color:'.CALENDAR_COLOR_HEADER.'; text-align:center; font-size:11pt;">'.htmlspecialchars(month_name_es($m).' '.$y,ENT_QUOTES,'UTF-8').'</th></tr><tr><th>L</th><th>M</th><th>X</th><th>J</th><th>V</th><th>S</th><th>D</th></tr></thead><tbody><tr>'; $col=1; while($col<$fd){$h.='<td style="border:1px solid #333333; height:28px;">&nbsp;</td>';$col++;} for($day=1;$day<=$dim;$day++,$col++){ $cur=(new DateTimeImmutable(sprintf('%04d-%02d-%02d',$y,$m,$day)))->setTime(0,0,0);$style='border:1px solid #333333; text-align:center; height:28px;';$k=(int)$cur->format('Ymd');$sk=(int)$startDate->format('Ymd');$ek=(int)$endDate->format('Ymd'); if($k===$sk){$style.=' background-color:'.CALENDAR_COLOR_START.'; color:#ffffff; font-weight:bold;';} elseif($k===$ek){$style.=' background-color:'.CALENDAR_COLOR_END.'; font-weight:bold;';} elseif($k<$sk||$k>$ek){} elseif(is_no_attendance_day($cur,$nonSchoolDays,$scheduleByDay)){$style.=' background-color:'.CALENDAR_COLOR_NO_ATTENDANCE.';';} else {$style.=' background-color:'.CALENDAR_COLOR_ATTENDANCE.';';} $h.='<td style="'.$style.'">'.$day.'</td>'; if($col%7===0&&$day<$dim)$h.='</tr><tr>'; } while($col%7!==1){$h.='<td style="border:1px solid #333333; height:28px;">&nbsp;</td>';$col++;} $months[]=$h.'</tr></tbody></table>'; } $html='<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'; for($i=0;$i<count($months);$i+=2){$html.='<tr><td width="50%" style="vertical-align:top; padding-right:8px;">'.$months[$i].'</td><td width="50%" style="vertical-align:top; padding-left:8px;">'.($months[$i+1]??'&nbsp;').'</td></tr>'; } return $html.'</table>'; }
function count_attendance_days(DateTimeImmutable $startDate, DateTimeImmutable $endDate, array $nonSchoolDays, array $scheduleByDay): int { $c=0; for($d=$startDate;$d<=$endDate;$d=$d->modify('+1 day')) if(!is_no_attendance_day($d,$nonSchoolDays,$scheduleByDay)) $c++; return $c; }

$id = filter_var($_GET['id_practica'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) { practicas_redirect_to_detail(0, null, 'Calendario: No se ha indicado un identificador de práctica válido.'); }

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT p.*,a.nombre AS alumno_nombre,a.apellido1 AS alumno_apellido1,a.apellido2 AS alumno_apellido2,e.convenio AS empresa_convenio,e.nombre AS empresa_nombre,d.nombre_via AS direccion_nombre_via,d.numero AS direccion_numero,d.bloque AS direccion_bloque,d.escalera AS direccion_escalera,d.planta AS direccion_planta,d.puerta AS direccion_puerta,d.otros AS direccion_otros,d.cp AS direccion_cp,v.via AS direccion_via_tipo,ld.nombre AS direccion_localidad,pd.nombre AS direccion_provincia,pa.pais AS direccion_pais FROM practicas p LEFT JOIN alumnos a ON a.id_alumno=p.id_alumno LEFT JOIN empresas e ON e.id_empresa=p.id_empresa LEFT JOIN direcciones d ON d.id_direccion=p.id_direccion LEFT JOIN vias v ON v.id_via=d.id_via LEFT JOIN localidades ld ON ld.id_localidad=d.id_localidad LEFT JOIN provincias pd ON pd.id_provincia=d.id_provincia LEFT JOIN paises pa ON pa.id_pais=d.id_pais WHERE p.id_practica=:id LIMIT 1');
  $stmt->execute(['id'=>$id]);
  $practice = $stmt->fetch();
  if (!$practice) { practicas_redirect_to_detail((int)$id, null, 'Calendario: No se encontró la práctica solicitada.'); }

  $schedule_by_day=[];
  $sch=$pdo->prepare('SELECT dia_semana, hora_entrada, hora_salida FROM practicas_horario WHERE id_practica=:id ORDER BY dia_semana ASC, hora_entrada ASC');
  $sch->execute(['id'=>$id]);
  foreach($sch->fetchAll() as $row){$d=(int)$row['dia_semana'];$schedule_by_day[$d][]=$row;}

  $paths = practicas_get_document_paths($practice);
  $startDateRaw = (string) ($practice['fecha_inicio'] ?? '');
  $endDateRaw = (string) ($practice['fecha_fin_real'] ?? '');
  if ($endDateRaw === '') { $endDateRaw = (string) ($practice['fecha_fin'] ?? ''); }
  $startDate = $startDateRaw !== '' ? (new DateTimeImmutable($startDateRaw))->setTime(0, 0, 0) : false;
  $endDate = $endDateRaw !== '' ? (new DateTimeImmutable($endDateRaw))->setTime(0, 0, 0) : false;
  if (!$startDate || !$endDate || $startDate > $endDate) { practicas_redirect_to_detail((int)$id, null, 'Calendario: No se puede generar el calendario porque las fechas de inicio/fin son inválidas.'); }

  $nonSchoolDays = []; $nonSchoolSourceNote = '';
  try { $f=$pdo->prepare('SELECT fecha FROM no_lectivos WHERE fecha BETWEEN :fi AND :ff'); $f->execute(['fi'=>$startDate->format('Y-m-d'),'ff'=>$endDate->format('Y-m-d')]); foreach($f->fetchAll(PDO::FETCH_ASSOC) as $x){ if(!empty($x['fecha'])) $nonSchoolDays[(string)$x['fecha']]=true; } } catch (Throwable $e) { $nonSchoolSourceNote='Nota: no hay no lectivos configurados en el sistema para esta instalación.'; }

  $dias_semana=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
  $attendanceDays = count_attendance_days($startDate, $endDate, $nonSchoolDays, $schedule_by_day);
  $pdfHtml = '<h1 style="margin-bottom:8px;">Calendario de prácticas</h1>';
  $pdfHtml .= '<p><strong>Alumno:</strong> ' . htmlspecialchars(full_name($practice, 'alumno'), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Empresa:</strong> ' . htmlspecialchars((string) ($practice['empresa_nombre'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Dirección Centro de Trabajo:</strong> ' . htmlspecialchars(build_address($practice), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Fecha inicio:</strong> ' . htmlspecialchars(format_date((string) ($practice['fecha_inicio'] ?? '')), ENT_QUOTES, 'UTF-8') . ' &nbsp;&nbsp; <strong>Fecha fin:</strong> ' . htmlspecialchars(format_date($endDateRaw), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Horario semanal</h3>';
  $pdfHtml .= build_weekly_schedule_pdf_table($schedule_by_day, $dias_semana);
  $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Calendario mensual</h3>';
  if ($nonSchoolSourceNote !== '') { $pdfHtml .= '<p style="font-size:9pt; color:#666666;">' . htmlspecialchars($nonSchoolSourceNote, ENT_QUOTES, 'UTF-8') . '</p>'; }
  $pdfHtml .= build_calendar_html($startDate, $endDate, $nonSchoolDays, $schedule_by_day);
  $pdfHtml .= '<p style="margin-top:8px;"><strong>Total de horas de prácticas: ' . htmlspecialchars(format_value($practice['horas'], '0'), ENT_QUOTES, 'UTF-8') . ' horas</strong></p>';
  $pdfHtml .= '<p><strong>Total de días que asistirá a la empresa: ' . $attendanceDays . ' días</strong></p>';

  $mpdf = new Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>12,'margin_right'=>12,'margin_top'=>12,'margin_bottom'=>12,'tempDir'=>ensure_mpdf_temp_dir()]);
  $css = '<style>html, body, .container { width: 100% !important; overflow: hidden; } table { table-layout: fixed !important; width: 100% !important; border-collapse: collapse; } td, th { word-break: break-all !important; overflow-wrap: break-word !important; } img { max-width: 150px !important; }</style>';
  $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
  $mpdf->WriteHTML($pdfHtml, \Mpdf\HTMLParserMode::HTML_BODY);
  $mpdf->Output($paths['calendar_file_path'], \Mpdf\Output\Destination::FILE);

  practicas_redirect_to_detail((int)$id, 'calendar_generated', null);
} catch (Throwable $e) {
  practicas_redirect_to_detail((int)($id ?: 0), null, 'Calendario: No se pudo generar el PDF del calendario en este momento.');
}

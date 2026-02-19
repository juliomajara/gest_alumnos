<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

const MAX_ATTACHMENTS_BYTES = 15 * 1024 * 1024;

$pdo = db();

function h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalize_email_address(string $email): string
{
  return function_exists('mb_strtolower') ? mb_strtolower(trim($email)) : strtolower(trim($email));
}

function get_mail_config(): array
{
  $cfg = require __DIR__ . '/config.php';
  $mail = is_array($cfg['mail'] ?? null) ? $cfg['mail'] : [];

  return [
    'from_email' => (string) ($mail['from_email'] ?? 'julio.sanchezfernandez@educa.madrid.org'),
    'from_name' => (string) ($mail['from_name'] ?? 'Julio Sánchez'),
    'reply_to' => (string) ($mail['reply_to'] ?? 'julio.sanchezfernandez@educa.madrid.org'),
    'transport' => (string) ($mail['transport'] ?? 'smtp'),
    'smtp_host' => (string) ($mail['smtp_host'] ?? 'smtp01.educa.madrid.org'),
    'smtp_port' => (int) ($mail['smtp_port'] ?? 465),
    'smtp_user' => (string) ($mail['smtp_user'] ?? 'julio.sanchezfernandez'),
    'smtp_pass' => (string) ($mail['smtp_pass'] ?? 'died10.Jerk'),
    'smtp_secure' => (string) ($mail['smtp_secure'] ?? 'ssl'),
  ];
}

function get_active_course_id(PDO $pdo): int
{
  $activeCourseId = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  return $activeCourseId ? (int) $activeCourseId : 0;
}

function get_groups(PDO $pdo, int $activeCourseId): array
{
  $stmt = $pdo->prepare(
    'SELECT DISTINCT g.id_grupo, g.grupo
     FROM grupos g
     INNER JOIN alumno_curso ac
      ON ac.id_grupo = g.id_grupo
      AND ac.id_curso_escolar = :active_course_id
     ORDER BY g.grupo'
  );
  $stmt->execute(['active_course_id' => $activeCourseId]);

  return $stmt->fetchAll();
}

function fetch_students(PDO $pdo, int $activeCourseId, string $nameFilter, string $surnameFilter, string $groupFilter): array
{
  $filters = [];
  $params = ['active_course_id' => $activeCourseId];

  if ($nameFilter !== '') {
    $filters[] = 'a.nombre LIKE :name_filter';
    $params['name_filter'] = '%' . $nameFilter . '%';
  }

  if ($surnameFilter !== '') {
    $filters[] = '(a.apellido1 LIKE :surname_filter OR a.apellido2 LIKE :surname_filter2)';
    $params['surname_filter'] = '%' . $surnameFilter . '%';
    $params['surname_filter2'] = '%' . $surnameFilter . '%';
  }

  if ($groupFilter !== '') {
    if ($groupFilter === 'sin') {
      $filters[] = 'g.id_grupo IS NULL';
    } elseif (ctype_digit($groupFilter)) {
      $filters[] = 'g.id_grupo = :group_id';
      $params['group_id'] = (int) $groupFilter;
    }
  }

  $whereClause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

  $stmt = $pdo->prepare(
    'SELECT
      a.id_alumno,
      a.apellido1,
      a.apellido2,
      a.nombre,
      t.telefono,
      c.direccion_correo AS correo_personal,
      a.nia,
      a.dni,
      g.grupo
    FROM alumnos a
    LEFT JOIN alumno_curso ac
      ON ac.id_alumno = a.id_alumno
      AND ac.id_curso_escolar = :active_course_id
    LEFT JOIN grupos g
      ON g.id_grupo = ac.id_grupo
    LEFT JOIN (
      SELECT id_entidad, MIN(telefono) AS telefono
      FROM telefonos
      WHERE entidad_tipo = \'alumno\'
      GROUP BY id_entidad
    ) t ON t.id_entidad = a.id_alumno
    LEFT JOIN (
      SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
      FROM correos
      WHERE entidad_tipo = \'alumno\'
      GROUP BY id_entidad
    ) c ON c.id_entidad = a.id_alumno
    ' . $whereClause . '
    ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre'
  );

  $stmt->execute($params);

  return $stmt->fetchAll();
}

function fetch_practices_for_students(PDO $pdo, array $studentIds): array
{
  if (!$studentIds) {
    return [];
  }

  $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
  $sql = 'SELECT
      p.id_practica,
      p.id_alumno,
      p.anexo,
      p.fecha_inicio,
      e.convenio AS empresa_convenio,
      a.nombre AS alumno_nombre,
      a.apellido1 AS alumno_apellido1,
      e.nombre_comercial AS empresa_nombre
    FROM practicas p
    INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
    INNER JOIN empresas e ON e.id_empresa = p.id_empresa
    WHERE p.id_alumno IN (' . $placeholders . ')';

  $stmt = $pdo->prepare($sql);
  foreach ($studentIds as $index => $studentId) {
    $stmt->bindValue($index + 1, $studentId, PDO::PARAM_INT);
  }
  $stmt->execute();

  return $stmt->fetchAll();
}

function is_safe_document_path(string $path, array $allowedDirs): bool
{
  $normalizeSeparators = static function (string $value): string {
    return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($value));
  };

  $normalizeForCompare = static function (string $value) use ($normalizeSeparators): string {
    $normalized = rtrim($normalizeSeparators($value), DIRECTORY_SEPARATOR);
    return DIRECTORY_SEPARATOR === '\\'
      ? strtolower($normalized)
      : $normalized;
  };

  $candidatePath = $normalizeSeparators($path);
  if ($candidatePath === '') {
    return false;
  }

  if (!preg_match('/^(?:[a-zA-Z]:[\\\\\/]|[\\\\\/]{2}|[\\\\\/])/', $candidatePath)) {
    $candidatePath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($candidatePath, '\\/');
  }

  $realPath = realpath($candidatePath);
  if ($realPath === false || !is_file($realPath)) {
    return false;
  }

  $realPathNormalized = $normalizeForCompare($realPath);

  foreach ($allowedDirs as $dir) {
    $normalizedDir = $normalizeSeparators((string) $dir);
    if ($normalizedDir === '') {
      continue;
    }

    if (!preg_match('/^(?:[a-zA-Z]:[\\\\\/]|[\\\\\/]{2}|[\\\\\/])/', $normalizedDir)) {
      $normalizedDir = __DIR__ . DIRECTORY_SEPARATOR . ltrim($normalizedDir, '\\/');
    }

    $realDir = realpath($normalizedDir);
    if ($realDir === false) {
      continue;
    }

    $realDirNormalized = $normalizeForCompare($realDir);
    $realDirWithSeparator = rtrim($realDirNormalized, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (str_starts_with($realPathNormalized, $realDirWithSeparator) || $realPathNormalized === $realDirNormalized) {
      return true;
    }
  }

  return false;
}

function resolve_document_path(array $practice, string $docType, array $allowedDirs): string
{
  $paths = practicas_get_document_paths($practice);
  $defaultPath = $docType === 'calendar'
    ? (string) ($paths['calendar_file_path'] ?? '')
    : (string) ($paths['plan_file_path'] ?? '');

  if ($defaultPath !== '' && is_safe_document_path($defaultPath, $allowedDirs)) {
    return $defaultPath;
  }

  if ($docType !== 'calendar') {
    return '';
  }

  $calendarDirectory = (string) ($paths['calendar_directory'] ?? (__DIR__ . '/docs/practicas_info'));
  $legacyCalendarFileName = implode('_', [
    practicas_sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
    practicas_sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
    practicas_sanitize_filename_component((string) ($practice['alumno_nombre'] ?? ''), 40),
    practicas_sanitize_filename_component((string) ($practice['alumno_apellido1'] ?? ''), 40),
    practicas_sanitize_filename_component((string) ($practice['empresa_nombre'] ?? ''), 70),
  ]) . '.pdf';

  $legacyPath = $calendarDirectory . '/' . $legacyCalendarFileName;
  return is_safe_document_path($legacyPath, $allowedDirs) ? $legacyPath : '';
}

function build_documents_by_student(array $practices): array
{
  $docsByStudent = [];
  $allowedDirs = [
    __DIR__ . '/docs/practicas_info',
    __DIR__ . '/docs/practicas_plan_formacion',
  ];

  foreach ($practices as $practice) {
    $studentId = (int) ($practice['id_alumno'] ?? 0);
    $practiceId = (int) ($practice['id_practica'] ?? 0);
    if ($studentId <= 0 || $practiceId <= 0) {
      continue;
    }

    $candidateDocs = [
      'calendar' => [
        'path' => resolve_document_path($practice, 'calendar', $allowedDirs),
        'label' => 'Calendario prácticas',
      ],
      'plan' => [
        'path' => resolve_document_path($practice, 'plan', $allowedDirs),
        'label' => 'Plan formación',
      ],
    ];

    foreach ($candidateDocs as $type => $docData) {
      $path = $docData['path'];
      if ($path === '' || !is_safe_document_path($path, $allowedDirs)) {
        continue;
      }

      $key = $studentId . ':' . $practiceId . ':' . $type;
      $docsByStudent[$studentId][$key] = [
        'key' => $key,
        'practice_id' => $practiceId,
        'type' => $type,
        'label' => $docData['label'],
        'file_name' => basename($path),
      ];
    }
  }

  return $docsByStudent;
}

function fetch_students_for_send(PDO $pdo, array $studentIds): array
{
  if (!$studentIds) {
    return [];
  }

  $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
  $stmt = $pdo->prepare(
    'SELECT id_alumno, nombre, apellido1, apellido2
     FROM alumnos
     WHERE id_alumno IN (' . $placeholders . ')'
  );

  foreach ($studentIds as $index => $studentId) {
    $stmt->bindValue($index + 1, $studentId, PDO::PARAM_INT);
  }

  $stmt->execute();
  $rows = $stmt->fetchAll();

  $result = [];
  foreach ($rows as $row) {
    $studentId = (int) $row['id_alumno'];
    $surname2 = trim((string) ($row['apellido2'] ?? ''));
    $result[$studentId] = [
      'id' => $studentId,
      'first_name' => trim((string) ($row['nombre'] ?? '')),
      'name' => trim(sprintf('%s %s, %s', (string) $row['apellido1'], $surname2, (string) $row['nombre'])),
    ];
  }

  return $result;
}

function fetch_emails_by_student(PDO $pdo, array $studentIds): array
{
  if (!$studentIds) {
    return [];
  }

  $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
  $stmt = $pdo->prepare(
    'SELECT id_entidad, direccion_correo
     FROM correos
     WHERE entidad_tipo = \'alumno\'
      AND id_entidad IN (' . $placeholders . ')'
  );

  foreach ($studentIds as $index => $studentId) {
    $stmt->bindValue($index + 1, $studentId, PDO::PARAM_INT);
  }

  $stmt->execute();

  $emailsByStudent = [];
  foreach ($stmt->fetchAll() as $row) {
    $studentId = (int) ($row['id_entidad'] ?? 0);
    $email = trim((string) ($row['direccion_correo'] ?? ''));
    if ($studentId <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      continue;
    }

    $normalized = normalize_email_address($email);
    $emailsByStudent[$studentId][$normalized] = $email;
  }

  return $emailsByStudent;
}

function encode_mime_header_value(string $value): string
{
  if (function_exists('mb_encode_mimeheader')) {
    return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
  }

  return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function sanitize_header_filename(string $filename): string
{
  $clean = str_replace(["\r", "\n", '"'], ['', '', "'"], $filename);
  return $clean === '' ? 'adjunto.pdf' : $clean;
}

function build_multipart_mail_payload(array $toEmails, string $subject, string $body, array $attachments, array $mailConfig): array
{
  $boundary = 'gestalumnos_' . md5((string) microtime(true));
  $headers = [];

  $fromEmail = trim((string) ($mailConfig['from_email'] ?? ''));
  if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
    return ['ok' => false, 'error' => 'Configura un remitente válido en config.php (mail.from_email).'];
  }

  $fromName = trim((string) ($mailConfig['from_name'] ?? 'Gestor de Alumnos'));
  $fromHeader = $fromName !== ''
    ? encode_mime_header_value($fromName) . ' <' . $fromEmail . '>'
    : $fromEmail;
  $headers[] = 'From: ' . $fromHeader;

  $replyTo = trim((string) ($mailConfig['reply_to'] ?? ''));
  if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
    $headers[] = 'Reply-To: ' . $replyTo;
  }

  $headers[] = 'To: ' . implode(', ', $toEmails);
  $headers[] = 'Subject: ' . encode_mime_header_value($subject);
  $headers[] = 'Date: ' . date('r');
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

  $message = "--{$boundary}\r\n";
  $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
  $message .= str_replace(["\r\n", "\r"], "\n", $body) . "\r\n";

  foreach ($attachments as $attachment) {
    $content = @file_get_contents((string) ($attachment['path'] ?? ''));
    if ($content === false) {
      return ['ok' => false, 'error' => 'No se pudo leer el adjunto ' . (string) ($attachment['name'] ?? '') . '.'];
    }

    $rawFilename = sanitize_header_filename((string) ($attachment['name'] ?? 'adjunto.pdf'));
    $encodedFilename = rawurlencode($rawFilename);

    $message .= "--{$boundary}\r\n";
    $message .= 'Content-Type: application/pdf; name="' . $rawFilename . '"; name*=UTF-8\'\'' . $encodedFilename . "\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= 'Content-Disposition: attachment; filename="' . $rawFilename . '"; filename*=UTF-8\'\'' . $encodedFilename . "\r\n\r\n";
    $message .= chunk_split(base64_encode($content), 76, "\r\n") . "\r\n";
  }

  $message .= "--{$boundary}--\r\n";

  return [
    'ok' => true,
    'headers' => $headers,
    'message' => $message,
    'from_email' => $fromEmail,
  ];
}

function smtp_read_response($socket): string
{
  $response = '';
  while (($line = fgets($socket, 515)) !== false) {
    $response .= $line;
    if (strlen($line) >= 4 && $line[3] === ' ') {
      break;
    }
  }

  return trim($response);
}

function smtp_send_command($socket, string $command, array $expectedCodes, string $stage, string &$lastResponse): ?string
{
  if (fwrite($socket, $command . "\r\n") === false) {
    return 'SMTP fallo en etapa ' . $stage . ': no se pudo escribir en el socket.';
  }

  $lastResponse = smtp_read_response($socket);
  $code = (int) substr($lastResponse, 0, 3);
  if (!in_array($code, $expectedCodes, true)) {
    return 'SMTP fallo en etapa ' . $stage . ': respuesta [' . $lastResponse . '].';
  }

  return null;
}

function send_mail_with_attachments(array $toEmails, string $subject, string $body, array $attachments, array $mailConfig): array
{
  $validRecipients = [];
  foreach ($toEmails as $email) {
    $cleanEmail = trim((string) $email);
    if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
      continue;
    }

    $validRecipients[normalize_email_address($cleanEmail)] = $cleanEmail;
  }

  $recipients = array_values($validRecipients);
  if (!$recipients) {
    return ['ok' => false, 'error' => 'No hay destinatarios válidos para el envío.'];
  }

  $payload = build_multipart_mail_payload($recipients, $subject, $body, $attachments, $mailConfig);
  if (!($payload['ok'] ?? false)) {
    return ['ok' => false, 'error' => (string) ($payload['error'] ?? 'No se pudo construir el email.')];
  }

  $transport = strtolower(trim((string) ($mailConfig['transport'] ?? 'smtp')));
  if ($transport !== 'smtp') {
    $ok = mail(
      implode(',', $recipients),
      encode_mime_header_value($subject),
      (string) $payload['message'],
      implode("\r\n", (array) $payload['headers'])
    );

    return $ok
      ? ['ok' => true, 'error' => null]
      : ['ok' => false, 'error' => 'mail() devolvió false. Revisa la configuración del servidor de correo.'];
  }

  $smtpHost = trim((string) ($mailConfig['smtp_host'] ?? ''));
  $smtpPort = (int) ($mailConfig['smtp_port'] ?? 465);
  $smtpUser = trim((string) ($mailConfig['smtp_user'] ?? ''));
  $smtpPass = (string) ($mailConfig['smtp_pass'] ?? '');
  $smtpSecure = strtolower(trim((string) ($mailConfig['smtp_secure'] ?? 'ssl')));

  if ($smtpHost === '' || $smtpPort <= 0 || $smtpUser === '' || $smtpPass === '') {
    return ['ok' => false, 'error' => 'Configuración SMTP incompleta en config.php.'];
  }

  $remoteHost = $smtpHost;
  if ($smtpSecure === 'ssl' || $smtpSecure === 'smtps' || $smtpPort === 465) {
    $remoteHost = 'ssl://' . $smtpHost;
  }

  $context = stream_context_create([
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
      'allow_self_signed' => false,
    ],
  ]);

  $lastResponse = '';
  $socket = @stream_socket_client($remoteHost . ':' . $smtpPort, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
  if ($socket === false) {
    return ['ok' => false, 'error' => 'SMTP fallo en etapa connect: ' . $errstr . ' (' . $errno . ').'];
  }

  stream_set_timeout($socket, 20);

  $lastResponse = smtp_read_response($socket);
  $initialCode = (int) substr($lastResponse, 0, 3);
  if ($initialCode !== 220) {
    fclose($socket);
    return ['ok' => false, 'error' => 'SMTP fallo en etapa connect: respuesta [' . $lastResponse . '].'];
  }

  $hostname = gethostname();
  $heloHost = is_string($hostname) && $hostname !== '' ? $hostname : 'localhost';

  $error = smtp_send_command($socket, 'EHLO ' . $heloHost, [250], 'ehlo', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  if ($smtpSecure === 'tls') {
    $error = smtp_send_command($socket, 'STARTTLS', [220], 'starttls', $lastResponse);
    if ($error !== null || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      fclose($socket);
      return ['ok' => false, 'error' => 'SMTP fallo en etapa starttls. Última respuesta: ' . $lastResponse];
    }

    $error = smtp_send_command($socket, 'EHLO ' . $heloHost, [250], 'ehlo', $lastResponse);
    if ($error !== null) {
      fclose($socket);
      return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
    }
  }

  $error = smtp_send_command($socket, 'AUTH LOGIN', [334], 'auth', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  $error = smtp_send_command($socket, base64_encode($smtpUser), [334], 'auth_user', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  $error = smtp_send_command($socket, base64_encode($smtpPass), [235], 'auth_pass', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  $error = smtp_send_command($socket, 'MAIL FROM:<' . (string) $payload['from_email'] . '>', [250], 'mailfrom', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  foreach ($recipients as $recipient) {
    $error = smtp_send_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251], 'rcptto', $lastResponse);
    if ($error !== null) {
      fclose($socket);
      return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
    }
  }

  $error = smtp_send_command($socket, 'DATA', [354], 'data', $lastResponse);
  if ($error !== null) {
    fclose($socket);
    return ['ok' => false, 'error' => $error . ' Última respuesta: ' . $lastResponse];
  }

  $rawData = implode("\r\n", (array) $payload['headers']) . "\r\n\r\n" . (string) $payload['message'];
  $rawData = str_replace(["\r\n", "\r"], "\n", $rawData);
  $lines = explode("\n", $rawData);
  foreach ($lines as &$line) {
    if (isset($line[0]) && $line[0] === '.') {
      $line = '.' . $line;
    }
  }
  unset($line);
  $smtpData = implode("\r\n", $lines) . "\r\n.\r\n";

  if (fwrite($socket, $smtpData) === false) {
    fclose($socket);
    return ['ok' => false, 'error' => 'SMTP fallo en etapa data: no se pudo enviar el cuerpo del mensaje.'];
  }

  $lastResponse = smtp_read_response($socket);
  $dataCode = (int) substr($lastResponse, 0, 3);
  if ($dataCode !== 250) {
    fclose($socket);
    return ['ok' => false, 'error' => 'SMTP fallo en etapa data: respuesta [' . $lastResponse . '].'];
  }

  smtp_send_command($socket, 'QUIT', [221], 'quit', $lastResponse);
  fclose($socket);

  return ['ok' => true, 'error' => null];
}

function render_student_rows(array $students, array $docsByStudent): string
{
  ob_start();

  if (!$students): ?>
    <tr>
      <td colspan="8">No hay alumnos para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($students as $student): ?>
      <?php
        $studentId = (int) $student['id_alumno'];
        $apellido2 = $student['apellido2'] ? ' ' . $student['apellido2'] : '';
        $nombreCompleto = sprintf('%s%s, %s', $student['apellido1'], $apellido2, $student['nombre']);
        $grupo = $student['grupo'] ?: 'Sin grupo';
        $telefono = $student['telefono'] ?: 'No disponible';
        $emailPersonal = $student['correo_personal'] ?: 'No disponible';
        $nia = $student['nia'] ?: 'No disponible';
        $dni = $student['dni'] ?: 'No disponible';
        $detalleUrl = sprintf('alumno_detalle.php?id_alumno=%d', $studentId);
        $docs = array_values($docsByStudent[$studentId] ?? []);
      ?>
      <tr data-student-id="<?php echo $studentId; ?>">
        <td>
          <input type="checkbox" class="student-checkbox" value="<?php echo $studentId; ?>" aria-label="Seleccionar alumno <?php echo h($nombreCompleto); ?>">
        </td>
        <td><?php echo h($grupo); ?></td>
        <td>
          <a class="practice-link" href="<?php echo h($detalleUrl); ?>"><?php echo h($nombreCompleto); ?></a>
        </td>
        <td><?php echo h($telefono); ?></td>
        <td><?php echo h($emailPersonal); ?></td>
        <td><?php echo h($nia); ?></td>
        <td><?php echo h($dni); ?></td>
        <td>
          <?php if (!$docs): ?>
            <span>No hay documentos</span>
          <?php else: ?>
            <?php foreach ($docs as $doc): ?>
              <label>
                <input
                  type="checkbox"
                  class="document-checkbox"
                  value="<?php echo h((string) $doc['key']); ?>"
                  data-student-id="<?php echo $studentId; ?>"
                >
                <?php echo h((string) $doc['label']); ?>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$activeCourseId = get_active_course_id($pdo);
$groups = get_groups($pdo, $activeCourseId);

$nameFilter = trim((string) ($_GET['nombre'] ?? ''));
$surnameFilter = trim((string) ($_GET['apellidos'] ?? ''));
$groupFilter = (string) ($_GET['grupo_id'] ?? '');
$groupFilter = $groupFilter === '' ? '' : $groupFilter;

$students = fetch_students($pdo, $activeCourseId, $nameFilter, $surnameFilter, $groupFilter);
$studentIds = array_values(array_map(static fn (array $student): int => (int) $student['id_alumno'], $students));
$practices = fetch_practices_for_students($pdo, $studentIds);
$docsByStudent = build_documents_by_student($practices);
$rowsHtml = render_student_rows($students, $docsByStudent);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode([
    'rows_html' => $rowsHtml,
    'visible_student_ids' => $studentIds,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$errors = [];
$summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'send_documents') {
  $selectedStudentIds = [];
  foreach ((array) ($_POST['selected_students'] ?? []) as $rawStudentId) {
    $studentId = filter_var($rawStudentId, FILTER_VALIDATE_INT);
    if ($studentId !== false && $studentId > 0) {
      $selectedStudentIds[] = (int) $studentId;
    }
  }
  $selectedStudentIds = array_values(array_unique($selectedStudentIds));

  $selectedDocumentKeys = [];
  foreach ((array) ($_POST['selected_documents'] ?? []) as $rawDocKey) {
    if (!is_string($rawDocKey)) {
      continue;
    }

    if (preg_match('/^(\d+):(\d+):(calendar|plan)$/', $rawDocKey, $matches) !== 1) {
      continue;
    }

    $studentId = filter_var($matches[1], FILTER_VALIDATE_INT);
    $practiceId = filter_var($matches[2], FILTER_VALIDATE_INT);
    $docType = $matches[3];

    if ($studentId === false || $practiceId === false || $studentId <= 0 || $practiceId <= 0) {
      continue;
    }

    $selectedDocumentKeys[] = [
      'key' => $rawDocKey,
      'student_id' => (int) $studentId,
      'practice_id' => (int) $practiceId,
      'type' => $docType,
    ];
  }

  if (!$selectedStudentIds) {
    $errors[] = 'Selecciona al menos un alumno.';
  }

  if (!$selectedDocumentKeys) {
    $errors[] = 'Selecciona al menos un documento.';
  }

  if (!$errors) {
    $studentsData = fetch_students_for_send($pdo, $selectedStudentIds);
    $emailsByStudent = fetch_emails_by_student($pdo, $selectedStudentIds);

    $practicesForSending = fetch_practices_for_students($pdo, $selectedStudentIds);
    $practiceMap = [];
    foreach ($practicesForSending as $practice) {
      $practiceMap[(int) $practice['id_practica']] = $practice;
    }

    $allowedDirs = [
      __DIR__ . '/docs/practicas_info',
      __DIR__ . '/docs/practicas_plan_formacion',
    ];

    $attachmentsByStudent = [];
    $documentLabels = [];
    $documentTypesByStudent = [];
    $practiceDataByStudent = [];

    foreach ($selectedDocumentKeys as $docRef) {
      $studentId = $docRef['student_id'];
      $practiceId = $docRef['practice_id'];
      $docType = $docRef['type'];

      if (!in_array($studentId, $selectedStudentIds, true)) {
        continue;
      }

      $practice = $practiceMap[$practiceId] ?? null;
      if (!$practice || (int) $practice['id_alumno'] !== $studentId) {
        $errors[] = 'Documento no válido para el alumno seleccionado (ref: ' . $docRef['key'] . ').';
        continue;
      }

      $path = resolve_document_path($practice, $docType, $allowedDirs);
      $label = $docType === 'calendar' ? 'Calendario prácticas' : 'Plan formación';

      if ($path === '' || !is_safe_document_path($path, $allowedDirs)) {
        $errors[] = 'El documento seleccionado no existe o no es accesible de forma segura (' . $docRef['key'] . ').';
        continue;
      }

      $attachmentsByStudent[$studentId][$path] = [
        'path' => $path,
        'name' => basename($path),
      ];

      $documentLabels[$studentId][$label] = true;
      $documentTypesByStudent[$studentId][$docType] = true;
      $practiceDataByStudent[$studentId] = $practice;
    }

    $summary = [
      'students_selected' => count($selectedStudentIds),
      'students_sent' => 0,
      'addresses_sent' => 0,
      'students_without_email' => [],
      'students_without_docs' => [],
      'errors' => [],
      'documents' => [],
    ];

    if (!$errors) {
      $mailConfig = get_mail_config();

      foreach ($selectedStudentIds as $studentId) {
        $studentData = $studentsData[$studentId] ?? ['name' => 'Alumno #' . $studentId];
        $studentName = (string) ($studentData['name'] ?? ('Alumno #' . $studentId));

        $emails = array_values($emailsByStudent[$studentId] ?? []);
        if (!$emails) {
          $summary['students_without_email'][] = $studentName;
          continue;
        }

        $attachments = array_values($attachmentsByStudent[$studentId] ?? []);
        if (!$attachments) {
          $summary['students_without_docs'][] = $studentName;
          continue;
        }

        $totalSize = 0;
        foreach ($attachments as $attachment) {
          $size = filesize($attachment['path']);
          $totalSize += $size === false ? 0 : $size;
        }

        if ($totalSize > MAX_ATTACHMENTS_BYTES) {
          $summary['errors'][] = sprintf(
            '%s: adjuntos demasiado grandes (%.2f MB, máximo %.2f MB).',
            $studentName,
            $totalSize / 1048576,
            MAX_ATTACHMENTS_BYTES / 1048576
          );
          continue;
        }

        $subject = 'Documentación de prácticas';
        $practiceData = $practiceDataByStudent[$studentId] ?? [];
        $studentFirstName = trim((string) ($practiceData['alumno_nombre'] ?? ''));
        if ($studentFirstName === '') {
          $studentFirstName = trim((string) ($studentData['first_name'] ?? ''));
        }
        $companyName = trim((string) ($practiceData['empresa_nombre'] ?? ''));
        $startDate = trim((string) ($practiceData['fecha_inicio'] ?? ''));
        if ($startDate !== '') {
          $date = DateTime::createFromFormat('Y-m-d', $startDate);
          if ($date instanceof DateTime) {
            $startDate = $date->format('d/m/Y');
          }
        }

        $bodyReplacements = [
          '[[nombre del alumno]]' => $studentFirstName,
          '[[fecha de inicio]]' => $startDate,
          '[[nombre de la empresa]]' => $companyName,
        ];
        $hasCalendar = isset($documentTypesByStudent[$studentId]['calendar']);
        $hasPlan = isset($documentTypesByStudent[$studentId]['plan']);

        if ($hasCalendar && $hasPlan) {
          $bodyTemplate = "Hola [[nombre del alumno]],\n\nempiezas tus prácticas de FFE el próximo [[fecha de inicio]] en la empresa [[nombre de la empresa]] en el horario adjunto.\n\nEn los archivos adjuntos tienes tu calendario de prácticas y tu Plan de Formación. Este Plan de Formación deberás entregárselo a tu tutor de prácticas para que te lo firme. Después deberás firmarlo tú y traerlo de vuelta al instituto cuanto antes.\n\nUn saludo,\n\nJulio Sánchez\nIES Laguna de Joatzel - Getafe";
          $body = str_replace(array_keys($bodyReplacements), array_values($bodyReplacements), $bodyTemplate);
        } elseif ($hasCalendar) {
          $bodyTemplate = "Hola [[nombre del alumno]],\n\nempiezas tus prácticas de FFE el próximo [[fecha de inicio]] en la empresa [[nombre de la empresa]] en el horario adjunto.\n\nUn saludo,\n\nJulio Sánchez\nIES Laguna de Joatzel - Getafe";
          $body = str_replace(array_keys($bodyReplacements), array_values($bodyReplacements), $bodyTemplate);
        } else {
          $bodyTemplate = "Hola [[nombre del alumno]],\n\nEn el archivo adjunto tienes tu Plan de Formación. Este Plan de Formación deberás entregárselo a tu tutor de prácticas para que te lo firme. Después deberás firmarlo tú y traerlo de vuelta al instituto cuanto antes.\n\nUn saludo,\n\nJulio Sánchez\nIES Laguna de Joatzel - Getafe";
          $body = str_replace(array_keys($bodyReplacements), array_values($bodyReplacements), $bodyTemplate);
        }

        $result = send_mail_with_attachments($emails, $subject, $body, $attachments, $mailConfig);

        if (!$result['ok']) {
          $summary['errors'][] = $studentName . ': ' . (string) $result['error'];
          continue;
        }

        $summary['students_sent']++;
        $summary['addresses_sent'] += count($emails);
        $summary['documents'][$studentName] = array_keys($documentLabels[$studentId] ?? []);
      }
    }
  }
}

$page_title = 'Enviar correos | Gestor de Alumnos';
$active_page = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($page_title); ?></title>
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
          <h1>Enviar documentos por correo</h1>
          <p class="subheading">Selecciona alumnos y documentos de prácticas para enviarlos por email.</p>
        </div>
      </header>

      <?php if ($errors): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>No se ha podido completar el envío</h3>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if (is_array($summary)): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Resumen de envío</h3>
            <p>Alumnos seleccionados: <?php echo (int) $summary['students_selected']; ?> · Alumnos enviados: <?php echo (int) $summary['students_sent']; ?> · Direcciones destino: <?php echo (int) $summary['addresses_sent']; ?></p>
          </div>
          <?php if ($summary['documents']): ?>
            <ul class="form-errors">
              <?php foreach ($summary['documents'] as $studentName => $labels): ?>
                <li><strong><?php echo h($studentName); ?>:</strong> <?php echo h(implode(', ', $labels)); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if ($summary['students_without_email']): ?>
            <p><strong>Sin correo:</strong> <?php echo h(implode(', ', $summary['students_without_email'])); ?></p>
          <?php endif; ?>
          <?php if ($summary['students_without_docs']): ?>
            <p><strong>Sin documentos seleccionables:</strong> <?php echo h(implode(', ', $summary['students_without_docs'])); ?></p>
          <?php endif; ?>
          <?php if ($summary['errors']): ?>
            <ul class="form-errors">
              <?php foreach ($summary['errors'] as $error): ?>
                <li><?php echo h($error); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form class="topbar" id="filtersForm" method="get">
        <div class="topbar-search">
          <input type="search" name="nombre" placeholder="Filtrar por nombre" value="<?php echo h($nameFilter); ?>">
        </div>
        <div class="topbar-search">
          <input type="search" name="apellidos" placeholder="Filtrar por apellidos" value="<?php echo h($surnameFilter); ?>">
        </div>
        <div class="topbar-actions">
          <label class="calendar-select">
            <select name="grupo_id">
              <option value="" <?php echo $groupFilter === '' ? 'selected' : ''; ?>>Todos los grupos</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $groupFilter ? 'selected' : ''; ?>>
                  <?php echo h((string) $group['grupo']); ?>
                </option>
              <?php endforeach; ?>
              <option value="sin" <?php echo $groupFilter === 'sin' ? 'selected' : ''; ?>>Sin grupo</option>
            </select>
          </label>
        </div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de alumnos y documentos</h3>
          <p>Marca alumnos y documentos disponibles para enviar.</p>
        </div>

        <form method="post" id="sendForm">
          <input type="hidden" name="action" value="send_documents">
          <input type="hidden" name="selected_students" id="selectedStudentsInput" value="">
          <input type="hidden" name="selected_documents" id="selectedDocumentsInput" value="">

          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllVisible" aria-label="Seleccionar o deseleccionar alumnos visibles"></th>
                  <th>Grupo</th>
                  <th>Apellidos y nombre</th>
                  <th>Teléfono</th>
                  <th>Correo personal</th>
                  <th>NIA</th>
                  <th>DNI</th>
                  <th>Documentos disponibles</th>
                </tr>
              </thead>
              <tbody id="studentsTableBody">
                <?php echo $rowsHtml; ?>
              </tbody>
            </table>
          </div>

          <div class="topbar-actions">
            <button type="submit">Enviar documentos</button>
          </div>
        </form>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const filtersForm = document.getElementById('filtersForm');
      const nameInput = filtersForm.querySelector('input[name="nombre"]');
      const surnameInput = filtersForm.querySelector('input[name="apellidos"]');
      const groupSelect = filtersForm.querySelector('select[name="grupo_id"]');
      const tableBody = document.getElementById('studentsTableBody');
      const selectAllVisible = document.getElementById('selectAllVisible');
      const sendForm = document.getElementById('sendForm');
      const selectedStudentsInput = document.getElementById('selectedStudentsInput');
      const selectedDocumentsInput = document.getElementById('selectedDocumentsInput');

      const selectedStudents = new Set();
      const selectedDocuments = new Set();
      let visibleStudentIds = [];
      let debounceTimer = null;

      const syncHiddenInputs = () => {
        selectedStudentsInput.value = JSON.stringify(Array.from(selectedStudents));
        selectedDocumentsInput.value = JSON.stringify(Array.from(selectedDocuments));
      };

      const updateSelectAllState = () => {
        if (!visibleStudentIds.length) {
          selectAllVisible.checked = false;
          selectAllVisible.indeterminate = false;
          return;
        }

        const selectedVisibleCount = visibleStudentIds.filter((id) => selectedStudents.has(String(id))).length;
        selectAllVisible.checked = selectedVisibleCount === visibleStudentIds.length;
        selectAllVisible.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < visibleStudentIds.length;
      };

      const applySelectionStateToDOM = () => {
        tableBody.querySelectorAll('.student-checkbox').forEach((checkbox) => {
          checkbox.checked = selectedStudents.has(checkbox.value);
        });

        tableBody.querySelectorAll('.document-checkbox').forEach((checkbox) => {
          checkbox.checked = selectedDocuments.has(checkbox.value);
        });

        updateSelectAllState();
      };

      const bindTableEvents = () => {
        tableBody.querySelectorAll('.student-checkbox').forEach((checkbox) => {
          checkbox.addEventListener('change', () => {
            const studentId = checkbox.value;
            if (checkbox.checked) {
              selectedStudents.add(studentId);
            } else {
              selectedStudents.delete(studentId);
              tableBody.querySelectorAll('.document-checkbox[data-student-id="' + studentId + '"]').forEach((docCheckbox) => {
                selectedDocuments.delete(docCheckbox.value);
                docCheckbox.checked = false;
              });
            }
            updateSelectAllState();
          });
        });

        tableBody.querySelectorAll('.document-checkbox').forEach((checkbox) => {
          checkbox.addEventListener('change', () => {
            const studentId = checkbox.dataset.studentId;
            const studentCheckbox = tableBody.querySelector('.student-checkbox[value="' + studentId + '"]');
            if (checkbox.checked) {
              selectedDocuments.add(checkbox.value);
              selectedStudents.add(String(studentId));
              if (studentCheckbox) {
                studentCheckbox.checked = true;
              }
            } else {
              selectedDocuments.delete(checkbox.value);
            }
            updateSelectAllState();
          });
        });
      };

      const fetchFilteredRows = (withDebounce = false) => {
        if (debounceTimer) {
          window.clearTimeout(debounceTimer);
        }

        const run = () => {
          const params = new URLSearchParams(new FormData(filtersForm));
          const cleanParams = new URLSearchParams(params);
          params.set('ajax', '1');

          fetch('enviar_correos.php?' + params.toString(), {
            headers: {
              'X-Requested-With': 'fetch'
            }
          })
            .then((response) => response.json())
            .then((payload) => {
              tableBody.innerHTML = payload.rows_html || '';
              visibleStudentIds = Array.isArray(payload.visible_student_ids) ? payload.visible_student_ids : [];
              bindTableEvents();
              applySelectionStateToDOM();
              history.replaceState(null, '', '?' + cleanParams.toString());
            })
            .catch(() => {});
        };

        if (withDebounce) {
          debounceTimer = window.setTimeout(run, 250);
          return;
        }

        run();
      };

      selectAllVisible.addEventListener('change', () => {
        const checked = selectAllVisible.checked;
        tableBody.querySelectorAll('.student-checkbox').forEach((checkbox) => {
          checkbox.checked = checked;
          if (checked) {
            selectedStudents.add(checkbox.value);
          } else {
            selectedStudents.delete(checkbox.value);
            tableBody.querySelectorAll('.document-checkbox[data-student-id="' + checkbox.value + '"]').forEach((docCheckbox) => {
              selectedDocuments.delete(docCheckbox.value);
              docCheckbox.checked = false;
            });
          }
        });
        updateSelectAllState();
      });

      filtersForm.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchFilteredRows();
      });

      nameInput.addEventListener('input', () => fetchFilteredRows(true));
      surnameInput.addEventListener('input', () => fetchFilteredRows(true));
      groupSelect.addEventListener('change', () => fetchFilteredRows());

      sendForm.addEventListener('submit', (event) => {
        event.preventDefault();

        syncHiddenInputs();

        const students = JSON.parse(selectedStudentsInput.value || '[]');
        const documents = JSON.parse(selectedDocumentsInput.value || '[]');

        const hasStudents = Array.isArray(students) && students.length > 0;
        const hasDocuments = Array.isArray(documents) && documents.length > 0;

        if (!hasStudents || !hasDocuments) {
          window.alert('Selecciona al menos un alumno y un documento antes de enviar.');
          return;
        }

        const formData = new FormData();
        formData.append('action', 'send_documents');
        students.forEach((id) => formData.append('selected_students[]', id));
        documents.forEach((key) => formData.append('selected_documents[]', key));

        fetch('enviar_correos.php?' + new URLSearchParams(new FormData(filtersForm)).toString(), {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'fetch'
          }
        })
          .then((response) => response.text())
          .then((html) => {
            document.open();
            document.write(html);
            document.close();
          })
          .catch(() => {
            window.alert('No se pudo completar el envío en este momento.');
          });
      });

      visibleStudentIds = Array.from(tableBody.querySelectorAll('.student-checkbox')).map((checkbox) => parseInt(checkbox.value, 10)).filter((id) => !Number.isNaN(id));
      bindTableEvents();
      updateSelectAllState();
    })();
  </script>
</body>
</html>

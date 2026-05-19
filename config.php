<?php
// config.php
declare(strict_types=1);

// Carga el archivo .env ubicado en la raíz del proyecto
(function (): void {
    $path = __DIR__ . '/.env';
    if (!is_readable($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Elimina comillas dobles opcionales: SMTP_PASS="mi contraseña"
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            $value = stripslashes(substr($value, 1, -1));
        }

        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
})();

return [
    'db' => [
        'host'    => $_ENV['DB_HOST']    ?? 'localhost',
        'name'    => $_ENV['DB_NAME']    ?? 'gest_alumnos',
        'user'    => $_ENV['DB_USER']    ?? 'root',
        'pass'    => $_ENV['DB_PASS']    ?? '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'from_email'  => $_ENV['MAIL_FROM_EMAIL'] ?? '',
        'from_name'   => $_ENV['MAIL_FROM_NAME']  ?? '',
        'reply_to'    => $_ENV['MAIL_REPLY_TO']   ?? '',
        'transport'   => $_ENV['MAIL_TRANSPORT']  ?? 'smtp',
        'smtp_host'   => $_ENV['SMTP_HOST']       ?? '',
        'smtp_port'   => (int) ($_ENV['SMTP_PORT'] ?? 465),
        'smtp_user'   => $_ENV['SMTP_USER']       ?? '',
        'smtp_pass'   => $_ENV['SMTP_PASS']       ?? '',
        'smtp_secure' => $_ENV['SMTP_SECURE']     ?? 'ssl',
    ],
];

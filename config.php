<?php
// config.php
declare(strict_types=1);

return [
  'db' => [
    'host' => 'localhost',
    'name' => 'gest_alumnos',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],

  'mail' => [
    'from_email'  => 'julio.sanchezfernandez@educa.madrid.org',
    'from_name'   => 'Julio Sánchez',
    'reply_to'    => 'julio.sanchezfernandez@educa.madrid.org',

    // IMPORTANTE: para EducaMadrid necesitas SMTP real (no mail()).
    'transport'   => 'smtp',

    'smtp_host'   => 'smtp01.educa.madrid.org',
    'smtp_port'   => 465,
    'smtp_user'   => 'julio.sanchezfernandez',
    'smtp_pass'   => 'died10.Jerk',
    'smtp_secure' => 'ssl',
  ],
];


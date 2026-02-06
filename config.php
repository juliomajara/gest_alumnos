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
  'libreoffice_path' => null,
  'anexo_ffe_template' => __DIR__ . '/docs/AccesoFFEAnexoI.html',
  'anexo_ffe_pdf_template' => __DIR__ . '/docs/AccesoFFEAnexoI.pdf',
  'anexo_ffe_defaults' => [
    'modulo_profesional' => '',
    'lugar' => '',
    'fecha' => '',
    'firma' => '',
  ],
  'anexo_ffe_pdf_coords' => [
    // =========================
    // Cabecera
    // =========================
    'alumno' => [
      'rect' => [135.36, 660.72, 413.40, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],
    'ciclo_formativo' => [
      'rect' => [135.36, 650.40, 413.40, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],
    'curso_academico' => [
      'rect' => [135.36, 640.08, 292.68, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],
    'grupo' => [
      'rect' => [464.52, 640.08, 84.24, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],
    'anyo_escolar' => [
      'rect' => [135.36, 629.76, 413.40, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],
    'modulo_profesional' => [
      'rect' => [135.36, 619.44, 413.40, 9.24],
      'font_size' => 7,
      'align' => 'left',
    ],

    // =========================
    // Tabla ítems 1..11 (marcar con "X" o lo que uses)
    // =========================
    'items' => [
      1 => [
        'si' => [440.46, 576.12, 10.56, 9.27],
        'no' => [477.67, 576.12, 10.56, 9.27],
        'np' => [521.27, 576.12, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      2 => [
        'si' => [440.46, 558.40, 10.56, 9.27],
        'no' => [477.67, 558.40, 10.56, 9.27],
        'np' => [521.27, 558.40, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      3 => [
        'si' => [440.46, 541.68, 10.56, 9.27],
        'no' => [477.67, 541.68, 10.56, 9.27],
        'np' => [521.27, 541.68, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      4 => [
        'si' => [440.46, 527.96, 10.56, 9.27],
        'no' => [477.67, 527.96, 10.56, 9.27],
        'np' => [521.27, 527.96, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      5 => [
        'si' => [440.46, 517.24, 10.56, 9.27],
        'no' => [477.67, 517.24, 10.56, 9.27],
        'np' => [521.27, 517.24, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      6 => [
        'si' => [440.46, 506.52, 10.56, 9.27],
        'no' => [477.67, 506.52, 10.56, 9.27],
        'np' => [521.27, 506.52, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      7 => [
        'si' => [440.46, 495.80, 10.56, 9.27],
        'no' => [477.67, 495.80, 10.56, 9.27],
        'np' => [521.27, 495.80, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      8 => [
        'si' => [440.46, 481.08, 10.56, 9.27],
        'no' => [477.67, 481.08, 10.56, 9.27],
        'np' => [521.27, 481.08, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      9 => [
        'si' => [440.46, 465.36, 10.56, 9.27],
        'no' => [477.67, 465.36, 10.56, 9.27],
        'np' => [521.27, 465.36, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      10 => [
        'si' => [440.46, 455.64, 10.56, 9.27],
        'no' => [477.67, 455.64, 10.56, 9.27],
        'np' => [521.27, 455.64, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
      11 => [
        'si' => [440.46, 440.42, 10.56, 9.27],
        'no' => [477.67, 440.42, 10.56, 9.27],
        'np' => [521.27, 440.42, 10.56, 9.27],
        'font_size' => 7,
        'align' => 'center',
        'valign' => 'middle',
      ],
    ],

    // =========================
    // Registro de conductas inseguras (4 filas x 5 columnas)
    // =========================
    'conductas' => [
      1 => [
        'item' => [56.88, 333.84, 33.72, 12.84],
        'descripcion' => [92.16, 333.84, 211.08, 12.84],
        'calificacion' => [304.80, 333.84, 62.28, 12.84],
        'consecuencias' => [368.64, 333.84, 111.84, 12.84],
        'lugar_fecha' => [482.04, 333.84, 62.88, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
      2 => [
        'item' => [56.88, 319.80, 33.72, 12.84],
        'descripcion' => [92.16, 319.80, 211.08, 12.84],
        'calificacion' => [304.80, 319.80, 62.28, 12.84],
        'consecuencias' => [368.64, 319.80, 111.84, 12.84],
        'lugar_fecha' => [482.04, 319.80, 62.88, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
      3 => [
        'item' => [56.88, 305.76, 33.72, 12.84],
        'descripcion' => [92.16, 305.76, 211.08, 12.84],
        'calificacion' => [304.80, 305.76, 62.28, 12.84],
        'consecuencias' => [368.64, 305.76, 111.84, 12.84],
        'lugar_fecha' => [482.04, 305.76, 62.88, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
      4 => [
        'item' => [56.88, 291.72, 33.72, 12.84],
        'descripcion' => [92.16, 291.72, 211.08, 12.84],
        'calificacion' => [304.80, 291.72, 62.28, 12.84],
        'consecuencias' => [368.64, 291.72, 111.84, 12.84],
        'lugar_fecha' => [482.04, 291.72, 62.88, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
    ],

    // =========================
    // Valoración final (marcar con "X")
    // =========================
    'valoracion' => [
      'positiva' => [118.16, 217.86, 10.56, 9.27],
      'negativa' => [118.16, 194.75, 10.56, 9.27],
      'font_size' => 7,
      'align' => 'center',
      'valign' => 'middle',
    ],

    // Caja grande (NO es campo AcroForm en este PDF): la deduzco del bloque inferior.
    // Útil para imprimir texto tipo “resultados de aprendizaje a reforzar”.
    'refuerzo_resultados' => [
      'rect' => [60.00, 153.24, 478.50, 37.70],
      'font_size' => 7,
      'align' => 'left',
    ],

    // =========================
    // Pie (En , a . / Firma)
    // =========================
    'pie' => [
      'lugar' => [
        'rect' => [212.23, 123.76, 55.54, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
      'fecha' => [
        'rect' => [278.12, 123.76, 107.90, 12.84],
        'font_size' => 7,
        'align' => 'left',
      ],
      'firma' => [
        'rect' => [214.60, 12.94, 163.76, 15.08],
        'font_size' => 7,
        'align' => 'center',
      ],
    ],
  ],
];

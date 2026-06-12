<?php
define('APP_NAME', 'Porras Mundial 2026');
define('DATA_DIR', __DIR__ . '/data');
define('CACHE_TTL', 900); // 15 minutos
define('MATCHES_API_URL', 'https://raw.githubusercontent.com/openfootball/worldcup.json/master/2026/worldcup.json');
define('ADMIN_PASSWORD', 'admin2026'); // Cambiar en producción

// Puntuación partidos
define('PTS_EXACT',   3);
define('PTS_OUTCOME', 1);

// Puntuación Top 4
define('PTS_IN_TOP4', 1);
define('PTS_POS', [1 => 7, 2 => 5, 3 => 3, 4 => 2]); // bonus por posición exacta

// Fase cuartos → cierre apuestas Top 4
define('QF_ROUND_KEYWORDS', ['quarter', 'cuartos', 'round of 8']);

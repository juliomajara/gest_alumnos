<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="#0a1628">
<title><?= h($page_title ?? APP_NAME) ?> · <?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= $assets_base ?? '' ?>assets/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="page-<?= h($active_page ?? 'default') ?>">
<div class="app-shell">

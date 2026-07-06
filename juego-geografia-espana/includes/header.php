<?php
/**
 * @var string $page_title
 * @var string $body_class
 */
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title><?= h($page_title ?? 'GeoEspaña — Provincias y Comunidades') ?></title>
<meta name="description" content="Aprende y practica las provincias y comunidades autónomas de España jugando desde el móvil.">
<meta name="theme-color" content="#1f6f78">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body class="<?= h($body_class ?? '') ?>">

<?php

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cargar_json(string $ruta): array
{
    $contenido = file_get_contents($ruta);
    return json_decode($contenido, true) ?? [];
}

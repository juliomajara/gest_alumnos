<?php
declare(strict_types=1);

function iniciar_sesion_app(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('geoespana_sesion');
        session_start();
    }
}

function usuario_actual(): ?array
{
    iniciar_sesion_app();
    if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_nombre'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['usuario_id'],
        'nombre' => (string) $_SESSION['usuario_nombre'],
    ];
}

function requerir_login(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        header('Location: login.php');
        exit;
    }
    return $usuario;
}

function iniciar_sesion_usuario(int $id, string $nombre): void
{
    iniciar_sesion_app();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $id;
    $_SESSION['usuario_nombre'] = $nombre;
}

function cerrar_sesion_usuario(): void
{
    iniciar_sesion_app();
    $_SESSION = [];
    session_destroy();
}

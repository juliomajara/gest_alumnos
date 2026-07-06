<?php

declare(strict_types=1);

require_once __DIR__ . '/almacen.php';

function ruta_usuarios(): string
{
    return ruta_almacen('usuarios.json');
}

function usuarios_todos(): array
{
    $datos = leer_json(ruta_usuarios(), ['siguiente_id' => 1, 'usuarios' => []]);
    return $datos['usuarios'] ?? [];
}

function usuario_por_nombre(string $nombre): ?array
{
    foreach (usuarios_todos() as $usuario) {
        if (mb_strtolower($usuario['nombre_usuario']) === mb_strtolower($nombre)) {
            return $usuario;
        }
    }
    return null;
}

function usuario_por_id(int $id): ?array
{
    foreach (usuarios_todos() as $usuario) {
        if ((int) $usuario['id'] === $id) {
            return $usuario;
        }
    }
    return null;
}

/**
 * Crea la cuenta si el nombre sigue libre. La comprobación de unicidad se
 * repite aquí dentro (ya con el fichero bloqueado) por si dos personas
 * intentan registrarse con el mismo nombre exactamente a la vez.
 *
 * Devuelve ['existente' => bool, 'usuario' => array].
 */
function crear_usuario(string $nombre, string $pinHash): array
{
    return actualizar_json(ruta_usuarios(), ['siguiente_id' => 1, 'usuarios' => []], function (array $datos) use ($nombre, $pinHash) {
        foreach ($datos['usuarios'] as $usuario) {
            if (mb_strtolower($usuario['nombre_usuario']) === mb_strtolower($nombre)) {
                return ['datos' => $datos, 'valor' => ['existente' => true, 'usuario' => $usuario]];
            }
        }

        $id = $datos['siguiente_id'] ?? 1;
        $nuevo = [
            'id' => $id,
            'nombre_usuario' => $nombre,
            'pin_hash' => $pinHash,
            'creado_en' => date('Y-m-d H:i:s'),
        ];
        $datos['usuarios'][] = $nuevo;
        $datos['siguiente_id'] = $id + 1;

        return ['datos' => $datos, 'valor' => ['existente' => false, 'usuario' => $nuevo]];
    });
}

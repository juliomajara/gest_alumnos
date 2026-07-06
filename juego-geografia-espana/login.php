<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/usuarios.php';

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim((string) ($_POST['usuario'] ?? ''));
    $pin = trim((string) ($_POST['pin'] ?? ''));

    if (!preg_match('/^[\p{L}0-9 _-]{2,30}$/u', $nombre)) {
        $error = 'El nombre de usuario debe tener entre 2 y 30 caracteres (letras, números, espacios, "-" o "_").';
    } elseif (!preg_match('/^\d{4}$/', $pin)) {
        $error = 'El PIN debe ser un número de 4 dígitos.';
    } else {
        $usuario = usuario_por_nombre($nombre);

        if ($usuario) {
            if (password_verify($pin, $usuario['pin_hash'])) {
                iniciar_sesion_usuario((int) $usuario['id'], $usuario['nombre_usuario']);
                header('Location: index.php');
                exit;
            }
            $error = 'PIN incorrecto para ese usuario.';
        } else {
            $hash = password_hash($pin, PASSWORD_DEFAULT);
            $resultado = crear_usuario($nombre, $hash);

            if ($resultado['existente']) {
                // Alguien se registró con ese mismo nombre justo antes.
                if (password_verify($pin, $resultado['usuario']['pin_hash'])) {
                    iniciar_sesion_usuario((int) $resultado['usuario']['id'], $resultado['usuario']['nombre_usuario']);
                    header('Location: index.php');
                    exit;
                }
                $error = 'Ese nombre acaba de registrarse con otro PIN. Prueba con otro nombre de usuario.';
            } else {
                iniciar_sesion_usuario((int) $resultado['usuario']['id'], $resultado['usuario']['nombre_usuario']);
                header('Location: index.php');
                exit;
            }
        }
    }
}

$page_title = 'Entrar — GeoEspaña';
$body_class = 'pagina-login';
require __DIR__ . '/includes/header.php';
?>
<div class="app-container app-container--centrado">
  <div class="login-card">
    <div class="portada-logo" aria-hidden="true">🇪🇸</div>
    <h1 class="login-titulo">GeoEspaña</h1>
    <p class="subtitulo">Escribe tu nombre de usuario y tu PIN de 4 dígitos.<br>Si es la primera vez, se creará tu cuenta.</p>

    <?php if ($error): ?>
      <p class="login-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="login-form" autocomplete="off">
      <label class="login-label" for="usuario">Nombre de usuario</label>
      <input type="text" id="usuario" name="usuario" class="login-input" maxlength="30"
             value="<?= h($_POST['usuario'] ?? '') ?>" required autofocus>

      <label class="login-label" for="pin">PIN (4 dígitos)</label>
      <input type="password" id="pin" name="pin" class="login-input login-input--pin"
             inputmode="numeric" pattern="\d{4}" maxlength="4" required>

      <button type="submit" class="btn-primario">Entrar</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

start_session();
if (current_user()) { header('Location: partidos.php'); exit; }

$page_title  = 'Entrar';
$active_page = 'login';
$assets_base = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="#0a1628">
<title><?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-bg-balls">
    <span style="top:5%;left:10%;transform:rotate(-20deg)">⚽</span>
    <span style="top:15%;right:8%;transform:rotate(30deg)">🏆</span>
    <span style="bottom:20%;left:5%;transform:rotate(10deg)">⚽</span>
    <span style="bottom:10%;right:12%;transform:rotate(-15deg)">🌎</span>
    <span style="top:45%;left:-2%;transform:rotate(25deg)">⚽</span>
    <span style="top:60%;right:3%;transform:rotate(-10deg)">⚽</span>
  </div>

  <div class="login-card">
    <div class="login-logo">
      <div class="trophy">🏆</div>
      <h1><span>Porras</span><br>Mundial 2026</h1>
      <p>USA · Canadá · México</p>
    </div>

    <div id="login-error" class="alert error" style="display:none"></div>

    <form id="login-form">
      <div class="form-group">
        <label class="form-label" for="name">Tu nombre</label>
        <input class="form-input" type="text" id="name" name="name"
               placeholder="Cómo te llamas" maxlength="30" autocomplete="nickname"
               autofocus required>
      </div>
      <div class="form-group">
        <label class="form-label">Tu PIN (4 dígitos)</label>
        <div class="pin-group" id="pin-group">
          <input class="pin-input" type="number" min="0" max="9" inputmode="numeric" maxlength="1" tabindex="1">
          <input class="pin-input" type="number" min="0" max="9" inputmode="numeric" maxlength="1" tabindex="2">
          <input class="pin-input" type="number" min="0" max="9" inputmode="numeric" maxlength="1" tabindex="3">
          <input class="pin-input" type="number" min="0" max="9" inputmode="numeric" maxlength="1" tabindex="4">
        </div>
      </div>
      <button type="submit" class="login-btn" id="login-btn">
        ⚽ Entrar
      </button>
    </form>

    <!-- Confirmation box (hidden until needed) -->
    <div id="confirm-box" style="display:none;margin-top:16px;background:rgba(255,152,0,.08);border:1px solid rgba(255,152,0,.3);border-radius:12px;padding:16px">
      <p style="font-size:.88rem;color:#ff9800;margin-bottom:12px;line-height:1.5">
        ⚠️ No encontramos ninguna cuenta con el nombre <strong id="confirm-name"></strong>.<br>
        ¿Es la primera vez que entras y quieres crear una cuenta nueva?
      </p>
      <div style="display:flex;gap:8px">
        <button id="confirm-yes" style="flex:1;padding:11px;border-radius:8px;border:none;background:#ffd700;color:#000;font-weight:800;cursor:pointer;font-size:.9rem">
          ✅ Sí, crear cuenta
        </button>
        <button id="confirm-no" style="flex:1;padding:11px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:transparent;color:#e0eaff;font-weight:600;cursor:pointer;font-size:.9rem">
          ✏️ Corregir nombre
        </button>
      </div>
    </div>

    <p class="login-note" id="login-note">
      Si ya tienes cuenta, introduce tu nombre exactamente como lo registraste y tu PIN.
    </p>
  </div>
</div>
<script src="assets/app.js"></script>
<script>
async function doLogin(name, pin, confirmNew = false) {
  const btn = document.getElementById('login-btn');
  const err = document.getElementById('login-error');

  btn.disabled = true;
  btn.textContent = 'Comprobando…';

  try {
    const fd = new FormData();
    fd.append('name', name);
    fd.append('pin', pin);
    if (confirmNew) fd.append('confirm_new', '1');

    const res  = await fetch('api/auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
      btn.textContent = '🎉 Entrando…';
      window.location.href = 'partidos.php';
      return;
    }

    // New user — needs confirmation
    if (data.is_new) {
      document.getElementById('confirm-name').textContent = '"' + name + '"';
      document.getElementById('confirm-box').style.display = '';
      document.getElementById('login-note').style.display = 'none';
      btn.disabled = false;
      btn.textContent = '⚽ Entrar';
      return;
    }

    // Error
    err.textContent = data.error || 'Error desconocido';
    err.style.display = '';
    btn.disabled = false;
    btn.textContent = '⚽ Entrar';

  } catch {
    err.textContent = 'Error de conexión. Inténtalo de nuevo.';
    err.style.display = '';
    btn.disabled = false;
    btn.textContent = '⚽ Entrar';
  }
}

function getPinValue() {
  return Array.from(document.querySelectorAll('.pin-input')).map(p => p.value).join('');
}

document.getElementById('login-form').addEventListener('submit', e => {
  e.preventDefault();
  const err  = document.getElementById('login-error');
  const name = document.getElementById('name').value.trim();
  const pin  = getPinValue();

  err.style.display = 'none';
  document.getElementById('confirm-box').style.display = 'none';
  document.getElementById('login-note').style.display = '';

  if (!name) { err.textContent = 'Escribe tu nombre'; err.style.display = ''; return; }
  if (pin.length !== 4) { err.textContent = 'El PIN debe ser de 4 dígitos'; err.style.display = ''; return; }

  doLogin(name, pin, false);
});

document.getElementById('confirm-yes').addEventListener('click', () => {
  const name = document.getElementById('name').value.trim();
  const pin  = getPinValue();
  document.getElementById('confirm-box').style.display = 'none';
  doLogin(name, pin, true);
});

document.getElementById('confirm-no').addEventListener('click', () => {
  document.getElementById('confirm-box').style.display = 'none';
  document.getElementById('login-note').style.display = '';
  document.getElementById('name').focus();
  document.getElementById('name').select();
});
</script>
</body>
</html>

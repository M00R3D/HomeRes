<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
  <main class="auth-page">
    <div class="card">
      <h1 class="title">Iniciar sesión</h1>

      @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif

      @if($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" class="form">
        @csrf
        <label class="field">
          <span class="label-text">Correo electrónico</span>
          <input type="email" name="email" value="{{ old('email') }}" required autocomplete="off" />
        </label>

        <label class="field">
          <span class="label-text">Contraseña</span>
          <input type="password" name="password" required />
        </label>

        <label class="remember">
          <input type="checkbox" name="remember"/>
          <span>Recordarme</span>
        </label>

        <button class="btn" type="submit">Iniciar sesión</button>

        <p class="links">
          <a href="#" id="open-register">¿No tienes cuenta? Regístrate</a> ·
          <a href="#" id="forgot-password">¿Olvidaste tu contraseña?</a>
        </p>
      </form>
    </div>
  </main>

  <!-- Modal registro -->
  <div id="modal-register" class="modal" aria-hidden="true">
    <div class="modal-backdrop" id="close-register"></div>
    <div class="modal-panel">
      <button class="modal-close" id="modal-close">&times;</button>
      <h2>Registro</h2>

      <form method="POST" action="{{ route('register.post') }}" class="form">
        @csrf
        <label class="field">
          <span class="label-text">Nombre</span>
          <input type="text" name="nombre" required />
        </label>

        <label class="field">
          <span class="label-text">Apellido</span>
          <input type="text" name="apellido" />
        </label>

        <label class="field">
          <span class="label-text">Correo electrónico</span>
          <input type="email" name="email" required />
        </label>

        <label class="field">
          <span class="label-text">Contraseña</span>
          <input type="password" name="password" required />
        </label>

        <label class="field">
          <span class="label-text">Confirmar contraseña</span>
          <input type="password" name="password_confirmation" required />
        </label>

        <div class="modal-actions">
          <button class="btn" type="submit">Registrarse</button>
          <button type="button" class="btn alt" id="cancel-register">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // JS mínimo para modal
    (function () {
      const open = document.getElementById('open-register');
      const modal = document.getElementById('modal-register');
      const closeBtns = [document.getElementById('modal-close'), document.getElementById('close-register'), document.getElementById('cancel-register')];

      function show() { modal.setAttribute('aria-hidden', 'false'); modal.classList.add('open'); }
      function hide() { modal.setAttribute('aria-hidden', 'true'); modal.classList.remove('open'); }

      open && open.addEventListener('click', function (e) { e.preventDefault(); show(); });
      closeBtns.forEach(b => b && b.addEventListener('click', hide));
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });
    })();
  </script>
</body>
</html>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  @php
    use App\Models\Theme;
    $__theme = null;
    try { $__theme = (Theme::find(5) ?? Theme::find(1))?->toArray(); } catch (\Throwable $e) {}

    $__bg = $__theme['bg'] ?? '#f7f7f8';
    $__btn = $__theme['btn_primary'] ?? '#2b6cb0';
    $__btnAlt = $__theme['btn_alt'] ?? '#06b6d4';
    $__card = '#ffffff';
    $__accent = $__btn;
    $__muted = '#6b6b6b';

    // Determine if bg is dark
    $__bgH = ltrim($__bg, '#');
    if (strlen($__bgH) === 3) $__bgH = $__bgH[0].$__bgH[0].$__bgH[1].$__bgH[1].$__bgH[2].$__bgH[2];
    $__bgLum = (strlen($__bgH) === 6)
      ? (0.2126*hexdec(substr($__bgH,0,2)) + 0.7152*hexdec(substr($__bgH,2,2)) + 0.0722*hexdec(substr($__bgH,4,2))) / 255
      : 0.95;
    $__isDark = $__bgLum < 0.45;

    if ($__isDark) {
      $__card = '#1e293b';
      $__muted = '#94a3b8';
    }

    // Compute text color for accent button
    $__aH = ltrim($__btn, '#');
    if (strlen($__aH) === 3) $__aH = $__aH[0].$__aH[0].$__aH[1].$__aH[1].$__aH[2].$__aH[2];
    $__aLum = (strlen($__aH) === 6) ? (0.2126*hexdec(substr($__aH,0,2)) + 0.7152*hexdec(substr($__aH,2,2)) + 0.0722*hexdec(substr($__aH,4,2))) / 255 : 0.5;
    $__btnText = ($__aLum < 0.5) ? '#ffffff' : '#111827';
  @endphp
  <style>
    :root {
      --bg: {{ $__bg }};
      --card: {{ $__card }};
      --accent: {{ $__accent }};
      --accent-contrast: {{ $__btnText }};
      --muted: {{ $__muted }};
    }
    @if($__isDark)
    body { color: #f1f5f9; }
    .card { background: {{ $__card }}; color: #f1f5f9; }
    .label-text { color: {{ $__muted }}; }
    .remember { color: {{ $__muted }}; }
    .links { color: {{ $__muted }}; }
    input[type="email"], input[type="password"], input[type="text"] {
      background: #0f172a; color: #f1f5f9; border-color: #334155;
    }
    input:focus { border-color: {{ $__btn }}; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
    .modal-panel { background: {{ $__card }}; color: #f1f5f9; }
    .modal-panel .label-text { color: {{ $__muted }}; }
    .modal-panel input[type="email"], .modal-panel input[type="password"], .modal-panel input[type="text"] {
      background: #0f172a; color: #f1f5f9; border-color: #334155;
    }
    .btn.alt { background: #0f172a; color: #f1f5f9; }
    .alert.success { background: #064e3b; color: #a7f3d0; }
    .alert.error { background: #7f1d1d; color: #fca5a5; }
    h2 { color: #f1f5f9; }
    .modal-close { color: {{ $__muted }}; }
    @endif
    .btn:not(.alt) {
      background: linear-gradient(90deg, {{ $__btn }}, {{ $__btnAlt }});
      color: {{ $__btnText }};
    }
    @if(!empty($__theme['bg_gradient_start']) && !empty($__theme['bg_gradient_end']))
    body {
      background: linear-gradient({{ $__theme['bg_gradient_angle'] ?? 90 }}deg, {{ $__theme['bg_gradient_start'] }}, {{ $__theme['bg_gradient_end'] }});
      @if($__theme['bg_animated'] ?? false)
      background-size: 200% 200%;
      animation: animBg {{ $__theme['animation_speed'] ?? 6 }}s ease infinite;
      @endif
    }
    @keyframes animBg { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
    @endif
  </style>
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
          <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}/>
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
  <script>
    // Show persistent session error (set by interceptor) and handle login via fetch to avoid full 419 page
    document.addEventListener('DOMContentLoaded', function(){
      try {
        const msg = localStorage.getItem('session_error');
        if (msg) {
          localStorage.removeItem('session_error');
          const alert = document.createElement('div');
          alert.className = 'alert error';
          alert.textContent = msg;
          const card = document.querySelector('.card');
          if (card) card.insertBefore(alert, card.firstChild);
          else document.body.insertAdjacentElement('afterbegin', alert);
        }
      } catch (e) {}

      const form = document.querySelector('form.form');
      if (!form) return;
      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fd = new FormData(form);
        try {
          const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin'
          });

          if (res.status === 419) {
            try { localStorage.setItem('session_error', 'Tu sesión expiró. Por favor inicia sesión de nuevo.'); } catch (e) {}
            window.location = '/login';
            return;
          }

          if (!res.ok) {
            let data = null;
            try { data = await res.json(); } catch (er) {}
            const msg = (data && (data.message || (data.errors && Object.values(data.errors).flat()[0]))) || 'Error al iniciar sesión. Intenta nuevamente.';
            try { localStorage.setItem('session_error', msg); } catch (e) {}
            window.location.reload();
            return;
          }

          // success: follow redirect if any, else reload
          if (res.redirected) window.location = res.url;
          else window.location.reload();

        } catch (err) {
          try { localStorage.setItem('session_error', 'Error de conexión. Intenta de nuevo.'); } catch (e) {}
          window.location.reload();
        }
      });
    });
  </script>
</body>
</html>
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
    .help-inline {
      margin-top: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .help-q {
      width: 24px;
      height: 24px;
      border-radius: 999px;
      border: 1px solid rgba(59, 130, 246, 0.35);
      color: #1d4ed8;
      background: rgba(59, 130, 246, 0.08);
      font-weight: 700;
      line-height: 1;
      cursor: pointer;
      transition: transform .15s ease, background-color .15s ease;
    }
    .help-q:hover { transform: translateY(-1px); background: rgba(59, 130, 246, 0.16); }
    .help-link {
      color: #2563eb;
      text-decoration: underline;
      text-underline-offset: 2px;
      font-size: .93rem;
    }
    .help-viewer {
      position: fixed;
      inset: 0;
      display: none;
      z-index: 70;
    }
    .help-viewer.open { display: block; }
    .help-viewer-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(15, 23, 42, 0.42);
      backdrop-filter: blur(2px);
    }
    .help-viewer-panel {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: min(920px, 94vw);
      height: min(84vh, 760px);
      background: rgba(255, 255, 255, 0.98);
      border-radius: 16px;
      box-shadow: 0 24px 80px rgba(15, 23, 42, 0.25);
      border: 1px solid rgba(148, 163, 184, 0.3);
      overflow: hidden;
      display: grid;
      grid-template-rows: auto 1fr;
    }
    .help-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 10px 12px;
      border-bottom: 1px solid rgba(148, 163, 184, 0.3);
      background: linear-gradient(90deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.95));
    }
    .help-toolbar strong { font-size: .92rem; color: #0f172a; }
    .help-controls { display: inline-flex; gap: 6px; }
    .help-btn {
      border: 1px solid rgba(148, 163, 184, 0.65);
      background: #ffffff;
      color: #0f172a;
      border-radius: 8px;
      min-width: 34px;
      height: 32px;
      padding: 0 10px;
      cursor: pointer;
      font-weight: 600;
    }
    .help-btn:hover { background: #f8fafc; }
    .help-stage {
      position: relative;
      overflow: hidden;
      background: #f8fafc;
      touch-action: none;
      cursor: grab;
    }
    .help-stage.dragging { cursor: grabbing; }
    .help-image {
      position: absolute;
      top: 50%;
      left: 50%;
      max-width: 100%;
      max-height: 100%;
      user-select: none;
      transform: translate(-50%, -50%) translate(0px, 0px) scale(1);
      transform-origin: center center;
      transition: transform .08s linear;
      will-change: transform;
    }
    .help-hint {
      position: absolute;
      right: 12px;
      bottom: 10px;
      color: #334155;
      font-size: .82rem;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(148, 163, 184, 0.4);
      padding: 4px 8px;
      border-radius: 999px;
    }
    @if($__isDark)
    .help-q { color: #93c5fd; border-color: rgba(147, 197, 253, 0.4); background: rgba(59, 130, 246, 0.2); }
    .help-link { color: #93c5fd; }
    .help-viewer-panel { background: rgba(30, 41, 59, 0.98); border-color: #334155; }
    .help-toolbar { background: linear-gradient(90deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95)); border-bottom-color: #334155; }
    .help-toolbar strong, .help-hint { color: #cbd5e1; }
    .help-btn { background: #0f172a; color: #e2e8f0; border-color: #334155; }
    .help-btn:hover { background: #111827; }
    .help-stage { background: #0b1220; }
    .help-hint { background: rgba(15, 23, 42, 0.86); border-color: #334155; }
    @endif
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
    <div class="help-inline">
      <button type="button" class="help-q" id="open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="help-link" id="open-help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>

    <div class="card">
      <h1 class="title">Iniciar sesión</h1>

      @if(request()->query('session_closed'))
        <div class="alert error">Tu sesión se cerró porque cerraste la pestaña o la ventana. Por seguridad necesitas iniciar sesión de nuevo.</div>
      @endif

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
          <input type="text" name="apellido" required />
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

    <div id="help-viewer" class="help-viewer" aria-hidden="true">
      <div class="help-viewer-backdrop" id="help-backdrop"></div>
      <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de uso">
        <div class="help-toolbar">
          <strong>Guía rápida de inicio de sesión y registro</strong>
          <div class="help-controls">
            <button type="button" class="help-btn" id="zoom-out" aria-label="Alejar">-</button>
            <button type="button" class="help-btn" id="zoom-reset" aria-label="Restablecer zoom">100%</button>
            <button type="button" class="help-btn" id="zoom-in" aria-label="Acercar">+</button>
            <button type="button" class="help-btn" id="close-help" aria-label="Cerrar ayuda">Cerrar</button>
          </div>
        </div>
        <div class="help-stage" id="help-stage">
          <img
            id="help-image"
            class="help-image"
            src="{{ asset('tutorial_imgs/no-admin/IniciarSesionyRegistrarse.png') }}"
            alt="Tutorial para iniciar sesión y registrarse"
            draggable="false"
          />
          <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
        </div>
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

            if (res.status === 403) {
              const ct = (res.headers.get('content-type') || '').toLowerCase();
              if (ct.includes('text/html')) {
                const html = await res.text();
                document.open();
                document.write(html);
                document.close();
                return;
              }
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

    <script>
      // Intercept register form submit and show inline validation errors without closing modal
      document.addEventListener('DOMContentLoaded', function(){
        const regForm = document.querySelector('#modal-register form');
        if (!regForm) return;

        // disable browser native validation so we can show consistent messages
        regForm.noValidate = true;

        function clearErrors() {
          regForm.querySelectorAll('.field-error').forEach(e => e.remove());
          const top = regForm.querySelector('.alert'); if (top) top.remove();
        }

        function showErrors(errors) {
          clearErrors();
          for (const key in errors) {
            if (!Object.prototype.hasOwnProperty.call(errors, key)) continue;
            const msgs = errors[key];
            const input = regForm.querySelector('[name="' + key + '"]');
            const el = document.createElement('div');
            el.className = 'field-error';
            el.style.color = '#b91c1c';
            el.style.marginTop = '6px';
            el.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            if (input && input.parentNode) input.parentNode.appendChild(el);
            else regForm.insertBefore(el, regForm.firstChild);
          }
        }

        function clientValidate() {
          const errs = {};
          const fNombre = regForm.querySelector('[name="nombre"]');
          const fApellido = regForm.querySelector('[name="apellido"]');
          const fEmail = regForm.querySelector('[name="email"]');
          const fPass = regForm.querySelector('[name="password"]');
          const fPassc = regForm.querySelector('[name="password_confirmation"]');

          if (!fNombre || !fNombre.value.trim()) errs['nombre'] = ['El nombre es obligatorio.'];
          if (!fApellido || !fApellido.value.trim()) errs['apellido'] = ['El apellido es obligatorio.'];
          if (!fEmail || !fEmail.value.trim()) errs['email'] = ['El correo electrónico es obligatorio.'];
          else if (!/^\S+@\S+\.\S+$/.test(fEmail.value.trim())) errs['email'] = ['Introduce un correo electrónico válido.'];
          if (!fPass || !fPass.value) errs['password'] = ['La contraseña es obligatoria.'];
          else if (fPass.value.length < 6) errs['password'] = ['La contraseña debe tener al menos 6 caracteres.'];
          if (!fPassc || fPassc.value !== fPass.value) errs['password_confirmation'] = ['Las contraseñas no coinciden.'];

          return Object.keys(errs).length ? errs : null;
        }

        regForm.addEventListener('submit', async function(e){
          e.preventDefault();
          clearErrors();
          const clientErrs = clientValidate();
          if (clientErrs) { showErrors(clientErrs); return; }

          const fd = new FormData(regForm);
          try {
            const res = await fetch(regForm.action, {
              method: 'POST',
              headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
              body: fd,
              credentials: 'same-origin'
            });

            if (res.status === 422) {
              let data = null;
              try { data = await res.json(); } catch (err) { }
              if (data && data.errors) { showErrors(data.errors); return; }
              showErrors({ _ : ['Datos inválidos'] });
              return;
            }

            if (!res.ok) {
              const text = await res.text();
              const alert = document.createElement('div');
              alert.className = 'alert error';
              alert.textContent = (text && text.length < 300) ? text : 'Error al registrar. Intenta de nuevo.';
              regForm.insertBefore(alert, regForm.firstChild);
              return;
            }

            // success
            if (res.redirected) window.location = res.url; else window.location.reload();

          } catch (err) {
            const alert = document.createElement('div');
            alert.className = 'alert error';
            alert.textContent = 'Error de conexión. Intenta de nuevo.';
            regForm.insertBefore(alert, regForm.firstChild);
          }
        });
      });
    </script>

    <script>
      // Help image viewer: elegant modal with zoom, pan and close controls
      document.addEventListener('DOMContentLoaded', function () {
        const viewer = document.getElementById('help-viewer');
        const stage = document.getElementById('help-stage');
        const img = document.getElementById('help-image');
        const openBtn = document.getElementById('open-help-btn');
        const openLink = document.getElementById('open-help-link');
        const closeBtn = document.getElementById('close-help');
        const backdrop = document.getElementById('help-backdrop');
        const zoomIn = document.getElementById('zoom-in');
        const zoomOut = document.getElementById('zoom-out');
        const zoomReset = document.getElementById('zoom-reset');

        if (!viewer || !stage || !img) return;

        let isOpen = false;
        let pushedHistory = false;
        let scale = 1;
        let x = 0;
        let y = 0;
        let dragging = false;
        let startX = 0;
        let startY = 0;
        const MIN_ZOOM = 1;
        const MAX_ZOOM = 4;

        function applyTransform() {
          img.style.transform = 'translate(-50%, -50%) translate(' + x + 'px, ' + y + 'px) scale(' + scale + ')';
          zoomReset.textContent = Math.round(scale * 100) + '%';
        }

        function setZoom(nextZoom) {
          scale = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, nextZoom));
          if (scale === 1) {
            x = 0;
            y = 0;
          }
          applyTransform();
        }

        function openViewer() {
          if (isOpen) return;
          isOpen = true;
          viewer.classList.add('open');
          viewer.setAttribute('aria-hidden', 'false');
          setZoom(1);

          try {
            if (!history.state || !history.state.helpViewerOpen) {
              history.pushState({ helpViewerOpen: true }, '');
              pushedHistory = true;
            } else {
              pushedHistory = false;
            }
          } catch (e) {
            pushedHistory = false;
          }
        }

        function closeViewer(fromPopState) {
          if (!isOpen) return;
          isOpen = false;
          viewer.classList.remove('open');
          viewer.setAttribute('aria-hidden', 'true');
          dragging = false;
          stage.classList.remove('dragging');

          if (!fromPopState && pushedHistory) {
            pushedHistory = false;
            try { history.back(); } catch (e) {}
          }
        }

        function beginDrag(clientX, clientY) {
          if (scale <= 1) return;
          dragging = true;
          startX = clientX;
          startY = clientY;
          stage.classList.add('dragging');
        }

        function moveDrag(clientX, clientY) {
          if (!dragging) return;
          x += clientX - startX;
          y += clientY - startY;
          startX = clientX;
          startY = clientY;
          applyTransform();
        }

        function endDrag() {
          dragging = false;
          stage.classList.remove('dragging');
        }

        [openBtn, openLink].forEach(function (el) {
          if (!el) return;
          el.addEventListener('click', function (e) {
            e.preventDefault();
            openViewer();
          });
        });

        closeBtn && closeBtn.addEventListener('click', function () { closeViewer(false); });
        backdrop && backdrop.addEventListener('click', function () { closeViewer(false); });

        zoomIn && zoomIn.addEventListener('click', function () { setZoom(scale + 0.2); });
        zoomOut && zoomOut.addEventListener('click', function () { setZoom(scale - 0.2); });
        zoomReset && zoomReset.addEventListener('click', function () { setZoom(1); });

        stage.addEventListener('wheel', function (e) {
          if (!isOpen) return;
          e.preventDefault();
          const delta = e.deltaY < 0 ? 0.18 : -0.18;
          setZoom(scale + delta);
        }, { passive: false });

        stage.addEventListener('mousedown', function (e) { beginDrag(e.clientX, e.clientY); });
        window.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
        window.addEventListener('mouseup', endDrag);

        stage.addEventListener('touchstart', function (e) {
          if (!e.touches || !e.touches[0]) return;
          beginDrag(e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: true });
        stage.addEventListener('touchmove', function (e) {
          if (!dragging || !e.touches || !e.touches[0]) return;
          moveDrag(e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: true });
        stage.addEventListener('touchend', endDrag, { passive: true });

        stage.addEventListener('dblclick', function () {
          setZoom(scale > 1 ? 1 : 2);
        });

        document.addEventListener('keydown', function (e) {
          if (!isOpen) return;
          if (e.key === 'Escape') closeViewer(false);
          if (e.key === '+' || e.key === '=') setZoom(scale + 0.2);
          if (e.key === '-') setZoom(scale - 0.2);
        });

        window.addEventListener('popstate', function () {
          if (isOpen) {
            pushedHistory = false;
            closeViewer(true);
          }
        });

        applyTransform();
      });
    </script>
  </body>
  </html>
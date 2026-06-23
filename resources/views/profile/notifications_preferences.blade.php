@extends('layouts.app')

@section('title','Preferencias de notificaciones')

  <style>
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
  </style>
@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Preferencias de notificaciones</h1>
  <div class="help-inline">
      <button type="button" class="help-q" id="open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="help-link" id="open-help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif
  @if($errors->any())<div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ $errors->first() }}</div>@endif

  @php $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin'); @endphp

  <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.04);">
    <h2>Canales de notificación</h2>
    @php
      $inAppEnabled = $prefs ? (bool) ($prefs->channel_inapp ?? false) : true;
      $pushEnabled = $prefs ? (bool) ($prefs->receive_push ?? false) : true;
    @endphp
    <form method="POST" action="{{ route('notifications.preferences.save') }}" style="margin:0 0 14px 0;padding:12px;border:1px solid #eef2f7;border-radius:10px;background:#f8fafc;">
      @csrf
      <input type="hidden" name="settings_scope" value="notifications">
      <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="display:flex;gap:8px;align-items:center;">
          <input type="checkbox" name="channel_inapp" value="1" {{ $inAppEnabled ? 'checked' : '' }}>
          <span>Recibir notificaciones in app</span>
        </label>
      </div>
      <div style="margin-top:10px;">
        <button class="btn" type="submit">Guardar preferencias de notificación</button>
      </div>
    </form>

    <h2>Información de cuenta</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start;margin-bottom:12px;">
      <div>
        <div style="font-weight:700">Nombre</div>
        <div>{{ $currentUser->nombre ?? '-' }}</div>
      </div>
      <div>
        <div style="font-weight:700">Apellido</div>
        <div>{{ $currentUser->apellido ?? '-' }}</div>
      </div>
      <div>
        <div style="font-weight:700">Email</div>
        <div>{{ $currentUser->email ?? '-' }}</div>
      </div>
      
    </div>

      <div style="margin-top:8px;">
        <div style="display:flex;gap:8px;align-items:center">
          @if($isAdmin)
          <div style="font-weight:700;margin-bottom:6px;">Contraseña</div>
        <input id="pw-mask" type="password" value="********" disabled style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;min-width:240px;">
          <button id="btn-toggle-edit" type="button" class="action-btn view">Editar</button>
        @endif
      </div>

      <div id="pw-edit-area" style="display:none;margin-top:12px;border-top:1px dashed #eef2f7;padding-top:12px;">
        <form method="POST" action="{{ route('notifications.updatePassword') }}">
          @csrf
          <div style="display:flex;flex-direction:column;gap:8px;max-width:420px">
            @if($isAdmin)
              <div style="color:#6b7280;font-size:0.95rem;">Nota: por seguridad las contraseñas están almacenadas en forma segura y no pueden mostrarse en texto plano. Puedes establecer una nueva contraseña a continuación.</div>

              <label>Nueva contraseña<br><input name="new_password" type="password" class="pw-input" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee;"></label>
              <label>Confirmar nueva contraseña<br><input name="new_password_confirmation" type="password" class="pw-input" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee;"></label>

              <label style="display:flex;align-items:center;gap:8px;"><input id="pw-reveal" type="checkbox"> Mostrar contraseñas</label>
              <div style="display:flex;gap:8px;">
                <button type="submit" class="action-btn primary">Guardar contraseña</button>
                <button id="pw-cancel" type="button" class="action-btn view">Cancelar</button>
              </div>
            @else
              <div style="color:#6b7280;font-size:0.95rem;">Por seguridad no es posible mostrar la contraseña en texto plano ni editarla desde aquí. Si necesitas cambiarla usa la opción de recuperar contraseña o contacta al administrador.</div>
              <div style="margin-top:8px;"><button id="pw-close-only" type="button" class="action-btn view">Cerrar</button></div>
            @endif
          </div>
        </form>
      </div>
    </div>
  </div>

  @if($isAdmin)
    <div style="margin-top:14px;">
      <hr />
      <h3>Admin — Preferencias y edición</h3>
      <form method="POST" action="{{ route('notifications.preferences.save') }}">
        @csrf
        <input type="hidden" name="settings_scope" value="admin_profile">
        <div style="display:flex;flex-direction:column;gap:12px;">
          <label>Nombre<br><input type="text" name="user_nombre" value="{{ old('user_nombre', $currentUser->nombre ?? '') }}" style="width:100%"></label>
          <label>Apellido<br><input type="text" name="user_apellido" value="{{ old('user_apellido', $currentUser->apellido ?? '') }}" style="width:100%"></label>
          <label>Email<br><input type="email" name="user_email" value="{{ old('user_email', $currentUser->email ?? '') }}" style="width:100%"></label>

        

          <button class="btn" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  @endif
</div>
@endsection
<div id="help-viewer" class="help-viewer" aria-hidden="true">
      <div class="help-viewer-backdrop" id="help-backdrop"></div>
      <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de uso">
        <div class="help-toolbar">
          <strong>Guía rápida de la pantalla Mi Perfil</strong>
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
            src="{{ asset('tutorial_imgs/no-admin/Perfil.png') }}"
            alt="Tutorial para la pantalla Mi Perfil"
            draggable="false"
          />
          <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
        </div>
      </div>
    </div>

@push('scripts')
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
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Toggle password edit area
  const btnToggle = document.getElementById('btn-toggle-edit');
  const pwEdit = document.getElementById('pw-edit-area');
  const pwCancel = document.getElementById('pw-cancel');
  const pwReveal = document.getElementById('pw-reveal');
  const pwInputs = document.querySelectorAll('.pw-input');
  if(btnToggle){
    btnToggle.addEventListener('click', function(){
      // If admin, toggle edit area; if not admin, show view-only message area
      var isAdmin = {{ $isAdmin ? 'true' : 'false' }};
      if(isAdmin){
        if(pwEdit.style.display === 'none' || pwEdit.style.display === '') pwEdit.style.display = 'block';
        else pwEdit.style.display = 'none';
      } else {
        // show edit area (contains view-only message) when non-admin clicks Ver
        pwEdit.style.display = 'block';
      }
    });
  }
  if(pwCancel){ pwCancel.addEventListener('click', function(){ pwEdit.style.display = 'none'; }); }
  // close-only button for non-admins
  var pwCloseOnly = document.getElementById('pw-close-only');
  if(pwCloseOnly){ pwCloseOnly.addEventListener('click', function(){ pwEdit.style.display = 'none'; }); }
  if(pwReveal){ pwReveal.addEventListener('change', function(){ pwInputs.forEach(i => i.type = this.checked ? 'text' : 'password'); }); }
});
</script>
@endpush

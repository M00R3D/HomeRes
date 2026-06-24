@extends('layouts.app')

@section('content')
  <style>
    .hp-help-inline{display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;position:relative;z-index:99999}
    .hp-help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
    .hp-help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
    .hp-help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem}
    .hp-help-viewer{position:fixed;inset:0;display:none;z-index:1500}
    .hp-help-viewer.open{display:block}
    .hp-help-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.42);backdrop-filter:blur(2px)}
    .hp-help-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(920px,94vw);height:min(84vh,760px);background:rgba(255,255,255,.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.25);border:1px solid rgba(148,163,184,.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
    .hp-help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,.3);background:linear-gradient(90deg,rgba(248,250,252,.95),rgba(241,245,249,.95))}
    .hp-help-toolbar strong{font-size:.92rem;color:#0f172a}
    .hp-help-controls{display:inline-flex;gap:6px}
    .hp-help-btn{border:1px solid rgba(148,163,184,.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
    .hp-help-btn:hover{background:#f8fafc}
    .hp-help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
    .hp-help-stage.dragging{cursor:grabbing}
    .hp-help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear;will-change:transform}
    .hp-help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,.86);border:1px solid rgba(148,163,184,.4);padding:4px 8px;border-radius:999px}
    .table td.btn-group-col { background: transparent !important; }
    .actions-inline { background: transparent !important; }
    .actions-inline form { background: transparent !important; margin: 0 !important; }
    .users-table .actions-inline { flex-wrap: wrap; }

    @media (max-width: 980px) {
      .users-table { table-layout: fixed; }
      .users-table th,
      .users-table td { font-size: 13px; }
      .users-table .actions-inline { gap: 6px !important; }
      .users-table .actions-inline .action-btn { padding: 6px 8px; font-size: 12px; }
    }

    @media (max-width: 768px) {
      .table-responsive { overflow-x: visible !important; }

      .users-table,
      .users-table thead,
      .users-table tbody,
      .users-table tr,
      .users-table th,
      .users-table td {
        display: block;
        width: 100%;
      }

      .users-table thead {
        display: none;
      }

      .users-table tr {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(2,6,23,0.04);
      }

      .users-table td {
        border: 0 !important;
        padding: 6px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        text-align: right;
        overflow-wrap: anywhere;
      }

      .users-table td::before {
        content: attr(data-label);
        color: #6b7280;
        font-weight: 700;
        text-align: left;
        white-space: nowrap;
        flex: 0 0 42%;
      }

      .users-table td.btn-group-col {
        display: block;
        text-align: left;
        padding-top: 8px;
        border-top: 1px dashed #e5e7eb !important;
        margin-top: 6px;
      }

      .users-table td.btn-group-col::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 8px;
      }

      .users-table td.btn-group-col .actions-inline {
        display: flex !important;
        gap: 6px !important;
        align-items: stretch !important;
      }

      .users-table td.btn-group-col .actions-inline form,
      .users-table td.btn-group-col .actions-inline a {
        width: calc(50% - 3px);
      }

      .users-table td.btn-group-col .actions-inline .action-btn,
      .users-table td.btn-group-col .actions-inline a.action-btn {
        width: 100%;
        text-align: center;
        justify-content: center;
      }
    }
  </style>

  <div class="page-header">
    <h1>Usuarios</h1>
    <div class="actions">
      <button id="open-new" class="btn-primary">Nuevo Usuario</button>
    </div>
      <div class="hp-help-inline" style="margin-top:6px;">
        <button type="button" class="hp-help-q" id="users-help-open-btn-admin" aria-label="Abrir ayuda" onclick="(function(){var v=document.getElementById('users-help-viewer-admin');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">?</button>
        <a href="#" class="hp-help-link" id="users-help-open-link-admin" onclick="(function(){var v=document.getElementById('users-help-viewer-admin');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">¿Necesitas ayuda para usar esta página?</a>
      </div>
  </div>
  
  
  

  @if(session('success'))
    <div class="card" style="margin-bottom:12px;">
      <div class="muted">{{ session('success') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="card" style="margin-bottom:12px;">
      <div class="alert error">{{ $errors->first() }}</div>
    </div>
  @endif

  <div class="card table-card">
    <div class="table-responsive">
      <table class="table users-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Tarjeta</th>
            <th>Intentos CVV</th>
            <th>Bloqueo</th>
            <th>Estado</th>
            <th>Rol</th>
            <th>Área</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users ?? [] as $user)
            <tr data-user-id="{{ $user->id }}">
              <td data-label="Nombre">{{ $user->nombre }}</td>
              <td data-label="Apellido">{{ $user->apellido }}</td>
              <td data-label="Email">{{ $user->email }}</td>
              <td data-label="Tarjeta">{{ optional($user->tarjeta)->numero_tarjeta ?? '-' }}</td>
              <td data-label="Intentos CVV">{{ $user->intentos_cvv ?? 0 }}</td>
              <td class="col-bloqueo" data-label="Bloqueo">{{ ($user->bloqueo_tarjetas ?? false) ? 'Bloqueado' : 'Activo' }}</td>
              <td data-label="Estado">
                @if($user->baneado ?? false)
                  <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700;">Baneado</span>
                @else
                  <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700;">Activo</span>
                @endif
              </td>
              <td data-label="Rol">{{ $user->rol }}</td>
              <td data-label="Area">{{ $user->area ?? '-' }}</td>
              <td class="btn-group-col" data-label="Acciones">
                <div class="actions-inline" style="display:flex;gap:8px;align-items:center;background:transparent !important;padding:0 !important;border:0 !important;box-shadow:none !important;">
                  <a class="action-btn edit" href="{{ route('users.edit', $user->id) }}">Editar</a>

                  <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="action-btn delete" type="submit" data-confirm="¿Borrar usuario {{ addslashes($user->nombre) }}?">Borrar</button>
                  </form>
                  <form method="POST" action="{{ route('users.toggleBloqueo', $user->id) }}" style="display:inline;" data-ajax-toggle>
                    @csrf
                    @if($user->bloqueo_tarjetas ?? false)
                      <button class="action-btn" type="submit" data-confirm="¿Quitar bloqueo de pagos a {{ addslashes($user->nombre) }}?" data-confirm-title="Confirmar desbloqueo de pagos" data-confirm-ok="Quitar bloqueo" data-confirm-ok-class="btn">Quitar bloqueo</button>
                    @else
                      <button class="action-btn" type="submit" data-confirm="¿Bloquear pagos con tarjeta para {{ addslashes($user->nombre) }}?" data-confirm-title="Confirmar bloqueo de pagos" data-confirm-ok="Bloquear pagos" data-confirm-ok-class="btn btn-danger">Bloquear pagos</button>
                    @endif
                  </form>

                  @if(($user->rol ?? '') !== 'admin')
                  <form method="POST" action="{{ route('users.toggleBan', $user->id) }}" style="display:inline;" data-confirm-ban>
                    @csrf
                    @if($user->baneado ?? false)
                      <button class="action-btn" type="submit" style="color:#166534;" data-confirm="¿Quitar ban a {{ addslashes($user->nombre) }}?" data-confirm-title="Confirmar desbaneo" data-confirm-ok="Quitar ban" data-confirm-ok-class="btn">Desbanear</button>
                    @else
                      <button class="action-btn delete" type="submit" data-confirm="¿Banear a {{ addslashes($user->nombre) }}? No podrá iniciar sesión." data-confirm-title="Confirmar ban de usuario" data-confirm-ok="Banear" data-confirm-ok-class="btn btn-danger">Banear</button>
                    @endif
                  </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="muted">No hay usuarios registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div id="users-help-viewer-admin" class="hp-help-viewer" aria-hidden="true" style="display:none !important;">
    <div class="hp-help-backdrop" id="users-help-backdrop-admin" onclick="(function(){var v=document.getElementById('users-help-viewer-admin');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();"></div>
    <div id="users-help-panel-admin" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de usuarios (admin)">
      <div class="hp-help-toolbar">
        <strong>Guía rápida de usuarios (admin)</strong>
        <div class="hp-help-controls">
          <button type="button" class="hp-help-btn" id="users-help-zoom-out-admin" aria-label="Alejar">-</button>
          <button type="button" class="hp-help-btn" id="users-help-zoom-reset-admin" aria-label="Restablecer zoom">100%</button>
          <button type="button" class="hp-help-btn" id="users-help-zoom-in-admin" aria-label="Acercar">+</button>
          <button type="button" class="hp-help-btn" id="users-help-close-admin" aria-label="Cerrar ayuda" onclick="(function(){var v=document.getElementById('users-help-viewer-admin');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();return false;">Cerrar</button>
        </div>
      </div>
      <div id="users-help-stage-admin" class="hp-help-stage">
        <img id="users-help-image-admin" class="hp-help-image" src="{{ asset('tutorial_imgs/admin/Usuarios.png') }}" alt="Tutorial admin usuarios" draggable="false" />
        <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
      </div>
    </div>
  </div>

  <div id="modal-new" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <button class="modal-close" data-close>✕</button>
      <h3>Nuevo usuario</h3>

      <form method="POST" action="{{ route('users.store') }}" class="form">
        @csrf
        <label class="field"><span class="label-text">Nombre</span><input name="nombre" required /></label>
        <label class="field"><span class="label-text">Apellido</span><input name="apellido" /></label>
        <label class="field"><span class="label-text">Email</span><input type="email" name="email" required /></label>
        <label class="field"><span class="label-text">Contraseña</span><input type="password" name="password" required /></label>
        <label class="field"><span class="label-text">Confirmar contraseña</span><input type="password" name="password_confirmation" required /></label>
        <label class="field"><span class="label-text">Rol</span>
          <select name="rol">
            <option value="cliente">cliente</option>
            <option value="recepcionista">recepcionista</option>
            <option value="admin">admin</option>
          </select>
        </label>
        <label class="field"><span class="label-text">Área</span><input name="area" /></label>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
          <button class="btn" type="submit">Crear</button>
          <button type="button" class="btn btn-danger" data-close>Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Admin edit page used instead of inline modal -->

  <div id="confirm-overlay" class="confirm-overlay" aria-hidden="true" style="display:none;">
    <div class="confirm-card" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
      <h3 id="confirm-title" class="confirm-title">Confirmar eliminación</h3>
      <p id="confirm-msg" class="confirm-msg">¿Estás seguro que deseas eliminar este usuario?</p>
      <div class="confirm-actions">
        <button type="button" id="confirm-cancel" class="btn btn-alt">Cancelar</button>
        <button type="button" id="confirm-ok" class="btn btn-danger">Eliminar</button>
      </div>
    </div>
  </div>

  <script>
    (function(){
      function show(modal){ modal && modal.setAttribute('aria-hidden','false'); modal && (modal.style.display = 'flex'); setTimeout(()=> modal.classList.add('open'),20); }
      function hide(modal){ modal && modal.setAttribute('aria-hidden','true'); modal && (modal.classList.remove('open')); setTimeout(()=> { if (modal) modal.style.display = 'none'; },180); }

      const modalNew = document.getElementById('modal-new');
      const modalEdit = document.getElementById('modal-edit');
      const confirmOverlay = document.getElementById('confirm-overlay');
      const confirmTitle = document.getElementById('confirm-title');
      const confirmMsg = document.getElementById('confirm-msg');
      const confirmOk = document.getElementById('confirm-ok');
      const confirmCancel = document.getElementById('confirm-cancel');

      document.getElementById('open-new').addEventListener('click', function(){ show(modalNew); });

      document.querySelectorAll('[data-close]').forEach(el=>{
        el.addEventListener('click', function(){
          hide(modalNew);
          hide(modalEdit);
        });
      });

      document.querySelectorAll('[data-edit]').forEach(btn=>{
        btn.addEventListener('click', function(){
          const id = this.dataset.id;
          const nombre = this.dataset.nombre || '';
          const apellido = this.dataset.apellido || '';
          const email = this.dataset.email || '';
          const rol = this.dataset.rol || 'cliente';
          const area = this.dataset.area || '';
          const updateUrl = this.dataset.updateUrl;

          document.getElementById('e-nombre').value = nombre;
          document.getElementById('e-apellido').value = apellido;
          document.getElementById('e-email').value = email;
          document.getElementById('e-rol').value = rol;
          document.getElementById('e-area').value = area;
          const form = document.getElementById('form-edit');
          form.action = updateUrl;
          document.getElementById('e-password').value = '';
          show(modalEdit);
        });
      });

      let pendingForm = null;
      document.addEventListener('click', function(e){
        const el = e.target.closest('[data-confirm]');
        if(!el) return;
        e.preventDefault();
        const title = el.getAttribute('data-confirm-title') || 'Confirmar eliminación';
        const msg = el.getAttribute('data-confirm') || '¿Estás seguro?';
        const okLabel = el.getAttribute('data-confirm-ok') || 'Eliminar';
        const okClass = el.getAttribute('data-confirm-ok-class') || 'btn btn-danger';
        confirmTitle.textContent = title;
        confirmMsg.textContent = msg;
        confirmOk.textContent = okLabel;
        confirmOk.className = okClass;
        pendingForm = el.closest('form');
        show(confirmOverlay);
        confirmCancel.focus();
      });

      confirmCancel.addEventListener('click', function(){ pendingForm = null; hide(confirmOverlay); });
      confirmOk.addEventListener('click', function(){
        if(pendingForm){
          if (typeof pendingForm.requestSubmit === 'function') {
            pendingForm.requestSubmit();
          } else {
            pendingForm.submit();
          }
          pendingForm = null;
        }
        hide(confirmOverlay);
      });

      confirmOverlay.addEventListener('click', function(e){ if(e.target === confirmOverlay) { pendingForm = null; hide(confirmOverlay); } });
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ pendingForm = null; hide(confirmOverlay); }});
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ hide(modalNew); hide(modalEdit); }});
    })();

    @if($isAdmin ?? false)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var viewer = document.getElementById('users-help-viewer-admin');
      var stage = document.getElementById('users-help-stage-admin');
      var img = document.getElementById('users-help-image-admin');
      var openBtn = document.getElementById('users-help-open-btn-admin');
      var openLink = document.getElementById('users-help-open-link-admin');
      var closeBtn = document.getElementById('users-help-close-admin');
      var backdrop = document.getElementById('users-help-backdrop-admin');
      var zoomIn = document.getElementById('users-help-zoom-in-admin');
      var zoomOut = document.getElementById('users-help-zoom-out-admin');
      var zoomReset = document.getElementById('users-help-zoom-reset-admin');

      if (!viewer || !stage || !img) return;

      viewer.style.setProperty('display', 'none', 'important');
      viewer.classList.remove('open');

      var isOpen = false;
      var scale = 1, x = 0, y = 0;
      var dragging = false, startX = 0, startY = 0;

      function applyTransform() {
        img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
        if (zoomReset) zoomReset.textContent = Math.round(scale * 100) + '%';
      }
      function setZoom(next) {
        scale = Math.max(1, Math.min(4, next));
        if (scale === 1) { x = 0; y = 0; }
        applyTransform();
      }
      function openViewer() { if (isOpen) return; isOpen = true; viewer.classList.add('open'); viewer.setAttribute('aria-hidden', 'false'); setZoom(1); }
      function closeViewer() { if (!isOpen) return; isOpen = false; viewer.classList.remove('open'); viewer.setAttribute('aria-hidden', 'true'); dragging = false; stage.classList.remove('dragging'); }
      function toggleViewer() { if (isOpen) closeViewer(); else openViewer(); }
      function beginDrag(cx, cy) { if (scale <= 1) return; dragging = true; startX = cx; startY = cy; stage.classList.add('dragging'); }
      function moveDrag(cx, cy) { if (!dragging) return; x += cx - startX; y += cy - startY; startX = cx; startY = cy; applyTransform(); }
      function endDrag() { dragging = false; stage.classList.remove('dragging'); }

      [openBtn, openLink].forEach(function (el) { if (!el) return; el.addEventListener('click', function (e) { e.preventDefault(); if (isOpen) closeViewer(); else openViewer(); }); });
      if (closeBtn) closeBtn.addEventListener('click', closeViewer);
      if (backdrop) backdrop.addEventListener('click', closeViewer);
      viewer.addEventListener('click', function (e) { if (e.target === viewer || e.target === backdrop) closeViewer(); });
      if (zoomIn) zoomIn.addEventListener('click', function () { setZoom(scale + 0.2); });
      if (zoomOut) zoomOut.addEventListener('click', function () { setZoom(scale - 0.2); });
      if (zoomReset) zoomReset.addEventListener('click', function () { setZoom(1); });

      stage.addEventListener('wheel', function (e) { if (!isOpen) return; e.preventDefault(); setZoom(scale + (e.deltaY < 0 ? 0.18 : -0.18)); }, { passive: false });
      stage.addEventListener('mousedown', function (e) { beginDrag(e.clientX, e.clientY); });
      window.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
      window.addEventListener('mouseup', endDrag);
      stage.addEventListener('touchstart', function (e) { if (e.touches && e.touches[0]) beginDrag(e.touches[0].clientX, e.touches[0].clientY); }, { passive: true });
      stage.addEventListener('touchmove', function (e) { if (dragging && e.touches && e.touches[0]) moveDrag(e.touches[0].clientX, e.touches[0].clientY); }, { passive: true });
      stage.addEventListener('touchend', endDrag, { passive: true });
      stage.addEventListener('dblclick', function () { setZoom(scale > 1 ? 1 : 2); });
      document.addEventListener('keydown', function (e) { if (!isOpen) return; if (e.key === 'Escape') closeViewer(); if (e.key === '+' || e.key === '=') setZoom(scale + 0.2); if (e.key === '-') setZoom(scale - 0.2); });
      applyTransform();

      window.HomeResUsersHelp = { open: openViewer, close: closeViewer, toggle: toggleViewer };
    });
    </script>
    @endpush
    @endif

    (function(){
      const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      document.querySelectorAll('form[data-ajax-toggle]').forEach(form => {
        form.addEventListener('submit', function(e){
          e.preventDefault();
          const url = form.action;
          fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({})
          }).then(async r => {
            const ct = (r.headers.get('content-type') || '').toLowerCase();
            let body = null;
            if (ct.includes('application/json')) {
              try {
                body = await r.json();
              } catch (e) {
                body = null;
              }
            } else {
              // not JSON (likely HTML login/CSRF page or error). capture text for debugging.
              try { body = await r.text(); } catch (e) { body = null; }
            }
            return { status: r.status, body };
          }).then(res => {
            // If server returned HTML (e.g. login page), redirect to login/refresh so user can re-authenticate
            if (typeof res.body === 'string' && res.body.trim().startsWith('<!doctype')) {
              window.location.reload();
              return;
            }

            if (res.status === 200 && res.body && typeof res.body === 'object') {
              // update row UI
              const tr = form.closest('tr[data-user-id]');
              if (tr) {
                const bloqueoCell = tr.querySelector('.col-bloqueo');
                if (bloqueoCell) bloqueoCell.textContent = res.body.bloqueo_tarjetas ? 'Bloqueado' : 'Activo';
                const intentosCell = tr.querySelector('td:nth-child(5)');
                if (intentosCell && typeof res.body.intentos_cvv !== 'undefined') intentosCell.textContent = res.body.intentos_cvv;
                const btn = form.querySelector('button');
                if (btn) btn.textContent = res.body.bloqueo_tarjetas ? 'Quitar bloqueo' : 'Bloquear pagos';
              }
            } else {
              const msg = (res.body && res.body.message) ? res.body.message : (typeof res.body === 'string' ? res.body : 'Error al actualizar bloqueo');
              alert(msg);
            }
          }).catch(err => { console.error(err); alert('Error de red'); });
        });
      });
    })();
  </script>
@endsection
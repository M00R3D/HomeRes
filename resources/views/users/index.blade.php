@extends('layouts.app')

@section('content')
  <style>
    .table td.btn-group-col { background: transparent !important; }
    .actions-inline { background: transparent !important; }
    .actions-inline form { background: transparent !important; margin: 0 !important; }
  </style>

  <div class="page-header">
    <h1>Usuarios</h1>
    <div class="actions">
      <button id="open-new" class="btn-primary">Nuevo Usuario</button>
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
      <table class="table">
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
              <td>{{ $user->nombre }}</td>
              <td>{{ $user->apellido }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ optional($user->tarjeta)->numero_tarjeta ?? '-' }}</td>
              <td>{{ $user->intentos_cvv ?? 0 }}</td>
              <td class="col-bloqueo">{{ ($user->bloqueo_tarjetas ?? false) ? 'Bloqueado' : 'Activo' }}</td>
              <td>
                @if($user->baneado ?? false)
                  <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700;">Baneado</span>
                @else
                  <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700;">Activo</span>
                @endif
              </td>
              <td>{{ $user->rol }}</td>
              <td>{{ $user->area ?? '-' }}</td>
              <td class="btn-group-col">
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
            <tr><td colspan="6" class="muted">No hay usuarios registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
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
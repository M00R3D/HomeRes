@extends('layouts.app')

@section('title','Reservaciones')

@section('content')
<style>
.container{max-width:1400px;margin:0 auto;padding:18px;} /* aumentado para aprovechar más espacio */
.split { display:flex; gap:18px; align-items:flex-start; }
.left { flex:1.6; min-width:420px; } /* columna principal más ancha */
.right { width:360px; }
.card-wide{ background:#fff;padding:14px;border-radius:12px;box-shadow:0 12px 34px rgba(2,6,23,0.06); }
.table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; padding:8px; }
.table th, .table td{ padding:8px 10px; text-align:left; border-bottom:1px solid #f3f4f6; }
.badge { padding:6px 10px;border-radius:999px;font-weight:700;font-size:0.85rem; display:inline-block; }
.badge-pendiente{ background:#f59e0b;color:#111; }
.badge-confirmada{ background:#10b981;color:#fff; }
.badge-cancelada{ background:#ef4444;color:#fff; }
.rv-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:8px; max-height:300px; overflow:auto; padding:6px; }
.rv-item{ background:#f8fafc;padding:8px;border-radius:8px;cursor:pointer; display:flex;flex-direction:column;gap:6px; align-items:center; }
.rv-item.selected{ outline:3px solid #06b6d4; background:#ecfeff; }
.small{ font-size:0.9rem;color:#6b7280; }
.actions-row{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }

.btn-edit{
  background: linear-gradient(90deg,#6366f1,#06b6d4);
  color: #fff;
  padding: 8px 10px;
  border-radius: 8px;
  border: 0;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 28px rgba(99,102,241,0.10);
  transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
}
.btn-edit:hover{ transform: translateY(-3px); }
.btn-edit:active{ transform: translateY(-1px); }
.btn-edit:focus{ outline:3px solid rgba(99,102,241,0.12); outline-offset:2px; }
</style>

<div class="container">
  <h1>Reservaciones</h1>

  @php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = $isAdmin ?? ($currentUser && $currentUser->rol === 'admin');
  @endphp

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:8px 0;font-weight:700;">{{ session('success') }}</div>
  @endif

  <div class="split">
    <div class="left">
      <div class="card-wide" style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <h2 style="margin:0;font-size:1.05rem">Lista de reservaciones</h2>
          <button id="btn-new" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Nueva reservación</button>
        </div>

        <form id="rv-filters" method="GET" action="{{ url('/reservaciones') }}" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        </form>

        <div style="margin-top:12px;overflow:auto;">
          <table class="table" aria-label="Reservaciones">
            <thead>
              <tr>
                <th>Imagen</th>
                <th>ID</th>
                <th>Propiedad</th>
                <th>Cliente</th>
                <th>Fechas</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acciones</th>
                <th style="width:200px">Cambiar estado</th>
              </tr>
            </thead>
            <tbody>
              @forelse($reservaciones ?? [] as $r)
                <tr>
                  <td style="width:120px;">
                    @php
                      $imgPath = $r->propiedad->ruta_img ?? ($r->ruta_img ?? null);
                    @endphp
                    @if($imgPath)
                      <img src="{{ asset($imgPath) }}" alt="Imagen propiedad" style="width:100px;height:64px;object-fit:cover;border-radius:8px;">
                    @else
                      <div style="width:100px;height:64px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:8px;color:#9ca3af;font-size:12px;">
                        Sin imagen
                      </div>
                    @endif
                  </td>

                  <td>{{ $r->id }}</td>
                  <td>{{ $r->propiedad_nombre ?? ($r->propiedad->nombre ?? ($r->propiedad_id ?? '-')) }}</td>
                  <td>{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</td>
                  <td>
                    {{ isset($r->check_in) ? \Carbon\Carbon::parse($r->check_in)->format('d M Y') : '-' }}
                    {{ isset($r->check_out) ? \Carbon\Carbon::parse($r->check_out)->format('d M Y') : '-' }}
                  </td>
                  <td>${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>
                  <td>
                    @if(($r->estado ?? '') === 'pendiente') <span class="badge badge-pendiente">Pendiente</span>
                    @elseif(($r->estado ?? '') === 'confirmada') <span class="badge badge-confirmada">Confirmada</span>
                    @elseif(($r->estado ?? '') === 'cancelada') <span class="badge badge-cancelada">Cancelada</span>
                    @else <span class="badge">{{ $r->estado }}</span>
                    @endif
                  </td>

                  <td>
                    <div class="btn-group" style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
                      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn edit" title="Ver reservación #{{ $r->id }}">Ver</a>

                      <button type="button" class="action-btn edit" data-edit data-res='@json($r)' data-update-url="{{ route('reservaciones.update', $r->id) }}">Editar</button>

                      <form method="POST" action="{{ route('reservaciones.destroy', $r->id) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="action-btn delete" type="button" data-confirm="¿Eliminar reservación {{ addslashes($r->id) }}?">Eliminar</button>
                      </form>
                    </div>
                  </td>

                  <td>
                    <form id="form-change-{{ $r->id }}" action="{{ route('reservaciones.changeEstado', $r->id) }}" method="POST" style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                      @csrf
                      <input type="hidden" name="estado" value="">
                      @if(($r->estado ?? '') !== 'confirmada')
                        <button type="button" class="action-btn edit" data-change data-id="{{ $r->id }}" data-estado="confirmada" >Confirmar</button>
                      @endif
                      @if(($r->estado ?? '') !== 'pendiente')
                        <button type="button" class="action-btn ghost" data-change data-id="{{ $r->id }}" data-estado="pendiente" >Pendiente</button>
                      @endif
                      @if(($r->estado ?? '') !== 'cancelada')
                        <button type="button" class="action-btn delete" data-change data-id="{{ $r->id }}" data-estado="cancelada" >Cancelar</button>
                      @endif
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="muted">No hay reservaciones aún.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <aside class="right">
      <div class="card-wide" style="padding:12px;">
        <h2 style="margin:0;font-size:1.05rem;">Resumen</h2>

        <div style="margin-top:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;">Total</div>
            <div style="font-size:1.2rem;">{{ collect($reservaciones ?? [])->count() }}</div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
            <div style="font-weight:700;">Pendientes</div>
            <div class="badge badge-pendiente" style="font-size:0.9rem;">{{ collect($reservaciones ?? [])->where('estado','pendiente')->count() }}</div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
            <div style="font-weight:700;">Confirmadas</div>
            <div class="badge badge-confirmada" style="font-size:0.9rem;">{{ collect($reservaciones ?? [])->where('estado','confirmada')->count() }}</div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
            <div style="font-weight:700;">Canceladas</div>
            <div class="badge badge-cancelada" style="font-size:0.9rem;">{{ collect($reservaciones ?? [])->where('estado','cancelada')->count() }}</div>
          </div>
        </div>
      </div>

      <div class="card-wide" style="margin-top:12px;padding:12px;">
        <h3 style="margin:0 0 8px 0;font-size:1rem;">Atajos</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <a href="{{ route('dashboard') }}" class="small">Volver al dashboard</a>
          <a href="/propiedades" class="small">Ver propiedades</a>
          <a href="/notificaciones" class="small">Notificaciones</a>
        </div>
      </div>
    </aside>
  </div>
</div>

<div id="rv-modal" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.45);align-items:center;justify-content:center;z-index:9999;padding:12px;">
  <div style="background:#fff;border-radius:10px;padding:12px;max-width:980px;width:100%;max-height:90vh;overflow:auto;">
    <button id="rv-close" style="float:right;border:0;background:transparent;font-size:20px;">✕</button>
    <h2 id="rv-title">Nueva reservación</h2>

    <form id="rv-form" method="POST" action="{{ url('/reservaciones') }}">
      @csrf
      <input type="hidden" name="_method" id="rv-method" value="POST">
      <input type="hidden" name="id" id="rv-id" value="">

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:320px;">
          <label class="small">Propiedad</label>
          <select id="rv-propiedad" name="propiedad_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar --</option>
            @foreach($propiedades ?? [] as $p)
              <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo ?? '' }})</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Check-in</label>
          <input id="rv-checkin" name="check_in" type="date" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Check-out</label>
          <input id="rv-checkout" name="check_out" type="date" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Número de personas</label>
          <input id="rv-num" name="num_personas" type="number" min="1" value="1" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
        </div>

        <div style="flex:1;min-width:260px;">
          <label class="small">Usuario</label>
          <select id="rv-usuario" name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar cliente --</option>
            @foreach($usuarios ?? [] as $u)
              <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }}</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Total</label>
          <input id="rv-total" name="total" type="number" step="0.01" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Estado</label>
          <select id="rv-estado" name="estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="pendiente">Pendiente</option>
            <option value="confirmada">Confirmada</option>
            <option value="cancelada">Cancelada</option>
            <option value="completada">Completada</option>
          </select>

          <label class="small" style="margin-top:8px;">Nota</label>
          <textarea id="rv-nota" name="nota" rows="3" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;"></textarea>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
        <button id="rv-save" type="submit" class="btn-edit">Guardar</button>
        <button type="button" id="rv-cancel" class="btn btn-alt" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="rv-change-confirm-modal" class="modal" aria-hidden="true" style="display:none;">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="max-width:480px;">
    <button class="modal-close" data-close>✕</button>
    <h3 id="rv-cc-title">Confirmar acción</h3>
    <p id="rv-cc-msg" style="color:#6b7280;margin-top:8px;"></p>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
      <button id="rv-cc-cancel" class="btn btn-alt" type="button">Cancelar</button>
      <button id="rv-cc-ok" class="btn btn-danger" type="button">Confirmar</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

  function openModal(mode='create', data=null){
    const modal = document.getElementById('rv-modal');
    const form = document.getElementById('rv-form');
    if(!modal || !form) return;
    modal.style.display = 'flex';
    const methodInput = document.getElementById('rv-method');
    const idInput = document.getElementById('rv-id');
    if(methodInput) methodInput.value = mode === 'create' ? 'POST' : 'PUT';
    if(idInput) idInput.value = data ? data.id : '';
    form.action = mode === 'create' ? "{{ url('/reservaciones') }}" : "{{ url('/reservaciones') }}/" + (data?.id || '');
    try {
      if(data){
        const set = (id, val) => { const el = document.getElementById(id); if(el) el.value = val ?? ''; };
        set('rv-propiedad', data.propiedad_id ?? data.cabana_id ?? '');
        set('rv-checkin', data.check_in ?? '');
        set('rv-checkout', data.check_out ?? '');
        set('rv-num', data.num_personas ?? 1);
        set('rv-total', data.total ?? '');
        set('rv-estado', data.estado ?? 'pendiente');
        set('rv-nota', data.nota ?? '');
        set('rv-usuario', data.usuario_id ?? '');
      } else {
        form.reset();
      }
    } catch(e){ console.error('fill modal', e); showNotice('Error','No se pudo abrir el modal'); }
    const saveBtn = document.getElementById('rv-save');
    const disabled = (mode === 'view');
    Array.from(form.querySelectorAll('input,select,textarea,button')).forEach(el=>{
      if(el === saveBtn) return;
      el.disabled = disabled;
    });
    if(saveBtn) saveBtn.style.display = disabled ? 'none' : '';
  }

  function closeModal(){
    const modal = document.getElementById('rv-modal');
    if(modal) modal.style.display = 'none';
  }

  document.getElementById('rv-close')?.addEventListener('click', closeModal);
  document.getElementById('rv-cancel')?.addEventListener('click', closeModal);

  const changeModal = document.getElementById('rv-change-confirm-modal');
  const changeTitle = document.getElementById('rv-cc-title');
  const changeMsg = document.getElementById('rv-cc-msg');
  const changeCancel = document.getElementById('rv-cc-cancel');
  const changeOk = document.getElementById('rv-cc-ok');

  function showChangeModal(title, msg, opts = {}){
    if(!changeModal) return;
    changeTitle.textContent = title || 'Confirmar acción';
    changeMsg.textContent = msg || '';
    if(opts.okLabel) changeOk.textContent = opts.okLabel;
    else changeOk.textContent = 'Confirmar';
    if(opts.cancelLabel) changeCancel.textContent = opts.cancelLabel;
    else changeCancel.textContent = 'Cancelar';
    changeModal.style.display = 'flex';
    changeModal.setAttribute('aria-hidden','false');
    setTimeout(()=> changeModal.classList.add('open'), 10);
  }
  function hideChangeModal(){
    if(!changeModal) return;
    changeModal.classList.remove('open');
    changeModal.setAttribute('aria-hidden','true');
    setTimeout(()=> changeModal.style.display = 'none', 180);
  }

  function showNotice(title, msg){
    showChangeModal(title, msg, { okLabel: 'OK', cancelLabel: 'Cerrar' });
    pendingAction = { type: 'notice' };
  }

  let pendingAction = null; 

  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-edit], [data-view], .action-btn.delete, [data-change]');
    if(!btn) return;

    if(btn.matches('[data-edit]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('edit', data);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch(err){
        console.error(err);
        showNotice('Error','No se pudo abrir edición');
      }
      return;
    }

    if(btn.matches('[data-view]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('view', data);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch(err){
        console.error(err);
        showNotice('Error','No se pudo abrir vista');
      }
      return;
    }

    if(btn.matches('.action-btn.delete')){
      const form = btn.closest('form');
      if(!form){
        e.preventDefault();
        showNotice('Error','No se encontró el formulario para eliminar.');
        return;
      }
      e.preventDefault();
      const msg = btn.getAttribute('data-confirm') || '¿Eliminar este registro?';
      showChangeModal('Confirmar eliminación', msg, { okLabel: 'Eliminar', cancelLabel: 'Cancelar' });
      pendingAction = { type: 'delete', form: form };
      return;
    }

    if(btn.matches('[data-change]')){
      e.preventDefault();
      const id = btn.getAttribute('data-id');
      const estado = btn.getAttribute('data-estado');
      const msg = btn.getAttribute('data-msg') || ('Cambiar estado a ' + estado + '?');
      const form = document.getElementById('form-change-' + id);
      if(!form){
        showNotice('Error','Formulario para cambiar estado no encontrado');
        return;
      }
      const titleMap = { confirmada: 'Confirmar reservación', cancelada: 'Cancelar reservación', pendiente: 'Marcar como pendiente' };
      showChangeModal(titleMap[estado] || 'Confirmar acción', msg, { okLabel: 'Confirmar', cancelLabel: 'Cancelar' });
      pendingAction = { type: 'state', action: form.action, estado: estado };
      return;
    }
  });

  changeCancel?.addEventListener('click', function(){ pendingAction = null; hideChangeModal(); });

  changeOk?.addEventListener('click', async function(){
    if(!pendingAction){ hideChangeModal(); return; }

    changeOk.disabled = true;
    try {
      if(pendingAction.type === 'notice'){
        hideChangeModal();
        pendingAction = null;
        return;
      }

      if(pendingAction.type === 'delete'){
        const f = pendingAction.form;
        if(f){
          f.submit();
        } else {
          showNotice('Error','Formulario no disponible');
        }
        pendingAction = null;
        hideChangeModal();
        return;
      }

      if(pendingAction.type === 'state'){
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const payload = new URLSearchParams();
        payload.append('estado', pendingAction.estado);

        const res = await fetch(pendingAction.action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: payload.toString(),
          credentials: 'same-origin'
        });

        const ct = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json().catch(()=>({})) : {};

        if(!res.ok){
          const msg = (data && data.message) ? data.message : ('Error ' + res.status);
          showNotice('Error', 'No se pudo cambiar estado: ' + msg);
          pendingAction = null;
          return;
        }

        hideChangeModal();
        setTimeout(()=> window.location.reload(), 200);
        pendingAction = null;
        return;
      }

    } catch(err){
      console.error(err);
      showNotice('Error','Error de red al procesar la solicitud');
    } finally {
      changeOk.disabled = false;
    }
  });

  document.querySelectorAll('form[id^="form-change-"]').forEach(f => {
    f.addEventListener('submit', function(e){ e.preventDefault(); });
  });

});
</script>
@endsection

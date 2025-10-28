@extends('layouts.app')

@section('title','Reservaciones')

@section('content')
@php
  use Carbon\Carbon;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp

<style>
.container{max-width:1400px;margin:0 auto;padding:18px;}
.split { display:flex; gap:18px; align-items:flex-start; }
.left { flex:1.6; min-width:420px; }
.right { width:360px; }
.card-wide{ background:#fff;padding:14px;border-radius:12px;box-shadow:0 12px 34px rgba(2,6,23,0.06); }
.table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; padding:8px; }
.table th, .table td{ padding:8px 10px; text-align:left; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.badge { padding:6px 10px;border-radius:999px;font-weight:700;font-size:0.85rem; display:inline-block; }
.badge-pendiente{ background:#f59e0b;color:#111; }
.badge-confirmada{ background:#10b981;color:#fff; }
.badge-cancelada{ background:#ef4444;color:#fff; }
.small{ font-size:0.9rem;color:#6b7280; }
.action-btn{ padding:8px 10px;border-radius:8px;border:0;font-weight:700;cursor:pointer; }
.action-btn.view{ background:transparent;color:#2563eb;border:1px solid #e6e9ee; }
.action-btn.primary{ background:linear-gradient(90deg,#6366f1,#06b6d4);color:#fff; }
.action-btn.danger{ background:linear-gradient(90deg,#ef4444,#f97316);color:#fff; }
.rv-days { display:flex;gap:6px;flex-wrap:wrap;margin-top:8px; }
.rv-day { min-width:64px;padding:8px;border-radius:8px;text-align:center;background:#f8fafc;border:1px solid #eef2f7;font-size:12px;color:#374151; }
.rv-day .date { font-weight:800; display:block; margin-bottom:6px; }
.rv-thumb{ width:100px;height:64px;border-radius:8px;overflow:hidden;border:1px solid #eef2f7; display:flex; align-items:center; justify-content:center; }
.rv-thumb img{ width:100%;height:100%;object-fit:cover;display:block }

@if(!$isAdmin)
.rv-day { min-width:90px; padding:10px; }
.rv-thumb{ width:140px; height:90px; }
.rv-days { gap:10px; }
@endif

.btn-group-col{ display:flex;flex-direction:column;gap:8px;align-items:flex-start; }
.muted{ color:#6b7280; }

@media (max-width:1100px){
  .container{padding:12px;}
  .rv-thumb{ width:120px; height:80px; }
}
@media (max-width:900px){
  .split { flex-direction:column-reverse; gap:12px; }
  .card-wide{ padding:12px; }
  .rv-day { min-width:72px; padding:8px; font-size:11px; }
  .rv-thumb { width:120px; height:72px; }
  .table{ display:block; overflow:auto; width:100%; }
  .table th, .table td{ white-space:nowrap; }
}
@media (max-width:640px){
  .rv-days > div[style*="grid-template-columns"] { grid-template-columns: repeat(2, 1fr) !important; }
  .rv-day { min-height:48px; }
}
</style>

<div class="container">
  <h1>Reservaciones</h1>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:8px 0;font-weight:700;">{{ session('success') }}</div>
  @endif

  <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;">
    <div style="flex:1; min-width:260px;">
      <div class="card-wide">
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
    </div>

    <div style="width:320px; min-width:220px;">
      <div class="card-wide">
        <h3 style="margin:0 0 8px 0;font-size:1rem;">Atajos</h3>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
          <a href="{{ route('dashboard') }}" class="small">Volver al dashboard</a>
          <a href="/propiedades" class="small">Ver propiedades</a>
          <a href="/notificaciones" class="small">Notificaciones</a>
          @if($isAdmin)
            <a href="{{ function_exists('route') && \Illuminate\Support\Facades\Route::has('images.index') ? route('images.index') : url('/imagenes') }}" class="small">Imágenes</a>
            <a href="{{ route('tarjetas.index') ?? '#' }}" class="small">Tarjetas</a>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:0;">
    <div class="card-wide" style="margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2 style="margin:0;font-size:1.05rem">Lista de reservaciones</h2>
        @if($isAdmin)
          <button id="btn-new" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Nueva reservación</button>
        @endif
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
              @php
                $imgPath = optional($r->propiedad)->ruta_img ?? ($r->ruta_img ?? null);
                $checkIn = $r->check_in ? Carbon::parse($r->check_in) : null;
                $checkOut = $r->check_out ? Carbon::parse($r->check_out) : null;
                $daysArray = [];
                if ($checkIn && $checkOut) {
                  $d = $checkIn->copy();
                  while ($d->lt($checkOut)) {
                    $daysArray[] = $d->copy();
                    $d->addDay();
                    if (count($daysArray) > 10000) break;
                  }
                }
                $totalDays = count($daysArray);
                $maxVisible = 25;
                $showAll = $totalDays <= $maxVisible;
                $displayDays = $showAll ? $daysArray : [($daysArray[0] ?? $checkIn), ($daysArray[$totalDays-1] ?? ($checkOut ? $checkOut->copy()->subDay() : $checkIn))];
              @endphp

              <tr>
                <td style="width:120px;">
                  <div class="rv-thumb" aria-hidden="true">
                    @if($imgPath)
                      <img src="{{ asset($imgPath) }}" alt="Imagen propiedad">
                    @else
                      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;font-size:12px;">Sin imagen</div>
                    @endif
                  </div>
                </td>

                <td style="vertical-align:middle;">{{ $r->id }}</td>

                <td style="vertical-align:middle;">
                  <div style="font-weight:700;">{{ $r->propiedad->nombre ?? ($r->propiedad_nombre ?? ($r->propiedad_id ?? '-')) }}</div>
                  <div class="small">{{ optional($r->propiedad)->codigo ?? '' }}</div>
                </td>

                <td style="vertical-align:middle;">{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</td>

                <td>
                  <div style="font-weight:700;">
                    {{ $checkIn ? $checkIn->format('d M Y') : '-' }} — {{ $checkOut ? $checkOut->format('d M Y') : '-' }}
                  </div>

                  <div class="rv-days" role="list" aria-label="Fechas reserva {{ $r->id }}">
                    @if($showAll)
                      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;width:100%;">
                        @foreach($displayDays as $d)
                          @php
                            $cls = 'rv-day';
                            if ($d->isToday()) $cls .= ' today';
                            elseif ($d->lt(Carbon::today())) $cls .= ' past';
                          @endphp
                          <div class="{{ $cls }}" title="{{ $d->toDateString() }}">
                            <div class="date">{{ $d->format('d') }}</div>
                            <div style="font-size:11px;color:#6b7280;">{{ $d->format('M') }}</div>
                          </div>
                        @endforeach
                        @php
                          $cells = count($displayDays);
                          $fill = (5 - ($cells % 5)) % 5;
                        @endphp
                        @for($i=0;$i<$fill;$i++)
                          <div style="background:transparent;height:56px;border-radius:8px"></div>
                        @endfor
                      </div>
                    @else
                      <div style="display:flex;gap:8px;align-items:center;">
                        <div class="rv-day" title="{{ $displayDays[0]->toDateString() }}" style="min-width:120px;padding:10px;text-align:center;">
                          <div style="font-weight:800">{{ $displayDays[0]->format('d M Y') }}</div>
                          <div class="small">Check-in</div>
                        </div>

                        <div style="font-weight:900;color:#6b7280;">…</div>

                        <div class="rv-day" title="{{ $displayDays[1]->toDateString() }}" style="min-width:120px;padding:10px;text-align:center;">
                          <div style="font-weight:800">{{ $displayDays[1]->format('d M Y') }}</div>
                          <div class="small">Check-out</div>
                        </div>

                        <div class="small-muted" style="margin-left:auto;">Total días: {{ $totalDays }}</div>
                      </div>
                    @endif
                  </div>
                </td>

                <td style="vertical-align:middle;">${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>

                <td style="vertical-align:middle;">
                  @if(($r->estado ?? '') === 'pendiente') <span class="badge badge-pendiente">Pendiente</span>
                  @elseif(($r->estado ?? '') === 'confirmada') <span class="badge badge-confirmada">Confirmada</span>
                  @elseif(($r->estado ?? '') === 'cancelada') <span class="badge badge-cancelada">Cancelada</span>
                  @else <span class="badge">{{ $r->estado }}</span>
                  @endif
                </td>

                <td style="vertical-align:middle;">
                  @if($isAdmin)
                    <div class="btn-group-col">
                      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>

                      <button type="button" class="action-btn primary" data-edit data-res='@json($r)' data-update-url="{{ route('reservaciones.update', $r->id) }}">Editar</button>

                      <form method="POST" action="{{ route('reservaciones.destroy', $r->id) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="action-btn danger" type="button" data-confirm="¿Eliminar reservación {{ addslashes($r->id) }}?">Eliminar</button>
                      </form>
                    </div>
                  @else
                    <div class="btn-group-col">
                      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>

                      @if(in_array($r->estado, ['pendiente','confirmada']))
                        <form method="POST" action="{{ route('reservaciones.changeEstado', $r->id) }}" class="request-cancel-form" style="display:inline;">
                          @csrf
                          <input type="hidden" name="estado" value="cancelada" />
                          <button type="button" class="action-btn danger request-cancel-btn" data-id="{{ $r->id }}">Solicitar cancelación</button>
                        </form>
                      @endif
                    </div>
                  @endif
                </td>

                <td style="vertical-align:middle;">
                  @if($isAdmin)
                    <form id="form-change-{{ $r->id }}" action="{{ route('reservaciones.changeEstado', $r->id) }}" method="POST" style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                      @csrf
                      <input type="hidden" name="estado" value="">
                      @if(($r->estado ?? '') !== 'confirmada')
                        <button type="button" class="action-btn primary" data-change data-id="{{ $r->id }}" data-estado="confirmada">Confirmar</button>
                      @endif
                      @if(($r->estado ?? '') !== 'pendiente')
                        <button type="button" class="action-btn view" data-change data-id="{{ $r->id }}" data-estado="pendiente">Pendiente</button>
                      @endif
                      @if(($r->estado ?? '') !== 'cancelada')
                        <button type="button" class="action-btn danger" data-change data-id="{{ $r->id }}" data-estado="cancelada">Cancelar</button>
                      @endif
                    </form>
                  @else
                    <div class="muted">Solo administración</div>
                  @endif
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
        <button id="rv-save" type="submit" class="action-btn primary">Guardar</button>
        <button type="button" id="rv-cancel" class="action-btn view" style="background:#fff;border:1px solid #e5e7eb;">Cancelar</button>
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
      <button id="rv-cc-cancel" class="action-btn view" type="button">Cancelar</button>
      <button id="rv-cc-ok" class="action-btn danger" type="button">Confirmar</button>
    </div>
  </div>
</div>

@push('scripts')
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
    if(data){
      try {
        const set = (id, val) => { const el = document.getElementById(id); if(el) el.value = val ?? ''; };
        set('rv-propiedad', data.propiedad_id ?? data.cabana_id ?? '');
        set('rv-checkin', data.check_in ?? '');
        set('rv-checkout', data.check_out ?? '');
        set('rv-num', data.num_personas ?? 1);
        set('rv-total', data.total ?? '');
        set('rv-estado', data.estado ?? 'pendiente');
        set('rv-nota', data.nota ?? '');
        set('rv-usuario', data.usuario_id ?? '');
      } catch(e){ console.error(e); }
    } else { form.reset(); }
    const saveBtn = document.getElementById('rv-save');
    const disabled = (mode === 'view');
    Array.from(form.querySelectorAll('input,select,textarea,button')).forEach(el=>{
      if(el === saveBtn) return;
      el.disabled = disabled;
    });
    if(saveBtn) saveBtn.style.display = disabled ? 'none' : '';
  }
  function closeModal(){ const modal = document.getElementById('rv-modal'); if(modal) modal.style.display = 'none'; }

  document.getElementById('rv-close')?.addEventListener('click', closeModal);
  document.getElementById('rv-cancel')?.addEventListener('click', closeModal);
  document.getElementById('btn-new')?.addEventListener('click', function(){ openModal('create', null); });

  const changeModal = document.getElementById('rv-change-confirm-modal');
  const changeTitle = document.getElementById('rv-cc-title');
  const changeMsg = document.getElementById('rv-cc-msg');
  const changeCancel = document.getElementById('rv-cc-cancel');
  const changeOk = document.getElementById('rv-cc-ok');
  let pendingAction = null;

  function showChangeModal(title, msg, opts = {}) {
    if(!changeModal) return;
    changeTitle.textContent = title || 'Confirmar acción';
    changeMsg.textContent = msg || '';
    changeOk.textContent = opts.okLabel || 'Confirmar';
    changeCancel.textContent = opts.cancelLabel || 'Cancelar';
    changeModal.style.display = 'flex';
    changeModal.setAttribute('aria-hidden','false');
    setTimeout(()=> changeModal.classList.add('open'), 10);
  }
  function hideChangeModal(){ if(!changeModal) return; changeModal.classList.remove('open'); changeModal.setAttribute('aria-hidden','true'); setTimeout(()=> changeModal.style.display = 'none', 180); }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-edit], [data-view], [data-change], [data-confirm], .request-cancel-btn');
    if(!btn) return;

    if(btn.matches('[data-edit]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('edit', data);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch(err){ console.error(err); }
      return;
    }

    if(btn.matches('[data-view]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('view', data);
      } catch(err){ console.error(err); }
      return;
    }

    if(btn.matches('[data-change]')){
      e.preventDefault();
      const id = btn.getAttribute('data-id');
      const estado = btn.getAttribute('data-estado');
      const form = document.getElementById('form-change-' + id);
      if(!form){ alert('Formulario no encontrado'); return; }
      showChangeModal('Confirmar', 'Cambiar estado a ' + estado + '?', { okLabel: 'Confirmar', cancelLabel: 'Cancelar' });
      pendingAction = { type: 'state', action: form.action, estado: estado };
      return;
    }

    if(btn.matches('[data-confirm]') || btn.matches('.request-cancel-btn')){
      e.preventDefault();
      if(btn.matches('[data-confirm]')){
        const form = btn.closest('form');
        showChangeModal('Confirmar eliminación', btn.getAttribute('data-confirm') || '¿Eliminar?', { okLabel: 'Eliminar', cancelLabel: 'Cancelar' });
        pendingAction = { type: 'delete', form: form };
        return;
      }
      if(btn.matches('.request-cancel-btn')){
        const id = btn.getAttribute('data-id');
        const form = btn.closest('form');
        showChangeModal('Solicitar cancelación', '¿Deseas solicitar la cancelación de la reservación #' + id + '?', { okLabel: 'Solicitar', cancelLabel: 'Cancelar' });
        pendingAction = { type: 'delete-like', form: form };
        return;
      }
    }
  });

  changeCancel?.addEventListener('click', function(){ pendingAction = null; hideChangeModal(); });

  changeOk?.addEventListener('click', async function(){
    if(!pendingAction){ hideChangeModal(); return; }
    changeOk.disabled = true;
    try {
      if(pendingAction.type === 'delete' || pendingAction.type === 'delete-like'){
        const f = pendingAction.form;
        if(f) f.submit();
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
        if(!res.ok){
          alert('No se pudo cambiar estado');
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
      alert('Error procesando la solicitud');
    } finally {
      changeOk.disabled = false;
    }
  });

  document.querySelectorAll('form[id^="form-change-"]').forEach(f => { f.addEventListener('submit', function(e){ e.preventDefault(); }); });

});
</script>
@endpush
@endsection

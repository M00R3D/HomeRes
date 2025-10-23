@extends('layouts.app')

@section('title','Reservaciones')

@section('content')
<style>
.container{max-width:1200px;margin:0 auto;padding:18px;}
.split { display:flex; gap:18px; align-items:flex-start; }
.left { flex:1; min-width:420px; }
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

/* agregado: estilos para el botón Editar */
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
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            @if($isAdmin)
              <div>
                <label style="display:block;font-weight:600;">Usuario</label>
                <select name="usuario_id" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                  <option value="">Todos</option>
                  @foreach($usuarios ?? [] as $u)
                    <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>{{ $u->nombre }} {{ $u->apellido }}</option>
                  @endforeach
                </select>
              </div>
            @endif

            <div>
              <label style="display:block;font-weight:600;">Casita</label>
              <select name="cabana_id" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                <option value="">Todas</option>
                @foreach($cabanas ?? [] as $c)
                  <option value="{{ $c->id }}" {{ request('cabana_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre ?? $c->codigo ?? 'ID '.$c->id }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label style="display:block;font-weight:600;">Estado</label>
              <select name="estado" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                <option value="">Todos</option>
                <option value="pendiente" {{ request('estado')=='pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="confirmada" {{ request('estado')=='confirmada' ? 'selected' : '' }}>Confirmada</option>
                <option value="cancelada" {{ request('estado')=='cancelada' ? 'selected' : '' }}>Cancelada</option>
                <option value="completada" {{ request('estado')=='completada' ? 'selected' : '' }}>Completada</option>
              </select>
            </div>
          </div>

          <div style="display:flex;gap:8px;">
            <button type="submit" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Aplicar</button>
            <button type="button" id="rv-clear-filters" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Limpiar</button>
          </div>
        </form>

        <div style="margin-top:12px;overflow:auto;">
          <table class="table" aria-label="Reservaciones">
            <thead>
              <tr><th>ID</th><th>Casita</th><th>Usuario</th><th>Fechas</th><th>Costo</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              @forelse($reservaciones ?? [] as $r)
                <tr>
                  <td>{{ $r->id }}</td>
                  <td>{{ $r->cabana_nombre ?? ($r->cabin->nombre ?? ($r->cabana_id ?? '-')) }}</td>
                  <td>{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</td>
                  <td>{{ isset($r->check_in) ? \Carbon\Carbon::parse($r->check_in)->format('d M Y') : '-' }} → {{ isset($r->check_out) ? \Carbon\Carbon::parse($r->check_out)->format('d M Y') : '-' }}</td>
                  <td>${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>
                  <td>
                    @php $st = $r->estado ?? 'pendiente'; $cls = 'badge-'.str_replace(' ','', $st); @endphp
                    <span class="badge {{ $cls }}">{{ ucfirst($st) }}</span>
                  </td>
                  <td style="white-space:nowrap;">
                    <div class="actions-row">
                      <button type="button" class="btn-edit" data-edit data-res='@json($r)'>Editar</button>

                      <form action="{{ url('/reservaciones/'.$r->id.'/changeEstado') }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="estado" value="confirmada">
                        <button type="submit" title="Confirmar" style="background:#10b981;color:#fff;padding:6px 8px;border-radius:8px;border:0;">Confirmar</button>
                      </form>

                      <form action="{{ url('/reservaciones/'.$r->id.'/changeEstado') }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="estado" value="cancelada">
                        <button type="submit" title="Cancelar" style="background:#ef4444;color:#fff;padding:6px 8px;border-radius:8px;border:0;">Cancelar</button>
                      </form>

                      <form action="{{ url('/reservaciones/'.$r->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" data-confirm="¿Eliminar reservación #{{ $r->id }}?" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:6px 8px;border-radius:8px;border:0;">Eliminar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7">No hay reservaciones aún.</td></tr>
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

<!-- Modal crear / editar -->
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
          <label>Casita</label>
          <select name="cabana_id" id="rv-cabana" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">Selecciona</option>
            @foreach($cabanas ?? [] as $c)
              <option value="{{ $c->id }}">{{ $c->nombre ?? $c->codigo ?? 'ID '.$c->id }}</option>
            @endforeach
          </select>

          <label style="margin-top:8px;">Check-in</label>
          <input type="date" name="check_in" id="rv-checkin" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label style="margin-top:8px;">Check-out</label>
          <input type="date" name="check_out" id="rv-checkout" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label style="margin-top:8px;">Personas</label>
          <input type="number" name="num_personas" id="rv-num" min="1" value="1" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
        </div>

        <div style="flex:1;min-width:260px;">
          <label>Usuario</label>
          @if(!empty($currentUser) && ($currentUser->rol ?? '') !== 'admin')
            <input type="hidden" name="usuario_id" value="{{ $currentUser->id }}">
            <div style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;background:#fafafa;font-weight:700;">
              {{ $currentUser->nombre }} {{ $currentUser->apellido }} (Conectado)
            </div>
          @else
            <select name="usuario_id" id="rv-usuario" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
              <option value="">Selecciona</option>
              @foreach($usuarios ?? [] as $u)
                <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }}</option>
              @endforeach
            </select>
          @endif

          <label style="margin-top:8px;">Total (USD)</label>
          <input type="number" step="0.01" name="total" id="rv-total" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label style="margin-top:8px;">Estado</label>
          <select name="estado" id="rv-estado" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="pendiente">pendiente</option>
            <option value="confirmada">confirmada</option>
            <option value="cancelada">cancelada</option>
            <option value="completada">completada</option>
          </select>

          <label style="margin-top:8px;">Nota</label>
          <textarea name="nota" id="rv-nota" rows="4" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;"></textarea>

          <div style="margin-top:12px;display:flex;gap:8px;">
            <button type="submit" id="rv-save" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Guardar</button>
            <button type="button" id="rv-cancel" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Cancelar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btnNew = document.getElementById('btn-new');
  const modal = document.getElementById('rv-modal');
  const closeBtn = document.getElementById('rv-close');
  const cancelBtn = document.getElementById('rv-cancel');
  const form = document.getElementById('rv-form');
  const methodInput = document.getElementById('rv-method');
  const idInput = document.getElementById('rv-id');

  function openModal(mode='create', data=null){
    modal.style.display = 'flex';
    methodInput.value = mode === 'create' ? 'POST' : 'PUT';
    idInput.value = data ? data.id : '';
    form.action = mode === 'create' ? "{{ url('/reservaciones') }}" : "{{ url('/reservaciones') }}/" + (data?.id || '');
    // fill
    if(data){
      document.getElementById('rv-cabana').value = data.cabana_id || '';
      document.getElementById('rv-checkin').value = data.check_in || '';
      document.getElementById('rv-checkout').value = data.check_out || '';
      document.getElementById('rv-num').value = data.num_personas || 1;
      document.getElementById('rv-total').value = data.total || '';
      document.getElementById('rv-estado').value = data.estado || 'pendiente';
      document.getElementById('rv-nota').value = data.nota || '';
      const usr = document.getElementById('rv-usuario'); if(usr) usr.value = data.usuario_id || '';
    } else {
      form.reset();
      methodInput.value = 'POST';
    }
  }

  function closeModal(){ modal.style.display = 'none'; }

  btnNew && btnNew.addEventListener('click', ()=> openModal());
  closeBtn && closeBtn.addEventListener('click', closeModal);
  cancelBtn && cancelBtn.addEventListener('click', closeModal);

  document.querySelectorAll('[data-edit]').forEach(btn=>{
    btn.addEventListener('click', function(){
      try {
        const data = JSON.parse(this.getAttribute('data-res'));
        openModal('edit', data);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch(e){ console.error(e); alert('No se pudo abrir edición'); }
    });
  });

  document.getElementById('rv-clear-filters')?.addEventListener('click', function(){
    document.querySelectorAll('#rv-filters input, #rv-filters select').forEach(i=> i.value = '');
    document.getElementById('rv-filters').submit();
  });

  form?.addEventListener('submit', async function(evt){
    // set default dates if empty
    function todayStr(offset=0){ const d=new Date(); d.setDate(d.getDate()+offset); return d.toISOString().slice(0,10); }
    const ci = document.getElementById('rv-checkin');
    const co = document.getElementById('rv-checkout');
    if(ci && !ci.value) ci.value = todayStr(0);
    if(co && !co.value) co.value = todayStr(1);
    // simple validation
    const cab = document.getElementById('rv-cabana');
    if(!cab || !cab.value){ evt.preventDefault(); alert('Selecciona una casita.'); return; }
    // allow normal submit (server handling) — optionally you can convert to AJAX here
  });
});
</script>
@endsection
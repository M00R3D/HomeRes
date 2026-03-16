@extends('layouts.app')

@section('title','Editar reservación #' . ($r->id ?? ''))

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  @php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin');
  @endphp

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0">Editar reservación #{{ $r->id }}</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('reservaciones.index') }}" class="link-button">Volver a la lista</a>
    </div>
  </div>

  @if(! $isAdmin)
    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px;">Acceso denegado. Solo administradores pueden editar reservaciones.</div>
  @endif

  <form method="POST" action="{{ route('reservaciones.update', $r->id) }}" style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    @csrf
    <input type="hidden" name="_method" value="PUT">

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <div style="flex:1;min-width:260px;">
        <label class="small">Propiedad</label>
        <select name="propiedad_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="">-- seleccionar --</option>
          @foreach($propiedades as $p)
            <option value="{{ $p->id }}" {{ ($r->propiedad_id == $p->id) ? 'selected' : '' }}>{{ $p->nombre }} {{ $p->codigo ? '· ' . $p->codigo : '' }}</option>
          @endforeach
        </select>

        <label class="small" style="margin-top:8px;">Check-in</label>
        <input name="check_in" type="date" value="{{ $r->check_in }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;">Check-out</label>
        <input name="check_out" type="date" value="{{ $r->check_out }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
      </div>

      <div style="flex:1;min-width:260px;">
        <label class="small">Usuario</label>
        <select name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="">-- seleccionar cliente --</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}" {{ ($r->usuario_id == $u->id) ? 'selected' : '' }}>{{ $u->nombre }} {{ $u->apellido }}</option>
          @endforeach
        </select>

        <label class="small" style="margin-top:8px;">Número de personas</label>
        <input name="num_personas" type="number" min="1" value="{{ $r->num_personas }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;">Total</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input id="rv-total" name="total" type="number" step="0.01" value="{{ $r->total }}" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <button type="button" id="btn-recalc" class="action-btn view small" style="white-space:nowrap;">Recalcular total</button>
        </div>

        <label class="small" style="margin-top:8px;">Estado</label>
        <select name="estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="pendiente" {{ $r->estado==='pendiente' ? 'selected' : '' }}>Pendiente</option>
          <option value="confirmada" {{ $r->estado==='confirmada' ? 'selected' : '' }}>Confirmada</option>
          <option value="cancelada" {{ $r->estado==='cancelada' ? 'selected' : '' }}>Cancelada</option>
          <option value="completada" {{ $r->estado==='completada' ? 'selected' : '' }}>Completada</option>
        </select>

        <label class="small" style="margin-top:8px;">Estado pago</label>
        @php $ep = strtolower(trim((string)($r->estado_pago ?? 'pendiente'))); @endphp
        <select id="rv-estado-pago" name="estado_pago" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="pendiente" {{ $ep==='pendiente' ? 'selected' : '' }}>Pendiente</option>
          <option value="pagado" {{ $ep==='pagado' ? 'selected' : '' }}>Pagado</option>
          <option value="parcial" {{ $ep==='parcial' ? 'selected' : '' }}>Parcial</option>
          <option value="fallido" {{ ($ep==='fallido' || $ep==='failed') ? 'selected' : '' }}>Fallido</option>
        </select>

        <label class="small" style="margin-top:8px;">Nota</label>
        <textarea name="nota" rows="4" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">{{ $r->nota }}</textarea>
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>
      <button type="submit" class="action-btn primary">Guardar cambios</button>
      <a href="{{ route('reservaciones.index') }}" class="action-btn" style="background:#fff;border:1px solid #e5e7eb;">Cancelar</a>
    </div>
  </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Map of property prices: id -> precio_noche
  const propPrices = @json($propiedades->pluck('precio_noche','id'));
  const btn = document.getElementById('btn-recalc');
  const totalInput = document.getElementById('rv-total');
  const propSelect = document.querySelector('select[name="propiedad_id"]');
  const inInput = document.querySelector('input[name="check_in"]');
  const outInput = document.querySelector('input[name="check_out"]');

  function parseDate(s){ if(!s) return null; const d = new Date(s + 'T00:00:00'); return isNaN(d.getTime()) ? null : d; }
  function diffDays(a,b){ if(!a||!b) return 0; const diff = (b - a) / (1000*60*60*24); return Math.max(0, Math.round(diff)); }

  btn?.addEventListener('click', function(){
    const pid = propSelect ? propSelect.value : '';
    const price = pid ? (parseFloat(propPrices[pid] || 0)) : 0;
    const ci = parseDate(inInput ? inInput.value : '');
    const co = parseDate(outInput ? outInput.value : '');
    const days = diffDays(ci, co);
    if (!pid) { alert('Selecciona una propiedad para recalcular.'); return; }
    if (!ci || !co) { alert('Selecciona fecha de check-in y check-out válidas.'); return; }
    if (days <= 0) { alert('La fecha de check-out debe ser posterior al check-in.'); return; }
    const newTotal = (price * days).toFixed(2);
    if (totalInput) totalInput.value = newTotal;
    // small feedback
    btn.textContent = 'Recalculado: ' + newTotal;
    setTimeout(()=> { btn.textContent = 'Recalcular total'; }, 2000);
  });
});
</script>
@endsection

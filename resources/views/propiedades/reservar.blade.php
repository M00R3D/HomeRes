@extends('layouts.app')

@section('title','Solicitar reservación - ' . ($propiedad->nombre ?? ''))

@section('content')
@php use Carbon\Carbon; use App\Models\Comentario; @endphp

<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">

@php
  $comentarios = Comentario::with('user')
      ->whereHas('reservation', function($q) use ($propiedad) {
          $q->where('propiedad_id', $propiedad->id);
      })
      ->orderByDesc('fecha_creacion')
      ->get();
@endphp

<style>
.calendar { display:block; background:#fff; padding:12px; border-radius:12px; box-shadow:0 8px 24px rgba(2,6,23,0.06); }
.cal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.cal-nav button{background:transparent;border:0;font-weight:800;cursor:pointer;padding:6px 8px;border-radius:8px}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
.cal-day, .cal-weekday { height:44px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; color:#374151; }
.cal-weekday { font-weight:700;color:#6b7280 }
.cal-day { border-radius:999px; cursor:pointer; transition: transform .08s; }
.cal-day:hover { transform:translateY(-3px) }
.cal-day.disabled { background:transparent;color:#c7c9cc; cursor:not-allowed; transform:none; }
.cal-day.blocked { background:#fde8e8;color:#991b1b; cursor:not-allowed; box-shadow:inset 0 0 0 1px rgba(220,38,38,0.06); }
.cal-day.in-range { background:linear-gradient(90deg,#e6f8ff,#dbeafe); color:#064e3b; font-weight:800; }
.cal-day.start, .cal-day.end { background:linear-gradient(90deg,#06b6d4,#6366f1); color:#fff; box-shadow:0 6px 18px rgba(99,102,241,0.12) }
.cal-day.today { outline:2px solid rgba(99,102,241,0.12); }
.person-selector { display:flex; align-items:center; gap:8px; background:#fff;padding:8px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.04); }
.person-count { font-weight:800; min-width:42px; text-align:center; }
.icon-btn { background:#f3f4f6;border:0;padding:6px 8px;border-radius:8px;cursor:pointer;font-weight:800 }
.small-muted{ color:#6b7280;font-size:0.92rem }
.comment { background:#fff;padding:10px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,0.04); }
.grid-two { display:grid; grid-template-columns:1fr 360px; gap:16px; align-items:start }
.calendar-row { display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; }
.calendar-month { width:260px; }
@media (max-width:980px){ .grid-two{ grid-template-columns:1fr; } .calendar{ max-width:100% } }
</style>

<div style="max-width:980px;margin:18px auto;padding:12px;">
  <div class="grid-two">
    <div>
      <div style="background:#fff;padding:16px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
        <h2 style="margin:0 0 8px 0;">{{ $propiedad->nombre }}</h2>
        <div class="small-muted">Código: {{ $propiedad->codigo ?? '-' }} · {{ ucfirst($propiedad->tipo) }}</div>
        <div style="font-weight:900;color:#065f46;margin-top:8px;font-size:1.1rem">${{ number_format($propiedad->precio_noche ?? 0,2,',','.') }} / noche</div>

        <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-top:12px;">
          <div style="flex:0 0 360px;">
            @if(!empty($propiedad->ruta_img))
              <img id="prop-img" src="{{ asset($propiedad->ruta_img) }}" alt="{{ $propiedad->nombre }}" style="width:100%;height:260px;object-fit:cover;border-radius:10px;box-shadow:0 12px 30px rgba(2,6,23,0.06);background:#f3f4f6;">
            @else
              <div id="prop-img" style="width:100%;height:260px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:10px;color:#9ca3af;">Sin imagen</div>
            @endif
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
              @foreach(collect(explode(',', $propiedad->servicios ?? ''))->map(fn($s)=>trim($s))->filter()->values() as $s)
                <span style="background:#f3f4f6;padding:6px 10px;border-radius:999px;font-weight:700;">{{ $s }}</span>
              @endforeach
            </div>
          </div>
          <div style="flex:1;min-width:240px;">
            <div style="display:flex;gap:12px;align-items:flex-start;">
              <div class="calendar calendar-month" id="calendar-left">
                <div class="cal-head">
                  <div class="cal-nav"><button id="prev-left" aria-label="Mes anterior (check-in)">‹</button></div>
                  <div id="cal-title-left" style="font-weight:800"></div>
                  <div class="cal-nav"><button id="next-left" aria-label="Mes siguiente (check-in)">›</button></div>
                </div>
                <div class="cal-grid">
                  <div class="cal-weekday">Lun</div><div class="cal-weekday">Mar</div><div class="cal-weekday">Mié</div><div class="cal-weekday">Jue</div><div class="cal-weekday">Vie</div><div class="cal-weekday">Sáb</div><div class="cal-weekday">Dom</div>
                </div>
                <div class="cal-grid" id="cal-grid-days-left" style="margin-top:6px;"></div>
              </div>

              <div class="calendar calendar-month" id="calendar-right">
                <div class="cal-head">
                  <div class="cal-nav"><button id="prev-right" aria-label="Mes anterior (check-out)">‹</button></div>
                  <div id="cal-title-right" style="font-weight:800;text-align:center"></div>
                  <div class="cal-nav"><button id="next-right" aria-label="Mes siguiente (check-out)">›</button></div>
                </div>
                <div class="cal-grid">
                  <div class="cal-weekday">Lun</div><div class="cal-weekday">Mar</div><div class="cal-weekday">Mié</div><div class="cal-weekday">Jue</div><div class="cal-weekday">Vie</div><div class="cal-weekday">Sáb</div><div class="cal-weekday">Dom</div>
                </div>
                <div class="cal-grid" id="cal-grid-days-right" style="margin-top:6px;"></div>
              </div>
            </div>

            <div style="margin-top:12px;color:#6b7280;font-size:0.95rem;">
              Fechas ya reservadas aparecen en rojo y no son seleccionables.
            </div>

            <div id="blocked-list" style="margin-top:10px;display:flex;flex-direction:column;gap:6px;">
              @foreach($blockedRanges as $r)
                <div style="background:#fff;padding:8px;border-radius:8px;border:1px solid #eef2f7;">
                  {{ \Carbon\Carbon::parse($r['from'])->format('d M Y') }} → {{ \Carbon\Carbon::parse($r['to'])->format('d M Y') }}
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div style="margin-top:14px;">
        <h3 style="margin-bottom:8px;">Comentarios ({{ $comentarios->count() }})</h3>
        @if($comentarios->isEmpty())
          <div class="small-muted">No hay comentarios públicos para esta propiedad.</div>
        @else
          <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($comentarios as $c)
              <div class="comment">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <div style="font-weight:700;">{{ $c->user->nombre ?? 'Usuario' }} {{ $c->user->apellido ?? '' }}</div>
                  <div class="small-muted">{{ \Carbon\Carbon::parse($c->fecha_creacion ?? now())->format('d M Y') }}</div>
                </div>
                <div style="margin-top:8px;color:#374151;">
                  <div style="font-weight:700;margin-bottom:6px;">Calificación: {{ $c->calificacion ?? '-' }}/5</div>
                  <div style="white-space:pre-wrap;">{{ $c->comentario }}</div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
    <div>
      <div style="background:#fff;padding:16px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);min-width:280px;">
        <h3 style="margin-top:0;">Reservar</h3>
        <form id="reserve-form" method="POST" action="{{ route('reservaciones.store') }}">
          @csrf
          <input type="hidden" name="propiedad_id" value="{{ $propiedad->id }}">
          <input type="hidden" name="usuario_id" value="{{ auth()->id() ?? '' }}">
          <input type="hidden" name="total" id="total-hidden" value="">
          <label class="field"><span class="label-text">Check-in</span>
            <input id="check-in" name="check_in" type="date" required readonly />
          </label>
          <label class="field"><span class="label-text">Check-out</span>
            <input id="check-out" name="check_out" type="date" required readonly />
          </label>

          <div style="margin-top:8px;">
            <div class="small-muted">Personas</div>
            <div class="person-selector" style="margin-top:6px;">
              <button type="button" id="person-decr" class="icon-btn" aria-label="Disminuir">−</button>
              <div class="person-count" id="person-count">1</div>
              <button type="button" id="person-incr" class="icon-btn" aria-label="Aumentar">+</button>
              <div style="margin-left:8px;color:#6b7280;display:flex;align-items:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="vertical-align:middle"><path d="M12 12a3 3 0 100-6 3 3 0 000 6z" stroke="#374151" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20a6 6 0 0116 0" stroke="#374151" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span style="margin-left:6px;" id="person-label">1 persona</span>
              </div>
              <input type="hidden" name="num_personas" id="num-personas" value="1">
            </div>
          </div>

          <div style="margin-top:12px;">
            <div class="small-muted">Precio calculado</div>
            <div style="font-weight:900;font-size:1.2rem;margin-top:6px;" id="price-display">$0.00</div>
            <div style="color:#6b7280;margin-top:6px;font-size:0.95rem;" id="price-breakdown"></div>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
            <a href="{{ route('propiedades.index') }}" class="btn btn-alt" style="text-decoration:none;">Volver</a>
            <button id="btn-submit" class="btn" type="submit">Solicitar reservación</button>
          </div>
        </form>

        <div style="margin-top:12px;color:#6b7280;font-size:0.95rem;">
          Al confirmar se creará la reserva con tu usuario autenticado. El sistema validará solapamientos.
        </div>

        <div style="margin-top:12px;">
          <h4 style="margin:8px 0 6px 0;">Resumen</h4>
          <div class="small-muted">Disponible: <strong>{{ ucfirst($propiedad->estado) }}</strong></div>
          <dl style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;">
            <div class="small-muted">Capacidad</div><div>{{ $propiedad->capacidad }}</div>
            <div class="small-muted">Ubicación</div><div>{{ $propiedad->ubicacion ?? '-' }}</div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  let rawBlocked = @json($blockedRanges ?? []);
  if (!Array.isArray(rawBlocked)) rawBlocked = rawBlocked ? Object.values(rawBlocked) : [];
  const blockedRanges = rawBlocked
    .map(r => {
      if (!r || !r.from || !r.to) return null;
      const from = new Date(String(r.from).slice(0,10) + 'T00:00:00');
      const to   = new Date(String(r.to).slice(0,10)   + 'T00:00:00');
      if (isNaN(from.getTime()) || isNaN(to.getTime()) || to.getTime() <= from.getTime()) return null;
      return { fromTs: from.getTime(), toTs: to.getTime(), from, to };
    })
    .filter(Boolean);

  const pricePerNight = Number({{ json_encode((float)$propiedad->precio_noche) }}) || 0;

  const checkInEl = document.getElementById('check-in');
  const checkOutEl = document.getElementById('check-out');
  const totalHidden = document.getElementById('total-hidden');
  const priceDisplay = document.getElementById('price-display');
  const breakdown = document.getElementById('price-breakdown');

  const numEl = document.getElementById('num-personas');
  const personCount = document.getElementById('person-count');
  const personLabel = document.getElementById('person-label');
  document.getElementById('person-incr')?.addEventListener('click', ()=> { numEl.value = Math.min(20, Number(numEl.value||1)+1); updatePersonUI(); });
  document.getElementById('person-decr')?.addEventListener('click', ()=> { numEl.value = Math.max(1, Number(numEl.value||1)-1); updatePersonUI(); });
  function updatePersonUI(){ const v = Number(numEl.value||1); personCount.textContent = v; personLabel.textContent = v + (v===1 ? ' persona' : ' personas'); }
  updatePersonUI();

  const daysGridLeft = document.getElementById('cal-grid-days-left');
  const daysGridRight = document.getElementById('cal-grid-days-right');
  const titleLeft = document.getElementById('cal-title-left');
  const titleRight = document.getElementById('cal-title-right');
  const prevLeft = document.getElementById('prev-left') || document.getElementById('prev-month');
  const nextLeft = document.getElementById('next-left') || document.getElementById('next-month');
  const prevRight = document.getElementById('prev-right');
  const nextRight = document.getElementById('next-right');

  let viewDateLeft = new Date(); viewDateLeft.setDate(1);
  let viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth() + 1, 1);
  let selStart = null, selEnd = null;
  function dayStartTs(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime(); }
  function sameDay(a,b){ return !!(a && b && a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate()); }

  function isDateBlocked(date){
    if (!date || isNaN(date.getTime())) return false;
    const t = dayStartTs(date);
    return blockedRanges.some(b => (t >= b.fromTs) && (t < b.toTs));
  }

  function rangeOverlapsBlocked(start, end){
    if (!start || !end) return false;
    const s = dayStartTs(start);
    const e = dayStartTs(end);
    return blockedRanges.some(b => (s < b.toTs) && (e > b.fromTs));
  }

  function daysBetween(a,b){
    if(!a||!b) return 0;
    const A = new Date(a); const B = new Date(b);
    const diff = Math.ceil((B - A) / (1000*60*60*24));
    return diff > 0 ? diff : 0;
  }
  function renderMonth(baseDate, containerGrid, titleEl){
    if (!containerGrid || !titleEl) return;
    containerGrid.innerHTML = '';
    const year = baseDate.getFullYear();
    const month = baseDate.getMonth();
    titleEl.textContent = baseDate.toLocaleString('es-AR', { month: 'long', year: 'numeric' });

    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month+1, 0).getDate();

    for(let i=0;i<firstWeekday;i++){
      const blank = document.createElement('div');
      blank.className = 'cal-day disabled';
      containerGrid.appendChild(blank);
    }

    for(let d=1; d<=daysInMonth; d++){
      const cur = new Date(year, month, d);
      const el = document.createElement('div');
      el.className = 'cal-day';
      el.textContent = d;

      if (isDateBlocked(cur)) {
        el.classList.add('blocked');
        el.addEventListener('click', ()=> { clearSelectionAndInputs(); });
      } else {
      }
      const today = new Date(); today.setHours(0,0,0,0);
      if (cur.getTime() === today.getTime()) el.classList.add('today');

      containerGrid.appendChild(el);
    }
  }

  function renderBothMonths(){
    renderMonth(viewDateLeft, daysGridLeft, titleLeft);
    renderMonth(viewDateRight, daysGridRight, titleRight);

    if (daysGridLeft) {
      const nodes = Array.from(daysGridLeft.querySelectorAll('.cal-day'));
      nodes.forEach(nd => {
        nd.classList.remove('start','end','in-range');
        const txt = nd.textContent.trim();
        const n = parseInt(txt,10);
        if (Number.isNaN(n)) return;
        const baseMonth = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth(), 1);
        const nodeDate = new Date(baseMonth.getFullYear(), baseMonth.getMonth(), n);
        if (!isDateBlocked(nodeDate)) {
          nd.onclick = ()=> onDayClickedLeft(nodeDate);
        } else {
          nd.onclick = ()=> clearSelectionAndInputs();
        }
      });
    }

    if (daysGridRight) {
      const nodesR = Array.from(daysGridRight.querySelectorAll('.cal-day'));
      nodesR.forEach(nd => {
        nd.classList.remove('start','end','in-range');
        const txt = nd.textContent.trim();
        const n = parseInt(txt,10);
        if (Number.isNaN(n)) return;
        const baseMonth = new Date(viewDateRight.getFullYear(), viewDateRight.getMonth(), 1);
        const nodeDate = new Date(baseMonth.getFullYear(), baseMonth.getMonth(), n);
        if (!isDateBlocked(nodeDate)) {
          nd.onclick = ()=> onDayClickedRight(nodeDate);
        } else {
          nd.onclick = ()=> { clearSelectionAndInputs(); };
        }
      });
    }

    applySelectionVisuals();
    validateSelectionAfterRender();
  }

  function applySelectionVisuals(){
    [daysGridLeft, daysGridRight].forEach((grid, idx) => {
      if (!grid) return;
      grid.querySelectorAll('.cal-day').forEach(nd => {
        nd.classList.remove('start','end','in-range');
        const txt = nd.textContent.trim();
        const n = parseInt(txt,10);
        if (Number.isNaN(n)) return;
        const baseMonth = new Date((idx===0?viewDateLeft:viewDateRight).getFullYear(), (idx===0?viewDateLeft:viewDateRight).getMonth(), 1);
        const nodeDate = new Date(baseMonth.getFullYear(), baseMonth.getMonth(), n);
        if (selStart && sameDay(nodeDate, selStart)) nd.classList.add('start');
        if (selEnd && sameDay(nodeDate, selEnd)) nd.classList.add('end');
        if (selStart && selEnd && nodeDate > selStart && nodeDate < selEnd) nd.classList.add('in-range');
        if (isDateBlocked(nodeDate)) nd.classList.add('blocked');
      });
    });
  }

  function onDayClickedLeft(date){
    if (isDateBlocked(date)) { clearSelectionAndInputs(); return; }
    if (!selStart || (selStart && selEnd)) {
      selStart = date;
      selEnd = null;
    } else {
      if (date.getTime() <= selStart.getTime()) {
        selStart = date; selEnd = null;
      } else {
        if (rangeOverlapsBlocked(selStart, date)) {
          alert('El rango seleccionado se solapa con fechas ya reservadas. Elige otras fechas.');
          clearSelectionAndInputs();
          return;
        }
        selEnd = date;
      }
    }
    if (viewDateRight.getFullYear() < selStart.getFullYear() || (viewDateRight.getFullYear() === selStart.getFullYear() && viewDateRight.getMonth() < selStart.getMonth())) {
      viewDateRight = new Date(selStart.getFullYear(), selStart.getMonth(), 1);
    }
    syncInputsFromSelection();
    renderBothMonths();
  }

  function onDayClickedRight(date){
    if (isDateBlocked(date)) { clearSelectionAndInputs(); return; }
    if (!selStart) {
      selStart = date;
      selEnd = null;
      viewDateLeft = new Date(selStart.getFullYear(), selStart.getMonth(), 1);
      viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth() + 1, 1);
      syncInputsFromSelection();
      renderBothMonths();
      return;
    }
    if (date.getTime() <= selStart.getTime()) {
      selStart = date; selEnd = null;
      viewDateRight = new Date(selStart.getFullYear(), selStart.getMonth(), 1);
      syncInputsFromSelection();
      renderBothMonths();
      return;
    }
    if (rangeOverlapsBlocked(selStart, date)) {
      alert('El rango seleccionado se solapa con fechas ya reservadas. Elige otras fechas.');
      clearSelectionAndInputs();
      renderBothMonths();
      return;
    }
    selEnd = date;
    syncInputsFromSelection();
    renderBothMonths();
  }

  function syncInputsFromSelection(){
    if (selStart) checkInEl.value = selStart.toISOString().slice(0,10); else checkInEl.value = '';
    if (selEnd)   checkOutEl.value = selEnd.toISOString().slice(0,10);   else checkOutEl.value = '';
    updatePrice();
  }

  function updatePrice(){
    const ci = checkInEl.value;
    const co = checkOutEl.value;
    if(!ci || !co) { priceDisplay.textContent='$0.00'; breakdown.textContent=''; if(totalHidden) totalHidden.value=''; return; }
    const nights = daysBetween(ci, co);
    const total = nights * pricePerNight;
    priceDisplay.textContent = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(total);
    breakdown.textContent = nights + ' noche(s) × ' + new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(pricePerNight);
    if (totalHidden) totalHidden.value = total.toFixed(2);
  }

  function clearSelectionAndInputs(){
    selStart = null; selEnd = null;
    if (checkInEl) checkInEl.value = '';
    if (checkOutEl) checkOutEl.value = '';
    if (totalHidden) totalHidden.value = '';
    if (priceDisplay) priceDisplay.textContent = '$0.00';
    if (breakdown) breakdown.textContent = '';
    applySelectionVisuals();
  }

  function validateSelectionAfterRender(){
    if (selStart && selEnd) {
      if (rangeOverlapsBlocked(selStart, selEnd)) clearSelectionAndInputs();
    } else if (selStart && isDateBlocked(selStart)) clearSelectionAndInputs();
    else if (selEnd && isDateBlocked(selEnd)) clearSelectionAndInputs();
    if (selStart) {
      const sMonth = selStart.getFullYear()*12 + selStart.getMonth();
      const rMonth = viewDateRight.getFullYear()*12 + viewDateRight.getMonth();
      if (rMonth < sMonth) viewDateRight = new Date(selStart.getFullYear(), selStart.getMonth(), 1);
    }
  }
  function monthIndex(d){ return d.getFullYear()*12 + d.getMonth(); }
  prevLeft?.addEventListener('click', ()=> {
    viewDateLeft.setMonth(viewDateLeft.getMonth()-1);
    if (monthIndex(viewDateLeft) > monthIndex(viewDateRight)) {
      viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth(), 1);
    }
    renderBothMonths();
  });

  nextLeft?.addEventListener('click', ()=> {
    viewDateLeft.setMonth(viewDateLeft.getMonth()+1);
    if (monthIndex(viewDateLeft) > monthIndex(viewDateRight)) {
      viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth(), 1);
    }
    renderBothMonths();
  });

  prevRight?.addEventListener('click', ()=> {
    const candidate = new Date(viewDateRight.getFullYear(), viewDateRight.getMonth()-1, 1);
    if (monthIndex(candidate) < monthIndex(viewDateLeft)) {
      viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth(), 1);
    } else {
      viewDateRight = candidate;
    }
    renderBothMonths();
  });

  nextRight?.addEventListener('click', ()=> {
    viewDateRight.setMonth(viewDateRight.getMonth()+1);
    if (monthIndex(viewDateRight) < monthIndex(viewDateLeft)) {
      viewDateRight = new Date(viewDateLeft.getFullYear(), viewDateLeft.getMonth(), 1);
    }
    renderBothMonths();
  });
  const form = document.getElementById('reserve-form');
  form?.addEventListener('submit', function(e){
    const ci = checkInEl.value;
    const co = checkOutEl.value;
    if(!ci || !co){ e.preventDefault(); alert('Selecciona check-in y check-out en los calendarios'); return; }
    if (new Date(ci) >= new Date(co)){ e.preventDefault(); alert('Check-out debe ser posterior a check-in'); return; }
    if (rangeOverlapsBlocked(new Date(ci+'T00:00:00'), new Date(co+'T00:00:00'))){
      e.preventDefault();
      alert('Las fechas seleccionadas se solapan con una reserva existente. Elige otras fechas.');
      return;
    }
  });
  renderBothMonths();
  daysGridLeft?.addEventListener('dblclick', ()=> { clearSelectionAndInputs(); renderBothMonths(); });
  daysGridRight?.addEventListener('dblclick', ()=> { clearSelectionAndInputs(); renderBothMonths(); });

});
</script>
@endpush

@endsection
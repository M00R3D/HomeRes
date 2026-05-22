@extends('layouts.app')

@section('title','Solicitar reservación - ' . ($propiedad->nombre ?? ''))

@section('content')
@php use Carbon\Carbon; @endphp

<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">

@php
  $currentUser = auth()->user();
  // Build gallery array from ruta_img which can be: a folder path, a comma-separated list, or a single file
  $gallery = [];
  $base = $propiedad->ruta_img ?? '';
  if (!empty($base)) {
    $dirPath = public_path($base);
    if (is_dir($dirPath)) {
      $files = array_merge(glob($dirPath.'/*.jpg')?:[], glob($dirPath.'/*.jpeg')?:[], glob($dirPath.'/*.png')?:[], glob($dirPath.'/*.webp')?:[], glob($dirPath.'/*.gif')?:[]);
      foreach($files as $f) {
        $gallery[] = str_replace(str_replace('\\','/', public_path()) . '/', '', str_replace('\\','/', $f));
      }
    } elseif (strpos($base, ',') !== false) {
      foreach(explode(',', $base) as $b) { $b = trim($b); if ($b) $gallery[] = $b; }
    } else {
      $gallery[] = $base;
    }
  }
  $gallery = collect($gallery)->filter()->values()->all();
  $today = Carbon::today();
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
.hp-help-inline{display:inline-flex;align-items:center;gap:8px;margin:2px 0 12px}
.hp-help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
.hp-help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
.hp-help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem}
.hp-help-viewer{position:fixed;inset:0;display:none;z-index:70}
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
@media (max-width:980px){ .grid-two{ grid-template-columns:1fr; } .calendar{ max-width:100% } }
</style>

<div style="max-width:980px;margin:18px auto;padding:12px;">
  @if(($currentUser->rol ?? '') !== 'admin')
    <div class="hp-help-inline">
      <button type="button" class="hp-help-q" id="hp-open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="hp-help-link" id="hp-open-help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @endif

  <div class="grid-two">
    <div>
      <div style="background:#fff;padding:16px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
        <h2 style="margin:0 0 8px 0;">{{ $propiedad->nombre }}</h2>
        <div class="small-muted">Código: {{ $propiedad->codigo ?? '-' }} · {{ ucfirst($propiedad->tipo) }}</div>
        <div style="font-weight:900;color:#065f46;margin-top:8px;font-size:1.1rem">${{ number_format($propiedad->precio_noche ?? 0,2,',','.') }} / noche</div>

        <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-top:12px;">
          <div style="flex:0 0 360px;">
            @if(!empty($gallery) && count($gallery) > 0)
              <div id="rs-carousel" style="position:relative;border-radius:10px;overflow:hidden;box-shadow:0 12px 30px rgba(2,6,23,0.06);background:#f3f4f6;">
                <div id="rs-track" style="display:flex;transition:transform .6s cubic-bezier(.22,.61,.36,1);width:100%;">
                  @foreach($gallery as $g)
                    <div class="rs-slide" style="flex:0 0 100%;max-width:100%;">
                      <img src="{{ asset($g) }}" alt="{{ $propiedad->nombre }}" style="width:100%;height:260px;object-fit:cover;display:block">
                    </div>
                  @endforeach
                </div>
                <button id="rs-prev" aria-label="Anterior" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.36);color:#fff;border:0;padding:8px 10px;border-radius:8px;cursor:pointer">‹</button>
                <button id="rs-next" aria-label="Siguiente" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.36);color:#fff;border:0;padding:8px 10px;border-radius:8px;cursor:pointer">›</button>
                <div id="rs-dots" style="position:absolute;left:50%;transform:translateX(-50%);bottom:8px;display:flex;gap:6px;"></div>
              </div>
              @if(count($gallery) > 1)
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                  @foreach($gallery as $i => $g)
                    <button type="button" class="thumb-btn" data-index="{{ $i }}" data-src="{{ asset($g) }}" style="border:0;padding:0;background:transparent;cursor:pointer;border-radius:8px;overflow:hidden;box-shadow:0 6px 14px rgba(2,6,23,0.06);">
                      <img src="{{ asset($g) }}" alt="thumb {{ $i+1 }}" style="width:72px;height:48px;object-fit:cover;display:block">
                    </button>
                  @endforeach
                </div>
              @endif
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
                @php $toDate = \Carbon\Carbon::parse($r['to']); @endphp
                @if($toDate->greaterThanOrEqualTo($today))
                  <div style="background:#fff;padding:8px;border-radius:8px;border:1px solid #eef2f7;">
                    {{ \Carbon\Carbon::parse($r['from'])->format('d M Y') }} → {{ $toDate->format('d M Y') }}
                  </div>
                @endif
              @endforeach
            </div>
          </div>
        </div>
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

@if(($currentUser->rol ?? '') !== 'admin')
<div id="hp-help-viewer" class="hp-help-viewer" aria-hidden="true">
  <div class="hp-help-backdrop" id="hp-help-backdrop"></div>
  <div class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de uso de solicitud de reservación">
    <div class="hp-help-toolbar">
      <strong>Guía rápida para solicitar reservación</strong>
      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="hp-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="hp-help-btn" id="hp-close-help" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="hp-help-stage" id="hp-help-stage">
      <img id="hp-help-image" class="hp-help-image"
        src="{{ asset('tutorial_imgs/no-admin/Propiedades(solicitar reservación).png') }}"
        alt="Tutorial para solicitar reservación" draggable="false" />
      <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  let rawBlocked = @json($blockedRanges ?? []);
  if (!Array.isArray(rawBlocked)) rawBlocked = rawBlocked ? Object.values(rawBlocked) : [];
  // keep only future/ongoing ranges (to >= today)
  rawBlocked = (rawBlocked || []).filter(r => {
    try { const t = new Date(String(r.to).slice(0,10) + 'T00:00:00'); t.setHours(0,0,0,0); return t.getTime() >= (new Date()).setHours(0,0,0,0); } catch(e) { return false; }
  });
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
  const maxPersons = Number({{ json_encode((int)($propiedad->capacidad ?? 1)) }}) || 1;

  const checkInEl = document.getElementById('check-in');
  const checkOutEl = document.getElementById('check-out');
  const totalHidden = document.getElementById('total-hidden');
  const priceDisplay = document.getElementById('price-display');
  const breakdown = document.getElementById('price-breakdown');

  const numEl = document.getElementById('num-personas');
  const personCount = document.getElementById('person-count');
  const personLabel = document.getElementById('person-label');
  document.getElementById('person-incr')?.addEventListener('click', ()=> { numEl.value = Math.min(maxPersons, Number(numEl.value||1)+1); updatePersonUI(); });
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

  const todayDate = new Date(); todayDate.setHours(0,0,0,0);
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

  function isPast(date){ if (!date) return false; const d = new Date(date); d.setHours(0,0,0,0); return d.getTime() < todayDate.getTime(); }

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
      if (isPast(cur)) {
        el.classList.add('disabled');
        el.addEventListener('click', ()=> { /* disabled past */ });
      } else if (isDateBlocked(cur)) {
        el.classList.add('blocked');
        el.addEventListener('click', ()=> { clearSelectionAndInputs(); });
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
        if (!isPast(nodeDate) && !isDateBlocked(nodeDate)) {
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
        if (!isPast(nodeDate) && !isDateBlocked(nodeDate)) {
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
    const num = Number(numEl.value || 1);
    if (num > maxPersons) { e.preventDefault(); alert('La cantidad de personas excede la capacidad máxima de la propiedad.'); return; }
  });
  // lightweight carousel initialization (autoplay, prev/next, dots, thumbnail sync)
  (function initCarousel(){
    const rsTrack = document.getElementById('rs-track');
    if (!rsTrack) return;
    const slides = Array.from(rsTrack.querySelectorAll('.rs-slide'));
    if (!slides.length) return;
    const prevBtn = document.getElementById('rs-prev');
    const nextBtn = document.getElementById('rs-next');
    const dotsWrap = document.getElementById('rs-dots');
    let idx = 0;
    function go(i){ idx = ((i % slides.length) + slides.length) % slides.length; rsTrack.style.transform = `translateX(-${idx*100}%)`; updateDots(); }
    function updateDots(){ if(!dotsWrap) return; dotsWrap.innerHTML=''; slides.forEach((s,i)=>{ const b = document.createElement('button'); b.type='button'; b.style.width='10px'; b.style.height='10px'; b.style.borderRadius='999px'; b.style.border='0'; b.style.margin='0 4px'; b.style.background = i===idx? '#111' : 'rgba(255,255,255,0.5)'; b.addEventListener('click', ()=> { go(i); pauseAuto(); }); dotsWrap.appendChild(b); }); }
    prevBtn?.addEventListener('click', ()=> { go(idx-1); pauseAuto(); });
    nextBtn?.addEventListener('click', ()=> { go(idx+1); pauseAuto(); });
    const thumbs = Array.from(document.querySelectorAll('.thumb-btn'));
    thumbs.forEach(t => t.addEventListener('click', ()=> { const i = Number(t.dataset.index||0); go(i); pauseAuto(); }));
    let autoId = setInterval(()=> go(idx+1), 4500);
    function pauseAuto(){ if (autoId) { clearInterval(autoId); autoId = null; setTimeout(()=> { if (!autoId) autoId = setInterval(()=> go(idx+1), 4500); }, 7000); } }
    rsTrack.addEventListener('mouseenter', pauseAuto);
    rsTrack.addEventListener('mouseleave', ()=> { if (!autoId) autoId = setInterval(()=> go(idx+1), 4500); });
    updateDots(); go(0);
  })();

  renderBothMonths();
  daysGridLeft?.addEventListener('dblclick', ()=> { clearSelectionAndInputs(); renderBothMonths(); });
  daysGridRight?.addEventListener('dblclick', ()=> { clearSelectionAndInputs(); renderBothMonths(); });

  var viewer = document.getElementById('hp-help-viewer');
  var stage = document.getElementById('hp-help-stage');
  var img = document.getElementById('hp-help-image');
  var openBtn = document.getElementById('hp-open-help-btn');
  var openLink = document.getElementById('hp-open-help-link');
  var closeBtn = document.getElementById('hp-close-help');
  var backdrop = document.getElementById('hp-help-backdrop');
  var zoomIn = document.getElementById('hp-zoom-in');
  var zoomOut = document.getElementById('hp-zoom-out');
  var zoomReset = document.getElementById('hp-zoom-reset');

  if (viewer && stage && img) {
    var isOpen = false, pushedHistory = false;
    var scale = 1, x = 0, y = 0;
    var dragging = false, startX = 0, startY = 0;
    var MIN_ZOOM = 1, MAX_ZOOM = 4;

    function applyTransform() {
      img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
      if (zoomReset) zoomReset.textContent = Math.round(scale * 100) + '%';
    }

    function setZoom(next) {
      scale = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, next));
      if (scale === 1) { x = 0; y = 0; }
      applyTransform();
    }

    function openViewer() {
      if (isOpen) return;
      isOpen = true;
      viewer.classList.add('open');
      viewer.setAttribute('aria-hidden', 'false');
      setZoom(1);
      try {
        if (!history.state || !history.state.hpHelpOpen) {
          history.pushState({ hpHelpOpen: true }, '');
          pushedHistory = true;
        } else {
          pushedHistory = false;
        }
      } catch (e) { pushedHistory = false; }
    }

    function closeViewer(fromPop) {
      if (!isOpen) return;
      isOpen = false;
      viewer.classList.remove('open');
      viewer.setAttribute('aria-hidden', 'true');
      dragging = false;
      stage.classList.remove('dragging');
      if (!fromPop && pushedHistory) {
        pushedHistory = false;
        try { history.back(); } catch (e) {}
      }
    }

    function beginDrag(cx, cy) {
      if (scale <= 1) return;
      dragging = true;
      startX = cx;
      startY = cy;
      stage.classList.add('dragging');
    }

    function moveDrag(cx, cy) {
      if (!dragging) return;
      x += cx - startX;
      y += cy - startY;
      startX = cx;
      startY = cy;
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

    if (closeBtn) closeBtn.addEventListener('click', function () { closeViewer(false); });
    if (backdrop) backdrop.addEventListener('click', function () { closeViewer(false); });
    if (zoomIn) zoomIn.addEventListener('click', function () { setZoom(scale + 0.2); });
    if (zoomOut) zoomOut.addEventListener('click', function () { setZoom(scale - 0.2); });
    if (zoomReset) zoomReset.addEventListener('click', function () { setZoom(1); });

    stage.addEventListener('wheel', function (e) {
      if (!isOpen) return;
      e.preventDefault();
      setZoom(scale + (e.deltaY < 0 ? 0.18 : -0.18));
    }, { passive: false });

    stage.addEventListener('mousedown', function (e) { beginDrag(e.clientX, e.clientY); });
    window.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
    window.addEventListener('mouseup', endDrag);

    stage.addEventListener('touchstart', function (e) {
      if (e.touches && e.touches[0]) beginDrag(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: true });
    stage.addEventListener('touchmove', function (e) {
      if (dragging && e.touches && e.touches[0]) moveDrag(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: true });
    stage.addEventListener('touchend', endDrag, { passive: true });
    stage.addEventListener('dblclick', function () { setZoom(scale > 1 ? 1 : 2); });

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
  }

});
</script>
@endpush

@endsection
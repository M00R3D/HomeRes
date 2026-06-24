@extends('layouts.app')
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
@section('title', 'Calendario operativo')
    
@section('content')
  
@php
  $isReception = (bool) ($isReception ?? false);
  $calendarTitle = ($isAdmin || $isReception) ? 'Calendario operativo' : 'Mi calendario';
  $calendarSubtitle = ($isAdmin || $isReception)
    ? 'Vista global de reservaciones, pagos y ocupacion diaria.'
    : 'Vista personal con tus reservaciones, pagos y ocupacion vinculada.';

  $resolvePreview = function ($rawImg) {
    if (!is_string($rawImg) || trim($rawImg) === '') {
      return null;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $normalized = trim(str_replace('\\', '/', $rawImg), '/');
    $target = public_path($normalized);

    if (preg_match('/\.([a-zA-Z0-9]+)$/', $normalized, $m)) {
      $ext = strtolower($m[1]);
      if (in_array($ext, $allowedExt, true)) {
        return asset($normalized);
      }
    }

    if (is_dir($target)) {
      $entries = @scandir($target) ?: [];
      $candidates = [];
      foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        $candidates[] = asset($normalized . '/' . $entry);
      }
      natcasesort($candidates);
      $candidates = array_values($candidates);
      return $candidates[0] ?? null;
    }

    return null;
  };

  $reservas = collect($reservacionesCalendar ?? [])->map(function($r) use ($resolvePreview){
    return [
      'id' => $r->id,
      'check_in' => $r->check_in,
      'check_out' => $r->check_out,
      'estado' => $r->estado,
      'estado_pago' => $r->estado_pago,
      'total' => (float) ($r->total ?? 0),
      'usuario' => trim((string) (($r->user->nombre ?? '-') . ' ' . ($r->user->apellido ?? ''))),
      'usuario_id' => $r->usuario_id,
      'propiedad_id' => $r->propiedad_id,
      'propiedad' => $r->propiedad->nombre ?? ('Propiedad #' . ($r->propiedad_id ?? '-')),
      'propiedad_tipo' => $r->propiedad->tipo ?? '-',
      'ubicacion' => $r->propiedad->ubicacion ?? '-',
      'preview' => $resolvePreview($r->propiedad->ruta_img ?? null),
      'link' => route('reservaciones.show', $r->id),
    ];
  })->values();

  $pagos = collect($pagosCalendar ?? [])->map(function($p) use ($resolvePreview){
    $fechaPago = null;
    try {
      $fechaPago = $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->toDateString() : null;
    } catch (\Throwable $e) {
      $fechaPago = null;
    }
    return [
      'id' => $p->id,
      'reservacion_id' => $p->reservacion_id,
      'monto' => (float) ($p->monto ?? 0),
      'estado' => $p->estado,
      'metodo' => $p->metodo_pago,
      'fecha_pago' => $fechaPago,
      'codigo_qr' => $p->codigo_qr,
      'propiedad' => $p->reservation->propiedad->nombre ?? '-',
      'usuario' => trim((string) (($p->reservation->user->nombre ?? '-') . ' ' . ($p->reservation->user->apellido ?? ''))),
      'preview' => $resolvePreview($p->reservation->propiedad->ruta_img ?? null),
      'code_link' => $p->codigo_qr ? route('pagos.codes.show', $p->id) : route('pagos.show', $p->id),
    ];
  })->values();
@endphp

<style>
  .cal-shell{max-width:1320px;margin:16px auto;padding:12px;display:grid;grid-template-columns:1.15fr .85fr;gap:14px;align-items:start}
  .cal-card{background:#fff;border-radius:14px;box-shadow:0 10px 28px rgba(2,6,23,.06);border:1px solid #e5e7eb}
  .cal-header{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px;border-bottom:1px solid #eef2f7;flex-wrap:wrap}
  .cal-title{margin:0;font-size:1.2rem}
  .cal-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .cal-btn{border:1px solid #dbe3ef;background:#fff;padding:8px 10px;border-radius:10px;cursor:pointer;font-weight:800;color:#1f2937}
  .cal-btn:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(2,6,23,.08)}
  .cal-badge{padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid #e5e7eb;color:#334155;font-weight:700;font-size:.83rem}

  .cal-grid-wrap{padding:12px}
  .cal-weekdays{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px;margin-bottom:8px}
  .cal-weekdays div{font-size:.78rem;color:#64748b;font-weight:800;text-align:center;text-transform:uppercase;letter-spacing:.04em}
  .cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px}
  .cal-day{position:relative;min-height:96px;border-radius:12px;border:1px solid #e6ebf2;background:#fff;padding:8px;cursor:pointer;transition:transform .12s ease, box-shadow .2s ease, border-color .2s ease}
  .cal-day:hover{transform:translateY(-2px);box-shadow:0 10px 18px rgba(2,6,23,.08)}
  .cal-day.is-other{opacity:.45;background:#f8fafc}
  .cal-day.is-today{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.15)}
  .cal-day.is-selected{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.18)}
  .cal-day-num{font-weight:900;color:#0f172a;font-size:.95rem}
  .cal-meta{margin-top:8px;display:grid;gap:4px}
  .cal-chip{display:inline-flex;align-items:center;gap:6px;padding:2px 7px;border-radius:999px;font-size:.72rem;font-weight:800;width:max-content}
  .chip-res{background:#e0f2fe;color:#075985}
  .chip-pay{background:#dcfce7;color:#166534}
  .chip-occ{background:#ede9fe;color:#5b21b6}

  .detail-head{padding:14px;border-bottom:1px solid #eef2f7}
  .detail-date{margin:0;font-size:1.08rem}
  .detail-wrap{padding:12px;display:grid;gap:12px}
  .detail-section{border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fcfdff}
  .detail-section h4{margin:0 0 8px 0;font-size:.92rem}
  .detail-list{display:grid;gap:8px}
  .detail-item{border:1px solid #e6ebf2;border-radius:10px;padding:9px;background:#fff}
  .detail-row{display:grid;grid-template-columns:74px 1fr;gap:10px;align-items:start}
  .detail-thumb{width:74px;height:74px;border-radius:10px;overflow:hidden;background:#f1f5f9;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center}
  .detail-thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .55s ease}
  .detail-item:hover .detail-thumb img{transform:scale(1.08)}
  .detail-thumb.is-empty{font-size:.68rem;font-weight:800;color:#64748b;text-align:center;padding:4px}
  .detail-body{min-width:0}
  .detail-main{font-weight:800;color:#0f172a;font-size:.9rem}
  .detail-sub{font-size:.82rem;color:#64748b}
  .detail-link{display:inline-block;margin-top:6px;color:#2563eb;font-weight:800;text-decoration:none;font-size:.82rem}

  .cal-help-inline{display:inline-flex;align-items:center;gap:8px;margin:0 auto 8px 16px}
  .cal-help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
  .cal-help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
  .cal-help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem}
  .cal-help-viewer{position:fixed;inset:0;display:none;z-index:70}
  .cal-help-viewer.open{display:block}
  .cal-help-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.42);backdrop-filter:blur(2px)}
  .cal-help-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(920px,94vw);height:min(84vh,760px);background:rgba(255,255,255,.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.25);border:1px solid rgba(148,163,184,.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
  .cal-help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,.3);background:linear-gradient(90deg,rgba(248,250,252,.95),rgba(241,245,249,.95))}
  .cal-help-toolbar strong{font-size:.92rem;color:#0f172a}
  .cal-help-controls{display:inline-flex;gap:6px}
  .cal-help-btn{border:1px solid rgba(148,163,184,.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
  .cal-help-btn:hover{background:#f8fafc}
  .cal-help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
  .cal-help-stage.dragging{cursor:grabbing}
  .cal-help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear;will-change:transform}
  .cal-help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,.86);border:1px solid rgba(148,163,184,.4);padding:4px 8px;border-radius:999px}

  @media (max-width: 1080px){
    .cal-shell{grid-template-columns:1fr}
  }
</style>

@if(!$isAdmin && !$isReception)
<div class="cal-help-inline">
  <button type="button" class="cal-help-q" id="cal-open-help-btn" aria-label="Abrir ayuda">?</button>
  <a href="#" class="cal-help-link" id="cal-open-help-link">¿Necesitas ayuda para usar esta página?</a>
</div>
@endif
@if($isAdmin)
<div class="cal-help-inline">
  <button type="button" class="cal-help-q" id="cal-open-help-btn" aria-label="Abrir ayuda">?</button>
  <a href="#" class="cal-help-link" id="cal-open-help-link">¿Necesitas ayuda para usar esta página?</a>
</div>
@endif

<div class="cal-shell">
  <section class="cal-card">
    <header class="cal-header">
      <div>
        <h1 class="cal-title">{{ $calendarTitle }}</h1>
        <div class="detail-sub" style="margin-top:4px;">{{ $calendarSubtitle }}</div>
      </div>
      <div class="cal-tools">
        <button class="cal-btn" id="btn-prev" type="button">Anterior</button>
        <span class="cal-badge" id="label-month">Mes</span>
        <button class="cal-btn" id="btn-next" type="button">Siguiente</button>
        <button class="cal-btn" id="btn-today" type="button">Hoy</button>
      </div>
    </header>

    <div class="cal-grid-wrap">
      <div class="cal-weekdays">
        <div>Lun</div><div>Mar</div><div>Mie</div><div>Jue</div><div>Vie</div><div>Sab</div><div>Dom</div>
      </div>
      <div class="cal-grid" id="calendar-grid"></div>
    </div>
  </section>

  <aside class="cal-card">
    <div class="detail-head">
      <h2 class="detail-date" id="detail-date">Selecciona una fecha</h2>
    </div>
    <div class="detail-wrap">
      <section class="detail-section">
        <h4>Reservaciones del dia</h4>
        <div class="detail-list" id="list-res"></div>
      </section>

      <section class="detail-section">
        <h4>Pagos registrados</h4>
        <div class="detail-list" id="list-pay"></div>
      </section>

      <section class="detail-section">
        <h4>Propiedades ocupadas</h4>
        <div class="detail-list" id="list-occ"></div>
      </section>
    </div>
  </aside>
</div>

@if(!$isAdmin && !$isReception)
<div id="cal-help-viewer" class="cal-help-viewer" aria-hidden="true">
  <div class="cal-help-backdrop" id="cal-help-backdrop"></div>
  <div class="cal-help-panel" role="dialog" aria-modal="true" aria-label="Guía de uso de mi calendario">
    <div class="cal-help-toolbar">
      <strong>Guía rápida de mi calendario</strong>
      <div class="cal-help-controls">
        <button type="button" class="cal-help-btn" id="cal-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="cal-help-btn" id="cal-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="cal-help-btn" id="cal-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="cal-help-btn" id="cal-close-help" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="cal-help-stage" id="cal-help-stage">
      <img id="cal-help-image" class="cal-help-image" src="{{ asset('tutorial_imgs/no-admin/MiCalendario.png') }}" alt="Tutorial de mi calendario" draggable="false" />
      <span class="cal-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif
@if($isAdmin)
<div id="cal-help-viewer" class="cal-help-viewer" aria-hidden="true">
  <div class="cal-help-backdrop" id="cal-help-backdrop"></div>
  <div class="cal-help-panel" role="dialog" aria-modal="true" aria-label="Guía de uso de mi calendario">
    <div class="cal-help-toolbar">
      <strong>Guía rápida de mi calendario</strong>
      <div class="cal-help-controls">
        <button type="button" class="cal-help-btn" id="cal-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="cal-help-btn" id="cal-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="cal-help-btn" id="cal-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="cal-help-btn" id="cal-close-help" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="cal-help-stage" id="cal-help-stage">
      <img id="cal-help-image" class="cal-help-image" src="{{ asset('tutorial_imgs/admin/Calendario.png') }}" alt="Tutorial de mi calendario" draggable="false" />
      <span class="cal-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif

<script>
(function(){
  const reservations = @json($reservas);
  const payments = @json($pagos);

  const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  const grid = document.getElementById('calendar-grid');
  const labelMonth = document.getElementById('label-month');
  const detailDate = document.getElementById('detail-date');
  const listRes = document.getElementById('list-res');
  const listPay = document.getElementById('list-pay');
  const listOcc = document.getElementById('list-occ');

  const today = new Date();
  let cursor = new Date(today.getFullYear(), today.getMonth(), 1);
  let selected = isoDate(today);

  function pad(v){ return String(v).padStart(2, '0'); }
  function isoDate(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
  function fromIso(s){ const [y,m,d] = String(s).split('-').map(Number); return new Date(y, (m||1)-1, d||1); }
  function normalizeJsWeekday(day){ return day === 0 ? 6 : day - 1; }

  function reservationIncludesDate(r, dayIso){
    if (!r.check_in || !r.check_out) return false;
    return dayIso >= r.check_in && dayIso < r.check_out;
  }

  function reservationsFor(dayIso){
    return reservations.filter(r => reservationIncludesDate(r, dayIso));
  }

  function paymentsFor(dayIso){
    return payments.filter(p => p.fecha_pago === dayIso);
  }

  function occupiedPropertiesFor(dayIso){
    const map = new Map();
    reservationsFor(dayIso).forEach(r => {
      const key = String(r.propiedad_id || 'x');
      if (!map.has(key)) {
        map.set(key, {
          propiedad: r.propiedad,
          tipo: r.propiedad_tipo,
          ubicacion: r.ubicacion,
          preview: r.preview || null,
          reservas: 0,
        });
      }
      map.get(key).reservas += 1;
    });
    return Array.from(map.values());
  }

  function monthMatrix(base){
    const y = base.getFullYear();
    const m = base.getMonth();
    const first = new Date(y, m, 1);
    const startPad = normalizeJsWeekday(first.getDay());
    const start = new Date(y, m, 1 - startPad);
    const cells = [];
    for (let i = 0; i < 42; i++) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      cells.push(d);
    }
    return cells;
  }

  function renderList(container, items, renderItem){
    if (!items.length) {
      container.innerHTML = '<div class="detail-sub">Sin registros para esta fecha.</div>';
      return;
    }
    container.innerHTML = items.map(renderItem).join('');
  }

  function renderDetails(dayIso){
    const d = fromIso(dayIso);
    detailDate.textContent = d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();

    const dayRes = reservationsFor(dayIso);
    const dayPay = paymentsFor(dayIso);
    const dayOcc = occupiedPropertiesFor(dayIso);

    const thumb = function(url, label){
      if (!url) {
        return '<div class="detail-thumb is-empty">Sin preview</div>';
      }
      return '<div class="detail-thumb"><img src="' + escapeAttr(url) + '" alt="' + escapeAttr(label || 'preview') + '"></div>';
    };

    renderList(listRes, dayRes, function(r){
      return '<div class="detail-item">'
        + '<div class="detail-row">'
        + thumb(r.preview, 'Preview reservacion')
        + '<div class="detail-body">'
        + '<div class="detail-main">#' + r.id + ' · ' + escapeHtml(r.propiedad || '-') + '</div>'
        + '<div class="detail-sub">Cliente: ' + escapeHtml(r.usuario || '-') + ' · Estado: ' + escapeHtml(r.estado || '-') + ' · Pago: ' + escapeHtml(r.estado_pago || '-') + '</div>'
        + '<div class="detail-sub">Entrada: ' + escapeHtml(r.check_in || '-') + ' · Salida: ' + escapeHtml(r.check_out || '-') + '</div>'
        + '<a class="detail-link" href="' + r.link + '">Ver reservacion</a>'
        + '</div>'
        + '</div>'
        + '</div>';
    });

    renderList(listPay, dayPay, function(p){
      return '<div class="detail-item">'
        + '<div class="detail-row">'
        + thumb(p.preview, 'Preview pago')
        + '<div class="detail-body">'
        + '<div class="detail-main">Pago #' + p.id + ' · Reserva #' + (p.reservacion_id || '-') + '</div>'
        + '<div class="detail-sub">Cliente: ' + escapeHtml(p.usuario || '-') + ' · Propiedad: ' + escapeHtml(p.propiedad || '-') + '</div>'
        + '<div class="detail-sub">Monto: $' + Number(p.monto || 0).toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2})
        + ' · Estado: ' + escapeHtml(p.estado || '-') + ' · Metodo: ' + escapeHtml(p.metodo || '-') + '</div>'
        + '<a class="detail-link" href="' + p.code_link + '">Ver detalle de pago/codigo</a>'
        + '</div>'
        + '</div>'
        + '</div>';
    });

    renderList(listOcc, dayOcc, function(o){
      return '<div class="detail-item">'
        + '<div class="detail-row">'
        + thumb(o.preview, 'Preview propiedad ocupada')
        + '<div class="detail-body">'
        + '<div class="detail-main">' + escapeHtml(o.propiedad || '-') + '</div>'
        + '<div class="detail-sub">Tipo: ' + escapeHtml(o.tipo || '-') + ' · Ubicacion: ' + escapeHtml(o.ubicacion || '-') + '</div>'
        + '<div class="detail-sub">Reservaciones activas ese dia: ' + String(o.reservas || 0) + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';
    });
  }

  function renderCalendar(){
    labelMonth.textContent = monthNames[cursor.getMonth()] + ' ' + cursor.getFullYear();

    const cells = monthMatrix(cursor);
    const currentMonth = cursor.getMonth();
    const todayIso = isoDate(today);

    grid.innerHTML = cells.map(function(d){
      const dayIso = isoDate(d);
      const rCount = reservationsFor(dayIso).length;
      const pCount = paymentsFor(dayIso).length;
      const oCount = occupiedPropertiesFor(dayIso).length;

      const classes = ['cal-day'];
      if (d.getMonth() !== currentMonth) classes.push('is-other');
      if (dayIso === todayIso) classes.push('is-today');
      if (dayIso === selected) classes.push('is-selected');

      return '<button type="button" class="' + classes.join(' ') + '" data-date="' + dayIso + '">'
        + '<div class="cal-day-num">' + d.getDate() + '</div>'
        + '<div class="cal-meta">'
        + (rCount ? '<span class="cal-chip chip-res">Res: ' + rCount + '</span>' : '')
        + (pCount ? '<span class="cal-chip chip-pay">Pagos: ' + pCount + '</span>' : '')
        + (oCount ? '<span class="cal-chip chip-occ">Ocup: ' + oCount + '</span>' : '')
        + '</div>'
        + '</button>';
    }).join('');

    grid.querySelectorAll('.cal-day').forEach(function(btn){
      btn.addEventListener('click', function(){
        selected = this.getAttribute('data-date');
        renderCalendar();
        renderDetails(selected);
      });
    });
  }

  function escapeHtml(s){
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttr(s){
    return escapeHtml(s);
  }

  document.getElementById('btn-prev').addEventListener('click', function(){
    cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
    renderCalendar();
  });

  document.getElementById('btn-next').addEventListener('click', function(){
    cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
    renderCalendar();
  });

  document.getElementById('btn-today').addEventListener('click', function(){
    cursor = new Date(today.getFullYear(), today.getMonth(), 1);
    selected = isoDate(today);
    renderCalendar();
    renderDetails(selected);
  });

  renderCalendar();
  renderDetails(selected);
})();
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('cal-help-viewer');
  var stage = document.getElementById('cal-help-stage');
  var img = document.getElementById('cal-help-image');
  var openBtn = document.getElementById('cal-open-help-btn');
  var openLink = document.getElementById('cal-open-help-link');
  var closeBtn = document.getElementById('cal-close-help');
  var backdrop = document.getElementById('cal-help-backdrop');
  var zoomIn = document.getElementById('cal-zoom-in');
  var zoomOut = document.getElementById('cal-zoom-out');
  var zoomReset = document.getElementById('cal-zoom-reset');
  if (!viewer || !stage || !img) return;

  var isOpen = false;
  var pushedHistory = false;
  var scale = 1;
  var x = 0;
  var y = 0;
  var dragging = false;
  var startX = 0;
  var startY = 0;

  function applyTransform() {
    img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
    zoomReset.textContent = Math.round(scale * 100) + '%';
  }

  function setZoom(next) {
    scale = Math.max(1, Math.min(4, next));
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
      if (!history.state || !history.state.calHelpOpen) {
        history.pushState({ calHelpOpen: true }, '');
        pushedHistory = true;
      } else {
        pushedHistory = false;
      }
    } catch (e) {
      pushedHistory = false;
    }
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

  closeBtn && closeBtn.addEventListener('click', function () { closeViewer(false); });
  backdrop && backdrop.addEventListener('click', function () { closeViewer(false); });
  zoomIn && zoomIn.addEventListener('click', function () { setZoom(scale + 0.2); });
  zoomOut && zoomOut.addEventListener('click', function () { setZoom(scale - 0.2); });
  zoomReset && zoomReset.addEventListener('click', function () { setZoom(1); });

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
@endsection

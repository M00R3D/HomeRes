@extends('layouts.app')

@section('title', 'Propiedad - ' . ($propiedad->nombre ?? ''))

@section('content')
<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">
@php
  $currentUser = $currentUser ?? auth()->user();
@endphp
<style>
.pd-carousel{position:relative;border-radius:14px;overflow:hidden;box-shadow:0 16px 34px rgba(2,6,23,0.12);background:#f3f4f6;--mx:50%;--my:50%}
.pd-track{display:flex;transition:transform .72s cubic-bezier(.22,.61,.36,1)}
.pd-slide{flex:0 0 100%;height:360px;position:relative;overflow:hidden}
.pd-slide img{width:100%;height:100%;object-fit:cover;display:block;transform:scale(1.03);transition:transform .9s ease, filter .5s ease;filter:saturate(1.04)}
.pd-slide::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at var(--mx) var(--my), rgba(255,255,255,0.22), rgba(255,255,255,0) 44%);opacity:0;transition:opacity .25s ease}
.pd-slide::after{content:'';position:absolute;inset:auto 0 0 0;height:42%;background:linear-gradient(to top, rgba(2,6,23,.44), rgba(2,6,23,0));pointer-events:none}
.pd-carousel:hover .pd-slide img{transform:scale(1.09);filter:saturate(1.15) contrast(1.07)}
.pd-carousel:hover .pd-slide::before{opacity:1}
.pd-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:3;border:0;width:38px;height:38px;border-radius:999px;background:rgba(2,6,23,.52);color:#fff;font-size:1.35rem;line-height:1;cursor:pointer;transition:background .2s ease, opacity .2s ease;opacity:.92}
.pd-nav:hover{background:rgba(2,6,23,.84)}
.pd-nav.prev{left:10px}.pd-nav.next{right:10px}
.pd-dots{position:absolute;left:50%;bottom:10px;transform:translateX(-50%);display:flex;gap:7px;z-index:3}
.pd-dot{width:8px;height:8px;border-radius:999px;border:0;padding:0;background:rgba(255,255,255,.6);cursor:pointer}
.pd-dot.active{width:22px;background:#fff}
.pd-counter{position:absolute;right:10px;top:10px;z-index:3;background:rgba(2,6,23,.62);color:#fff;padding:4px 9px;border-radius:999px;font-size:.8rem;font-weight:700}
.pd-thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(78px,1fr));gap:8px;margin-top:10px}
.pd-thumb{border:0;padding:0;background:#fff;border-radius:9px;overflow:hidden;height:62px;cursor:pointer;box-shadow:0 4px 14px rgba(2,6,23,.08);outline:2px solid transparent;transition:transform .2s ease, outline-color .2s ease, box-shadow .2s ease}
.pd-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.pd-thumb:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(2,6,23,.16)}
.pd-thumb.active{outline-color:#06b6d4}
.pd-reserve-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:48px;padding:0 16px;border-radius:12px;text-decoration:none;color:#fff;font-weight:800;letter-spacing:.2px;background:linear-gradient(90deg,#06b6d4,#3b82f6);box-shadow:0 12px 28px rgba(59,130,246,.26);transition:transform .18s ease, box-shadow .22s ease, filter .2s ease}
.pd-reserve-btn:hover{transform:translateY(-2px);filter:saturate(1.08);box-shadow:0 18px 34px rgba(59,130,246,.34)}
.pd-reserve-btn:focus-visible{outline:3px solid rgba(14,165,233,.32);outline-offset:2px}
.hp-help-inline{display:inline-flex;align-items:center;gap:8px;margin:4px 0 12px}
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
@media (max-width: 820px){
  .pd-slide{height:280px}
  .pd-nav{width:34px;height:34px}
}
</style>

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  @if(($currentUser->rol ?? '') !== 'admin')
    <div class="hp-help-inline">
      <button type="button" class="hp-help-q" id="hp-open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="hp-help-link" id="hp-open-help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @endif

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">{{ $propiedad->nombre }}</h1>
      <div style="color:#6b7280;margin-top:6px;">Código: {{ $propiedad->codigo ?? '-' }} · {{ ucfirst($propiedad->tipo) }}</div>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('propiedades.index') }}" class="link-button">Volver</a>
      @if($currentUser && ( ($currentUser->rol ?? '') === 'admin' || $currentUser->id === $propiedad->owner_id ))
      <a href="{{ route('propiedades.index', ['edit' => $propiedad->id]) }}" class="btn" title="Abrir editor">Editar</a>
      @endif
    </div>
      <div class="property-actions" style="margin-top:14px;">
        <a href="{{ route('reservaciones.create_for_propiedad', $propiedad->id) }}" class="btn btn-primary pd-reserve-btn" style="margin-left:8px;">Solicitar reserva</a>
      </div>
  </div>

  <div style="display:flex;gap:16px;flex-wrap:wrap;">
    <div style="flex:0 0 360px;">
      @php
        $gallery = collect($gallery ?? [])->filter()->values()->all();
      @endphp

      @if(!empty($gallery))
        <div class="pd-carousel" id="pd-carousel" aria-label="Galería de {{ $propiedad->nombre }}">
          <div class="pd-track" id="pd-track">
            @foreach($gallery as $i => $g)
              <div class="pd-slide">
                <img src="{{ asset($g) }}" alt="{{ $propiedad->nombre }} imagen {{ $i + 1 }}">
              </div>
            @endforeach
          </div>
          @if(count($gallery) > 1)
            <button type="button" class="pd-nav prev" id="pd-prev" aria-label="Imagen anterior">‹</button>
            <button type="button" class="pd-nav next" id="pd-next" aria-label="Siguiente imagen">›</button>
            <div class="pd-dots" id="pd-dots"></div>
            <div class="pd-counter" id="pd-counter">1 / {{ count($gallery) }}</div>
          @endif
        </div>

        @if(count($gallery) > 1)
          <div class="pd-thumbs" id="pd-thumbs">
            @foreach($gallery as $i => $g)
              <button type="button" class="pd-thumb {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}" aria-label="Ir a imagen {{ $i + 1 }}">
                <img src="{{ asset($g) }}" alt="Miniatura {{ $i + 1 }}">
              </button>
            @endforeach
          </div>
        @endif
      @else
        <div style="position:relative;border-radius:10px;overflow:hidden;box-shadow:0 12px 30px rgba(2,6,23,0.06);">
          @if(!empty($propiedad->ruta_img))
            <img src="{{ asset($propiedad->ruta_img) }}" alt="{{ $propiedad->nombre }}" style="width:100%;height:320px;object-fit:cover;display:block;">
          @else
            <div style="width:100%;height:320px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;">Sin imagen</div>
          @endif
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
          @foreach(collect(explode(',', $propiedad->servicios ?? ''))->map(fn($s)=>trim($s))->filter()->values() as $s)
            <span style="background:#f3f4f6;padding:6px 10px;border-radius:999px;font-weight:700;">{{ $s }}</span>
          @endforeach
        </div>
      @endif
    </div>

    <div style="flex:1;min-width:320px;">
      <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 28px rgba(2,6,23,0.06);">
        <dl style="display:grid;grid-template-columns:140px 1fr;gap:10px 18px;">
          <dt class="small">Tipo</dt><dd>{{ $propiedad->tipo }}</dd>
          <dt class="small">Código</dt><dd>{{ $propiedad->codigo ?? '-' }}</dd>
          <dt class="small">Precio / noche</dt><dd>${{ number_format($propiedad->precio_noche ?? 0, 2, ',', '.') }}</dd>
          <dt class="small">Capacidad</dt><dd>{{ $propiedad->capacidad ?? '-' }}</dd>
          <dt class="small">Ubicación</dt><dd>{{ $propiedad->ubicacion ?? '-' }}</dd>
          <dt class="small">Estado</dt>
          <dd><span class="pr-estado {{ $propiedad->estado }}">{{ $propiedad->estado }}</span></dd>
          <dt class="small">Descripción</dt><dd style="white-space:pre-wrap;">{{ $propiedad->descripcion ?? '-' }}</dd>
        </dl>
        <div style="margin-top:14px;">
          <a href="{{ route('reservaciones.create_for_propiedad', $propiedad->id) }}" class="pd-reserve-btn" aria-label="Solicitar reserva de {{ $propiedad->nombre }}">
            <span>Solicitar reserva</span>
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

@if(($currentUser->rol ?? '') !== 'admin')
<div id="hp-help-viewer" class="hp-help-viewer" aria-hidden="true">
  <div class="hp-help-backdrop" id="hp-help-backdrop"></div>
  <div class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de uso de detalle de propiedad">
    <div class="hp-help-toolbar">
      <strong>Guía rápida de detalle de propiedad</strong>
      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="hp-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="hp-help-btn" id="hp-close-help" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="hp-help-stage" id="hp-help-stage">
      <img id="hp-help-image" class="hp-help-image"
        src="{{ asset('tutorial_imgs/no-admin/Propiedades(ver detalle).png') }}"
        alt="Tutorial de detalle de propiedad" draggable="false" />
      <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const car = document.getElementById('pd-carousel');
  const track = document.getElementById('pd-track');
  const prev = document.getElementById('pd-prev');
  const next = document.getElementById('pd-next');
  const dotsWrap = document.getElementById('pd-dots');
  const counter = document.getElementById('pd-counter');
  const thumbs = Array.from(document.querySelectorAll('#pd-thumbs .pd-thumb'));
  if(!car || !track) return;

  const total = Number(track.children.length || 0);
  if(total <= 1) return;

  let index = 0;
  let timer = null;
  const dots = [];

  function render(){
    track.style.transform = 'translateX(' + (-index * 100) + '%)';
    dots.forEach((d,i)=> d.classList.toggle('active', i === index));
    thumbs.forEach((t,i)=> t.classList.toggle('active', i === index));
    if(counter) counter.textContent = (index + 1) + ' / ' + total;
  }

  function go(i){
    index = (i + total) % total;
    render();
  }

  function start(){
    stop();
    timer = setInterval(()=> go(index + 1), 4300);
  }

  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  if (dotsWrap){
    for(let i=0;i<total;i++){
      const d = document.createElement('button');
      d.type = 'button';
      d.className = 'pd-dot' + (i === 0 ? ' active' : '');
      d.setAttribute('aria-label', 'Ir a imagen ' + (i + 1));
      d.addEventListener('click', function(){ go(i); start(); });
      dotsWrap.appendChild(d);
      dots.push(d);
    }
  }

  prev?.addEventListener('click', function(){ go(index - 1); start(); });
  next?.addEventListener('click', function(){ go(index + 1); start(); });
  thumbs.forEach(function(t){ t.addEventListener('click', function(){ go(Number(this.dataset.index || 0)); start(); }); });

  car.addEventListener('mouseenter', stop);
  car.addEventListener('mouseleave', start);
  car.addEventListener('focusin', stop);
  car.addEventListener('focusout', start);
  car.addEventListener('mousemove', function(e){
    const r = car.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * 100;
    const y = ((e.clientY - r.top) / r.height) * 100;
    car.style.setProperty('--mx', x.toFixed(2) + '%');
    car.style.setProperty('--my', y.toFixed(2) + '%');
  });

  start();

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

  if (!viewer || !stage || !img) return;

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
});
</script>
@endpush

@endsection
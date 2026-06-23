@extends('layouts.app')

@section('title', 'Código de reservación #' . ($payment->reservacion_id ?? '-'))

@section('content')
@php
  $qrPayload = 'HOMERES|RES:' . ($payment->reservacion_id ?? '-') . '|PAGO:' . ($payment->id ?? '-') . '|COD:' . ($payment->codigo_qr ?? '');
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . rawurlencode($qrPayload);

  $rawImg = $payment->reservation->propiedad->ruta_img ?? null;
  $galleryUrls = [];
  $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  if (is_string($rawImg) && $rawImg !== '') {
    $normalized = trim(str_replace('\\', '/', $rawImg), '/');
    $publicTarget = public_path($normalized);

    if (preg_match('/\.([a-zA-Z0-9]+)$/', $normalized, $m)) {
      $ext = strtolower($m[1]);
      if (in_array($ext, $allowedExt, true)) {
        $galleryUrls[] = asset($normalized);
      }
    } elseif (is_dir($publicTarget)) {
      $entries = @scandir($publicTarget) ?: [];
      foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        $galleryUrls[] = asset($normalized . '/' . $entry);
      }
      natcasesort($galleryUrls);
      $galleryUrls = array_values($galleryUrls);
    }
  }
@endphp

<style>
  .hp-help-inline{display:inline-flex;align-items:center;gap:8px;margin-bottom:10px}
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
  .code-prop-square {
    width: min(100%, 320px);
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    overflow: hidden;
    position: relative;
    border: 1px solid #e5e7eb;
    background: linear-gradient(145deg, #f8fafc, #e2e8f0);
    box-shadow: 0 14px 30px rgba(2,6,23,0.1);
    transform: rotate(-1.5deg);
  }
  .code-prop-square::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at var(--mx, 50%) var(--my, 50%), rgba(255,255,255,0.32), rgba(255,255,255,0) 50%);
    opacity: 0;
    transition: opacity .24s ease;
  }
  .code-prop-square:hover::before { opacity: 1; }
  .code-prop-track {
    width: 100%;
    height: 100%;
    display: flex;
    transition: transform .72s cubic-bezier(.22,.61,.36,1);
  }
  .code-prop-slide { flex: 0 0 100%; width: 100%; height: 100%; }
  .code-prop-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1.03);
    transition: transform .95s ease, filter .35s ease;
    filter: saturate(1.06);
  }
  .code-prop-square:hover .code-prop-slide img {
    transform: scale(1.1);
    filter: saturate(1.18) contrast(1.05);
  }
  .code-prop-dots {
    position: absolute;
    left: 10px;
    bottom: 10px;
    display: flex;
    gap: 6px;
    z-index: 2;
  }
  .code-prop-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: rgba(255,255,255,0.58);
    border: 1px solid rgba(2,6,23,0.22);
  }
  .code-prop-dot.is-active {
    width: 16px;
    background: #fff;
  }
</style>

<div style="max-width:900px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0;">Detalle de código QR</h1>
      @if(!($isAdmin ?? false))
        <div class="hp-help-inline">
          <button type="button" class="hp-help-q" id="code-detail-help-open-btn" aria-label="Abrir ayuda" onclick="(function(){var v=document.getElementById('code-detail-help-viewer');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">?</button>
          <a href="#" class="hp-help-link" id="code-detail-help-open-link" onclick="(function(){var v=document.getElementById('code-detail-help-viewer');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">¿Necesitas ayuda para usar esta página?</a>
        </div>
      @endif
    </div>
    <a href="{{ route('pagos.codes') }}" class="btn">Volver a códigos</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;">
    <div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
      <h3 style="margin:0 0 10px 0;">Información del código</h3>
      <p><strong>Código:</strong> <span style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;">{{ $payment->codigo_qr }}</span></p>
      <p><strong>Pago:</strong> #{{ $payment->id }}</p>
      <p><strong>Reservación:</strong> #{{ $payment->reservacion_id }}</p>
      <p><strong>Cliente:</strong> {{ $payment->reservation->user->nombre ?? '-' }} {{ $payment->reservation->user->apellido ?? '' }}</p>
      <p><strong>Generado:</strong> {{ $payment->codigo_qr_generado_en ? $payment->codigo_qr_generado_en->format('d M Y H:i') : '-' }}</p>

      <div style="margin-top:10px;display:flex;justify-content:center;">
        @if(!empty($galleryUrls))
          <div class="code-prop-square" data-code-prop-carousel>
            <div class="code-prop-track" data-code-prop-track>
              @foreach($galleryUrls as $i => $url)
                <div class="code-prop-slide" data-code-prop-slide>
                  <img src="{{ $url }}" alt="Preview {{ $i + 1 }} de {{ $payment->reservation->propiedad->nombre ?? 'propiedad' }}">
                </div>
              @endforeach
            </div>
            @if(count($galleryUrls) > 1)
              <div class="code-prop-dots" data-code-prop-dots>
                @foreach($galleryUrls as $i => $url)
                  <span class="code-prop-dot {{ $i === 0 ? 'is-active' : '' }}"></span>
                @endforeach
              </div>
            @endif
          </div>
        @else
          <div class="code-prop-square" style="display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700;">Sin preview</div>
        @endif
      </div>

      <div style="margin-top:14px;padding:14px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;">
        <div style="font-size:1rem;line-height:1.5;color:#0f172a;">
          Este es el código que debes mostrar para hacer tu check-in al llegar a la propiedad. El personal escaneará este QR para validar que la reservación está pagada y activa.
        </div>
      </div>
    </div>

    <div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 24px rgba(2,6,23,0.06);text-align:center;">
      <img src="{{ $qrUrl }}" alt="QR de check-in" style="width:100%;max-width:360px;height:auto;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fff;">
    </div>
  </div>
</div>

@if(!($isAdmin ?? false))
<div id="code-detail-help-viewer" class="hp-help-viewer" aria-hidden="true" style="display:none !important;">
  <div class="hp-help-backdrop" id="code-detail-help-backdrop" onclick="(function(){var v=document.getElementById('code-detail-help-viewer');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();"></div>
  <div id="code-detail-help-panel" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de detalle de código">
    <div class="hp-help-toolbar">
      <strong>Guía rápida del detalle de código</strong>
      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="code-detail-help-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="hp-help-btn" id="code-detail-help-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="hp-help-btn" id="code-detail-help-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="hp-help-btn" id="code-detail-help-close" aria-label="Cerrar ayuda" onclick="(function(){var v=document.getElementById('code-detail-help-viewer');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();return false;">Cerrar</button>
      </div>
    </div>
    <div id="code-detail-help-stage" class="hp-help-stage">
      <img id="code-detail-help-image" class="hp-help-image" src="{{ asset('tutorial_imgs/no-admin/Detalle de código.png') }}" alt="Tutorial del detalle de código" draggable="false" />
      <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif

<script>
  (function() {
    const carousel = document.querySelector('[data-code-prop-carousel]');
    if (!carousel) return;

    const track = carousel.querySelector('[data-code-prop-track]');
    const slides = carousel.querySelectorAll('[data-code-prop-slide]');
    const dots = carousel.querySelectorAll('.code-prop-dot');
    if (!track || slides.length < 2) return;

    let idx = 0;
    const show = function(next) {
      idx = (next + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-idx * 100) + '%)';
      dots.forEach(function(dot, i) { dot.classList.toggle('is-active', i === idx); });
    };

    let timer = setInterval(function() { show(idx + 1); }, 2600);
    carousel.addEventListener('mouseenter', function() { clearInterval(timer); });
    carousel.addEventListener('mouseleave', function() {
      clearInterval(timer);
      timer = setInterval(function() { show(idx + 1); }, 2600);
    });

    carousel.addEventListener('mousemove', function(ev) {
      const rect = carousel.getBoundingClientRect();
      const x = ((ev.clientX - rect.left) / rect.width) * 100;
      const y = ((ev.clientY - rect.top) / rect.height) * 100;
      carousel.style.setProperty('--mx', x.toFixed(1) + '%');
      carousel.style.setProperty('--my', y.toFixed(1) + '%');
    });
  })();
</script>

@if(!($isAdmin ?? false))
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('code-detail-help-viewer');
  var stage = document.getElementById('code-detail-help-stage');
  var img = document.getElementById('code-detail-help-image');
  var openBtn = document.getElementById('code-detail-help-open-btn');
  var openLink = document.getElementById('code-detail-help-open-link');
  var closeBtn = document.getElementById('code-detail-help-close');
  var backdrop = document.getElementById('code-detail-help-backdrop');
  var zoomIn = document.getElementById('code-detail-help-zoom-in');
  var zoomOut = document.getElementById('code-detail-help-zoom-out');
  var zoomReset = document.getElementById('code-detail-help-zoom-reset');

  if (!viewer || !stage || !img) return;

  viewer.style.display = 'none';
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
});
</script>
@endif
@endsection

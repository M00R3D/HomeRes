@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Códigos QR (todos)' : 'Mis códigos')

@section('content')
<style>
  .pagos-codes-pagination {
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
    padding-top: 12px;
  }
  .hp-help-inline{display:inline-flex;align-items:center;gap:8px;margin:4px 0 12px}
  .hp-help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
  .hp-help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
  .pagos-codes-pagination .np-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .pagos-codes-pagination .np-meta {
    color: #6b7280;
    font-size: 13px;
  }
  .pagos-codes-pagination .np-controls {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  .pagos-codes-pagination .np-btn,
  .pagos-codes-pagination .np-page,
  .pagos-codes-pagination .np-ellipsis {
    min-width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    background: #fff;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    color: #111827;
    padding: 0 10px;
  }
  .pagos-codes-pagination .np-btn:hover,
  .pagos-codes-pagination .np-page:hover {
    background: #f8fafc;
  }
  .pagos-codes-pagination .np-page.is-active {
    background: #111827;
    border-color: #111827;
    color: #fff;
  }
  .pagos-codes-pagination .np-btn.is-disabled {
    opacity: .45;
    pointer-events: none;
  }
  .pagos-codes-pagination .np-ellipsis {
    min-width: auto;
    border: 0;
    background: transparent;
    color: #6b7280;
    padding: 0 4px;
  }
</style>
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  @if(!($isAdmin ?? false))
    <div class="hp-help-inline">
      <button type="button" class="hp-help-q" id="codes-help-open-btn" aria-label="Abrir ayuda" onclick="(function(){var v=document.getElementById('codes-help-viewer');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">?</button>
      <a href="#" class="hp-help-link" id="codes-help-open-link" onclick="(function(){var v=document.getElementById('codes-help-viewer');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @endif

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">{{ ($isAdmin ?? false) ? 'Códigos QR (todos)' : 'Mis códigos' }}</h1>
    <a href="{{ route('pagos.mine') }}" class="btn">{{ ($isAdmin ?? false) ? 'Ver pagos' : 'Mis pagos' }}</a>
  </div>

  <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th style="padding:8px;">Código</th>
          <th style="padding:8px;">Pago</th>
          <th style="padding:8px;">Reservación</th>
          <th style="padding:8px;">Cliente</th>
          <th style="padding:8px;">Generado</th>
          <th style="padding:8px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($codes as $p)
          <tr style="border-bottom:1px solid #f6f6f6;">
            <td style="padding:8px;vertical-align:top;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;">{{ $p->codigo_qr }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->id }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->reservacion_id ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->reservation->user->nombre ?? '-' }} {{ $p->reservation->user->apellido ?? '' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->codigo_qr_generado_en ? $p->codigo_qr_generado_en->format('d M Y H:i') : '-' }}</td>
            <td style="padding:8px;vertical-align:top;">
              <a href="{{ route('pagos.codes.show', $p->id) }}" class="link-button">Ver detalle código</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="padding:12px;color:#6b7280;">No hay códigos disponibles.</td></tr>
        @endforelse
      </tbody>
    </table>

    @if($codes->lastPage() > 1)
      @php
        $currentPage = $codes->currentPage();
        $lastPage = $codes->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
      @endphp
      <nav class="pagos-codes-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="np-wrap">
          <div class="np-meta">
            Showing {{ $codes->firstItem() ?? 0 }} to {{ $codes->lastItem() ?? 0 }} of {{ $codes->total() }} results
          </div>
          <div class="np-controls">
            @if($codes->onFirstPage())
              <span class="np-btn is-disabled" aria-disabled="true">Anterior</span>
            @else
              <a class="np-btn" href="{{ $codes->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
              <a class="np-page" href="{{ $codes->url(1) }}">1</a>
              @if($startPage > 2)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
              @if($page === $currentPage)
                <span class="np-page is-active" aria-current="page">{{ $page }}</span>
              @else
                <a class="np-page" href="{{ $codes->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
              <a class="np-page" href="{{ $codes->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($codes->hasMorePages())
              <a class="np-btn" href="{{ $codes->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
              <span class="np-btn is-disabled" aria-disabled="true">Siguiente</span>
            @endif
          </div>
        </div>
      </nav>
    @endif
  </div>
</div>

@if(!($isAdmin ?? false))
<div id="codes-help-viewer" class="hp-help-viewer" aria-hidden="true" style="position:fixed;inset:0;display:none !important;z-index:1500;">
  <div class="hp-help-backdrop" id="codes-help-backdrop" onclick="(function(){var v=document.getElementById('codes-help-viewer');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();"></div>
  <div id="codes-help-panel" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de mis códigos">
    <div class="hp-help-toolbar">
      <strong>Guía rápida de mis códigos</strong>
      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="codes-help-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="hp-help-btn" id="codes-help-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="hp-help-btn" id="codes-help-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="hp-help-btn" id="codes-help-close" aria-label="Cerrar ayuda" onclick="(function(){var v=document.getElementById('codes-help-viewer');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();return false;">Cerrar</button>
      </div>
    </div>
    <div id="codes-help-stage" class="hp-help-stage">
      <img id="codes-help-image" class="hp-help-image" src="{{ asset('tutorial_imgs/no-admin/Mis Códigos.png') }}" alt="Tutorial de mis códigos" draggable="false" />
      <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif

@if(!($isAdmin ?? false))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('codes-help-viewer');
  var stage = document.getElementById('codes-help-stage');
  var img = document.getElementById('codes-help-image');
  var openBtn = document.getElementById('codes-help-open-btn');
  var openLink = document.getElementById('codes-help-open-link');
  var closeBtn = document.getElementById('codes-help-close');
  var backdrop = document.getElementById('codes-help-backdrop');
  var zoomIn = document.getElementById('codes-help-zoom-in');
  var zoomOut = document.getElementById('codes-help-zoom-out');
  var zoomReset = document.getElementById('codes-help-zoom-reset');

  if (!viewer || !stage || !img) return;

  viewer.style.setProperty('display', 'none', 'important');
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
  function toggleViewer() { if (isOpen) closeViewer(); else openViewer(); }
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

  window.HomeResCodesHelp = { open: openViewer, close: closeViewer, toggle: toggleViewer };
});
</script>
@endpush
@endif
@endsection

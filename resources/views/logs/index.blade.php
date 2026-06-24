@extends('layouts.app')

@section('title','Logs')

@section('content')
<style>
  .logs-pagination {
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
    padding-top: 12px;
  }
  .logs-pagination .np-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .logs-pagination .np-meta {
    color: #6b7280;
    font-size: 13px;
  }
  .logs-pagination .np-controls {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  .logs-pagination .np-btn,
  .logs-pagination .np-page,
  .logs-pagination .np-ellipsis {
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
  .logs-pagination .np-btn:hover,
  .logs-pagination .np-page:hover {
    background: #f8fafc;
  }
  .logs-pagination .np-page.is-active {
    background: #111827;
    border-color: #111827;
    color: #fff;
  }
  .logs-pagination .np-btn.is-disabled {
    opacity: .45;
    pointer-events: none;
  }
  .logs-pagination .np-ellipsis {
    min-width: auto;
    border: 0;
    background: transparent;
    color: #6b7280;
    padding: 0 4px;
  }
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
</style>
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1>Logs</h1>
    @if($isAdmin ?? false)
      <div class="hp-help-inline" style="margin-left:12px;">
        <button type="button" class="hp-help-q" id="logs-help-open-btn-admin" aria-label="Abrir ayuda" onclick="(function(){var v=document.getElementById('logs-help-viewer-admin');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">?</button>
        <a href="#" class="hp-help-link" id="logs-help-open-link-admin" onclick="(function(){var v=document.getElementById('logs-help-viewer-admin');if(!v)return;var open=v.getAttribute('aria-hidden')!=='false';v.classList.toggle('open', open);v.setAttribute('aria-hidden', open ? 'false' : 'true');v.style.setProperty('display', open ? 'block' : 'none', 'important');})();return false;">¿Necesitas ayuda para usar esta página?</a>
      </div>
    @endif
  </div>

  <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee">
          <th style="padding:8px">ID</th>
          <th style="padding:8px">Tipo</th>
          <th style="padding:8px">Mensaje</th>
          <th style="padding:8px">Usuario</th>
          <th style="padding:8px">Referencia</th>
          <th style="padding:8px">Fecha</th>
        </tr>
      </thead>
      <tbody>
        @foreach($logs as $log)
        <tr style="border-bottom:1px solid #f6f6f6">
          <td style="padding:8px;vertical-align:top">{{ $log->id }}</td>
          <td style="padding:8px;vertical-align:top">{{ $log->tipo }}</td>
          <td style="padding:8px;vertical-align:top;max-width:420px;word-break:break-word">{{ $log->mensaje }}</td>
          <td style="padding:8px;vertical-align:top">
            @if($log->usuario_id)
              @if(method_exists($log, 'user') && $log->user)
                {{ $log->user->email ?? ($log->user->nombre ?? 'Usuario #'.$log->usuario_id) }}
              @else
                Usuario #{{ $log->usuario_id }}
              @endif
            @else
              -
            @endif
          </td>
          <td style="padding:8px;vertical-align:top">
            @if($log->referencia_tipo || $log->referencia_id)
              {{ $log->referencia_tipo }}{{ $log->referencia_id ? (' #' . $log->referencia_id) : '' }}
            @else
              -
            @endif
          </td>
          <td style="padding:8px;vertical-align:top">{{ $log->created_at }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    @if($logs->lastPage() > 1)
      @php
        $currentPage = $logs->currentPage();
        $lastPage = $logs->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
      @endphp
      <nav class="logs-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="np-wrap">
          <div class="np-meta">
            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
          </div>
          <div class="np-controls">
            @if($logs->onFirstPage())
              <span class="np-btn is-disabled" aria-disabled="true">Anterior</span>
            @else
              <a class="np-btn" href="{{ $logs->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
              <a class="np-page" href="{{ $logs->url(1) }}">1</a>
              @if($startPage > 2)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
              @if($page === $currentPage)
                <span class="np-page is-active" aria-current="page">{{ $page }}</span>
              @else
                <a class="np-page" href="{{ $logs->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
              <a class="np-page" href="{{ $logs->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($logs->hasMorePages())
              <a class="np-btn" href="{{ $logs->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
              <span class="np-btn is-disabled" aria-disabled="true">Siguiente</span>
            @endif
          </div>
        </div>
      </nav>
    @endif
  </div>
  @if($isAdmin ?? false)
  <div id="logs-help-viewer-admin" class="hp-help-viewer" aria-hidden="true" style="display:none !important;">
    <div class="hp-help-backdrop" id="logs-help-backdrop-admin" onclick="(function(){var v=document.getElementById('logs-help-viewer-admin');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();"></div>
    <div id="logs-help-panel-admin" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de logs (admin)">
      <div class="hp-help-toolbar">
        <strong>Guía rápida de logs (admin)</strong>
        <div class="hp-help-controls">
          <button type="button" class="hp-help-btn" id="logs-help-zoom-out-admin" aria-label="Alejar">-</button>
          <button type="button" class="hp-help-btn" id="logs-help-zoom-reset-admin" aria-label="Restablecer zoom">100%</button>
          <button type="button" class="hp-help-btn" id="logs-help-zoom-in-admin" aria-label="Acercar">+</button>
          <button type="button" class="hp-help-btn" id="logs-help-close-admin" aria-label="Cerrar ayuda" onclick="(function(){var v=document.getElementById('logs-help-viewer-admin');if(!v)return;v.classList.remove('open');v.setAttribute('aria-hidden','true');v.style.setProperty('display','none','important');})();return false;">Cerrar</button>
        </div>
      </div>
      <div id="logs-help-stage-admin" class="hp-help-stage">
        <img id="logs-help-image-admin" class="hp-help-image" src="{{ asset('tutorial_imgs/admin/Logs.png') }}" alt="Tutorial admin logs" draggable="false" />
        <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
      </div>
    </div>
  </div>
  @endif
</div>
@if($isAdmin ?? false)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('logs-help-viewer-admin');
  var stage = document.getElementById('logs-help-stage-admin');
  var img = document.getElementById('logs-help-image-admin');
  var openBtn = document.getElementById('logs-help-open-btn-admin');
  var openLink = document.getElementById('logs-help-open-link-admin');
  var closeBtn = document.getElementById('logs-help-close-admin');
  var backdrop = document.getElementById('logs-help-backdrop-admin');
  var zoomIn = document.getElementById('logs-help-zoom-in-admin');
  var zoomOut = document.getElementById('logs-help-zoom-out-admin');
  var zoomReset = document.getElementById('logs-help-zoom-reset-admin');

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

  window.HomeResLogsHelp = { open: openViewer, close: closeViewer, toggle: toggleViewer };
});
</script>
@endpush
@endif
@endsection

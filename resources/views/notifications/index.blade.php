@extends('layouts.app')

@section('title','Notificaciones')
@php
  use App\Support\NotificationPresenter;
    $currentUser = auth()->user();
    $isAdmin = $currentUser && (($currentUser->rol ?? '') === 'admin');
@endphp
@section('content')
<style>
  .notifications-pagination {
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
    padding-top: 12px;
  }

  .notifications-pagination .np-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .notifications-pagination .np-meta {
    color: #6b7280;
    font-size: 13px;
  }

  .notifications-pagination .np-controls {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }

  .notifications-pagination .np-btn,
  .notifications-pagination .np-page,
  .notifications-pagination .np-ellipsis {
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

  .notifications-pagination .np-btn:hover,
  .notifications-pagination .np-page:hover {
    background: #f8fafc;
  }

  .notifications-pagination .np-page.is-active {
    background: #111827;
    border-color: #111827;
    color: #fff;
  }

  .notifications-pagination .np-btn.is-disabled {
    opacity: .45;
    pointer-events: none;
  }

  .notifications-pagination .np-ellipsis {
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
  .hp-help-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(920px,94vw);height:min(70vh,660px);background:rgba(255,255,255,.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.25);border:1px solid rgba(148,163,184,.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
  .hp-help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,.3);background:linear-gradient(90deg,rgba(248,250,252,.95),rgba(241,245,249,.95))}
  .hp-help-toolbar strong{font-size:.92rem;color:#0f172a}
  .hp-help-controls{display:inline-flex;gap:6px}
  .hp-help-btn{border:1px solid rgba(148,163,184,.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
  .hp-help-btn:hover{background:#f8fafc}
  .hp-help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
  .hp-help-stage.dragging{cursor:grabbing}
  .hp-help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear;will-change:transform}
  .hp-help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,.86);border:1px solid rgba(148,163,184,.4);padding:4px 8px;border-radius:999px}

  @media (max-width: 640px) {
    .notifications-pagination .np-wrap {
      align-items: stretch;
    }

    .notifications-pagination .np-meta {
      width: 100%;
      text-align: center;
    }

    .notifications-pagination .np-controls {
      width: 100%;
      justify-content: center;
    }
  }
</style>
<div style="max-width:980px;margin:20px auto;padding:12px;">
  <h1>Notificaciones</h1>
  
  @if($isAdmin)
    <div class="hp-help-inline" style="font-size:1.05rem;align-items:center;gap:12px;margin-bottom:12px;">
      <button type="button" id="notif-page-help-open-btn-admin" class="hp-help-q" aria-label="Abrir ayuda" style="width:36px;height:36px;font-size:1rem;line-height:1;">?</button>
      <a href="javascript:void(0)" id="notif-page-help-open-link-admin" class="hp-help-link" style="font-weight:700;font-size:1.05rem;">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @endif

  @if(!$isAdmin)
    <div class="hp-help-inline">
      <button type="button" class="hp-help-q" id="notif-page-help-open-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="hp-help-link" id="notif-page-help-open-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @endif

  <form method="GET" style="display:flex;gap:8px;margin-bottom:12px;">
    <select name="filter">
      <option value="all" {{ request('filter', 'all')=='all' ? 'selected':'' }}>Todas</option>
      <option value="unread" {{ request('filter')=='unread' ? 'selected':'' }}>Sin leer</option>
      <option value="read" {{ request('filter')=='read' ? 'selected':'' }}>Leídas</option>
    </select>

    <select name="type">
      <option value="all">Todos los tipos</option>
      @foreach(($availableNotificationTypes ?? []) as $typeKey => $typeLabel)
        <option value="{{ $typeKey }}" {{ request('type')===$typeKey ? 'selected':'' }}>{{ $typeLabel }}</option>
      @endforeach
    </select>

    <button class="btn">Filtrar</button>
  </form>

  <div class="card table-card">
    <table class="table">
      <thead>
        <tr><th>Title</th><th>Body</th><th>Fecha</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        @foreach($notifications as $n)
        @php
          $presented = NotificationPresenter::present($n, $resourceLinkTypes ?? null);
        @endphp
        <tr style="{{ $n->read_at ? 'opacity:.6':'' }}">
          <td style="white-space:normal;overflow-wrap:break-word;word-break:break-word;max-width:320px">
            <span style="display:inline-flex;align-items:center;gap:10px;">
              <span aria-hidden="true" style="width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;background:{{ $presented['color'] }};color:#fff;flex:0 0 28px;">{{ $presented['symbol'] }}</span>
              <span>
                <span style="display:block;white-space:normal;overflow-wrap:break-word;word-break:break-word">{{ $n->data['title'] ?? '' }}</span>
                <span style="display:block;font-size:12px;color:#6b7280;margin-top:2px;">{{ $presented['label'] }}</span>
              </span>
            </span>
          </td>
          <td style="max-width:760px;white-space:normal;overflow-wrap:break-word;word-break:break-word">{{ $n->data['body'] ?? '' }}</td>
          <td>{{ $n->created_at->diffForHumans() }}</td>
          <td>
            @if($isAdmin)
              <a class="btn-alt" href="{{ route('notifications.show', $n->id) }}">Ver notificación</a>
            @endif
            @if($presented['allow_resource'])
              <a class="btn-alt" href="{{ $presented['link'] }}" target="_blank" style="margin-left:8px">{{ 'Ver ' . ($presented['resource_label'] ?? 'recurso') }}</a>
            @endif
            @if(!$n->read_at)
              <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="display:inline;margin-left:8px">@csrf<button class="btn-alt">Marcar leído</button></form>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @if($notifications->lastPage() > 1)
      @php
        $currentPage = $notifications->currentPage();
        $lastPage = $notifications->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
      @endphp
      <nav class="notifications-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="np-wrap">
          <div class="np-meta">
            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} results
          </div>
          <div class="np-controls">
            @if($notifications->onFirstPage())
              <span class="np-btn is-disabled" aria-disabled="true">Anterior</span>
            @else
              <a class="np-btn" href="{{ $notifications->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
              <a class="np-page" href="{{ $notifications->url(1) }}">1</a>
              @if($startPage > 2)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
              @if($page === $currentPage)
                <span class="np-page is-active" aria-current="page">{{ $page }}</span>
              @else
                <a class="np-page" href="{{ $notifications->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
              <a class="np-page" href="{{ $notifications->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($notifications->hasMorePages())
              <a class="np-btn" href="{{ $notifications->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
              <span class="np-btn is-disabled" aria-disabled="true">Siguiente</span>
            @endif
          </div>
        </div>
      </nav>
    @endif
  </div>
</div>

@if(!$isAdmin)
<div id="notif-page-help-viewer" class="hp-help-viewer" aria-hidden="true">
  <div class="hp-help-backdrop" id="notif-page-help-backdrop"></div>
  <div id="notif-page-help-panel" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de página de notificaciones">
    <div class="hp-help-toolbar">
      <strong>Guía rápida de todas las notificaciones</strong>
      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="notif-page-help-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="hp-help-btn" id="notif-page-help-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="hp-help-btn" id="notif-page-help-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="hp-help-btn" id="notif-page-help-close" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="hp-help-stage" id="notif-page-help-stage">
      <img id="notif-page-help-image" class="hp-help-image"
        src="{{ asset('tutorial_imgs/no-admin/Notificaciónes(todas).png') }}"
        alt="Tutorial de la página de notificaciones" draggable="false" />
      <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif
  <?php if($isAdmin): ?>
  <div id="notif-page-help-viewer-admin" class="hp-help-viewer" aria-hidden="true">
    <div class="hp-help-backdrop" id="notif-page-help-backdrop-admin"></div>
    <div id="notif-page-help-panel-admin" class="hp-help-panel" role="dialog" aria-modal="true" aria-label="Guía de administración de notificaciones">
      <div class="hp-help-toolbar">
        <strong>Guía rápida (admin)</strong>
        <div class="hp-help-controls">
          <button type="button" class="hp-help-btn" id="notif-page-help-zoom-out-admin" aria-label="Alejar">-</button>
          <button type="button" class="hp-help-btn" id="notif-page-help-zoom-reset-admin" aria-label="Restablecer zoom">100%</button>
          <button type="button" class="hp-help-btn" id="notif-page-help-zoom-in-admin" aria-label="Acercar">+</button>
          <button type="button" class="hp-help-btn" id="notif-page-help-close-admin" aria-label="Cerrar ayuda">Cerrar</button>
        </div>
      </div>
      <div class="hp-help-stage" id="notif-page-help-stage-admin">
        <img id="notif-page-help-image-admin" class="hp-help-image"
          src="<?php echo e(asset('tutorial_imgs/admin/Notificaciones.png')); ?>"
          alt="Tutorial admin de notificaciones" draggable="false" />
        <span class="hp-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
@endsection

@push('scripts')
@if(!$isAdmin)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('notif-page-help-viewer');
  var stage = document.getElementById('notif-page-help-stage');
  var img = document.getElementById('notif-page-help-image');
  var openBtn = document.getElementById('notif-page-help-open-btn');
  var openLink = document.getElementById('notif-page-help-open-link');
  var closeBtn = document.getElementById('notif-page-help-close');
  var panel = document.getElementById('notif-page-help-panel');
  var backdrop = document.getElementById('notif-page-help-backdrop');
  var zoomIn = document.getElementById('notif-page-help-zoom-in');
  var zoomOut = document.getElementById('notif-page-help-zoom-out');
  var zoomReset = document.getElementById('notif-page-help-zoom-reset');

  if (!viewer || !stage || !img) return;

  var isOpen = false;
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
  }

  function closeViewer() {
    if (!isOpen) return;
    isOpen = false;
    viewer.classList.remove('open');
    viewer.setAttribute('aria-hidden', 'true');
    dragging = false;
    stage.classList.remove('dragging');
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

  if (closeBtn) closeBtn.addEventListener('click', closeViewer);
  if (backdrop) backdrop.addEventListener('click', closeViewer);
  viewer.addEventListener('click', function (e) {
    if (e.target === viewer || e.target === backdrop) closeViewer();
  });
  document.addEventListener('mousedown', function (e) {
    if (!isOpen || !panel) return;
    if (e.target === openBtn || e.target === openLink) return;
    if (!panel.contains(e.target)) closeViewer();
  });
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
    if (e.key === 'Escape') closeViewer();
    if (e.key === '+' || e.key === '=') setZoom(scale + 0.2);
    if (e.key === '-') setZoom(scale - 0.2);
  });

  applyTransform();
});
</script>
@endif
@if($isAdmin)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('notif-page-help-viewer-admin');
  var stage = document.getElementById('notif-page-help-stage-admin');
  var img = document.getElementById('notif-page-help-image-admin');
  var openBtn = document.getElementById('notif-page-help-open-btn-admin');
  var openLink = document.getElementById('notif-page-help-open-link-admin');
  var closeBtn = document.getElementById('notif-page-help-close-admin');
  var panel = document.getElementById('notif-page-help-panel-admin');
  var backdrop = document.getElementById('notif-page-help-backdrop-admin');
  var zoomIn = document.getElementById('notif-page-help-zoom-in-admin');
  var zoomOut = document.getElementById('notif-page-help-zoom-out-admin');
  var zoomReset = document.getElementById('notif-page-help-zoom-reset-admin');

  if (!viewer || !stage || !img) return;

  var isOpen = false;
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
  }

  function closeViewer() {
    if (!isOpen) return;
    isOpen = false;
    viewer.classList.remove('open');
    viewer.setAttribute('aria-hidden', 'true');
    dragging = false;
    stage.classList.remove('dragging');
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

  function endDrag() { dragging = false; stage.classList.remove('dragging'); }

  [openBtn, openLink].forEach(function (el) { if (!el) return; el.addEventListener('click', function (e) { e.preventDefault(); openViewer(); }); });

  if (closeBtn) closeBtn.addEventListener('click', closeViewer);
  if (backdrop) backdrop.addEventListener('click', closeViewer);
  viewer.addEventListener('click', function (e) { if (e.target === viewer || e.target === backdrop) closeViewer(); });
  document.addEventListener('mousedown', function (e) { if (!isOpen || !panel) return; if (e.target === openBtn || e.target === openLink) return; if (!panel.contains(e.target)) closeViewer(); });
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
@endpush

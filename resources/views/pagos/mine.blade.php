@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos')

@section('content')
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
  .pagos-mine-pagination {
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
    padding-top: 12px;
  }
  .pagos-mine-pagination .np-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .pagos-mine-pagination .np-meta {
    color: #6b7280;
    font-size: 13px;
  }
  .pagos-mine-pagination .np-controls {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  .pagos-mine-pagination .np-btn,
  .pagos-mine-pagination .np-page,
  .pagos-mine-pagination .np-ellipsis {
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
  .pagos-mine-pagination .np-btn:hover,
  .pagos-mine-pagination .np-page:hover {
    background: #f8fafc;
  }
  .pagos-mine-pagination .np-page.is-active {
    background: #111827;
    border-color: #111827;
    color: #fff;
  }
  .pagos-mine-pagination .np-btn.is-disabled {
    opacity: .45;
    pointer-events: none;
  }
  .pagos-mine-pagination .np-ellipsis {
    min-width: auto;
    border: 0;
    background: transparent;
    color: #6b7280;
    padding: 0 4px;
  }
</style>
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="help-inline">
      <h1 style="margin:0;">{{ ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos' }}</h1>
      <button type="button" class="help-q" id="open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="help-link" id="open-help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
    <a href="{{ route('pagos.codes') }}" class="btn">{{ ($isAdmin ?? false) ? 'Ver códigos QR' : 'Mis códigos' }}</a>
  </div>

  <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th style="padding:8px;">ID</th>
          <th style="padding:8px;">Reservación</th>
          <th style="padding:8px;">Cliente</th>
          <th style="padding:8px;">Monto</th>
          <th style="padding:8px;">Método</th>
          <th style="padding:8px;">Estado</th>
          <th style="padding:8px;">Fecha</th>
          <th style="padding:8px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr style="border-bottom:1px solid #f6f6f6;">
            <td style="padding:8px;vertical-align:top;">#{{ $p->id }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->reservacion_id ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->reservation->user->nombre ?? '-' }} {{ $p->reservation->user->apellido ?? '' }}</td>
            <td style="padding:8px;vertical-align:top;font-weight:700;">${{ number_format($p->monto ?? 0, 2, ',', '.') }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->metodo_pago ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ ucfirst($p->estado ?? '-') }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d M Y H:i') : '-' }}</td>
            <td style="padding:8px;vertical-align:top;">
              <a href="{{ route('pagos.show', $p->id) }}" class="link-button">Ver pago</a>
              @if(($p->estado ?? '') === 'pagado' && !empty($p->codigo_qr))
                <a href="{{ route('pagos.codes.show', $p->id) }}" class="link-button" style="margin-left:6px;">Ver código</a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" style="padding:12px;color:#6b7280;">No hay pagos para mostrar.</td></tr>
        @endforelse
      </tbody>
    </table>

    @if($payments->lastPage() > 1)
      @php
        $currentPage = $payments->currentPage();
        $lastPage = $payments->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
      @endphp
      <nav class="pagos-mine-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="np-wrap">
          <div class="np-meta">
            Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results
          </div>
          <div class="np-controls">
            @if($payments->onFirstPage())
              <span class="np-btn is-disabled" aria-disabled="true">Anterior</span>
            @else
              <a class="np-btn" href="{{ $payments->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
              <a class="np-page" href="{{ $payments->url(1) }}">1</a>
              @if($startPage > 2)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
              @if($page === $currentPage)
                <span class="np-page is-active" aria-current="page">{{ $page }}</span>
              @else
                <a class="np-page" href="{{ $payments->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
              <a class="np-page" href="{{ $payments->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($payments->hasMorePages())
              <a class="np-btn" href="{{ $payments->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
              <span class="np-btn is-disabled" aria-disabled="true">Siguiente</span>
            @endif
          </div>
        </div>
        
      </nav>
    @endif
  </div>
</div>
    <div id="help-viewer" class="help-viewer" aria-hidden="true">
      <div class="help-viewer-backdrop" id="help-backdrop"></div>
      <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de uso">
        <div class="help-toolbar">
          <strong>Guía rápida de la pantalla Mis Pagos</strong>
          <div class="help-controls">
            <button type="button" class="help-btn" id="zoom-out" aria-label="Alejar">-</button>
            <button type="button" class="help-btn" id="zoom-reset" aria-label="Restablecer zoom">100%</button>
            <button type="button" class="help-btn" id="zoom-in" aria-label="Acercar">+</button>
            <button type="button" class="help-btn" id="close-help" aria-label="Cerrar ayuda">Cerrar</button>
          </div>
        </div>
        <div class="help-stage" id="help-stage">
          <img
            id="help-image"
            class="help-image"
            src="{{ asset('tutorial_imgs/no-admin/MisPagos.png') }}"
            alt="Tutorial para la pantalla Mis Pagos"
            draggable="false"
          />
          <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
        </div>
      </div>
    </div>
@endsection
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
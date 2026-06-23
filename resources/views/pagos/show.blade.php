@extends('layouts.app')

@section('title','Pago #' . ($payment->id ?? ($p->id ?? '')))

@section('content')
@php
  $payment = $payment ?? ($p ?? null);
  use Carbon\Carbon;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp
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
<div style="max-width:800px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1>Pago #{{ $payment->id ?? '-' }}</h1>
    <div>
      <a href="{{ url('/pagos') }}" class="btn">Volver</a>
    </div>
  </div>
      <button type="button" class="help-q" id="open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="help-link" id="open-help-link">¿Necesitas ayuda para usar esta página?</a>
  <div class="card" style="padding:16px;">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start;">
      <div>
        <h3 style="margin:0 0 8px 0;">Información del pago</h3>
        <p><strong>ID:</strong> {{ $payment->id ?? '-' }}</p>
        <p><strong>Monto:</strong> ${{ number_format($payment->monto ?? 0,2,',','.') }}</p>
        <p><strong>Método:</strong> {{ $payment->metodo_pago ?? '-' }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($payment->estado ?? '-') }}</p>
        <p><strong>Fecha pago:</strong> {{ $payment->fecha_pago ? Carbon::parse($payment->fecha_pago)->format('d M Y H:i') : '-' }}</p>
        <hr />
        <h4 style="margin:8px 0">Reservación</h4>
        @if($payment && $payment->reservation)
          <p><a href="{{ route('reservaciones.show', $payment->reservation->id) }}">Reservación #{{ $payment->reservation->id }}</a></p>
          <p class="small">Cliente: {{ $payment->reservation->user->nombre ?? '-' }} {{ $payment->reservation->user->apellido ?? '' }}</p>
        @else
          <p>Reservación: #{{ $payment->reservacion_id ?? '-' }}</p>
        @endif
      </div>

      <div>
        <h4 style="margin:0 0 8px 0">Tarjeta</h4>
        @if($payment && $payment->tarjeta)
          <p><strong>Nombre:</strong> {{ $payment->tarjeta->nombre ?? '-' }}</p>
          <p><strong>Número:</strong> ••••{{ substr($payment->tarjeta->numero_tarjeta ?? '', -4) }}</p>
          <p class="small">Asignada a: {{ optional($payment->tarjeta->assignedUser)->nombre ? trim(optional($payment->tarjeta->assignedUser)->nombre . ' ' . optional($payment->tarjeta->assignedUser)->apellido) : 'No asignada' }}</p>
        @else
          <p>No hay tarjeta asociada.</p>
        @endif

        @if(($payment->estado ?? '') === 'pagado' && !empty($payment->codigo_qr))
          @php
            $qrPayload = 'HOMERES|RES:' . ($payment->reservacion_id ?? '-') . '|PAGO:' . ($payment->id ?? '-') . '|COD:' . ($payment->codigo_qr ?? '');
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($qrPayload);
          @endphp
          <hr />
          <h4 style="margin:8px 0">Código de llegada</h4>
          <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
            <img src="{{ $qrUrl }}" alt="QR reservación pagada" style="width:220px;height:220px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;padding:8px;">
            <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;color:#0f172a;">{{ $payment->codigo_qr }}</div>
            <div class="small">Muestra este QR o código al llegar a la propiedad.</div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
<div id="help-viewer" class="help-viewer" aria-hidden="true">
      <div class="help-viewer-backdrop" id="help-backdrop"></div>
      <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de uso">
        <div class="help-toolbar">
          <strong>Guía rápida de la pantalla Detalle de Pago</strong>
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
            src="{{ asset('tutorial_imgs/no-admin/DetalleDePago.png') }}"
            alt="Tutorial para la pantalla Detalle de Pago"
            draggable="false"
          />
          <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
        </div>
      </div>
    </div>
@endsection
<div id="help-viewer" class="help-viewer" aria-hidden="true">
      <div class="help-viewer-backdrop" id="help-backdrop"></div>
      <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de uso">
        <div class="help-toolbar">
          <strong>Guía rápida de la pantalla Detalle de Pago</strong>
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
            src="{{ asset('tutorial_imgs/no-admin/DetalleDePago.png') }}"
            alt="Tutorial para la pantalla Detalle de Pago"
            draggable="false"
          />
          <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
        </div>
      </div>
    </div>
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
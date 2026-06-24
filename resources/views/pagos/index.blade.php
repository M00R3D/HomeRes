@extends('layouts.app')

@section('title','Pagos')

@section('content')
<style>
.card { background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06); }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; vertical-align:middle; }
.btn { background: linear-gradient(90deg,#6366f1,#06b6d4); color:#fff; padding:8px 12px; border-radius:8px; border:0; font-weight:700; cursor:pointer; }
.btn-alt { background:#f3f4f6; color:#0f172a; padding:8px 10px; border-radius:8px; border:0; cursor:pointer; }
.link-button { background:transparent;border:0;color:#2563eb;cursor:pointer;padding:6px 8px;font-weight:700;border-radius:6px; }
.link-button.danger { color:#ef4444; }
.small { font-size:0.9rem;color:#6b7280; }
.input-inline { display:flex; gap:8px; align-items:center; }
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999; }
.modal .modal-backdrop { position:absolute; inset:0; background:rgba(2,6,23,0.45); }
.modal-panel { position:relative; z-index:1200; background:#fff; border-radius:12px; padding:18px; width:560px; max-width:calc(100% - 32px); box-shadow:0 18px 40px rgba(2,6,23,0.08); }
.field { display:block; margin-bottom:12px; }
.field .label-text { display:block; font-weight:700; margin-bottom:6px; color:#111827; }
.input-inline input, .input-inline select { padding:8px 10px; border-radius:8px; border:1px solid #e6e9ee; background:#fff; }
.form-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }
.delete-modal-panel { width:460px; max-width:calc(100% - 32px); padding:18px; border-radius:12px; background:#fff; box-shadow:0 18px 40px rgba(2,6,23,0.08); }
#modal-delete .modal-backdrop { z-index:1000; }
#modal-delete .delete-modal-panel { position:relative; z-index:1200; pointer-events:auto; }

.muted { color:#6b7280; }
.row-actions { text-align:right; white-space:nowrap; }
.pagos-index-pagination {
  border-top: 1px solid #e5e7eb;
  margin-top: 10px;
  padding-top: 12px;
}
.pagos-index-pagination .np-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.pagos-index-pagination .np-meta {
  color: #6b7280;
  font-size: 13px;
}
.pagos-index-pagination .np-controls {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.pagos-index-pagination .np-btn,
.pagos-index-pagination .np-page,
.pagos-index-pagination .np-ellipsis {
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
.pagos-index-pagination .np-btn:hover,
.pagos-index-pagination .np-page:hover {
  background: #f8fafc;
}
.pagos-index-pagination .np-page.is-active {
  background: #111827;
  border-color: #111827;
  color: #fff;
}
.pagos-index-pagination .np-btn.is-disabled {
  opacity: .45;
  pointer-events: none;
}
.pagos-index-pagination .np-ellipsis {
  min-width: auto;
  border: 0;
  background: transparent;
  color: #6b7280;
  padding: 0 4px;
}
/* help viewer styles (compact copy) */
.help-viewer{position:fixed;inset:0;display:none;z-index:9999}
.help-viewer.open{display:block}
.help-viewer-backdrop{position:absolute;inset:0;background:rgba(15,23,42,0.42);backdrop-filter:blur(2px)}
.help-viewer-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(920px,94vw);height:min(84vh,760px);background:rgba(255,255,255,0.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,0.25);border:1px solid rgba(148,163,184,0.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
.help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,0.3);background:linear-gradient(90deg,rgba(248,250,252,.95),rgba(241,245,249,.95))}
.help-toolbar strong{font-size:.92rem;color:#0f172a}
.help-controls{display:inline-flex;gap:6px}
.help-btn{border:1px solid rgba(148,163,184,.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
.help-btn:hover{background:#f8fafc}
.help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
.help-stage.dragging{cursor:grabbing}
.help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear;will-change:transform}
.help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,.86);border:1px solid rgba(148,163,184,.4);padding:4px 8px;border-radius:999px}
/* trigger styles */
.help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
.help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
.help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem}
</style>

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">Pagos</h1>
      <div class="small" style="margin-top:6px;">CRUD de pagos vinculados a reservaciones y tarjetas</div>
      @php $isAdmin = auth()->check() && (auth()->user()->rol ?? '') === 'admin'; @endphp
      @if($isAdmin)
        <div class="hp-help-inline" style="margin-top:8px;display:inline-flex;align-items:center;gap:8px;">
          <button type="button" class="help-q" id="pagos-help-open-btn-admin" aria-label="Abrir ayuda">?</button>
          <a href="javascript:void(0)" class="help-link" id="pagos-help-open-link-admin">¿Necesitas ayuda para usar esta página?</a>
        </div>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      {{ session('success') }}
    </div>
  @endif

  <div class="card">
    <table class="table" style="min-width:900px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Reservación</th>
          <th>Tarjeta</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Estado</th>
          <th>Fecha pago</th>
          <th style="text-align:right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td style="display:flex;align-items:center;gap:12px;">
              @php
                $img = null;
                $imgPathRaw = $p->reservation->propiedad->ruta_img ?? null;
                if (!empty($imgPathRaw)) {
                  $ruta = ltrim((string) $imgPathRaw, '/\\');
                  $full = public_path($ruta);
                  if (is_dir($full)) {
                    $files = @scandir($full) ?: [];
                    foreach ($files as $f) {
                      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                      if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                        $img = asset(trim($ruta, '/\\') . '/' . ltrim($f, '/\\'));
                        break;
                      }
                    }
                  } elseif (is_file($full)) {
                    $img = asset($ruta);
                  }
                }
              @endphp
              @if($img)
                <img src="{{ $img }}" alt="propiedad" style="width:56px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #eef2f7" />
              @else
                <div style="width:56px;height:40px;border-radius:6px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af">No img</div>
              @endif
              <div>
                @if($p->reservation)
                  <div style="font-weight:700;">#{{ $p->reservation->id }} — {{ $p->reservation->user->nombre ?? '-' }}</div>
                  <div class="small">{{ $p->reservation->propiedad->nombre ?? '' }}</div>
                @else
                  <div class="small">#{{ $p->reservacion_id ?? '-' }}</div>
                @endif
              </div>
            </td>

            <td>
              @if($p->tarjeta)
                @php
                  $raw = preg_replace('/\D/','', $p->tarjeta->numero_tarjeta ?? '');
                  $first4 = \Illuminate\Support\Str::substr($raw, 0, 4) ?: '••••';
                  $last = (int) (strlen($raw) ? substr($raw, -1) : 0);
                  $assignedUser = optional($p->tarjeta->assignedUser);
                  $assignedName = $assignedUser->nombre ? trim(($assignedUser->nombre ?? '') . ' ' . ($assignedUser->apellido ?? '')) : null;
                  $colors = ['#0ea5e9','#06b6d4','#7c3aed','#ef4444','#f59e0b','#10b981','#f97316','#8b5cf6','#0f172a','#065f46'];
                  $bg = $colors[$last % count($colors)];
                @endphp

                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:120px;height:64px;border-radius:8px;overflow:hidden;box-shadow:0 6px 18px rgba(2,6,23,0.06);">
                    <svg width="100%" height="100%" viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg" role="img">
                      <rect width="160" height="64" rx="8" fill="{{ $bg }}" />
                      <rect width="160" height="64" rx="8" fill="rgba(255,255,255,0.06)" />
                      <text x="12" y="42" font-family="monospace" font-size="18" fill="#fff" font-weight="700">{{ $first4 }}</text>
                    </svg>
                  </div>
                  <div>
                    <div style="font-weight:700;">{{ $p->tarjeta->nombre ?? 'Tarjeta' }}</div>
                    <div class="small">••••{{ substr($p->tarjeta->numero_tarjeta ?? '', -4) }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                      {{ $assignedName ?? 'No asignada' }}
                    </div>
                  </div>
                </div>
              @else
                <div class="small">-</div>
              @endif
            </td>

            <td style="font-weight:800">${{ number_format($p->monto,2,',','.') }}</td>
            <td>{{ $p->metodo_pago }}</td>
            <td>{{ ucfirst($p->estado) }}</td>
            <td>{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d M Y H:i') : '-' }}</td>
            <td style="text-align:right">
              <span style="position:relative;display:inline-block;">
                <a href="{{ url('/pagos/'.$p->id) }}" class="link-button">Ver</a>
                <span class="action-hint" style="right:-6px;top:100%;margin-top:6px;">Clic para ver detalle</span>
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="small">No hay pagos.</td></tr>
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
      <nav class="pagos-index-pagination" role="navigation" aria-label="Pagination Navigation">
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
  <div id="pagos-help-viewer-admin" class="help-viewer" aria-hidden="true" style="display:none;">
    <div class="help-viewer-backdrop" id="pagos-help-backdrop-admin"></div>
    <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía de Pagos (admin)">
      <div class="help-toolbar">
        <strong>Guía rápida — Pagos (admin)</strong>
        <div class="help-controls">
          <button type="button" class="help-btn" id="pagos-help-zoom-out-admin" aria-label="Alejar">-</button>
          <button type="button" class="help-btn" id="pagos-help-zoom-reset-admin" aria-label="Restablecer zoom">100%</button>
          <button type="button" class="help-btn" id="pagos-help-zoom-in-admin" aria-label="Acercar">+</button>
          <button type="button" class="help-btn" id="pagos-help-close-admin" aria-label="Cerrar ayuda">Cerrar</button>
        </div>
      </div>
      <div class="help-stage" id="pagos-help-stage-admin">
        <img id="pagos-help-image-admin" class="help-image" src="{{ asset('tutorial_imgs/admin/Pagos.png') }}" alt="Tutorial Pagos admin" draggable="false" />
        <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
      </div>
    </div>
  </div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function(){
  (function(){
    const openBtn = document.getElementById('pagos-help-open-btn-admin');
    const openLink = document.getElementById('pagos-help-open-link-admin');
    const viewer = document.getElementById('pagos-help-viewer-admin');
    const backdrop = document.getElementById('pagos-help-backdrop-admin');
    const closeBtn = document.getElementById('pagos-help-close-admin');
    const zoomIn = document.getElementById('pagos-help-zoom-in-admin');
    const zoomOut = document.getElementById('pagos-help-zoom-out-admin');
    const zoomReset = document.getElementById('pagos-help-zoom-reset-admin');
    const stage = document.getElementById('pagos-help-stage-admin');
    const img = document.getElementById('pagos-help-image-admin');
    if (!viewer || !img) return;
    let open = false, scale = 1, x = 0, y = 0, dragging = false, sx = 0, sy = 0;
    function apply(){ img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')'; if (zoomReset) zoomReset.textContent = Math.round(scale*100)+'%'; }
    function openViewer(){ if (open) return; open = true; viewer.style.display = 'block'; viewer.classList.add('open'); viewer.setAttribute('aria-hidden','false'); scale = 1; x = 0; y = 0; apply(); document.body.style.overflow='hidden'; }
    function closeViewer(){ if (!open) return; open = false; viewer.classList.remove('open'); viewer.setAttribute('aria-hidden','true'); setTimeout(()=> viewer.style.display='none',180); document.body.style.overflow=''; }
    openBtn?.addEventListener('click', function(e){ e.preventDefault(); open ? closeViewer() : openViewer(); });
    openLink?.addEventListener('click', function(e){ e.preventDefault(); open ? closeViewer() : openViewer(); });
    closeBtn?.addEventListener('click', function(e){ e.preventDefault(); closeViewer(); });
    viewer.addEventListener('click', function(e){ if (e.target === viewer || e.target === backdrop) closeViewer(); });
    zoomIn?.addEventListener('click', function(){ scale = Math.min(4, scale + 0.2); apply(); });
    zoomOut?.addEventListener('click', function(){ scale = Math.max(1, scale - 0.2); apply(); });
    zoomReset?.addEventListener('click', function(){ scale = 1; x = 0; y = 0; apply(); });
    stage?.addEventListener('wheel', function(e){ if (!open) return; e.preventDefault(); scale = Math.min(4, Math.max(1, scale + (e.deltaY < 0 ? 0.18 : -0.18))); apply(); }, { passive:false });
    stage?.addEventListener('mousedown', function(e){ if (scale <= 1) return; dragging = true; sx = e.clientX; sy = e.clientY; stage.classList.add('dragging'); });
    window.addEventListener('mousemove', function(e){ if (!dragging) return; x += e.clientX - sx; y += e.clientY - sy; sx = e.clientX; sy = e.clientY; apply(); });
    window.addEventListener('mouseup', function(){ if (dragging){ dragging = false; stage.classList.remove('dragging'); } });
    stage?.addEventListener('dblclick', function(){ scale = (scale > 1) ? 1 : 2; x = 0; y = 0; apply(); });
    document.addEventListener('keydown', function(e){ if (!open) return; if (e.key === 'Escape') closeViewer(); if (e.key === '+' || e.key === '=') { scale = Math.min(4, scale + 0.2); apply(); } if (e.key === '-') { scale = Math.max(1, scale - 0.2); apply(); } });
  })();
});
</script>

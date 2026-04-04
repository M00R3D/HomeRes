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
</style>

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">Pagos</h1>
      <div class="small" style="margin-top:6px;">CRUD de pagos vinculados a reservaciones y tarjetas</div>
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
@endsection

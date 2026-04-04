@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos')

@section('content')
<style>
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
    <h1 style="margin:0;">{{ ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos' }}</h1>
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
@endsection

@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Códigos QR (todos)' : 'Mis códigos')

@section('content')
<style>
  .pagos-codes-pagination {
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
    padding-top: 12px;
  }
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
@endsection

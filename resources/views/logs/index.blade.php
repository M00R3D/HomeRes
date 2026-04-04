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
</style>
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1>Logs</h1>
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
</div>
@endsection

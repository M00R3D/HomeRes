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
@endsection

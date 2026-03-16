@extends('layouts.app')

@section('title','Notificaciones')

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  <h1>Notificaciones</h1>

  <form method="GET" style="display:flex;gap:8px;margin-bottom:12px;">
    <select name="filter">
      <option value="all">Todas</option>
      <option value="unread" {{ request('filter')=='unread' ? 'selected':'' }}>Sin leer</option>
    </select>
    <input type="text" name="type" placeholder="Tipo" value="{{ request('type') }}">
    <button class="btn">Filtrar</button>
  </form>

  <div class="card table-card">
    <table class="table">
      <thead>
        <tr><th>Title</th><th>Body</th><th>Fecha</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        @foreach($notifications as $n)
        <tr style="{{ $n->read_at ? 'opacity:.6':'' }}">
          <td>{{ $n->data['title'] ?? '' }}</td>
          <td style="max-width:480px;overflow:hidden;text-overflow:ellipsis">{{ $n->data['body'] ?? '' }}</td>
          <td>{{ $n->created_at->diffForHumans() }}</td>
          <td>
            @if(!$n->read_at)
              <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="display:inline">@csrf<button class="btn-alt">Marcar leído</button></form>
            @endif
            <form method="POST" action="{{ route('notifications.destroy', $n->id) }}" style="display:inline">@csrf @method('DELETE')<button class="btn-alt">Eliminar</button></form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div style="margin-top:12px">{{ $notifications->links() }}</div>
  </div>
</div>
@endsection

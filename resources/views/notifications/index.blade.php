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
          <td style="white-space:normal;overflow-wrap:break-word;word-break:break-word;max-width:320px">{{ $n->data['title'] ?? '' }}</td>
          <td style="max-width:760px;white-space:normal;overflow-wrap:break-word;word-break:break-word">{{ $n->data['body'] ?? '' }}</td>
          <td>{{ $n->created_at->diffForHumans() }}</td>
          <td>
            <a class="btn-alt" href="{{ route('notifications.show', $n->id) }}">Ver notificación</a>
            @php
              $resLink = $n->data['link'] ?? ($n->data['url'] ?? ($n->link ?? null));
              $resTipo = null;
              if(!empty($n->data['tipo'])){ $resTipo = strtolower($n->data['tipo']); }
              elseif(!empty($n->data['type'])){ $resTipo = strtolower($n->data['type']); }
              elseif(!empty($n->type)){ $resTipo = strtolower($n->type); }
              else {
                if($resLink){
                  $path = parse_url($resLink, PHP_URL_PATH) ?: '';
                  $path = trim($path, '/');
                  $parts = $path === '' ? [] : explode('/', $path);
                  $first = strtolower($parts[0] ?? '');
                  $map = [
                    'reservaciones' => 'reservacion',
                    'pagos' => 'pago',
                    'propiedades' => 'propiedad',
                    'usuarios' => 'usuario',
                    'tarjetas_simuladas' => 'tarjeta',
                    'tarjetas' => 'tarjeta',
                    'cabanas' => 'cabana',
                  ];
                  if(isset($map[$first])) $resTipo = $map[$first];
                  else {
                    if(substr($first, -2) === 'es') $resTipo = substr($first, 0, -2);
                    else $resTipo = rtrim($first, 's');
                  }
                } else {
                  $resTipo = 'recurso';
                }
              }
            @endphp
            @if($resLink)
              <a class="btn-alt" href="{{ $resLink }}" target="_blank" style="margin-left:8px">{{ 'ver ' .  $n->data['title']  }}</a>
            @endif
            @if(!$n->read_at)
              <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="display:inline;margin-left:8px">@csrf<button class="btn-alt">Marcar leído</button></form>
            @endif
            <form method="POST" action="{{ route('notifications.destroy', $n->id) }}" style="display:inline;margin-left:8px">@csrf @method('DELETE')<button class="btn-alt">Eliminar</button></form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div style="margin-top:12px">{{ $notifications->links() }}</div>
  </div>
</div>
@endsection

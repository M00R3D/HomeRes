@extends('layouts.app')

@section('title','Notificación')

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  <h1 style="margin-bottom:6px">Detalle de notificación</h1>
  <div class="card" style="margin-top:12px;background:#fbfdff;">
    <div style="padding:16px;">
      <div style="font-weight:700;font-size:1.05rem;white-space:normal;overflow-wrap:break-word;word-break:break-word">{{ $notification->data['title'] ?? 'Notificación' }}</div>
      <div style="color:#6b7280;margin-top:8px;white-space:pre-wrap;overflow-wrap:break-word;word-break:break-word">{{ $notification->data['body'] ?? '' }}</div>
      <div style="margin-top:12px;font-size:13px;color:#94a3b8">Creado: {{ $notification->created_at->toDateTimeString() }}</div>
      @if(!empty($link))
        @php
          $resTipo = null;
          if(!empty($notification->data['tipo'])){ $resTipo = strtolower($notification->data['tipo']); }
          elseif(!empty($notification->data['type'])){ $resTipo = strtolower($notification->data['type']); }
          else {
            $path = parse_url($link, PHP_URL_PATH) ?: '';
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
            else { if(substr($first, -2) === 'es') $resTipo = substr($first, 0, -2); else $resTipo = rtrim($first, 's'); }
            if(empty($resTipo)) $resTipo = 'recurso';
          }
        @endphp
        <div style="margin-top:12px">
          <a href="{{ $link }}" class="btn">{{ 'ver ' . $resTipo }}</a>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

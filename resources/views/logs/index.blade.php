@extends('layouts.app')

@section('title','Logs')

@section('content')
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

    <div style="margin-top:12px;display:flex;justify-content:center">
      {{ $logs->links() }}
    </div>
  </div>
</div>
@endsection

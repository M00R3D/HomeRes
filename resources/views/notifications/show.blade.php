@extends('layouts.app')

@section('title','Notificación')

@section('content')
<div style="max-width:820px;margin:20px auto;padding:12px;">
  <h1>Detalle de notificación</h1>
  <div class="card" style="margin-top:12px;">
    <div style="padding:12px;">
      <div style="font-weight:700;font-size:1.05rem">{{ $notification->data['title'] ?? 'Notificación' }}</div>
      <div style="color:#6b7280;margin-top:8px">{{ $notification->data['body'] ?? '' }}</div>
      <div style="margin-top:12px;font-size:13px;color:#94a3b8">Creado: {{ $notification->created_at->toDateTimeString() }}</div>
      @if(!empty($link))
        <div style="margin-top:12px">
          <a href="{{ $link }}" class="btn">Abrir recurso</a>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

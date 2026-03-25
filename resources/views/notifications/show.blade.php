@extends('layouts.app')

@section('title','Notificación')

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <h1 style="margin-bottom:6px">Detalle de notificación</h1>
    <a href="{{ route('notifications.index') }}" class="btn-alt">Volver</a>
  </div>
  <div class="card" style="margin-top:12px;background:#fbfdff;">
    <div style="padding:16px;">
      <div style="display:flex;align-items:flex-start;gap:12px;">
        <span aria-hidden="true" style="width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;background:{{ $notificationPresentation['color'] ?? '#3b82f6' }};color:#fff;flex:0 0 36px;">{{ $notificationPresentation['symbol'] ?? 'i' }}</span>
        <div>
          <div style="font-weight:700;font-size:1.05rem;white-space:normal;overflow-wrap:break-word;word-break:break-word">{{ $notification->data['title'] ?? 'Notificación' }}</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px;">{{ $notificationPresentation['label'] ?? 'Informacion' }}</div>
        </div>
      </div>
      <div style="color:#6b7280;margin-top:8px;white-space:pre-wrap;overflow-wrap:break-word;word-break:break-word">{{ $notification->data['body'] ?? '' }}</div>
      <div style="margin-top:12px;font-size:13px;color:#94a3b8">Creado: {{ $notification->created_at->toDateTimeString() }}</div>
      @if(!empty($link) && !empty($notificationPresentation['allow_resource']))
        <div style="margin-top:12px">
          <a href="{{ $link }}" class="btn">{{ 'Ver ' . ($notificationPresentation['resource_label'] ?? 'recurso') }}</a>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

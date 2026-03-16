@extends('layouts.app')

@section('title','Preferencias de notificaciones')

@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Preferencias de notificaciones</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <form method="POST" action="{{ route('notifications.preferences.save') }}">
    @csrf
    <div style="display:flex;flex-direction:column;gap:12px;">
      <label><input type="checkbox" name="channel_email" {{ ($prefs && $prefs->channel_email) ? 'checked':'' }}> Email</label>
      <label><input type="checkbox" name="channel_inapp" {{ ($prefs && $prefs->channel_inapp) ? 'checked':'' }}> In-app (campana)</label>
      <label><input type="checkbox" name="receive_push" {{ ($prefs && $prefs->receive_push) ? 'checked':'' }}> Recibir push</label>
      <button class="btn" type="submit">Guardar</button>
    </div>
  </form>
</div>
@endsection

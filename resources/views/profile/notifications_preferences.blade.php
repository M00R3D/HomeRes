@extends('layouts.app')

@section('title','Preferencias de notificaciones')

@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Preferencias de notificaciones</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <form method="POST" action="{{ route('notifications.preferences.save') }}">
    @csrf
    <div style="display:flex;flex-direction:column;gap:12px;">
      <label><input type="checkbox" name="channel_inapp" {{ ($prefs && $prefs->channel_inapp) ? 'checked':'' }}> In-app (campana)</label>
      <label><input type="checkbox" name="receive_push" {{ ($prefs && $prefs->receive_push) ? 'checked':'' }}> Recibir push</label>

      {{-- No theme selector here: site-wide theme is controlled by admins. --}}

      @php $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin'); @endphp
      @if($isAdmin)
        <hr />
        <h3>Admin — Editar usuario</h3>
        <label>Nombre<br><input type="text" name="user_nombre" value="{{ old('user_nombre', $currentUser->nombre ?? '') }}" style="width:100%"></label>
        <label>Apellido<br><input type="text" name="user_apellido" value="{{ old('user_apellido', $currentUser->apellido ?? '') }}" style="width:100%"></label>
        <label>Email<br><input type="email" name="user_email" value="{{ old('user_email', $currentUser->email ?? '') }}" style="width:100%"></label>

        <h4>Propiedades</h4>
        <div style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow:auto;padding:6px;border:1px solid #f3f4f6;border-radius:8px;background:#fff;">
          @foreach($propiedades as $p)
            @php $checked = false; if($prefs && !empty($prefs->categories)) { $cats = is_array($prefs->categories) ? $prefs->categories : json_decode($prefs->categories, true); $checked = in_array($p->id, $cats ?? []); } @endphp
            <label><input type="checkbox" name="propiedades[]" value="{{ $p->id }}" {{ $checked ? 'checked':'' }}> {{ $p->nombre }}</label>
          @endforeach
        </div>
      @endif
      <button class="btn" type="submit">Guardar</button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // No per-user theme controls (admin-only global theme)
});
</script>
@endpush

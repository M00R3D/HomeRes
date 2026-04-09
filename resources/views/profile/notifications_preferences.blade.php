@extends('layouts.app')

@section('title','Preferencias de notificaciones')

@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Preferencias de notificaciones</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif
  @if($errors->any())<div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ $errors->first() }}</div>@endif

  @php $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin'); @endphp

  <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.04);">
    <h2>Canales de notificación</h2>
    @php
      $inAppEnabled = $prefs ? (bool) ($prefs->channel_inapp ?? false) : true;
      $pushEnabled = $prefs ? (bool) ($prefs->receive_push ?? false) : true;
    @endphp
    <form method="POST" action="{{ route('notifications.preferences.save') }}" style="margin:0 0 14px 0;padding:12px;border:1px solid #eef2f7;border-radius:10px;background:#f8fafc;">
      @csrf
      <input type="hidden" name="settings_scope" value="notifications">
      <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="display:flex;gap:8px;align-items:center;">
          <input type="checkbox" name="channel_inapp" value="1" {{ $inAppEnabled ? 'checked' : '' }}>
          <span>Recibir notificaciones in app</span>
        </label>
      </div>
      <div style="margin-top:10px;">
        <button class="btn" type="submit">Guardar preferencias de notificación</button>
      </div>
    </form>

    <h2>Información de cuenta</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start;margin-bottom:12px;">
      <div>
        <div style="font-weight:700">Nombre</div>
        <div>{{ $currentUser->nombre ?? '-' }}</div>
      </div>
      <div>
        <div style="font-weight:700">Apellido</div>
        <div>{{ $currentUser->apellido ?? '-' }}</div>
      </div>
      <div>
        <div style="font-weight:700">Email</div>
        <div>{{ $currentUser->email ?? '-' }}</div>
      </div>
      
    </div>

      <div style="margin-top:8px;">
        <div style="display:flex;gap:8px;align-items:center">
          @if($isAdmin)
          <div style="font-weight:700;margin-bottom:6px;">Contraseña</div>
        <input id="pw-mask" type="password" value="********" disabled style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;min-width:240px;">
          <button id="btn-toggle-edit" type="button" class="action-btn view">Editar</button>
        @endif
      </div>

      <div id="pw-edit-area" style="display:none;margin-top:12px;border-top:1px dashed #eef2f7;padding-top:12px;">
        <form method="POST" action="{{ route('notifications.updatePassword') }}">
          @csrf
          <div style="display:flex;flex-direction:column;gap:8px;max-width:420px">
            @if($isAdmin)
              <div style="color:#6b7280;font-size:0.95rem;">Nota: por seguridad las contraseñas están almacenadas en forma segura y no pueden mostrarse en texto plano. Puedes establecer una nueva contraseña a continuación.</div>

              <label>Nueva contraseña<br><input name="new_password" type="password" class="pw-input" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee;"></label>
              <label>Confirmar nueva contraseña<br><input name="new_password_confirmation" type="password" class="pw-input" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee;"></label>

              <label style="display:flex;align-items:center;gap:8px;"><input id="pw-reveal" type="checkbox"> Mostrar contraseñas</label>
              <div style="display:flex;gap:8px;">
                <button type="submit" class="action-btn primary">Guardar contraseña</button>
                <button id="pw-cancel" type="button" class="action-btn view">Cancelar</button>
              </div>
            @else
              <div style="color:#6b7280;font-size:0.95rem;">Por seguridad no es posible mostrar la contraseña en texto plano ni editarla desde aquí. Si necesitas cambiarla usa la opción de recuperar contraseña o contacta al administrador.</div>
              <div style="margin-top:8px;"><button id="pw-close-only" type="button" class="action-btn view">Cerrar</button></div>
            @endif
          </div>
        </form>
      </div>
    </div>
  </div>

  @if($isAdmin)
    <div style="margin-top:14px;">
      <hr />
      <h3>Admin — Preferencias y edición</h3>
      <form method="POST" action="{{ route('notifications.preferences.save') }}">
        @csrf
        <input type="hidden" name="settings_scope" value="admin_profile">
        <div style="display:flex;flex-direction:column;gap:12px;">
          <label>Nombre<br><input type="text" name="user_nombre" value="{{ old('user_nombre', $currentUser->nombre ?? '') }}" style="width:100%"></label>
          <label>Apellido<br><input type="text" name="user_apellido" value="{{ old('user_apellido', $currentUser->apellido ?? '') }}" style="width:100%"></label>
          <label>Email<br><input type="email" name="user_email" value="{{ old('user_email', $currentUser->email ?? '') }}" style="width:100%"></label>

        

          <button class="btn" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Toggle password edit area
  const btnToggle = document.getElementById('btn-toggle-edit');
  const pwEdit = document.getElementById('pw-edit-area');
  const pwCancel = document.getElementById('pw-cancel');
  const pwReveal = document.getElementById('pw-reveal');
  const pwInputs = document.querySelectorAll('.pw-input');
  if(btnToggle){
    btnToggle.addEventListener('click', function(){
      // If admin, toggle edit area; if not admin, show view-only message area
      var isAdmin = {{ $isAdmin ? 'true' : 'false' }};
      if(isAdmin){
        if(pwEdit.style.display === 'none' || pwEdit.style.display === '') pwEdit.style.display = 'block';
        else pwEdit.style.display = 'none';
      } else {
        // show edit area (contains view-only message) when non-admin clicks Ver
        pwEdit.style.display = 'block';
      }
    });
  }
  if(pwCancel){ pwCancel.addEventListener('click', function(){ pwEdit.style.display = 'none'; }); }
  // close-only button for non-admins
  var pwCloseOnly = document.getElementById('pw-close-only');
  if(pwCloseOnly){ pwCloseOnly.addEventListener('click', function(){ pwEdit.style.display = 'none'; }); }
  if(pwReveal){ pwReveal.addEventListener('change', function(){ pwInputs.forEach(i => i.type = this.checked ? 'text' : 'password'); }); }
});
</script>
@endpush

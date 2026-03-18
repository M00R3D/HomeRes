@extends('layouts.app')

@section('title', 'Editar usuario - ' . ($user->nombre ?? ''))

@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Editar usuario</h1>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('users.update', $user->id) }}">
    @csrf
    @method('PUT')

    <label class="field"><span class="label-text">Nombre</span><input name="nombre" value="{{ old('nombre', $user->nombre) }}" required /></label>
    <label class="field"><span class="label-text">Apellido</span><input name="apellido" value="{{ old('apellido', $user->apellido) }}" /></label>
    <label class="field"><span class="label-text">Email</span><input type="email" name="email" value="{{ old('email', $user->email) }}" required /></label>

    <label class="field"><span class="label-text">Nueva contraseña (dejar vacío para mantener)</span><input type="password" name="password" /></label>
    <label class="field"><span class="label-text">Confirmar contraseña</span><input type="password" name="password_confirmation" /></label>

    <label class="field"><span class="label-text">Rol</span>
      <select name="rol">
        <option value="cliente" {{ (old('rol', $user->rol) === 'cliente') ? 'selected' : '' }}>cliente</option>
        <option value="recepcionista" {{ (old('rol', $user->rol) === 'recepcionista') ? 'selected' : '' }}>recepcionista</option>
        <option value="admin" {{ (old('rol', $user->rol) === 'admin') ? 'selected' : '' }}>admin</option>
      </select>
    </label>

    <label class="field"><span class="label-text">Área</span><input name="area" value="{{ old('area', $user->area) }}" /></label>

    <label class="field"><span class="label-text">Tarjeta asignada</span>
      <select name="id_tarjeta">
        <option value="">-- Ninguna --</option>
        @foreach($tarjetas as $t)
          <option value="{{ $t->id }}" {{ ($user->id_tarjeta == $t->id) ? 'selected' : '' }}>{{ $t->numero_tarjeta }} — {{ $t->nombre }}</option>
        @endforeach
      </select>
    </label>

    <label class="field"><span class="label-text">Intentos CVV</span><input type="number" name="intentos_cvv" value="{{ old('intentos_cvv', $user->intentos_cvv ?? 0) }}" min="0" /></label>

    <label class="field"><span class="label-text">Bloquear pagos con tarjeta</span>
      <select name="bloqueo_tarjetas">
        <option value="0" {{ (!$user->bloqueo_tarjetas) ? 'selected' : '' }}>Activo</option>
        <option value="1" {{ ($user->bloqueo_tarjetas) ? 'selected' : '' }}>Bloqueado</option>
      </select>
    </label>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
      <button class="btn" type="submit">Guardar</button>
      <a class="btn btn-alt" href="{{ route('users.index') }}">Cancelar</a>
    </div>
  </form>

  @if(($user->rol ?? '') !== 'admin')
  <div style="border:1px solid #fecaca;border-radius:8px;padding:12px;margin-top:12px;background:#fff5f5;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <div>
        <strong style="color:#991b1b;">Ban de cuenta</strong>
        <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">El usuario baneado no puede iniciar sesión en el sistema.</p>
      </div>
      <form method="POST" action="{{ route('users.toggleBan', $user->id) }}" id="ban-toggle-form">
        @csrf
        @if($user->baneado ?? false)
          <button type="button" class="btn" style="background:#16a34a;color:#fff;" data-ban-confirm data-confirm-title="Confirmar desbaneo" data-confirm-message="¿Quitar ban a este usuario?" data-confirm-ok="Quitar ban" data-confirm-ok-class="btn">Quitar ban</button>
        @else
          <button type="button" class="btn btn-danger" data-ban-confirm data-confirm-title="Confirmar ban" data-confirm-message="¿Banear a este usuario? No podrá iniciar sesión." data-confirm-ok="Banear usuario" data-confirm-ok-class="btn btn-danger">Banear usuario</button>
        @endif
      </form>
    </div>
  </div>
  @endif

  <div id="ban-confirm-overlay" class="confirm-overlay" aria-hidden="true" style="display:none;">
    <div class="confirm-card" role="dialog" aria-modal="true" aria-labelledby="ban-confirm-title">
      <h3 id="ban-confirm-title" class="confirm-title">Confirmar acción</h3>
      <p id="ban-confirm-message" class="confirm-msg">¿Estás seguro?</p>
      <div class="confirm-actions">
        <button type="button" id="ban-confirm-cancel" class="btn btn-alt">Cancelar</button>
        <button type="button" id="ban-confirm-ok" class="btn btn-danger">Confirmar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const overlay = document.getElementById('ban-confirm-overlay');
  const title = document.getElementById('ban-confirm-title');
  const message = document.getElementById('ban-confirm-message');
  const ok = document.getElementById('ban-confirm-ok');
  const cancel = document.getElementById('ban-confirm-cancel');
  const form = document.getElementById('ban-toggle-form');

  if (!overlay || !ok || !cancel || !form) return;

  let pending = null;

  function show() {
    overlay.setAttribute('aria-hidden', 'false');
    overlay.style.display = 'flex';
  }

  function hide() {
    overlay.setAttribute('aria-hidden', 'true');
    overlay.style.display = 'none';
    pending = null;
  }

  document.querySelectorAll('[data-ban-confirm]').forEach((btn) => {
    btn.addEventListener('click', function(){
      pending = this;
      title.textContent = this.getAttribute('data-confirm-title') || 'Confirmar acción';
      message.textContent = this.getAttribute('data-confirm-message') || '¿Estás seguro?';
      ok.textContent = this.getAttribute('data-confirm-ok') || 'Confirmar';
      ok.className = this.getAttribute('data-confirm-ok-class') || 'btn btn-danger';
      show();
      cancel.focus();
    });
  });

  cancel.addEventListener('click', hide);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) hide(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hide(); });

  ok.addEventListener('click', function(){
    if (!pending) return hide();
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
    hide();
  });
})();
</script>
@endpush

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
</div>
@endsection

@extends('layouts.app')

@section('title','Editar reservación #' . ($r->id ?? ''))

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  @php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin');
  @endphp

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0">Editar reservación #{{ $r->id }}</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('reservaciones.index') }}" class="link-button">Volver a la lista</a>
    </div>
  </div>

  @if(! $isAdmin)
    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px;">Acceso denegado. Solo administradores pueden editar reservaciones.</div>
  @endif

  <form method="POST" action="{{ route('reservaciones.update', $r->id) }}" style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    @csrf
    <input type="hidden" name="_method" value="PUT">

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <div style="flex:1;min-width:260px;">
        <label class="small">Propiedad</label>
        <select name="propiedad_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="">-- seleccionar --</option>
          @foreach($propiedades as $p)
            <option value="{{ $p->id }}" {{ ($r->propiedad_id == $p->id) ? 'selected' : '' }}>{{ $p->nombre }} {{ $p->codigo ? '· ' . $p->codigo : '' }}</option>
          @endforeach
        </select>

        <label class="small" style="margin-top:8px;">Check-in</label>
        <input name="check_in" type="date" value="{{ $r->check_in }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;">Check-out</label>
        <input name="check_out" type="date" value="{{ $r->check_out }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
      </div>

      <div style="flex:1;min-width:260px;">
        <label class="small">Usuario</label>
        <select name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="">-- seleccionar cliente --</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}" {{ ($r->usuario_id == $u->id) ? 'selected' : '' }}>{{ $u->nombre }} {{ $u->apellido }}</option>
          @endforeach
        </select>

        <label class="small" style="margin-top:8px;">Número de personas</label>
        <input name="num_personas" type="number" min="1" value="{{ $r->num_personas }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;">Total</label>
        <input name="total" type="number" step="0.01" value="{{ $r->total }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;">Estado</label>
        <select name="estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="pendiente" {{ $r->estado==='pendiente' ? 'selected' : '' }}>Pendiente</option>
          <option value="confirmada" {{ $r->estado==='confirmada' ? 'selected' : '' }}>Confirmada</option>
          <option value="cancelada" {{ $r->estado==='cancelada' ? 'selected' : '' }}>Cancelada</option>
          <option value="completada" {{ $r->estado==='completada' ? 'selected' : '' }}>Completada</option>
        </select>

        <label class="small" style="margin-top:8px;">Nota</label>
        <textarea name="nota" rows="4" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">{{ $r->nota }}</textarea>
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>
      <button type="submit" class="action-btn primary">Guardar cambios</button>
      <a href="{{ route('reservaciones.index') }}" class="action-btn" style="background:#fff;border:1px solid #e5e7eb;">Cancelar</a>
    </div>
  </form>
</div>

@endsection

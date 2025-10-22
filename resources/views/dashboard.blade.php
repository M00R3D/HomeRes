@extends('layouts.app')

@section('content')
  <div class="page-header">
    <h1>Reservaciones</h1>
    <div class="actions">
      <button id="open-new" class="btn-primary">Nueva Reservación</button>
    </div>
  </div>

  <div class="card table-card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Estado</th>
            <th>Casita</th>
            <th>Costo</th>
            <th>Llegada</th>
            <th>Salida</th>
            <th>Detalle</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reservaciones ?? [] as $r)
            <tr>
              <td class="estado {{ \Illuminate\Support\Str::slug($r->estado ?? 'pendiente') }}">{{ $r->estado ?? 'pendiente' }}</td>
              <td>{{ $r->cabana_nombre ?? $r->cabana_id ?? '-' }}</td>
              <td>${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>
              <td>{{ isset($r->check_in) ? \Carbon\Carbon::parse($r->check_in)->format('d M Y') : '-' }}</td>
              <td>{{ isset($r->check_out) ? \Carbon\Carbon::parse($r->check_out)->format('d M Y') : '-' }}</td>
              <td><button class="link-button" onclick="alert('Detalle: {{ addslashes($r->nota ?? '') }}')">Ver</button></td>
              <td>
                <a class="link-button" href="#">Editar</a>
                <a class="link-button danger" href="#">Borrar</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="muted">No hay reservaciones aún.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal (simple) -->
  <div id="modal-new" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <button class="modal-close" data-close>✕</button>
      <h3>Nueva Reservación</h3>
      <form method="POST" action="#" class="form-grid">
        @csrf
        <label><span>Casita</span><input name="cabana_id" /></label>
        <label><span>Check-in</span><input type="date" name="check_in" /></label>
        <label><span>Check-out</span><input type="date" name="check_out" /></label>
        <label><span>Personas</span><input type="number" name="num_personas" min="1" /></label>
        <label class="full"><button type="button" id="save-new" class="btn-primary">Guardar</button></label>
      </form>
    </div>
  </div>

  <script>
    // modal minimal
    (function () {
      const open = document.getElementById('open-new');
      const modal = document.getElementById('modal-new');
      const closes = modal && modal.querySelectorAll('[data-close]');
      function show(){ modal && modal.setAttribute('aria-hidden','false'); modal && modal.classList.add('open'); }
      function hide(){ modal && modal.setAttribute('aria-hidden','true'); modal && modal.classList.remove('open'); }
      open && open.addEventListener('click', show);
      closes && closes.forEach(c => c.addEventListener('click', hide));
    })();
  </script>
@endsection
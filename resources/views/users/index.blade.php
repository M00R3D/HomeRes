@extends('layouts.app')

@section('content')
  <div class="page-header">
    <h1>Usuarios</h1>
    <div class="actions">
      <button id="open-new" class="btn-primary">Nuevo Usuario</button>
    </div>
  </div>

  @if(session('success'))
    <div class="card" style="margin-bottom:12px;">
      <div class="muted">{{ session('success') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="card" style="margin-bottom:12px;">
      <div class="alert error">{{ $errors->first() }}</div>
    </div>
  @endif

  <div class="card table-card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Área</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users ?? [] as $user)
            <tr>
              <td>{{ $user->nombre }}</td>
              <td>{{ $user->apellido }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->rol }}</td>
              <td>{{ $user->area ?? '-' }}</td>
              <td>
                <button class="link-button" 
                        data-edit
                        data-id="{{ $user->id }}"
                        data-nombre="{{ e($user->nombre) }}"
                        data-apellido="{{ e($user->apellido) }}"
                        data-email="{{ e($user->email) }}"
                        data-rol="{{ $user->rol }}"
                        data-area="{{ e($user->area) }}"
                        data-update-url="{{ route('users.update', $user->id) }}">
                  Editar
                </button>

                <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display:inline" onsubmit="return confirm('¿Borrar usuario {{ addslashes($user->nombre) }}?');">
                  @csrf
                  @method('DELETE')
                  <button class="link-button danger" type="submit">Borrar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="muted">No hay usuarios registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal nuevo -->
  <div id="modal-new" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <button class="modal-close" data-close>✕</button>
      <h3>Nuevo usuario</h3>

      <form method="POST" action="{{ route('users.store') }}" class="form">
        @csrf
        <label class="field"><span class="label-text">Nombre</span><input name="nombre" required /></label>
        <label class="field"><span class="label-text">Apellido</span><input name="apellido" /></label>
        <label class="field"><span class="label-text">Email</span><input type="email" name="email" required /></label>
        <label class="field"><span class="label-text">Contraseña</span><input type="password" name="password" required /></label>
        <label class="field"><span class="label-text">Confirmar contraseña</span><input type="password" name="password_confirmation" required /></label>
        <label class="field"><span class="label-text">Rol</span>
          <select name="rol">
            <option value="cliente">cliente</option>
            <option value="recepcionista">recepcionista</option>
            <option value="admin">admin</option>
          </select>
        </label>
        <label class="field"><span class="label-text">Área</span><input name="area" /></label>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
          <button class="btn" type="submit">Crear</button>
          <button type="button" class="btn alt" data-close>Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal editar -->
  <div id="modal-edit" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <button class="modal-close" data-close>✕</button>
      <h3>Editar usuario</h3>

      <form id="form-edit" method="POST" action="#" class="form">
        @csrf
        @method('PUT')
        <label class="field"><span class="label-text">Nombre</span><input id="e-nombre" name="nombre" required /></label>
        <label class="field"><span class="label-text">Apellido</span><input id="e-apellido" name="apellido" /></label>
        <label class="field"><span class="label-text">Email</span><input id="e-email" type="email" name="email" required /></label>
        <label class="field"><span class="label-text">Nueva contraseña (dejar vacío para mantener)</span><input id="e-password" type="password" name="password" /></label>
        <label class="field"><span class="label-text">Rol</span>
          <select id="e-rol" name="rol">
            <option value="cliente">cliente</option>
            <option value="recepcionista">recepcionista</option>
            <option value="admin">admin</option>
          </select>
        </label>
        <label class="field"><span class="label-text">Área</span><input id="e-area" name="area" /></label>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
          <button class="btn" type="submit">Guardar</button>
          <button type="button" class="btn alt" data-close>Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function(){
      // open/close modal helpers
      function show(modal){ modal && modal.setAttribute('aria-hidden','false'); modal && modal.classList.add('open'); }
      function hide(modal){ modal && modal.setAttribute('aria-hidden','true'); modal && modal.classList.remove('open'); }

      const modalNew = document.getElementById('modal-new');
      const modalEdit = document.getElementById('modal-edit');

      document.getElementById('open-new').addEventListener('click', function(){ show(modalNew); });

      // close buttons
      document.querySelectorAll('[data-close]').forEach(el=>{
        el.addEventListener('click', function(){
          hide(modalNew);
          hide(modalEdit);
        });
      });

      // populate edit modal
      document.querySelectorAll('[data-edit]').forEach(btn=>{
        btn.addEventListener('click', function(){
          const id = this.dataset.id;
          const nombre = this.dataset.nombre || '';
          const apellido = this.dataset.apellido || '';
          const email = this.dataset.email || '';
          const rol = this.dataset.rol || 'cliente';
          const area = this.dataset.area || '';
          const updateUrl = this.dataset.updateUrl;

          // fill fields
          document.getElementById('e-nombre').value = nombre;
          document.getElementById('e-apellido').value = apellido;
          document.getElementById('e-email').value = email;
          document.getElementById('e-rol').value = rol;
          document.getElementById('e-area').value = area;
          const form = document.getElementById('form-edit');
          form.action = updateUrl;
          // clear password
          document.getElementById('e-password').value = '';
          show(modalEdit);
        });
      });

      // close on Esc
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ hide(modalNew); hide(modalEdit); }});
    })();
  </script>
@endsection
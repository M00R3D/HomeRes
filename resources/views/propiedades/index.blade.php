@extends('layouts.app')

@section('title', 'Propiedades')

@section('content')
<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">

<div class="pr-container">
  <header class="pr-hero">
    <div>
      <h1>Propiedades</h1>
      @if(session('success'))
        <div class="pr-alert pr-success">{{ session('success') }}</div>
      @endif
    </div>

    <div class="pr-actions">
      <a href="{{ route('dashboard') }}" class="pr-link">Volver</a>
      @if(auth()->check() && auth()->user()->rol === 'admin')
        <button id="pr-new" class="pr-btn">Nueva propiedad</button>
      @endif
    </div>
  </header>

  @if($errors->any())
    <div class="pr-alert" style="background:#fff6f6;color:#7f1d1d;margin-bottom:12px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form id="pr-filters" method="GET" class="pr-filters" action="{{ route('propiedades.index') }}">
    <input type="text" id="pr-q" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o código" />
    <select name="tipo">
      <option value="">Todos los tipos</option>
      <option value="cabaña" {{ request('tipo')=='cabaña' ? 'selected' : '' }}>Cabaña</option>
      <option value="casa" {{ request('tipo')=='casa' ? 'selected' : '' }}>Casa</option>
      <option value="departamento" {{ request('tipo')=='departamento' ? 'selected' : '' }}>Departamento</option>
    </select>
    <select name="estado">
      <option value="">Cualquier estado</option>
      <option value="disponible" {{ request('estado')=='disponible' ? 'selected' : '' }}>Disponible</option>
      <option value="ocupada" {{ request('estado')=='ocupada' ? 'selected' : '' }}>Ocupada</option>
      <option value="mantenimiento" {{ request('estado')=='mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
    </select>
    <input name="max_precio" type="number" step="0.01" value="{{ request('max_precio') }}" placeholder="Máx. precio noche" />
    <button type="submit" class="pr-btn alt">Filtrar</button>
    <button type="button" id="pr-clear" class="pr-btn danger">Limpiar</button>
  </form>

  <div class="card table-card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Código</th>
            <th>Tipo</th>
            <th>Precio / noche</th>
            <th>Capacidad</th>
            <th>Estado</th>
            <th style="text-align:right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($propiedades as $prop)
            <tr>
              <td style="width:120px;">
                @if(!empty($prop->ruta_img))
                  <img src="{{ asset($prop->ruta_img) }}" alt="{{ $prop->nombre }}" style="width:100px;height:64px;object-fit:cover;border-radius:8px;">
                @else
                  <div style="width:100px;height:64px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:8px;color:#9ca3af">Sin imagen</div>
                @endif
              </td>
              <td style="min-width:180px;">{{ $prop->nombre }}</td>
              <td>{{ $prop->codigo }}</td>
              <td>{{ $prop->tipo }}</td>
              <td>${{ number_format($prop->precio_noche ?? 0, 2, ',', '.') }}</td>
              <td>{{ $prop->capacidad ?? '-' }}</td>
              <td><span class="pr-estado {{ $prop->estado }}">{{ $prop->estado }}</span></td>
              <td style="text-align:right;white-space:nowrap;">
                <div class="btn-group">
                  <button
                    type="button"
                    class="action-btn edit"
                    data-view-btn
                    data-prop='@json($prop)'
                    title="Ver detalle"
                  >Ver</button>

                  <button
                    type="button"
                    class="action-btn edit"
                    data-edit
                    data-prop='@json($prop)'
                    data-update-url="{{ route('propiedades.update', $prop->id) }}"
                    title="Editar"
                  >Editar</button>

                  <form method="POST" action="{{ route('propiedades.destroy', $prop->id) }}" style="display:inline" class="form-delete">
                    @csrf
                    @method('DELETE')
                    <button class="action-btn delete" type="button" data-delete-confirm="¿Eliminar propiedad {{ addslashes($prop->nombre ?? $prop->codigo) }}?" style="margin-left:6px;">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="muted">No hay propiedades registradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{ $propiedades->withQueryString()->links() ?? '' }}
</div>

<div id="modal-prop-new" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel">
    <button class="modal-close" data-close>✕</button>
    <h3>Crear propiedad</h3>

    <form id="form-prop-new" method="POST" action="{{ route('propiedades.store') }}" class="form">
      @csrf
      <div class="pr-grid" style="grid-template-columns:1fr 140px;align-items:end;gap:10px">
        <label class="field" style="grid-column:1">
          <span class="label-text">Tipo</span>
          <select name="tipo" required>
            <option value="cabaña">cabaña</option>
            <option value="casa">casa</option>
            <option value="departamento">departamento</option>
          </select>
        </label>

        <label class="field" style="grid-column:2">
          <span class="label-text">Precio / noche</span>
          <div style="display:flex;gap:6px;align-items:center;">
            <input id="new-precio-pesos" inputmode="numeric" pattern="[0-9]*" placeholder="Pesos" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
            <span style="font-weight:700;color:#6b7280;">.</span>
            <input id="new-precio-centavos" inputmode="numeric" pattern="[0-9]{0,2}" placeholder="¢" style="width:64px;padding:8px;border-radius:8px;border:1px solid #e5e7eb;text-align:center;" />
          </div>
          <input type="hidden" name="precio_noche" id="new-precio-hidden" />
          <div style="font-size:12px;color:#6b7280;margin-top:6px;">Escribe pesos y centavos por separado (ej: 120 y 50 → 120.50)</div>
        </label>
      </div>

      <label class="field"><span class="label-text">Código</span><input name="codigo" required /></label>
      <label class="field"><span class="label-text">Nombre</span><input name="nombre" required /></label>
      <label class="field"><span class="label-text">Capacidad</span><input name="capacidad" type="number" min="1" required /></label>
      <label class="field"><span class="label-text">Ubicación</span><input name="ubicacion" /></label>

      <label class="field"><span class="label-text">Servicios</span>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
          <input id="service-input-new" placeholder="Agregar servicio (ej: Wifi)" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
          <button type="button" id="service-add-new" class="pr-btn" style="padding:8px 10px;">+</button>
        </div>
        <div id="service-chips-new" style="display:flex;gap:8px;flex-wrap:wrap;"></div>
        <input type="hidden" name="servicios" id="new-servicios-hidden" />
      </label>

      <label class="field"><span class="label-text">Estado</span>
        <select name="estado">
          <option value="disponible">disponible</option>
          <option value="ocupada">ocupada</option>
          <option value="mantenimiento">mantenimiento</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Imagen principal (ruta pública)</span>
        <div style="display:flex;gap:8px;align-items:center;">
          <input id="new-ruta_img" name="ruta_img" placeholder="uploads/mi.jpg" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
          <button type="button" id="btn-open-image-picker-new" class="pr-btn">Seleccionar</button>
        </div>
      </label>

      <label class="field"><span class="label-text">Descripción</span><textarea name="descripcion" rows="4"></textarea></label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Crear</button>
        <button type="button" class="btn btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-prop-edit" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel">
    <button class="modal-close" data-close>✕</button>
    <h3>Editar propiedad</h3>

    <form id="form-prop-edit" method="POST" action="#" class="form">
      @csrf
      @method('PUT')
      <input type="hidden" id="e-id" name="id" />
      <div class="pr-grid" style="grid-template-columns:1fr 140px;align-items:end;gap:10px">
        <label class="field" style="grid-column:1">
          <span class="label-text">Tipo</span>
          <select id="e-tipo" name="tipo" required>
            <option value="cabaña">cabaña</option>
            <option value="casa">casa</option>
            <option value="departamento">departamento</option>
          </select>
        </label>

        <label class="field" style="grid-column:2">
          <span class="label-text">Precio / noche</span>
          <div style="display:flex;gap:6px;align-items:center;">
            <input id="edit-precio-pesos" inputmode="numeric" pattern="[0-9]*" placeholder="Pesos" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
            <span style="font-weight:700;color:#6b7280;">.</span>
            <input id="edit-precio-centavos" inputmode="numeric" pattern="[0-9]{0,2}" placeholder="¢" style="width:64px;padding:8px;border-radius:8px;border:1px solid #e5e7eb;text-align:center;" />
          </div>
          <input type="hidden" name="precio_noche" id="edit-precio-hidden" />
        </label>
      </div>

      <label class="field"><span class="label-text">Código</span><input id="e-codigo" name="codigo" required /></label>
      <label class="field"><span class="label-text">Nombre</span><input id="e-nombre" name="nombre" required /></label>
      <label class="field"><span class="label-text">Capacidad</span><input id="e-capacidad" name="capacidad" type="number" min="1" required /></label>
      <label class="field"><span class="label-text">Ubicación</span><input id="e-ubicacion" name="ubicacion" /></label>

      <label class="field"><span class="label-text">Servicios</span>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
          <input id="service-input-edit" placeholder="Agregar servicio" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
          <button type="button" id="service-add-edit" class="pr-btn" style="padding:8px 10px;">+</button>
        </div>
        <div id="service-chips-edit" style="display:flex;gap:8px;flex-wrap:wrap;"></div>
        <input type="hidden" name="servicios" id="edit-servicios-hidden" />
      </label>

      <label class="field"><span class="label-text">Estado</span>
        <select id="e-estado" name="estado">
          <option value="disponible">disponible</option>
          <option value="ocupada">ocupada</option>
          <option value="mantenimiento">mantenimiento</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Imagen principal (ruta pública)</span>
        <div style="display:flex;gap:8px;align-items:center;">
          <input id="e-ruta_img" name="ruta_img" placeholder="uploads/mi.jpg" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" />
          <button type="button" id="btn-open-image-picker-edit" class="pr-btn">Seleccionar</button>
        </div>
      </label>

      <label class="field"><span class="label-text">Descripción</span><textarea id="e-descripcion" name="descripcion" rows="4"></textarea></label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Guardar</button>
        <button type="button" class="btn btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal selector de imágenes públicas (explorer) -->
<div id="modal-image-picker" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" style="max-width:900px;display:grid;grid-template-columns:240px 1fr;gap:12px;">
    <button class="modal-close" data-close>✕</button>
    <h3 style="grid-column:1 / -1;margin-top:0;">Seleccionar imagen pública</h3>

    <div style="display:flex;flex-direction:column;gap:8px;">
      <input id="picker-folder-filter" placeholder="Filtrar carpetas..." style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
      <div id="picker-folders" style="overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:8px;background:#fff;min-height:240px;"></div>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px;">
      <div style="display:flex;gap:8px;align-items:center;">
        <input id="picker-current-folder" readonly style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;background:#fafafa;">
        <button id="picker-refresh" class="pr-btn">Refrescar</button>
      </div>
      <div id="picker-files" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:8px;background:#fff;min-height:240px;"></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button id="picker-select" class="btn">Seleccionar</button>
        <button type="button" class="btn btn-alt" data-close>Cancelar</button>
      </div>
    </div>
  </div>
</div>

<div id="modal-prop-view" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" style="max-width:900px;">
    <button class="modal-close" data-close>✕</button>
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
      <div style="flex:1;min-width:320px;">
        <div id="view-main-img" style="background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;height:360px;overflow:hidden;">
        </div>
        <div id="view-thumbs" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;"></div>
      </div>
      <div style="width:360px;">
        <h3 id="view-nombre" style="margin:0 0 6px 0;"></h3>
        <div id="view-codigo" style="color:#6b7280;margin-bottom:8px;"></div>
        <div id="view-tipo" style="font-weight:700;margin-bottom:8px;"></div>
        <div id="view-precio" style="font-size:1.1rem;font-weight:800;margin-bottom:8px;"></div>
        <div id="view-capacidad" style="margin-bottom:8px;"></div>
        <div id="view-ubicacion" style="color:#6b7280;margin-bottom:8px;"></div>
        <div id="view-servicios" style="margin-bottom:12px;"></div>
        <div id="view-descripcion" style="color:#374151;white-space:pre-wrap;"></div>
      </div>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <button class="btn" id="view-edit-btn">Editar</button>
      <button class="btn btn-alt" data-close>Cerrar</button>
    </div>
  </div>
</div>

<div id="modal-confirm-delete" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" style="max-width:420px;">
    <button class="modal-close" data-close>✕</button>
    <h3>Confirmar eliminación</h3>
    <p id="confirm-delete-msg" style="color:#6b7280;"></p>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <button class="btn btn-alt" data-close id="confirm-delete-cancel">Cancelar</button>
      <button class="btn btn-danger" id="confirm-delete-ok">Eliminar</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  function show(modal){ if(!modal) return; modal.setAttribute('aria-hidden','false'); modal.style.display='flex'; setTimeout(()=> modal.classList.add('open'),20); }
  function hide(modal){ if(!modal) return; modal.setAttribute('aria-hidden','true'); modal.classList.remove('open'); setTimeout(()=> modal.style.display='none',160); }

  function normalizePrice(pesos, centavos){
    const P = String(pesos || '').replace(/[^\d]/g,'') || '0';
    let C = String(centavos || '').replace(/[^\d]/g,'');
    C = (C + '00').slice(0,2);
    return Number(P + '.' + C).toFixed(2);
  }

  function makeChip(text){
    const el = document.createElement('div');
    el.className = 'service-chip';
    el.style = 'background:#f3f4f6;padding:6px 8px;border-radius:999px;display:inline-flex;gap:8px;align-items:center;font-weight:600;color:#111';
    el.textContent = text;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = '✕';
    btn.style = 'background:transparent;border:0;color:#6b7280;margin-left:6px;cursor:pointer;font-weight:900';
    btn.addEventListener('click', ()=> el.remove());
    el.appendChild(btn);
    return el;
  }

  function readChips(container){ return Array.from(container.children).map(c=> c.firstChild.textContent.trim()).filter(Boolean); }
  function writeHiddenFromChips(hidden, container){ hidden.value = readChips(container).join(','); }

  const modalNew = document.getElementById('modal-prop-new');
  document.getElementById('pr-new')?.addEventListener('click', function(){
    document.getElementById('form-prop-new').reset();
    document.getElementById('service-chips-new').innerHTML = '';
    document.getElementById('new-precio-hidden').value = '';
    show(modalNew);
  });

  document.getElementById('service-add-new')?.addEventListener('click', function(){
    const input = document.getElementById('service-input-new');
    const val = (input.value || '').trim();
    if(!val) return;
    document.getElementById('service-chips-new').appendChild(makeChip(val));
    writeHiddenFromChips(document.getElementById('new-servicios-hidden'), document.getElementById('service-chips-new'));
    input.value = '';
  });
  document.getElementById('service-input-new')?.addEventListener('keypress', function(e){ if(e.key === 'Enter'){ e.preventDefault(); document.getElementById('service-add-new').click(); } });

  document.getElementById('form-prop-new')?.addEventListener('submit', function(e){
    const pesos = document.getElementById('new-precio-pesos').value;
    const cents = document.getElementById('new-precio-centavos').value;
    document.getElementById('new-precio-hidden').value = normalizePrice(pesos, cents);
    writeHiddenFromChips(document.getElementById('new-servicios-hidden'), document.getElementById('service-chips-new'));
  });

  const modalEdit = document.getElementById('modal-prop-edit');

  document.querySelectorAll('[data-edit]').forEach(btn=>{
    btn.addEventListener('click', function(){
      const raw = this.getAttribute('data-prop') || '{}';
      let data = {};
      try { data = JSON.parse(raw); } catch(e){ console.error('parse prop', e); return; }
      const form = document.getElementById('form-prop-edit');
      form.action = this.getAttribute('data-update-url') || '#';
      document.getElementById('e-id').value = data.id || '';
      document.getElementById('e-tipo').value = data.tipo || '';
      document.getElementById('e-codigo').value = data.codigo || '';
      document.getElementById('e-nombre').value = data.nombre || '';
      const precio = Number(data.precio_noche || 0).toFixed(2).split('.');
      document.getElementById('edit-precio-pesos').value = precio[0] || '';
      document.getElementById('edit-precio-centavos').value = precio[1] || '';
      document.getElementById('e-capacidad').value = data.capacidad ?? '';
      document.getElementById('e-ubicacion').value = data.ubicacion || '';
      const chipsContainer = document.getElementById('service-chips-edit');
      chipsContainer.innerHTML = '';
      (String(data.servicios || '').split(',').map(s=> s.trim()).filter(Boolean)).forEach(s=> chipsContainer.appendChild(makeChip(s)));
      document.getElementById('e-estado').value = data.estado || 'disponible';
      document.getElementById('e-ruta_img').value = data.ruta_img || '';
      document.getElementById('e-descripcion').value = data.descripcion || '';
      show(modalEdit);
    });
  });

  document.getElementById('service-add-edit')?.addEventListener('click', function(){
    const input = document.getElementById('service-input-edit');
    const val = (input.value || '').trim();
    if(!val) return;
    document.getElementById('service-chips-edit').appendChild(makeChip(val));
    writeHiddenFromChips(document.getElementById('edit-servicios-hidden'), document.getElementById('service-chips-edit'));
    input.value = '';
  });
  document.getElementById('service-input-edit')?.addEventListener('keypress', function(e){ if(e.key === 'Enter'){ e.preventDefault(); document.getElementById('service-add-edit').click(); } });

  document.getElementById('form-prop-edit')?.addEventListener('submit', function(e){
    const pesos = document.getElementById('edit-precio-pesos').value;
    const cents = document.getElementById('edit-precio-centavos').value;
    document.getElementById('edit-precio-hidden').value = normalizePrice(pesos, cents);
    writeHiddenFromChips(document.getElementById('edit-servicios-hidden'), document.getElementById('service-chips-edit'));
  });

  let pickerTargetInput = null;
  const modalPicker = document.getElementById('modal-image-picker');
  const foldersContainer = document.getElementById('picker-folders');
  const filesContainer = document.getElementById('picker-files');
  const currentFolderInput = document.getElementById('picker-current-folder');

  async function loadFolders(filter=''){
    foldersContainer.innerHTML = 'Cargando...';
    try {
      const resp = await fetch("{{ route('images.dirs') }}", { credentials:'same-origin' });
      const json = await resp.json();
      foldersContainer.innerHTML = '';
      const dirs = Array.isArray(json.dirs) ? json.dirs : [];
      dirs.filter(d=> d.toLowerCase().includes(filter.toLowerCase())).forEach(d=>{
        const el = document.createElement('div');
        el.textContent = d;
        el.style = 'padding:8px;border-radius:6px;cursor:pointer;border:1px solid transparent';
        el.addEventListener('click', async function(){
          foldersContainer.querySelectorAll('.active').forEach(x=> x.classList.remove('active'));
          this.classList.add('active');
          currentFolderInput.value = d;
          await loadFiles(d);
        });
        foldersContainer.appendChild(el);
      });
      if(dirs.length === 0) foldersContainer.innerHTML = '<div style="color:#6b7280">No hay carpetas públicas</div>';
    } catch(err){
      foldersContainer.innerHTML = '<div style="color:#b91c1c">Error listando carpetas</div>';
      console.error(err);
    }
  }

  async function loadFiles(folder){
    filesContainer.innerHTML = 'Cargando...';
    try {
      const resp = await fetch("{{ route('images.list') }}?folder=" + encodeURIComponent(folder), { credentials:'same-origin' });
      const json = await resp.json();
      filesContainer.innerHTML = '';
      if(!json.files || json.files.length === 0){
        filesContainer.innerHTML = '<div style="color:#6b7280;padding:8px;">No hay imágenes en ' + folder + '</div>';
        return;
      }
      json.files.forEach(f=>{
        const url = "{{ url('/') }}/" + folder.replace(/\/$/, '') + '/' + f;
        const card = document.createElement('div');
        card.style = 'border-radius:8px;overflow:hidden;cursor:pointer;position:relative;background:#f3f4f6;border:1px solid transparent;';
        card.innerHTML = '<img src="'+url+'" style="width:100%;height:100px;object-fit:cover;display:block;" alt="'+f+'">';
        card.dataset.path = folder.replace(/\/$/, '') + '/' + f;
        card.addEventListener('click', function(){
          filesContainer.querySelectorAll('.selected-thumb').forEach(x=> x.classList.remove('selected-thumb'));
          this.classList.add('selected-thumb');
        });
        filesContainer.appendChild(card);
      });
    } catch(err){
      filesContainer.innerHTML = '<div style="color:#b91c1c;padding:8px;">Error cargando imágenes</div>';
      console.error(err);
    }
  }

  function openImagePicker(targetInput){
    pickerTargetInput = targetInput;
    currentFolderInput.value = '';
    filesContainer.innerHTML = '';
    loadFolders();
    show(modalPicker);
  }

  document.getElementById('btn-open-image-picker-new')?.addEventListener('click', function(){ openImagePicker(document.getElementById('new-ruta_img')); });
  document.getElementById('btn-open-image-picker-edit')?.addEventListener('click', function(){ openImagePicker(document.getElementById('e-ruta_img')); });

  document.getElementById('picker-refresh')?.addEventListener('click', function(){
    const f = document.getElementById('picker-current-folder').value || '';
    if(f) loadFiles(f); else loadFolders();
  });

  document.getElementById('picker-folder-filter')?.addEventListener('input', function(){ loadFolders(this.value); });

  document.getElementById('picker-select')?.addEventListener('click', function(){
    const sel = document.querySelector('#picker-files .selected-thumb');
    if(!sel){ alert('Selecciona una imagen'); return; }
    const path = sel.dataset.path;
    if(pickerTargetInput) pickerTargetInput.value = path;
    hide(modalPicker);
  });

  document.querySelectorAll('[data-close]').forEach(btn=>{
    btn.addEventListener('click', function(){ const m = this.closest('.modal'); hide(m); });
  });

  const viewModal = document.getElementById('modal-prop-view');
  const viewEditBtn = document.getElementById('view-edit-btn');
  document.querySelectorAll('[data-view-btn]').forEach(btn=>{
    btn.addEventListener('click', function(){
      const raw = this.getAttribute('data-prop') || '{}';
      let data = {};
      try { data = JSON.parse(raw); } catch(e){ console.error(e); return; }
      document.getElementById('view-main-img').innerHTML = data.ruta_img ? '<img src=\"'+ (location.origin + '/' + data.ruta_img) +'\" style=\"width:100%;height:100%;object-fit:cover;\">' : '<div style=\"color:#9ca3af;\">Sin imagen</div>';
      document.getElementById('view-thumbs').innerHTML = '';
      document.getElementById('view-nombre').textContent = data.nombre || '';
      document.getElementById('view-codigo').textContent = data.codigo || '';
      document.getElementById('view-tipo').textContent = data.tipo || '';
      document.getElementById('view-precio').textContent = '$' + (Number(data.precio_noche || 0).toFixed(2)).replace('.', ',');
      document.getElementById('view-capacidad').textContent = 'Capacidad: ' + (data.capacidad ?? '-');
      document.getElementById('view-ubicacion').textContent = data.ubicacion || '';
      document.getElementById('view-servicios').innerHTML = (String(data.servicios || '').split(',').map(s=> s.trim()).filter(Boolean).map(s=> '<span style=\"background:#f3f4f6;padding:6px 8px;border-radius:999px;margin-right:6px;display:inline-block;font-weight:700\">'+s+'</span>').join('')) || '';
      document.getElementById('view-descripcion').textContent = data.descripcion || '';
      show(viewModal);

      viewEditBtn.onclick = function(){
        document.querySelectorAll('[data-edit]').forEach(e => {
          try {
            const d = JSON.parse(e.getAttribute('data-prop') || '{}');
            if(String(d.id) === String(data.id)) { e.click(); }
          } catch(e) {}
        });
      };
    });
  });

  let pendingDeleteForm = null;
  document.querySelectorAll('.form-delete .action-btn.delete').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const form = this.closest('form');
      pendingDeleteForm = form;
      const msg = this.getAttribute('data-delete-confirm') || '¿Eliminar registro?';
      document.getElementById('confirm-delete-msg').textContent = msg;
      show(document.getElementById('modal-confirm-delete'));
    });
  });
  document.getElementById('confirm-delete-cancel')?.addEventListener('click', function(){ pendingDeleteForm = null; hide(document.getElementById('modal-confirm-delete')); });
  document.getElementById('confirm-delete-ok')?.addEventListener('click', function(){ if(pendingDeleteForm) pendingDeleteForm.submit(); });

  document.getElementById('pr-clear')?.addEventListener('click', function(){
    const form = document.getElementById('pr-filters');
    if (!form) return;
    const inputs = Array.from(form.querySelectorAll('input, select, textarea'));
    inputs.forEach(i => {
      const tag = (i.tagName || '').toLowerCase();
      const type = (i.getAttribute('type') || '').toLowerCase();
      if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'image') return;
      if (type === 'checkbox' || type === 'radio') { i.checked = false; return; }
      if (tag === 'select') { i.selectedIndex = 0; return; }
      i.value = '';
    });
    form.submit();
  });

});
</script>
@endsection
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
      @can('create', App\Models\Propiedad::class)
        <button id="pr-new" class="pr-btn">Nueva propiedad</button>
      @endcan
    </div>
  </header>

  <form id="pr-filters" method="GET" class="pr-filters" action="{{ route('propiedades.index') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o código" />
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

  <div id="pr-form-card" class="pr-card pr-collapsed" aria-hidden="true" style="display:none;">
    <h2 id="pr-form-title">Nueva propiedad</h2>
    <form id="pr-form" method="POST" action="{{ route('propiedades.store') }}">
      @csrf
      <input type="hidden" name="_method" id="pr-method" value="POST">
      <input type="hidden" name="id" id="pr-id" value="">

      <div class="pr-grid">
        <label>
          <span>Tipo</span>
          <select name="tipo" id="p-tipo" required>
            <option value="cabaña">cabaña</option>
            <option value="casa">casa</option>
            <option value="departamento">departamento</option>
          </select>
        </label>

        <label>
          <span>Código</span>
          <input name="codigo" id="p-codigo" required />
        </label>

        <label>
          <span>Nombre</span>
          <input name="nombre" id="p-nombre" required />
        </label>

        <label>
          <span>Precio / noche</span>
          <input name="precio_noche" id="p-precio" type="number" step="0.01" required />
        </label>

        <label>
          <span>Capacidad</span>
          <input name="capacidad" id="p-capacidad" type="number" min="1" required />
        </label>

        <label>
          <span>Ubicación</span>
          <input name="ubicacion" id="p-ubicacion" />
        </label>

        <label class="full">
          <span>Descripción</span>
          <textarea name="descripcion" id="p-descripcion" rows="4"></textarea>
        </label>

        <label>
          <span>Servicios (coma-separado)</span>
          <input name="servicios" id="p-servicios" />
        </label>

        <label>
          <span>Estado</span>
          <select name="estado" id="p-estado">
            <option value="disponible">disponible</option>
            <option value="ocupada">ocupada</option>
            <option value="mantenimiento">mantenimiento</option>
          </select>
        </label>

        <label>
          <span>Ruta imagen (public)</span>
          <input name="ruta_img" id="p-ruta" placeholder="imgs/prop/mi.jpg" />
        </label>

        <div class="preview-col">
          <span>Preview</span>
          <div class="pr-preview">
            <img id="p-preview" src="{{ asset('imgs/default.webp') }}" alt="preview">
          </div>
        </div>
      </div>

      <div class="pr-form-actions">
        <button type="submit" id="p-save" class="pr-btn">Guardar</button>
        <button type="button" id="p-cancel" class="pr-btn danger">Cancelar</button>
      </div>
    </form>
  </div>

  @if($propiedades->isEmpty())
    <div class="pr-card">No hay propiedades registradas.</div>
  @else
    <div class="pr-grid-list" role="list">
      @foreach($propiedades as $prop)
        <article class="pr-item" role="listitem" aria-labelledby="prop-{{ $prop->id }}">
          <div class="pr-media">
            @if($prop->ruta_img)
              <img src="{{ asset($prop->ruta_img) }}" alt="{{ $prop->nombre }}" />
            @else
              <div class="pr-noimg">Sin imagen</div>
            @endif
          </div>

          <div class="pr-body">
            <div class="pr-top">
              <h3 id="prop-{{ $prop->id }}">{{ $prop->nombre ?? $prop->codigo }}</h3>
              <div class="pr-code">{{ $prop->codigo }}</div>
            </div>

            <div class="pr-meta">
              <div class="pr-price">${{ number_format($prop->precio_noche ?? 0, 2, ',', '.') }} / noche</div>
              <div class="pr-cap">Cap: {{ $prop->capacidad ?? '-' }}</div>
              <div class="pr-estado estado-{{ $prop->estado ?? 'desconocido' }}">{{ $prop->estado ?? '-' }}</div>
            </div>

            <p class="pr-desc">{{ \Illuminate\Support\Str::limit($prop->descripcion ?? '-', 160) }}</p>

            <div class="pr-foot">
              <div class="pr-actions-inline">
                @can('update', $prop)
                  <button class="pr-edit" data-prop='@json($prop)'>Editar</button>
                @endcan

                @can('delete', $prop)
                  <form method="POST" action="{{ route('propiedades.destroy', $prop->id) }}" onsubmit="return confirm('Eliminar propiedad {{ addslashes($prop->nombre ?? $prop->codigo) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="pr-delete">Eliminar</button>
                  </form>
                @endcan

                <a href="{{ route('propiedades.show', $prop->id) }}" class="pr-link">Ver</a>
              </div>

              @if(method_exists($prop, 'comentarios'))
                @php
                  $avg = $prop->comentarios()->avg('calificacion') ?? null;
                  $count = $prop->comentarios()->count();
                @endphp
                <div class="pr-comments">
                  <span class="pr-rating">{{ $avg ? number_format($avg,1) : '-' }} ★</span>
                  <a href="{{ url('/comentarios?propiedad_id='.$prop->id) }}" class="pr-link-small">{{ $count }} comentarios</a>
                </div>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>

    {{ $propiedades->withQueryString()->links() ?? '' }}
  @endif
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const formCard = document.getElementById('pr-form-card');
  const btnNew = document.getElementById('pr-new');
  const btnCancel = document.getElementById('p-cancel');
  const form = document.getElementById('pr-form');
  const methodInput = document.getElementById('pr-method');
  const idInput = document.getElementById('pr-id');
  const title = document.getElementById('pr-form-title');
  const preview = document.getElementById('p-preview');
  const ruta = document.getElementById('p-ruta');

  function openForm(mode='create', data = null){
    title.textContent = mode === 'create' ? 'Nueva propiedad' : 'Editar propiedad';
    form.action = mode === 'create' ? "{{ route('propiedades.store') }}" : "{{ url('propiedades') }}/" + data.id;
    methodInput.value = mode === 'create' ? 'POST' : 'PUT';
    idInput.value = data ? data.id : '';
    ['tipo','codigo','nombre','precio_noche','capacidad','ubicacion','descripcion','servicios','estado','ruta_img'].forEach(k=>{
      const el = document.getElementById('p-' + k.replace(/_/g,'-'));
      if(!el) return;
      el.value = data ? (data[k] ?? '') : '';
    });
    setPreview(data ? data.ruta_img : '');
    formCard.style.display = 'block';
    formCard.removeAttribute('aria-hidden');
  }

  function closeForm(){
    formCard.style.display = 'none';
    formCard.setAttribute('aria-hidden', 'true');
  }

  function setPreview(path){
    if(!path) preview.src = "{{ asset('imgs/default.webp') }}";
    else preview.src = "{{ url('/') }}/" + path.replace(/^\//,'');
    const input = document.getElementById('p-ruta');
    if(input) input.value = path || '';
  }

  if(btnNew) btnNew.addEventListener('click', ()=> openForm('create'));

  btnCancel && btnCancel.addEventListener('click', closeForm);

  document.querySelectorAll('.pr-edit').forEach(btn=>{
    btn.addEventListener('click', function(){
      try {
        const p = JSON.parse(this.getAttribute('data-prop'));
        openForm('edit', p);
      } catch(e){ console.error(e); alert('No se pudo abrir editor'); }
    });
  });

  ruta && ruta.addEventListener('input', function(){ setPreview(this.value); });

  document.getElementById('pr-clear').addEventListener('click', function(){
    document.querySelectorAll('#pr-filters input, #pr-filters select').forEach(i=> i.value = '');
    document.getElementById('pr-filters').submit();
  });
});
</script>
@endsection
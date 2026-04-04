@extends('layouts.app')

@section('title', 'Propiedades')

@section('content')
@php
  use App\Models\Comentario;
  use Illuminate\Support\Str;
  $currentUser = $currentUser ?? auth()->user();
  $layoutPreviewMode = ($currentUser && ($currentUser->rol ?? '') === 'admin') ? session('layout_preview_as', 'admin') : 'user';
  $isAdmin = ($isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin')) && $layoutPreviewMode !== 'user';
@endphp

<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">

<style>
.pr-container{max-width:1100px;margin:18px auto;padding:12px;}
.pr-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap:16px; }
.pr-card{ background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06); overflow:hidden; display:flex;flex-direction:column; }
.pr-thumb{ width:100%;height:190px; background:#f3f4f6; display:flex;align-items:center;justify-content:center; overflow:hidden; position:relative; }
.pr-thumb.pr-thumb-admin{ height:72px; border-radius:10px; }
.pr-carousel{ position:relative; width:100%; height:100%; overflow:hidden; border-radius:inherit; --mx:50%; --my:50%; }
.pr-track{ display:flex; width:100%; height:100%; transition:transform .7s cubic-bezier(.22,.61,.36,1); }
.pr-slide{ flex:0 0 100%; height:100%; position:relative; overflow:hidden; }
.pr-slide img{ width:100%; height:100%; object-fit:cover; display:block; transform:scale(1.03); transition:transform .8s ease, filter .45s ease; filter:saturate(1.02) contrast(1.02); }
.pr-slide::before{ content:''; position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at var(--mx) var(--my), rgba(255,255,255,0.22), rgba(255,255,255,0) 42%); opacity:0; transition:opacity .28s ease; }
.pr-slide::after{ content:''; position:absolute; inset:auto 0 0 0; height:42%; background:linear-gradient(to top, rgba(2,6,23,.42), rgba(2,6,23,0)); pointer-events:none; }
.pr-carousel:hover .pr-slide::before{ opacity:1; }
.pr-carousel:hover .pr-slide img{ transform:scale(1.09); filter:saturate(1.12) contrast(1.08); }
.pr-carousel.is-paused .pr-slide img{ transition-duration:.35s; }
.pr-nav{ position:absolute; top:50%; transform:translateY(-50%); width:32px; height:32px; border-radius:999px; border:0; background:rgba(15,23,42,.56); color:#fff; font-weight:800; cursor:pointer; opacity:0; transition:opacity .2s ease, transform .2s ease, background .2s ease; z-index:2; }
.pr-nav:hover{ background:rgba(2,6,23,.82); }
.pr-nav.prev{ left:8px; }
.pr-nav.next{ right:8px; }
.pr-carousel:hover .pr-nav{ opacity:1; }
.pr-carousel:hover .pr-nav.prev{ transform:translateY(-50%) translateX(0); }
.pr-carousel:hover .pr-nav.next{ transform:translateY(-50%) translateX(0); }
.pr-dots{ position:absolute; left:50%; bottom:8px; transform:translateX(-50%); display:flex; gap:6px; z-index:2; }
.pr-dot{ width:7px; height:7px; border-radius:999px; border:0; background:rgba(255,255,255,.55); cursor:pointer; padding:0; }
.pr-dot.active{ width:20px; background:#fff; }
.pr-badge{ position:absolute; left:8px; top:8px; z-index:2; background:rgba(2,6,23,.6); color:#fff; font-size:.72rem; font-weight:700; border-radius:999px; padding:3px 8px; }
.pr-zoom-hint{ position:absolute; right:8px; top:8px; z-index:2; color:#fff; background:rgba(2,6,23,.45); padding:3px 8px; border-radius:999px; font-size:.7rem; opacity:0; transition:opacity .25s ease; }
.pr-carousel:hover .pr-zoom-hint{ opacity:1; }
.pr-body{ padding:12px; display:flex;flex-direction:column; gap:8px; flex:1; }
.pr-title{ font-weight:800; color:#111827; }
.pr-meta{ color:#6b7280; font-size:0.95rem; }
.pr-actions{ display:flex; gap:8px; margin-top:auto; align-items:center; justify-content:space-between; }
.pr-btn{ padding:8px 10px;border-radius:8px;border:0;font-weight:700;cursor:pointer; }
.pr-btn.edit{ background:linear-gradient(90deg,#06b6d4,#6366f1); color:#fff; }
.pr-btn.delete{ background:linear-gradient(90deg,#ef4444,#f97316); color:#fff; }
.list-view .table-responsive { overflow:auto; }
.muted{ color:#6b7280; }
.action-btn{ padding:8px 10px;border-radius:8px;border:0;font-weight:700;cursor:pointer; }
.action-btn.edit{ background:linear-gradient(90deg,#3b82f6,#06b6d4);color:#fff; }
.action-btn.delete{ background:linear-gradient(90deg,#ef4444,#f97316);color:#fff; }
.modal-panel { background:#fff;border-radius:12px;padding:16px;box-shadow:0 18px 40px rgba(2,6,23,0.08); }
.field { display:block;margin-bottom:10px; }
.label-text{ display:block;font-weight:700;margin-bottom:6px; }
.service-chip{ background:#f3f4f6;padding:6px 8px;border-radius:999px;display:inline-flex;gap:8px;align-items:center;font-weight:600;color:#111; }
.propiedades-pagination {
  border-top: 1px solid #e5e7eb;
  margin-top: 10px;
  padding-top: 12px;
}
.propiedades-pagination .np-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.propiedades-pagination .np-meta {
  color: #6b7280;
  font-size: 13px;
}
.propiedades-pagination .np-controls {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.propiedades-pagination .np-btn,
.propiedades-pagination .np-page,
.propiedades-pagination .np-ellipsis {
  min-width: 34px;
  height: 34px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e5e7eb;
  background: #fff;
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  color: #111827;
  padding: 0 10px;
}
.propiedades-pagination .np-btn:hover,
.propiedades-pagination .np-page:hover {
  background: #f8fafc;
}
.propiedades-pagination .np-page.is-active {
  background: #111827;
  border-color: #111827;
  color: #fff;
}
.propiedades-pagination .np-btn.is-disabled {
  opacity: .45;
  pointer-events: none;
}
.propiedades-pagination .np-ellipsis {
  min-width: auto;
  border: 0;
  background: transparent;
  color: #6b7280;
  padding: 0 4px;
}
@media (max-width: 768px){
  .pr-thumb{ height:176px; }
  .pr-nav{ opacity:1; width:30px; height:30px; }
}
</style>

<div class="pr-container">
  <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">Propiedades</h1>
      <div style="color:#6b7280;margin-top:6px;">Listado de propiedades disponibles</div>
    </div>

    <div style="display:flex;gap:8px;align-items:center;">
      @if($isAdmin)
        <a href="{{ route('propiedades.create') }}" class="pr-btn edit">Crear propiedad</a>
      @endif
    </div>
  </header>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      {{ session('success') }}
    </div>
  @endif

  @if($isAdmin)
    <div class="card table-card list-view">
      <div class="table-responsive">
        <table class="table" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Tipo / Código</th>
              <th>Precio / noche</th>
              <th>Capacidad</th>
              <th>Estado</th>
              <th style="width:220px">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($propiedades ?? [] as $prop)
            <tr>
              <td style="width:120px;">
                @php
                  $gallery = [];
                  if (!empty($prop->ruta_img)) {
                    $ruta = ltrim($prop->ruta_img, '/\\');
                    $full = public_path($ruta);
                    if (is_dir($full)) {
                      $files = @scandir($full) ?: [];
                      foreach ($files as $f) {
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $gallery[] = asset($ruta . '/' . $f); }
                      }
                    } elseif (is_file($full)) {
                      $gallery[] = asset($ruta);
                    }
                  }
                @endphp
                @if(!empty($gallery))
                  <div class="pr-thumb pr-thumb-admin">
                    <div class="pr-carousel" data-pr-carousel data-interval="3800">
                      <div class="pr-track" data-pr-track>
                        @foreach($gallery as $gi => $g)
                          <div class="pr-slide" data-pr-slide>
                            <img src="{{ $g }}" alt="{{ $prop->nombre }} {{ $gi + 1 }}">
                          </div>
                        @endforeach
                      </div>
                      @if(count($gallery) > 1)
                        <button type="button" class="pr-nav prev" data-pr-prev aria-label="Anterior">‹</button>
                        <button type="button" class="pr-nav next" data-pr-next aria-label="Siguiente">›</button>
                        <div class="pr-dots" data-pr-dots></div>
                      @endif
                    </div>
                  </div>
                @else
                  <div style="width:100px;height:64px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;">Sin imagen</div>
                @endif
              </td>
              <td style="vertical-align:middle;">{{ $prop->nombre }}</td>
              <td style="vertical-align:middle;">{{ ucfirst($prop->tipo) }} · {{ $prop->codigo ?? '-' }}</td>
              <td style="vertical-align:middle;">${{ number_format($prop->precio_noche ?? 0,2,',','.') }}</td>
              <td style="vertical-align:middle;">{{ $prop->capacidad }}</td>
              <td style="vertical-align:middle;">{{ ucfirst($prop->estado) }}</td>
              <td style="vertical-align:middle;white-space:nowrap;">
                <a href="{{ route('propiedades.show', $prop->id) }}" class="action-btn edit" style="margin-right:6px">Ver</a>

                <a href="{{ route('propiedades.edit', $prop->id) }}" class="action-btn edit" style="margin-right:6px">Editar</a>

                <form method="POST" action="{{ route('propiedades.destroy', $prop->id) }}" style="display:inline" class="form-delete">
                  @csrf
                  @method('DELETE')
                  <button class="action-btn delete" type="button" data-delete-confirm="¿Eliminar propiedad {{ addslashes($prop->nombre ?? $prop->codigo) }}?" style="margin-left:6px;">Eliminar</button>
                </form>
              </td>
            </tr>
            @empty
              <tr><td colspan="7" class="muted">No hay propiedades.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  @else
    <div class="pr-grid" role="list">
      @forelse($propiedades ?? [] as $prop)
        @php
          $gallery = [];
          if (!empty($prop->ruta_img)) {
            $ruta = ltrim($prop->ruta_img, '/\\');
            $full = public_path($ruta);
            if (is_dir($full)) {
              $files = @scandir($full) ?: [];
              foreach ($files as $f) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $gallery[] = asset($ruta . '/' . $f); }
              }
            } elseif (is_file($full)) {
              $gallery[] = asset($ruta);
            }
          }
        @endphp

        <article class="pr-card" role="listitem" aria-labelledby="prop-{{ $prop->id }}">
          <div class="pr-thumb" aria-hidden="true">
            @if(!empty($gallery))
              <div class="pr-carousel" data-pr-carousel data-interval="4200">
                <div class="pr-track" data-pr-track>
                  @foreach($gallery as $gi => $g)
                    <div class="pr-slide" data-pr-slide>
                      <img src="{{ $g }}" alt="{{ $prop->nombre }} {{ $gi + 1 }}">
                    </div>
                  @endforeach
                </div>
                <div class="pr-badge">{{ count($gallery) }} fotos</div>
                <div class="pr-zoom-hint">hover preview</div>
                @if(count($gallery) > 1)
                  <button type="button" class="pr-nav prev" data-pr-prev aria-label="Anterior">‹</button>
                  <button type="button" class="pr-nav next" data-pr-next aria-label="Siguiente">›</button>
                  <div class="pr-dots" data-pr-dots></div>
                @endif
              </div>
            @else
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Sin imagen</div>
            @endif
          </div>

          <div class="pr-body">
            <div>
              <div id="prop-{{ $prop->id }}" class="pr-title">{{ $prop->nombre }}</div>
              <div class="pr-meta">{{ ucfirst($prop->tipo) }} · {{ $prop->codigo ?? '-' }} · Capacidad: {{ $prop->capacidad }}</div>
              <div style="margin-top:6px;font-weight:800;color:#065f46;">${{ number_format($prop->precio_noche ?? 0,2,',','.') }} / noche</div>
            </div>

            <div class="pr-actions" aria-hidden="false">
              <a href="{{ route('propiedades.show', $prop->id) }}" class="pr-btn" style="background:#f3f4f6;color:#111;border-radius:8px;border:1px solid #e6e9ee;">Ver detalles</a>
              <a href="{{ route('reservaciones.create_for_propiedad', $prop->id) }}" class="pr-btn" style="background:linear-gradient(90deg,#06b6d4,#3b82f6);color:#fff;border-radius:8px;text-decoration:none;">Solicitar reserva</a>
              <div class="muted">{{ ucfirst($prop->estado) }}</div>
            </div>
          </div>
        </article>
      @empty
        <div class="muted">No hay propiedades disponibles.</div>
      @endforelse
    </div>
  @endif

  <div class="propiedades-pagination" style="margin-top:14px;">
    @php
      $currentPage = $propiedades->currentPage();
      $lastPage = $propiedades->lastPage();
      $startPage = max(1, $currentPage - 2);
      $endPage = min($lastPage, $currentPage + 2);
    @endphp
    @if($propiedades->lastPage() > 1)
      <nav role="navigation" aria-label="Pagination Navigation">
        <div class="np-wrap">
          <div class="np-meta">
            Showing {{ $propiedades->firstItem() ?? 0 }} to {{ $propiedades->lastItem() ?? 0 }} of {{ $propiedades->total() }} results
          </div>
          <div class="np-controls">
            @if($propiedades->onFirstPage())
              <span class="np-btn is-disabled" aria-disabled="true">Anterior</span>
            @else
              <a class="np-btn" href="{{ $propiedades->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
              <a class="np-page" href="{{ $propiedades->url(1) }}">1</a>
              @if($startPage > 2)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
              @if($page === $currentPage)
                <span class="np-page is-active" aria-current="page">{{ $page }}</span>
              @else
                <a class="np-page" href="{{ $propiedades->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <span class="np-ellipsis" aria-hidden="true">...</span>
              @endif
              <a class="np-page" href="{{ $propiedades->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($propiedades->hasMorePages())
              <a class="np-btn" href="{{ $propiedades->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
              <span class="np-btn is-disabled" aria-disabled="true">Siguiente</span>
            @endif
          </div>
        </div>
      </nav>
    @endif
  </div>
</div>

<div id="modal-prop-new" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;z-index:1200;max-width:900px;">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3>Crear propiedad</h3>

    <form id="form-prop-new" method="POST" action="{{ route('propiedades.store') }}" class="form" enctype="multipart/form-data">
      @csrf
      <div style="display:grid;grid-template-columns:1fr 140px;gap:10px;align-items:end;margin-bottom:10px;">
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

<div id="modal-prop-edit" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;z-index:1200;max-width:900px;">
    <button href="propiedades/{id}/edit" class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3>Editar propiedad</h3>

    <form id="form-prop-edit" method="POST" action="#" class="form">
      @csrf
      @method('PUT')
      <input type="hidden" id="e-id" name="id" />
      <div style="display:grid;grid-template-columns:1fr 140px;gap:10px;align-items:end;margin-bottom:10px;">
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

<div id="modal-image-picker" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="max-width:900px;display:grid;grid-template-columns:240px 1fr;gap:12px;position:relative;z-index:1200;">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
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

<div id="modal-confirm-delete" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;z-index:1200;max-width:420px;">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3>Confirmar eliminación</h3>
    <p id="confirm-delete-msg" class="muted">¿Estás seguro?</p>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <button class="btn btn-alt" data-close id="confirm-delete-cancel">Cancelar</button>
      <button class="btn btn-danger" id="confirm-delete-ok">Eliminar</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
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
    if(!foldersContainer) return;
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
    if(!filesContainer) return;
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

  let pendingDeleteForm = null;
  document.querySelectorAll('button[data-delete-confirm]').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const form = this.closest('form');
      pendingDeleteForm = form;
      const msg = this.getAttribute('data-delete-confirm') || '¿Eliminar?';
      document.getElementById('confirm-delete-msg').textContent = msg;
      show(document.getElementById('modal-confirm-delete'));
    });
  });
  document.getElementById('confirm-delete-cancel')?.addEventListener('click', function(){ pendingDeleteForm = null; hide(document.getElementById('modal-confirm-delete')); });
  document.getElementById('confirm-delete-ok')?.addEventListener('click', function(){ if(pendingDeleteForm){ pendingDeleteForm.submit(); } pendingDeleteForm = null; hide(document.getElementById('modal-confirm-delete')); });

  // Animated carousels for property previews (admin + user)
  document.querySelectorAll('[data-pr-carousel]').forEach(function(car){
    const track = car.querySelector('[data-pr-track]');
    const slides = Array.from(car.querySelectorAll('[data-pr-slide]'));
    const btnPrev = car.querySelector('[data-pr-prev]');
    const btnNext = car.querySelector('[data-pr-next]');
    const dotsWrap = car.querySelector('[data-pr-dots]');
    if(!track || slides.length <= 1){
      if (btnPrev) btnPrev.style.display = 'none';
      if (btnNext) btnNext.style.display = 'none';
      if (dotsWrap) dotsWrap.style.display = 'none';
      return;
    }

    let idx = 0;
    let timer = null;
    const interval = Number(car.getAttribute('data-interval') || 4200);
    const dots = [];

    function render(){
      track.style.transform = 'translateX(' + (-idx * 100) + '%)';
      dots.forEach((d,i)=> d.classList.toggle('active', i === idx));
    }
    function go(next){
      idx = (next + slides.length) % slides.length;
      render();
    }
    function start(){
      stop();
      timer = setInterval(()=> go(idx + 1), interval);
    }
    function stop(){ if(timer){ clearInterval(timer); timer = null; } }

    if (dotsWrap){
      slides.forEach(function(_, i){
        const d = document.createElement('button');
        d.type = 'button';
        d.className = 'pr-dot' + (i === 0 ? ' active' : '');
        d.setAttribute('aria-label', 'Ir a imagen ' + (i + 1));
        d.addEventListener('click', function(){ go(i); start(); });
        dotsWrap.appendChild(d);
        dots.push(d);
      });
    }

    btnPrev?.addEventListener('click', function(e){ e.preventDefault(); go(idx - 1); start(); });
    btnNext?.addEventListener('click', function(e){ e.preventDefault(); go(idx + 1); start(); });

    car.addEventListener('mouseenter', function(){ car.classList.add('is-paused'); stop(); });
    car.addEventListener('mouseleave', function(){ car.classList.remove('is-paused'); start(); });
    car.addEventListener('focusin', stop);
    car.addEventListener('focusout', start);

    car.addEventListener('mousemove', function(e){
      const r = car.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      car.style.setProperty('--mx', x.toFixed(2) + '%');
      car.style.setProperty('--my', y.toFixed(2) + '%');
    });

    start();
  });

});
</script>
@endpush
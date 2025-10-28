@extends('layouts.app')

@section('title','Inicio')

@section('content')
@php
  use App\Models\Reservation;
  use App\Models\Propiedad;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
  $first = ($homepages->first() ?? null);
  // usar Collection para evitar llamadas a isEmpty() sobre arrays
  $userReservs = collect();
  if (!$isAdmin && $currentUser) {
    $userReservs = Reservation::with('propiedad')->where('usuario_id', $currentUser->id)->orderByDesc('created_at')->take(6)->get();
  }
  $allProps = Propiedad::orderBy('nombre')->get();
@endphp

<style>
/* simple responsive hero + cards layout suitable for blade (no frameworks) */
.hp-wrap{max-width:1200px;margin:18px auto;padding:12px;}
.hp-toggle { display:flex;align-items:center;gap:10px;margin-bottom:12px;justify-content:flex-end; }
.hp-toggle .switch { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#fff; box-shadow:0 6px 18px rgba(2,6,23,0.04); border:1px solid #eef2f7; }
.hp-toggle input[type="checkbox"]{ width:42px; height:26px; -webkit-appearance:none; background:#e6eefc; border-radius:999px; position:relative; outline:none; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.02); cursor:pointer; }
.hp-toggle input[type="checkbox"]::after{ content:''; position:absolute; left:4px; top:4px; width:18px; height:18px; background:#fff; border-radius:50%; transition:transform .18s ease; transform:translateX(0); box-shadow:0 4px 12px rgba(2,6,23,0.08); }
.hp-toggle input[type="checkbox"]:checked{ background:linear-gradient(90deg,#6366f1,#06b6d4); }
.hp-toggle input[type="checkbox"]:checked::after{ transform:translateX(16px); }

/* admin-only helpers */
.admin-only{ display:block; }
.preview-only{ display:none; }

/* card-per-field */
.field-card{ background:#fff;padding:12px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.04); display:flex;flex-direction:column; gap:10px; }
.field-card .label{ font-weight:700;color:#374151; }
.field-card .small{ color:#6b7280; font-size:0.9rem; }

/* preview rules (visible when preview-mode active) */
.preview-mode .admin-only{ display:none !important; }
.preview-mode .preview-only{ display:block !important; }

/* grid */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}

/* responsive tweaks */
@media (max-width:900px){
  .hp-toggle{ justify-content:stretch; }
  .hp-toggle .switch{ width:100%; justify-content:space-between; padding:8px; }
  .grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr));}
}

/* Image picker modal */
#hp-image-picker { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:12000; padding:18px; }
#hp-image-picker.open { display:flex; }
#hp-image-picker .picker-back { position:absolute; inset:0; background:rgba(2,6,23,0.55); }
#hp-image-picker .picker-panel { position:relative; z-index:2; width:100%; max-width:1100px; background:#fff; border-radius:12px; box-shadow:0 18px 48px rgba(2,6,23,0.12); padding:14px; display:grid; grid-template-columns:280px 1fr; gap:12px; max-height:80vh; overflow:auto; }
.picker-folders { display:flex; flex-direction:column; gap:8px; }
.picker-folder { padding:8px 10px; border-radius:8px; cursor:pointer; border:1px solid #eef2f7; background:#fbfdff; font-weight:700; color:#0f172a; }
.picker-folder.active { background:linear-gradient(90deg,#6366f1,#06b6d4); color:#fff; box-shadow:0 8px 20px rgba(6,182,212,0.08); }
.picker-files { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:8px; }
.picker-thumb { position:relative; border-radius:8px; overflow:hidden; background:#f3f4f6; height:90px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
.picker-thumb img{ width:100%; height:100%; object-fit:cover; display:block; transition:transform .18s ease; }
.picker-thumb:hover img{ transform:scale(1.04); }
.picker-thumb .overlay { position:absolute; left:0; right:0; bottom:0; padding:6px; background:linear-gradient(180deg, rgba(0,0,0,0), rgba(0,0,0,0.38)); color:#fff; font-size:12px; display:flex; justify-content:space-between; gap:6px; align-items:center; }
.picker-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:8px; }

/* Carousel (preview for non-admins, and for admin preview-mode) */
.hp-carousel { position:relative; width:100%; overflow:hidden; border-radius:12px; box-shadow:0 12px 30px rgba(2,6,23,0.06); touch-action: pan-y; }
.hp-carousel .track { display:flex; transition:transform .6s cubic-bezier(.22,.9,.3,1); }
.hp-carousel .slide { min-width:100%; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:#f3f4f6; height:320px; }
.hp-carousel .slide img{ width:100%; height:100%; object-fit:cover; display:block; }
.hp-carousel .nav { position:absolute; top:50%; transform:translateY(-50%); width:100%; display:flex; justify-content:space-between; pointer-events:none; padding:0 8px; }
.hp-carousel .nav button { pointer-events:auto; background:rgba(0,0,0,0.38); color:#fff; border:0; padding:8px 10px; border-radius:8px; cursor:pointer; }
.hp-carousel .dots { position:absolute; left:50%; bottom:12px; transform:translateX(-50%); display:flex; gap:6px; }
.hp-carousel .dot { width:10px; height:10px; border-radius:999px; background:rgba(255,255,255,0.6); cursor:pointer; border:1px solid rgba(0,0,0,0.06); }
.hp-carousel .dot.active { background:#fff; box-shadow:0 6px 18px rgba(2,6,23,0.06); }

/* ensure nav buttons are positioned outside slides and receive clicks */
.hp-carousel-btn{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  z-index:6;
  border:0;
  padding:10px 12px;
  border-radius:8px;
  background:rgba(0,0,0,0.36);
  color:#fff;
  cursor:pointer;
  pointer-events:auto; /* allow clicks */
}
.hp-carousel-btn-left{ left:12px; }
.hp-carousel-btn-right{ right:12px; }

.hp-carousel .dots{ z-index:6; bottom:14px; position:absolute; left:50%; transform:translateX(-50%); display:flex; gap:8px; }
.hp-carousel .dot{ width:10px; height:10px; border-radius:999px; border:1px solid rgba(255,255,255,0.4); background:rgba(255,255,255,0.45); cursor:pointer; }
.hp-carousel .dot.active{ background:#fff; box-shadow:0 8px 20px rgba(2,6,23,0.06); }

/* small screens */
@media (max-width:900px){
  .hp-carousel .slide { height:200px; }
  #hp-image-picker .picker-panel { grid-template-columns:1fr; }
  .picker-folders { flex-direction:row; flex-wrap:wrap; gap:6px; }
}
</style>

<div class="hp-wrap" id="hp-wrap">
  {{-- toggle only for admins --}}
  @if($isAdmin)
    <div class="hp-toggle" aria-hidden="false">
      <div style="flex:1;">
        <h1 style="margin:0">Inicio — Administración</h1>
      </div>
      <div class="switch" title="Ver como usuario" style="align-self:center;">
        <label style="display:flex;align-items:center;gap:8px;">
          <span class="small-muted">Ver como usuario</span>
          <input id="hp-view-toggle" type="checkbox" aria-label="Ver como usuario">
        </label>
      </div>
    </div>
  @else
    <h1>Inicio</h1>
  @endif

  {{-- PUBLIC PREVIEW (siempre renderizado) --}}
  <div class="preview-view preview-only" id="hp-preview">
    @php $initial = $folderFiles ?? []; @endphp

    <div id="hp-preview-carousel-wrap" class="preview-only" style="margin-bottom:12px;">
      @php $initial = $folderFiles ?? []; @endphp

      @if(!empty($initial) && count($initial) >= 1)
        <div id="hp-carousel" class="hp-carousel" aria-roledescription="carousel" role="region" tabindex="0">
          <div class="track" id="hp-carousel-track" aria-live="polite">
            @foreach($initial as $url)
              <div class="slide"><img src="{{ $url }}" alt="Banner"></div>
            @endforeach
          </div>

          <!-- arrows with fixed IDs the JS expects -->
          <button id="hp-carousel-prev" class="hp-carousel-btn hp-carousel-btn-left" aria-label="Anterior">◀</button>
          <button id="hp-carousel-next" class="hp-carousel-btn hp-carousel-btn-right" aria-label="Siguiente">▶</button>

          <div class="dots" id="hp-carousel-dots" role="tablist" aria-hidden="{{ count($initial) <= 1 ? 'true' : 'false' }}">
            @foreach($initial as $i => $u)
              <button class="dot {{ $i === 0 ? 'active' : '' }}" data-dot-index="{{ $i }}" aria-label="Ir a slide {{ $i + 1 }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}"></button>
            @endforeach
          </div>
        </div>

        <script>window.hpInitialCarousel = @json($initial);</script>
      @else
        {{-- no images: nothing to show --}}
      @endif
    </div>

    <section class="section">
      <h3 class="section-title">Tus reservaciones</h3>

      @if($currentUser && $userReservs->isNotEmpty())
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr));">
          @foreach($userReservs as $rv)
            <div class="card" role="article">
              <div style="display:flex;gap:10px;align-items:center">
                <div style="flex:0 0 84px;height:64px;border-radius:8px;overflow:hidden;background:#f3f4f6;border:1px solid #eef2f7">
                  @if(optional($rv->propiedad)->ruta_img)
                    <img src="{{ asset($rv->propiedad->ruta_img) }}" alt="" style="width:100%;height:100%;object-fit:cover">
                  @endif
                </div>
                <div style="flex:1">
                  <div class="label">{{ $rv->propiedad->nombre ?? ('Propiedad #'.$rv->propiedad_id) }}</div>
                  <div class="small-muted">{{ \Carbon\Carbon::parse($rv->check_in)->format('d M Y') }} — {{ \Carbon\Carbon::parse($rv->check_out)->format('d M Y') }}</div>
                </div>
              </div>

              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                <div class="small-muted">Estado: <strong style="text-transform:capitalize">{{ $rv->estado }}</strong></div>
                <div>
                  <a href="{{ route('reservaciones.show', $rv->id) }}" class="btn btn-ghost" style="padding:6px 8px">Ver</a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="card small-muted">@if($currentUser) No tienes reservaciones registradas. @else Inicia sesión para ver tus reservaciones. @endif</div>
      @endif
    </section>

    <section class="section">
      <h3 class="section-title">Cabañas disponibles</h3>
      <div class="grid">
        @foreach($allProps as $p)
          <div class="card">
            <div style="display:flex;gap:10px;align-items:center">
              <div style="flex:0 0 84px;height:64px;border-radius:8px;overflow:hidden;background:#f3f4f6;border:1px solid #eef2f7">
                @if($p->ruta_img)
                  <img src="{{ asset($p->ruta_img) }}" alt="{{ $p->nombre }}" style="width:100%;height:100%;object-fit:cover">
                @endif
              </div>
              <div style="flex:1">
                <div class="label">{{ $p->nombre }}</div>
                <div class="small-muted">{{ $p->ubicacion }}</div>
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
              <div class="small-muted">${{ number_format($p->precio_noche ?? 0,2,',','.') }} / noche</div>
              <div>
                <a class="btn btn-ghost" href="{{ route('propiedades.show', $p->id) }}">Ver</a>
                <a class="btn btn-primary" href="{{ route('reservaciones.create_for_propiedad', $p->id) }}">Reservar</a>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  </div>

  {{-- ADMIN UI: per-field cards (cada input en su propia carta) --}}
  @if($isAdmin)
    <div class="admin-view admin-only" id="hp-admin">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-weight:800;font-size:1.05rem">Administrar contenido — tarjetas por campo</div>
        <div class="small-muted">Cada tarjeta guarda solo el campo correspondiente</div>
      </div>

      <div class="grid">
        @foreach($homepages as $h)
          {{-- banner_image --}}
          <div class="field-card">
            <div class="label">Banner (ruta)</div>
            <div class="small">Ruta al archivo mostrado en el banner principal</div>
            <form class="hp-field-form" data-id="{{ $h->id }}" method="POST" action="{{ route('homepage.update', $h->id) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="field" value="banner_image">
              <input class="input" name="banner_image" value="{{ $h->banner_image }}" placeholder="uploads/banner.jpg">
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-ghost" data-open-picker data-field="banner_image">Seleccionar imagen...</button>
              </div>
            </form>
            <div class="small-muted">Actualizado: {{ $h->updated_at }}</div>
          </div>

          {{-- image_folder --}}
          <div class="field-card">
            <div class="label">Carpeta de imágenes</div>
            <div class="small">Carpeta usada para recursos (ej: uploads/homepage)</div>
            <form class="hp-field-form" data-id="{{ $h->id }}" method="POST" action="{{ route('homepage.update', $h->id) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="field" value="image_folder">
              <input class="input" name="image_folder" value="{{ $h->image_folder }}" placeholder="uploads/homepage">
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-ghost" data-open-picker data-field="image_folder">Seleccionar carpeta...</button>
              </div>
            </form>
            <div class="small-muted">Actualizado: {{ $h->updated_at }}</div>
          </div>

          {{-- ubicacion --}}
          <div class="field-card">
            <div class="label">Ubicación</div>
            <form class="hp-field-form" data-id="{{ $h->id }}" method="POST" action="{{ route('homepage.update', $h->id) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="field" value="ubicacion">
              <input class="input" name="ubicacion" value="{{ $h->ubicacion }}">
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Guardar</button>
              </div>
            </form>
          </div>

          {{-- eslogan --}}
          <div class="field-card">
            <div class="label">Eslogan</div>
            <form class="hp-field-form" data-id="{{ $h->id }}" method="POST" action="{{ route('homepage.update', $h->id) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="field" value="eslogan">
              <input class="input" name="eslogan" value="{{ $h->eslogan }}">
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Guardar</button>
              </div>
            </form>
          </div>

          {{-- nombre_empresa --}}
          <div class="field-card">
            <div class="label">Nombre empresa</div>
            <form class="hp-field-form" data-id="{{ $h->id }}" method="POST" action="{{ route('homepage.update', $h->id) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="field" value="nombre_empresa">
              <input class="input" name="nombre_empresa" value="{{ $h->nombre_empresa }}">
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Guardar</button>

                <form method="POST" action="{{ route('homepage.destroy', $h->id) }}" style="display:inline;" onsubmit="return confirm('Eliminar entrada #{{ $h->id }}?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;margin-left:6px;">Borrar</button>
                </form>
              </div>
            </form>
            <div class="small-muted">Creado: {{ $h->created_at }} · Actualizado: {{ $h->updated_at }}</div>
          </div>

        @endforeach

        {{-- create card --}}
        <div class="field-card" id="hp-create-card" style="grid-column: 1 / -1;">
          <div class="label">Crear nueva entrada</div>
          <form id="hp-create-form" method="POST" action="{{ route('homepage.store') }}">
            @csrf
            <label class="small">Banner (ruta)</label>
            <input class="input" name="banner_image" placeholder="uploads/banner.jpg">
            <label class="small" style="margin-top:8px">Carpeta de imágenes</label>
            <input class="input" name="image_folder" placeholder="uploads/homepage">
            <label class="small" style="margin-top:8px">Ubicación</label>
            <input class="input" name="ubicacion" placeholder="Valle de ...">
            <label class="small" style="margin-top:8px">Eslogan</label>
            <input class="input" name="eslogan" placeholder="Escápate y descansa">
            <label class="small" style="margin-top:8px">Nombre empresa</label>
            <input class="input" name="nombre_empresa" placeholder="HomeRes Demo">
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
              <button type="submit" class="btn btn-primary">Crear</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  @endif

  <!-- image picker modal -->
  <div id="hp-image-picker" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="picker-back" data-close></div>
    <div class="picker-panel" role="document">
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <div style="font-weight:800">Seleccionar carpeta</div>
          <button data-close class="btn btn-ghost" style="padding:6px 8px;">Cerrar</button>
        </div>

        <div style="margin-bottom:8px;">
          <input id="hp-picker-filter" placeholder="Filtrar carpetas..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee;margin-bottom:8px;">
          <div class="picker-folders" id="hp-picker-folders" aria-live="polite"></div>
        </div>

        <div style="display:flex;gap:8px;">
          <button id="hp-picker-select-folder" class="btn btn-primary">Usar carpeta seleccionada</button>
          <button id="hp-picker-refresh" class="btn btn-ghost">Actualizar</button>
        </div>
      </div>

      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <div style="font-weight:800" id="hp-picker-title">Vista previa</div>
          <div class="small-muted" id="hp-picker-info"></div>
        </div>

        <div class="picker-files" id="hp-picker-files" aria-live="polite"></div>

        <div class="picker-actions" style="margin-top:8px;">
          <button id="hp-picker-use-as-banner" class="btn btn-primary" disabled>Usar imagen seleccionada como banner</button>
          <button data-close class="btn btn-ghost">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){

  // --- preview toggle (existing) ---
  const wrap = document.getElementById('hp-wrap');
  const toggle = document.getElementById('hp-view-toggle');
  const preview = document.getElementById('hp-preview');
  if (wrap) {
    @if(!$isAdmin)
      wrap.classList.add('preview-mode');
      if (preview) preview.style.display = 'block';
    @else
      if (toggle) {
        const stored = localStorage.getItem('hp_view_as_user') === '1';
        toggle.checked = stored;
        wrap.classList.toggle('preview-mode', stored);
        if (preview) preview.style.display = stored ? 'block' : 'none';
        toggle.addEventListener('change', function(){
          const isPreview = toggle.checked;
          localStorage.setItem('hp_view_as_user', isPreview ? '1' : '0');
          wrap.classList.toggle('preview-mode', isPreview);
          if (preview) preview.style.display = isPreview ? 'block' : 'none';
          if (isPreview && preview) preview.scrollIntoView({behavior:'smooth'});
        });
      }
    @endif
  }

  // --- Carousel initialization & behavior ---
  (function initCarousel(){
    const initial = window.hpInitialCarousel || [];
    const carousel = document.getElementById('hp-carousel');
    if (!carousel) return;

    const track = document.getElementById('hp-carousel-track');
    const prev = document.getElementById('hp-carousel-prev');
    const next = document.getElementById('hp-carousel-next');
    const dotsContainer = document.getElementById('hp-carousel-dots');
    let slides = Array.from(track.children);
    let idx = 0;
    let width = carousel.clientWidth || carousel.offsetWidth;
    let autoTimer = null;

    // adjust nav buttons visually outside image and accessible
    const styleNav = document.createElement('style');
    styleNav.innerHTML = `
      .hp-carousel-btn{ position:absolute; top:50%; transform:translateY(-50%); z-index:6; border:0; padding:10px 12px; border-radius:8px; background:rgba(0,0,0,0.36); color:#fff; cursor:pointer; }
      .hp-carousel-btn-left{ left:12px; }
      .hp-carousel-btn-right{ right:12px; }
      .hp-carousel .dots{ z-index:6; bottom:14px; position:absolute; left:50%; transform:translateX(-50%); display:flex; gap:8px; }
      .hp-carousel .dot{ width:10px; height:10px; border-radius:999px; border:1px solid rgba(255,255,255,0.5); background:rgba(255,255,255,0.45); cursor:pointer; }
      .hp-carousel .dot.active{ background:#fff; box-shadow:0 8px 20px rgba(2,6,23,0.06); }
      .hp-carousel .track{ will-change:transform; }
    `;
    document.head.appendChild(styleNav);

    function refresh() {
      slides = Array.from(track.children);
      width = carousel.clientWidth || carousel.offsetWidth;
      updatePosition(true);
      // show/hide controls if single slide
      if (slides.length <= 1) {
        prev.style.display = 'none';
        next.style.display = 'none';
        if (dotsContainer) dotsContainer.style.display = 'none';
      } else {
        prev.style.display = '';
        next.style.display = '';
        if (dotsContainer) dotsContainer.style.display = '';
      }
    }

    function updatePosition(noAnim){
      if (noAnim) track.style.transition = 'none';
      else track.style.transition = 'transform .6s cubic-bezier(.22,.9,.3,1)';
      track.style.transform = 'translateX(' + (-idx * width) + 'px)';
      // update dots active
      if (dotsContainer) {
        Array.from(dotsContainer.children).forEach((d,i)=>{
          d.classList.toggle('active', i === idx);
          d.setAttribute('aria-selected', i === idx ? 'true' : 'false');
        });
      }
      // force reflow to re-enable transition
      if (noAnim) { void track.offsetWidth; track.style.transition = ''; }
    }

    function nextSlide(){
      if (slides.length === 0) return;
      idx = (idx + 1) % slides.length;
      updatePosition();
    }
    function prevSlide(){
      if (slides.length === 0) return;
      idx = (idx - 1 + slides.length) % slides.length;
      updatePosition();
    }

    // attach buttons
    prev?.addEventListener('click', function(e){ e.preventDefault(); stopAuto(); prevSlide(); startAuto(); });
    next?.addEventListener('click', function(e){ e.preventDefault(); stopAuto(); nextSlide(); startAuto(); });

    // dots
    if (dotsContainer) {
      dotsContainer.addEventListener('click', function(e){
        const d = e.target.closest('.dot');
        if (!d) return;
        const to = Number(d.dataset.dotIndex || 0);
        if (isNaN(to)) return;
        stopAuto();
        idx = Math.max(0, Math.min(to, slides.length-1));
        updatePosition();
        startAuto();
      });
    }

    // auto rotate
    function startAuto(){ stopAuto(); if (slides.length > 1) autoTimer = setInterval(nextSlide, 4500); }
    function stopAuto(){ if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }

    // make responsive
    window.addEventListener('resize', function(){ refresh(); });

    // touch / swipe support
    let pointer = { startX:0, dx:0, dragging:false, startTime:0 };

    carousel.addEventListener('pointerdown', function(e){
      // if clicking controls (buttons, dots) do not start dragging — allow click
      if (e.target.closest('.hp-carousel-btn') || e.target.closest('.dot') || e.target.closest('#hp-carousel-prev') || e.target.closest('#hp-carousel-next')) {
        return;
      }
      pointer.dragging = true;
      pointer.startX = e.clientX;
      pointer.startTime = Date.now();
      track.style.transition = 'none';
      try { carousel.setPointerCapture && carousel.setPointerCapture(e.pointerId); } catch(e){}
      stopAuto();
    });

    carousel.addEventListener('pointermove', function(e){
      if (!pointer.dragging) return;
      pointer.dx = e.clientX - pointer.startX;
      track.style.transform = `translateX(${ -idx * width + pointer.dx }px)`;
    });
    carousel.addEventListener('pointerup', function(e){
      if (!pointer.dragging) return;
      pointer.dragging = false;
      const dt = Date.now() - pointer.startTime;
      const vx = pointer.dx / Math.max(1, dt);
      // threshold
      if (pointer.dx > width * 0.2 || vx > 0.5) { prevSlide(); }
      else if (pointer.dx < -width * 0.2 || vx < -0.5) { nextSlide(); }
      else updatePosition();
      startAuto();
      pointer.dx = 0;
    });
    carousel.addEventListener('pointercancel', function(){ pointer.dragging = false; updatePosition(); startAuto(); });

    // keyboard navigation
    carousel.tabIndex = 0;
    carousel.addEventListener('keydown', function(e){
      if (e.key === 'ArrowLeft') { stopAuto(); prevSlide(); startAuto(); }
      if (e.key === 'ArrowRight') { stopAuto(); nextSlide(); startAuto(); }
    });

    // init
    refresh();
    startAuto();

    // expose helper to rebuild carousel from dynamic list (used when admin changes folder)
    window.hpRebuildCarousel = function(urls){
      if (!Array.isArray(urls)) return;
      track.innerHTML = '';
      urls.forEach(u=>{
        const s = document.createElement('div');
        s.className = 'slide';
        s.innerHTML = '<img src="'+u+'" alt="Banner">';
        track.appendChild(s);
      });
      // rebuild dots
      if (dotsContainer) {
        dotsContainer.innerHTML = '';
        urls.forEach((u,i)=>{
          const b = document.createElement('button');
          b.className = 'dot' + (i===0 ? ' active' : '');
          b.dataset.dotIndex = i;
          b.setAttribute('aria-label', 'Ir a slide ' + (i+1));
          b.setAttribute('role','tab');
          b.setAttribute('aria-selected', i===0 ? 'true' : 'false');
          dotsContainer.appendChild(b);
        });
      }
      idx = 0;
      refresh();
    };

  })();

});
</script>
@endpush

@section('scripts')
@show
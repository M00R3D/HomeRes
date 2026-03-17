@extends('layouts.app')

@section('title','Inicio')

@section('content')
@php
  use App\Models\Reservation;
  use App\Models\Propiedad;

  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
  $homepage = $homepage ?? null;
  $homepageMeta = $homepageMeta ?? ['settings' => [], 'blocks' => []];
  $settings = $homepageMeta['settings'] ?? [];
  $blocks = $homepageMeta['blocks'] ?? [];

  $userReservs = collect();
  if ($currentUser) {
      $userReservs = Reservation::with('propiedad')
          ->where('usuario_id', $currentUser->id)
          ->orderByDesc('created_at')
          ->take(6)
          ->get();
  }

  $allProps = Propiedad::orderBy('nombre')->get();
  $baseUrl = url('/');
    $resolveMedia = function ($path) use ($settings, $homepage) {
      if (! $path) {
        return null;
      }

      $raw = trim((string) $path);
      if ($raw === '') {
        return null;
      }

      if (preg_match('/^(?:https?:)?\/\//', $raw) || str_starts_with($raw, 'data:')) {
        return $raw;
      }

      $clean = ltrim(str_replace('\\', '/', $raw), '/');
      $folder = trim((string) ($settings['image_folder'] ?? $homepage->image_folder ?? ''), '/');
      $candidates = [$clean];

      if ($folder !== '' && ! str_starts_with($clean, $folder . '/')) {
        $candidates[] = $folder . '/' . $clean;
      }
      if (! str_starts_with($clean, 'uploads/')) {
        $candidates[] = 'uploads/' . $clean;
      }
      if (preg_match('/^\d+\//', $clean)) {
        $candidates[] = 'uploads_propiedades_' . strtok($clean, '/') . '_/' . basename($clean);
      }
      if (! str_starts_with($clean, 'logos/')) {
        $candidates[] = 'logos/' . basename($clean);
      }
        if (str_contains($clean, 'banner-default')) {
          $candidates[] = 'uploads/banner.jpg';
        }

      foreach (array_values(array_unique($candidates)) as $candidate) {
        if (is_file(public_path($candidate))) {
          return asset($candidate);
        }
      }

      return null;
    };

  $quickMedia = collect($folderFiles ?? [])->map(function ($url) use ($baseUrl) {
      if (str_starts_with($url, $baseUrl)) {
          return ltrim(substr($url, strlen($baseUrl)), '/');
      }
      return $url;
  })->values()->all();

  $previewPayload = [
      'settings' => $settings,
      'blocks' => $blocks,
  ];

  $dynamicPayload = [
      'reservations' => $userReservs->map(function ($rv) {
          return [
              'id' => $rv->id,
              'name' => $rv->propiedad->nombre ?? ('Propiedad #' . $rv->propiedad_id),
              'image' => $rv->propiedad->ruta_img ?? null,
              'location' => $rv->propiedad->ubicacion ?? '',
            'check_in' => $rv->check_in ? \Carbon\Carbon::parse($rv->check_in)->format('Y-m-d') : '',
            'check_out' => $rv->check_out ? \Carbon\Carbon::parse($rv->check_out)->format('Y-m-d') : '',
              'status' => $rv->estado,
              'url' => route('reservaciones.show', $rv->id),
          ];
      })->values(),
      'properties' => $allProps->map(function ($p) {
          return [
              'id' => $p->id,
              'name' => $p->nombre,
              'image' => $p->ruta_img,
              'location' => $p->ubicacion,
              'price' => (float) ($p->precio_noche ?? 0),
              'showUrl' => route('propiedades.show', $p->id),
              'reserveUrl' => route('reservaciones.create_for_propiedad', $p->id),
          ];
      })->values(),
      'loggedIn' => (bool) $currentUser,
  ];
@endphp

<style>
.hp-shell{max-width:1400px;margin:18px auto;padding:12px;display:flex;flex-direction:column;gap:18px}
.hp-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.hp-toolbar h1{margin:0;font-size:clamp(1.6rem,2vw,2.4rem)}
.hp-toolbar-copy{color:var(--muted,#6b7280);max-width:760px}
.hp-view-switch{display:inline-flex;align-items:center;gap:10px;padding:8px 12px;border-radius:999px;background:var(--card,#fff);box-shadow:0 10px 24px rgba(2,6,23,0.06);border:1px solid var(--input-border,#e5e7eb)}
.hp-view-switch input[type="checkbox"]{width:44px;height:26px;-webkit-appearance:none;appearance:none;background:#dbe4f7;border-radius:999px;position:relative;cursor:pointer;transition:background .2s ease}
.hp-view-switch input[type="checkbox"]::after{content:'';position:absolute;left:4px;top:4px;width:18px;height:18px;border-radius:999px;background:#fff;box-shadow:0 4px 12px rgba(2,6,23,0.14);transition:transform .2s ease}
.hp-view-switch input[type="checkbox"]:checked{background:linear-gradient(90deg,var(--btn-primary,#2563eb),var(--btn-alt,#06b6d4))}
.hp-view-switch input[type="checkbox"]:checked::after{transform:translateX(18px)}
.hp-admin-layout{display:grid;grid-template-columns:minmax(360px, 560px) minmax(360px, 1fr);gap:18px;align-items:start}
.hp-shell.preview-mode .hp-admin-layout{grid-template-columns:1fr}
.hp-shell.preview-mode .hp-editor-pane{display:none}
.hp-editor-pane,.hp-preview-pane,.hp-public-standalone{background:var(--card,#fff);border:1px solid var(--input-border,#e5e7eb);border-radius:20px;box-shadow:0 18px 50px rgba(2,6,23,0.08)}
.hp-editor-pane{padding:18px;display:flex;flex-direction:column;gap:18px}
.hp-preview-pane{padding:14px;position:sticky;top:18px}
.hp-shell.preview-mode .hp-preview-pane{position:static}
.hp-pane-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.hp-pane-head h2,.hp-pane-head h3{margin:0}
.hp-pane-subtitle{color:var(--muted,#6b7280);font-size:.95rem}
.hp-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.hp-field{display:flex;flex-direction:column;gap:6px}
.hp-field label,.hp-field span{font-weight:700;color:var(--text-color,#111827);font-size:.95rem}
.hp-field input,.hp-field textarea,.hp-field select{width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--input-border,#d1d5db);background:var(--input-bg,#fff);color:var(--text-color,#111827)}
.hp-field textarea{min-height:108px;resize:vertical}
.hp-field.compact textarea{min-height:84px}
.hp-field-row{display:flex;gap:8px;align-items:center}
.hp-chip-row{display:flex;flex-wrap:wrap;gap:8px}
.hp-add-block{border:0;border-radius:999px;padding:8px 12px;font-weight:700;cursor:pointer;background:var(--btn-primary,#2563eb);color:#fff}
.hp-block-editor-list{display:flex;flex-direction:column;gap:12px}
.hp-block-editor{border:1px solid var(--input-border,#e5e7eb);border-radius:18px;padding:14px;background:linear-gradient(180deg,rgba(255,255,255,.9),rgba(248,250,252,.96));display:flex;flex-direction:column;gap:12px;transition:transform .28s cubic-bezier(.2,.8,.2,1),box-shadow .2s ease;will-change:transform}
.hp-block-editor.is-moving{box-shadow:0 16px 34px rgba(2,6,23,.12)}
.hp-block-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
.hp-block-title{display:flex;flex-direction:column;gap:4px}
.hp-block-title strong{text-transform:capitalize}
.hp-block-tools{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.hp-tool-btn{border:1px solid var(--input-border,#d1d5db);background:#fff;border-radius:10px;padding:7px 10px;cursor:pointer;font-weight:700}
.hp-tool-btn.danger{color:#b91c1c;border-color:#fecaca;background:#fff5f5}
.hp-tool-toggle{display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--muted,#6b7280)}
.hp-inline-list{display:flex;flex-direction:column;gap:8px}
.hp-inline-item{display:grid;grid-template-columns:1fr 1fr 120px auto;gap:8px;align-items:end}
.hp-inline-item.faq{grid-template-columns:1fr auto}
.hp-inline-item.faq textarea{grid-column:1 / span 1}
.hp-media-strip{display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:8px}
.hp-media-thumb{position:relative;border:1px solid var(--input-border,#e5e7eb);background:#f8fafc;border-radius:12px;overflow:hidden;height:72px;cursor:pointer}
.hp-media-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.hp-media-thumb span{position:absolute;left:6px;right:6px;bottom:6px;font-size:11px;color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.55);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.hp-actions-row{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
.hp-submit-row{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
.hp-reset-form{margin:0}
.hp-preview-frame{background:linear-gradient(180deg,#fbfdff,#f5f8fc);border-radius:16px;padding:16px;min-height:720px}
.hp-public-content{display:flex;flex-direction:column;gap:18px}
.hp-public-content.is-narrow{max-width:960px;margin:0 auto}
.hp-block{position:relative}
.hp-block-hero{border-radius:24px;overflow:hidden;min-height:320px;background:#0f172a center/cover no-repeat;color:#fff;display:flex;align-items:stretch}
.hp-block-hero.hero-sm{min-height:260px}
.hp-block-hero.hero-md{min-height:340px}
.hp-block-hero.hero-lg{min-height:440px}
.hp-hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,23,42,var(--hero-overlay,.45)),rgba(15,23,42,.18))}
.hp-hero-inner{position:relative;z-index:1;padding:clamp(22px,4vw,42px);display:flex;flex-direction:column;gap:12px;max-width:min(720px,100%)}
.hp-block-hero.align-center .hp-hero-inner{margin:0 auto;text-align:center;align-items:center}
.hp-block-hero.align-right .hp-hero-inner{margin-left:auto;text-align:right;align-items:flex-end}
.hp-hero-inner h2{margin:0;font-size:clamp(2rem,4vw,4rem);line-height:1.02}
.hp-eyebrow{text-transform:uppercase;letter-spacing:.18em;font-size:.75rem;font-weight:800;opacity:.82}
.hp-subtitle{margin:0;font-size:1.05rem;color:var(--muted,#6b7280)}
.hp-block-hero .hp-subtitle,.hp-block-hero .hp-copy{color:rgba(255,255,255,.92)}
.hp-copy{line-height:1.7}
.hp-actions{display:flex;gap:10px;flex-wrap:wrap}
.hp-block-banner{display:flex;flex-direction:column;gap:10px}
.hp-block-banner img{width:100%;display:block;border-radius:18px;object-fit:cover;min-height:200px;max-height:420px}
.hp-block-banner.banner-sm img{min-height:160px;max-height:220px}
.hp-block-banner.banner-md img{min-height:220px;max-height:320px}
.hp-block-banner.banner-lg img{min-height:320px;max-height:460px}
.hp-banner-copy{padding:8px 4px;display:flex;flex-direction:column;gap:4px}
.hp-block-title h3{margin:0;font-size:clamp(1.6rem,2vw,3rem)}
.hp-block-title p{margin:8px 0 0;color:var(--muted,#6b7280)}
.hp-block-title.align-center{text-align:center}
.hp-block-title.align-right{text-align:right}
.hp-block-title.size-sm h3{font-size:1.5rem}
.hp-block-title.size-md h3{font-size:2rem}
.hp-block-title.size-lg h3{font-size:2.6rem}
.hp-block-title.size-xl h3{font-size:3.3rem}
.hp-block-text{padding:22px;border-radius:20px}
.hp-block-text.style-card{background:var(--card,#fff);box-shadow:0 16px 40px rgba(2,6,23,.06)}
.hp-block-text.style-soft{background:linear-gradient(180deg,rgba(37,99,235,.08),rgba(6,182,212,.04));border:1px solid rgba(37,99,235,.08)}
.hp-block-text.style-plain{padding:0;background:transparent}
.hp-block-text.align-center{text-align:center}
.hp-block-text.align-right{text-align:right}
.hp-block-text h3{margin:0 0 8px;font-size:1.7rem}
.hp-block-image{display:flex;flex-direction:column;gap:10px}
.hp-block-image img{width:100%;display:block;border-radius:20px;object-fit:cover;box-shadow:0 20px 48px rgba(2,6,23,.12)}
.hp-block-image.width-sm{max-width:380px}
.hp-block-image.width-md{max-width:640px}
.hp-block-image.width-lg{max-width:860px}
.hp-block-image.width-full{max-width:none}
.hp-block-image.height-sm img{height:220px}
.hp-block-image.height-md img{height:340px}
.hp-block-image.height-lg img{height:460px}
.hp-block-image figcaption{display:flex;flex-direction:column;gap:4px;color:var(--muted,#6b7280)}
.hp-link-grid{display:flex;flex-wrap:wrap;gap:10px}
.hp-block-links h3,.hp-block-faq h3,.hp-section-head h3{margin:0 0 6px;font-size:1.6rem}
.hp-faq-list{display:flex;flex-direction:column;gap:10px}
.hp-faq-item{background:var(--card,#fff);border:1px solid var(--input-border,#e5e7eb);border-radius:16px;padding:14px 16px}
.hp-faq-item summary{cursor:pointer;font-weight:800}
.hp-faq-item div{padding-top:10px;color:var(--muted,#6b7280);line-height:1.6}
.hp-dynamic-block{display:flex;flex-direction:column;gap:12px}
.hp-section-head{display:flex;justify-content:space-between;gap:12px;align-items:end;flex-wrap:wrap}
.hp-section-head span{color:var(--muted,#6b7280)}
.hp-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.hp-card-mini{background:var(--card,#fff);border:1px solid var(--input-border,#e5e7eb);border-radius:18px;padding:12px;display:flex;flex-direction:column;gap:12px;box-shadow:0 14px 38px rgba(2,6,23,.05)}
.hp-card-media{width:100%;height:160px;border-radius:14px;overflow:hidden;background:#eef2f7}
.hp-card-media img{width:100%;height:100%;object-fit:cover;display:block}
.hp-card-copy{display:flex;flex-direction:column;gap:4px;color:var(--muted,#6b7280)}
.hp-inline-actions{display:flex;gap:8px;flex-wrap:wrap}
.hp-empty{padding:18px;border:1px dashed var(--input-border,#d1d5db);border-radius:16px;color:var(--muted,#6b7280);background:rgba(255,255,255,.56)}
@media (max-width:1100px){.hp-admin-layout{grid-template-columns:1fr}.hp-preview-pane{position:static}.hp-form-grid{grid-template-columns:1fr}.hp-inline-item{grid-template-columns:1fr}.hp-inline-item.faq{grid-template-columns:1fr}.hp-toolbar{align-items:flex-start}}
</style>

<div class="hp-shell" id="hp-shell">
  <div class="hp-toolbar">
    <div>
      <h1>{{ $isAdmin ? 'Homepage Builder' : 'Inicio' }}</h1>
      <div class="hp-toolbar-copy">
        {{ $isAdmin ? 'Configura banners, bloques, FAQs, links e imagenes desde un singleton guardado en homepage.id = 1. La vista previa se actualiza en tiempo real.' : ($homepage->eslogan ?? 'Escapate y descansa') }}
      </div>
    </div>
    @if($isAdmin)
      <label class="hp-view-switch">
        <span>Ver como usuario</span>
        <input type="checkbox" id="hp-preview-toggle" aria-label="Ver como usuario normal en home">
      </label>
    @endif
  </div>

  @if(session('success'))
    <div class="card" style="padding:12px;color:#065f46;background:#ecfdf5;border-radius:14px;border:1px solid #bbf7d0;">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="card" style="padding:12px;color:#991b1b;background:#fee2e2;border-radius:14px;border:1px solid #fecaca;">{{ $errors->first() }}</div>
  @endif

  @if($isAdmin)
    <div class="hp-admin-layout">
      <form id="hp-editor-form" class="hp-editor-pane" method="POST" action="{{ route('homepage.update', 1) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="meta_json" id="hp-meta-json">

        <div class="hp-pane-head">
          <div>
            <h2>Editor de homepage</h2>
            <div class="hp-pane-subtitle">Edita el contenido estructurado y guárdalo en una sola configuración global.</div>
          </div>
        </div>

        <div class="hp-form-grid">
          <div class="hp-field">
            <label for="hp-company-name">Nombre empresa</label>
            <input id="hp-company-name" name="nombre_empresa" value="{{ old('nombre_empresa', $homepage->nombre_empresa) }}" placeholder="HomeRes Demo">
          </div>
          <div class="hp-field">
            <label for="hp-location">Ubicación</label>
            <input id="hp-location" name="ubicacion" value="{{ old('ubicacion', $homepage->ubicacion) }}" placeholder="Valle de las Flores">
          </div>
          <div class="hp-field compact">
            <label for="hp-slogan">Eslogan</label>
            <textarea id="hp-slogan" name="eslogan" placeholder="Escapate y descansa">{{ old('eslogan', $homepage->eslogan) }}</textarea>
          </div>
          <div class="hp-field compact">
            <label for="hp-banner-image">Banner principal</label>
            <input id="hp-banner-image" class="js-media-input" data-media-role="banner" name="banner_image" value="{{ old('banner_image', $homepage->banner_image) }}" placeholder="uploads/banner.jpg">
          </div>
          <div class="hp-field compact">
            <label for="hp-image-folder">Carpeta de imágenes</label>
            <input id="hp-image-folder" name="image_folder" value="{{ old('image_folder', $homepage->image_folder) }}" placeholder="uploads/homepage">
          </div>
          <div class="hp-field compact">
            <label for="hp-container-width">Ancho del layout</label>
            <select id="hp-container-width" data-setting="container">
              <option value="wide" {{ ($settings['container'] ?? 'wide') === 'wide' ? 'selected' : '' }}>Amplio</option>
              <option value="narrow" {{ ($settings['container'] ?? 'wide') === 'narrow' ? 'selected' : '' }}>Compacto</option>
            </select>
          </div>
        </div>

        <div class="hp-actions-row">
          <div class="hp-chip-row">
            <label class="hp-tool-toggle"><input type="checkbox" id="hp-show-reservations" {{ !empty($settings['show_reservations']) ? 'checked' : '' }}> Mostrar reservaciones</label>
            <label class="hp-tool-toggle"><input type="checkbox" id="hp-show-properties" {{ !empty($settings['show_properties']) ? 'checked' : '' }}> Mostrar propiedades</label>
          </div>
        </div>

        @if(!empty($quickMedia))
          <div class="hp-field">
            <span>Media rápida</span>
            <div class="hp-pane-subtitle">Haz clic en una miniatura para insertarla en el último campo de imagen seleccionado.</div>
            <div class="hp-media-strip" id="hp-quick-media">
              @foreach($quickMedia as $media)
                <button type="button" class="hp-media-thumb" data-media-path="{{ $media }}" title="{{ $media }}">
                  <img src="{{ $resolveMedia($media) }}" alt="{{ $media }}">
                  <span>{{ basename($media) }}</span>
                </button>
              @endforeach
            </div>
          </div>
        @endif

        <div class="hp-pane-head">
          <div>
            <h3>Bloques del layout</h3>
            <div class="hp-pane-subtitle">Agrega secciones visuales, mueve su orden y ajusta su tamaño o estilo.</div>
          </div>
          <div class="hp-chip-row">
            <button type="button" class="hp-add-block" data-add-block="hero">+ Hero</button>
            <button type="button" class="hp-add-block" data-add-block="banner">+ Banner</button>
            <button type="button" class="hp-add-block" data-add-block="title">+ Título</button>
            <button type="button" class="hp-add-block" data-add-block="text">+ Texto</button>
            <button type="button" class="hp-add-block" data-add-block="image">+ Imagen</button>
            <button type="button" class="hp-add-block" data-add-block="links">+ Links</button>
            <button type="button" class="hp-add-block" data-add-block="faq">+ FAQ</button>
          </div>
        </div>

        <div id="hp-blocks-editor" class="hp-block-editor-list"></div>

        <div class="hp-submit-row">
          <button type="button" class="hp-tool-btn danger" id="hp-reset-button">Reiniciar homepage</button>
          <button type="submit" class="btn btn-primary">Guardar homepage</button>
        </div>
      </form>

      <section class="hp-preview-pane">
        <div class="hp-pane-head" style="margin-bottom:12px;">
          <div>
            <h3>Vista previa en vivo</h3>
            <div class="hp-pane-subtitle">Refleja tus cambios antes de guardar.</div>
          </div>
        </div>
        <div class="hp-preview-frame">
          <div id="hp-live-preview">
            @include('homepage._render', [
              'blocks' => $blocks,
              'settings' => $settings,
              'currentUser' => $currentUser,
              'userReservs' => $userReservs,
              'allProps' => $allProps,
              'resolveMedia' => $resolveMedia,
            ])
          </div>
        </div>
      </section>
    </div>
  @else
    <div class="hp-public-standalone" style="padding:16px;">
      @include('homepage._render', [
        'blocks' => $blocks,
        'settings' => $settings,
        'currentUser' => $currentUser,
        'userReservs' => $userReservs,
        'allProps' => $allProps,
        'resolveMedia' => $resolveMedia,
      ])
    </div>
  @endif
</div>
@endsection

@push('scripts')
@if($isAdmin)
<script>
document.addEventListener('DOMContentLoaded', function () {
  const shell = document.getElementById('hp-shell');
  const previewToggle = document.getElementById('hp-preview-toggle');
  const blocksEditor = document.getElementById('hp-blocks-editor');
  const previewRoot = document.getElementById('hp-live-preview');
  const metaInput = document.getElementById('hp-meta-json');
  const form = document.getElementById('hp-editor-form');
  const resetButton = document.getElementById('hp-reset-button');
  const baseUrl = @json($baseUrl);
  const dynamicData = @json($dynamicPayload);
  const initialState = @json($previewPayload);
  let activeMediaInput = document.getElementById('hp-banner-image');

  const state = {
    settings: Object.assign({
      image_folder: document.getElementById('hp-image-folder')?.value || '',
      show_reservations: true,
      show_properties: true,
      container: 'wide',
      hero_height: 'lg',
    }, initialState.settings || {}),
    blocks: Array.isArray(initialState.blocks) ? initialState.blocks : [],
  };

  const blockTypes = {
    hero: 'Hero',
    banner: 'Banner',
    title: 'Título',
    text: 'Texto',
    image: 'Imagen',
    links: 'Links',
    faq: 'FAQ',
  };

  function uid(prefix) {
    return prefix + '-' + Math.random().toString(36).slice(2, 10);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function nl2brSafe(value) {
    return escapeHtml(value).replace(/\n/g, '<br>');
  }

  function normalizeMediaPath(path) {
    return String(path || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
  }

  function mediaPathCandidates(path) {
    const raw = String(path || '').trim();
    if (!raw) return [];
    if (/^(https?:)?\/\//.test(raw) || raw.startsWith('data:')) return [raw];

    const normalized = normalizeMediaPath(raw);
    const folder = normalizeMediaPath(state.settings.image_folder || document.getElementById('hp-image-folder')?.value || '');
    const candidates = [normalized];

    if (folder && !normalized.startsWith(folder + '/')) {
      candidates.push(folder + '/' + normalized);
    }
    if (!normalized.startsWith('uploads/')) {
      candidates.push('uploads/' + normalized);
    }
    if (/^\d+\//.test(normalized)) {
      const parts = normalized.split('/');
      candidates.push('uploads_propiedades_' + parts[0] + '_/' + parts.slice(1).join('/'));
    }
    if (!normalized.startsWith('logos/')) {
      candidates.push('logos/' + normalized.split('/').pop());
    }
    if (normalized.includes('banner-default')) {
      candidates.push('uploads/banner.jpg');
    }

    return Array.from(new Set(candidates.filter(Boolean)));
  }

  function toMediaUrl(path) {
    const candidates = mediaPathCandidates(path);
    if (!candidates.length) return '';
    if (/^(https?:)?\/\//.test(candidates[0]) || candidates[0].startsWith('data:')) return candidates[0];
    return baseUrl.replace(/\/$/, '') + '/' + candidates[0].replace(/^\/+/, '');
  }

  function syncDefaultHeroFromBase(shouldRenderEditor = false) {
    const hero = state.blocks.find((block) => block.type === 'hero' && block.id === 'hero-default');
    if (!hero) return;
    hero.title = document.getElementById('hp-company-name')?.value || '';
    hero.subtitle = document.getElementById('hp-slogan')?.value || '';
    hero.eyebrow = document.getElementById('hp-location')?.value || '';
    hero.image = document.getElementById('hp-banner-image')?.value || '';
    if (shouldRenderEditor) {
      renderBlocksEditor();
    }
  }

  function animateEditorReorder(mutator) {
    const before = new Map();
    blocksEditor.querySelectorAll('.hp-block-editor').forEach((card) => {
      before.set(card.dataset.blockId || '', card.getBoundingClientRect());
    });

    mutator();
    renderBlocksEditor();

    blocksEditor.querySelectorAll('.hp-block-editor').forEach((card) => {
      const key = card.dataset.blockId || '';
      const prev = before.get(key);
      if (!prev) return;
      const next = card.getBoundingClientRect();
      const dx = prev.left - next.left;
      const dy = prev.top - next.top;
      if (!dx && !dy) return;

      card.classList.add('is-moving');
      card.style.transition = 'none';
      card.style.transform = `translate(${dx}px, ${dy}px)`;
      requestAnimationFrame(() => {
        card.style.transition = 'transform .28s cubic-bezier(.2,.8,.2,1)';
        card.style.transform = 'translate(0,0)';
      });
      window.setTimeout(() => {
        card.classList.remove('is-moving');
        card.style.transition = '';
        card.style.transform = '';
      }, 320);
    });
  }

  function defaultBlock(type) {
    const base = { id: uid(type), type, enabled: true, title: '', subtitle: '', body: '', image: '', link: '', label: '', height: 'md', width: 'md', align: 'left', overlay: 45, eyebrow: '', primary_label: '', primary_url: '', secondary_label: '', secondary_url: '', size: 'md', style: 'card', items: [] };
    if (type === 'hero') {
      return Object.assign(base, {
        title: document.getElementById('hp-company-name')?.value || 'Nuevo hero',
        subtitle: document.getElementById('hp-slogan')?.value || 'Describe aquí la propuesta principal.',
        image: document.getElementById('hp-banner-image')?.value || '',
        height: 'lg',
        body: 'Un bloque principal para el homepage con botones, imagen y texto destacado.',
        primary_label: 'Explorar propiedades',
        primary_url: '/propiedades',
        secondary_label: 'Ver reservaciones',
        secondary_url: '/reservaciones',
        eyebrow: document.getElementById('hp-location')?.value || '',
      });
    }
    if (type === 'banner') {
      return Object.assign(base, { title: 'Banner visual', subtitle: 'Un recurso visual estático con link opcional.', image: document.getElementById('hp-banner-image')?.value || '', height: 'md', link: '/propiedades' });
    }
    if (type === 'title') {
      return Object.assign(base, { title: 'Título de sección', subtitle: 'Subtítulo breve con un tono claro.', size: 'xl', align: 'center' });
    }
    if (type === 'text') {
      return Object.assign(base, { title: 'Bloque de texto', subtitle: 'Contexto corto', body: 'Escribe aquí contenido editorial, instrucciones o un pitch del homepage.', style: 'card' });
    }
    if (type === 'image') {
      return Object.assign(base, { title: 'Imagen destacada', subtitle: 'Un caption opcional', image: document.getElementById('hp-banner-image')?.value || '', width: 'lg', height: 'md' });
    }
    if (type === 'links') {
      return Object.assign(base, { title: 'Accesos rápidos', subtitle: 'Botones o links importantes', items: [{ label: 'Ir a propiedades', url: '/propiedades', style: 'primary' }, { label: 'Ver reservaciones', url: '/reservaciones', style: 'ghost' }] });
    }
    if (type === 'faq') {
      return Object.assign(base, { title: 'Preguntas frecuentes', subtitle: 'Responde dudas comunes', items: [{ question: '¿Cómo reservo?', answer: 'Entra a una propiedad y usa el flujo de reservación.' }] });
    }
    return base;
  }

  function syncLegacySettings() {
    state.settings.image_folder = document.getElementById('hp-image-folder')?.value || '';
    state.settings.show_reservations = Boolean(document.getElementById('hp-show-reservations')?.checked);
    state.settings.show_properties = Boolean(document.getElementById('hp-show-properties')?.checked);
    state.settings.container = document.getElementById('hp-container-width')?.value || 'wide';
  }

  function renderBlocksEditor() {
    blocksEditor.innerHTML = state.blocks.map((block, index) => renderBlockEditor(block, index)).join('');
  }

  function renderBlockEditor(block, index) {
    const commonTop = `
      <div class="hp-block-top">
        <div class="hp-block-title">
          <strong>${escapeHtml(blockTypes[block.type] || block.type)}</strong>
          <span class="hp-pane-subtitle">ID: ${escapeHtml(block.id || '')}</span>
        </div>
        <div class="hp-block-tools">
          <label class="hp-tool-toggle"><input type="checkbox" data-action="toggle-enabled" data-index="${index}" ${block.enabled ? 'checked' : ''}> Activo</label>
          <button type="button" class="hp-tool-btn" data-action="move-up" data-index="${index}">Subir</button>
          <button type="button" class="hp-tool-btn" data-action="move-down" data-index="${index}">Bajar</button>
          <button type="button" class="hp-tool-btn" data-action="duplicate" data-index="${index}">Duplicar</button>
          <button type="button" class="hp-tool-btn danger" data-action="delete" data-index="${index}">Eliminar</button>
        </div>
      </div>`;

    const titleFields = `
      <div class="hp-form-grid">
        <div class="hp-field"><label>Título</label><input data-index="${index}" data-field="title" value="${escapeHtml(block.title || '')}"></div>
        <div class="hp-field"><label>Subtítulo</label><input data-index="${index}" data-field="subtitle" value="${escapeHtml(block.subtitle || '')}"></div>
      </div>`;

    if (block.type === 'hero') {
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          <div class="hp-form-grid">
            <div class="hp-field"><label>Eyebrow</label><input data-index="${index}" data-field="eyebrow" value="${escapeHtml(block.eyebrow || '')}"></div>
            <div class="hp-field"><label>Alineación</label><select data-index="${index}" data-field="align"><option value="left" ${block.align === 'left' ? 'selected' : ''}>Izquierda</option><option value="center" ${block.align === 'center' ? 'selected' : ''}>Centro</option><option value="right" ${block.align === 'right' ? 'selected' : ''}>Derecha</option></select></div>
          </div>
          ${titleFields}
          <div class="hp-field compact"><label>Texto largo</label><textarea data-index="${index}" data-field="body">${escapeHtml(block.body || '')}</textarea></div>
          <div class="hp-form-grid">
            <div class="hp-field"><label>Imagen</label><input class="js-media-input" data-media-role="block-image" data-index="${index}" data-field="image" value="${escapeHtml(block.image || '')}"></div>
            <div class="hp-field"><label>Altura</label><select data-index="${index}" data-field="height"><option value="sm" ${block.height === 'sm' ? 'selected' : ''}>Pequeña</option><option value="md" ${block.height === 'md' ? 'selected' : ''}>Media</option><option value="lg" ${block.height === 'lg' ? 'selected' : ''}>Grande</option></select></div>
          </div>
          <div class="hp-field"><label>Overlay (${escapeHtml(block.overlay || 45)}%)</label><input type="range" min="0" max="90" data-index="${index}" data-field="overlay" value="${escapeHtml(block.overlay || 45)}"></div>
          <div class="hp-form-grid">
            <div class="hp-field"><label>Botón primario</label><input data-index="${index}" data-field="primary_label" value="${escapeHtml(block.primary_label || '')}"></div>
            <div class="hp-field"><label>URL primaria</label><input data-index="${index}" data-field="primary_url" value="${escapeHtml(block.primary_url || '')}"></div>
            <div class="hp-field"><label>Botón secundario</label><input data-index="${index}" data-field="secondary_label" value="${escapeHtml(block.secondary_label || '')}"></div>
            <div class="hp-field"><label>URL secundaria</label><input data-index="${index}" data-field="secondary_url" value="${escapeHtml(block.secondary_url || '')}"></div>
          </div>
        </div>`;
    }

    if (block.type === 'banner') {
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-form-grid">
            <div class="hp-field"><label>Imagen</label><input class="js-media-input" data-media-role="block-image" data-index="${index}" data-field="image" value="${escapeHtml(block.image || '')}"></div>
            <div class="hp-field"><label>Link</label><input data-index="${index}" data-field="link" value="${escapeHtml(block.link || '')}"></div>
            <div class="hp-field"><label>Altura</label><select data-index="${index}" data-field="height"><option value="sm" ${block.height === 'sm' ? 'selected' : ''}>Pequeña</option><option value="md" ${block.height === 'md' ? 'selected' : ''}>Media</option><option value="lg" ${block.height === 'lg' ? 'selected' : ''}>Grande</option></select></div>
          </div>
        </div>`;
    }

    if (block.type === 'title') {
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-form-grid">
            <div class="hp-field"><label>Tamaño</label><select data-index="${index}" data-field="size"><option value="sm" ${block.size === 'sm' ? 'selected' : ''}>S</option><option value="md" ${block.size === 'md' ? 'selected' : ''}>M</option><option value="lg" ${block.size === 'lg' ? 'selected' : ''}>L</option><option value="xl" ${block.size === 'xl' ? 'selected' : ''}>XL</option></select></div>
            <div class="hp-field"><label>Alineación</label><select data-index="${index}" data-field="align"><option value="left" ${block.align === 'left' ? 'selected' : ''}>Izquierda</option><option value="center" ${block.align === 'center' ? 'selected' : ''}>Centro</option><option value="right" ${block.align === 'right' ? 'selected' : ''}>Derecha</option></select></div>
          </div>
        </div>`;
    }

    if (block.type === 'text') {
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-field compact"><label>Contenido</label><textarea data-index="${index}" data-field="body">${escapeHtml(block.body || '')}</textarea></div>
          <div class="hp-form-grid">
            <div class="hp-field"><label>Estilo</label><select data-index="${index}" data-field="style"><option value="card" ${block.style === 'card' ? 'selected' : ''}>Card</option><option value="soft" ${block.style === 'soft' ? 'selected' : ''}>Soft</option><option value="plain" ${block.style === 'plain' ? 'selected' : ''}>Plain</option></select></div>
            <div class="hp-field"><label>Alineación</label><select data-index="${index}" data-field="align"><option value="left" ${block.align === 'left' ? 'selected' : ''}>Izquierda</option><option value="center" ${block.align === 'center' ? 'selected' : ''}>Centro</option><option value="right" ${block.align === 'right' ? 'selected' : ''}>Derecha</option></select></div>
          </div>
        </div>`;
    }

    if (block.type === 'image') {
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-form-grid">
            <div class="hp-field"><label>Imagen</label><input class="js-media-input" data-media-role="block-image" data-index="${index}" data-field="image" value="${escapeHtml(block.image || '')}"></div>
            <div class="hp-field"><label>Link opcional</label><input data-index="${index}" data-field="link" value="${escapeHtml(block.link || '')}"></div>
            <div class="hp-field"><label>Ancho</label><select data-index="${index}" data-field="width"><option value="sm" ${block.width === 'sm' ? 'selected' : ''}>S</option><option value="md" ${block.width === 'md' ? 'selected' : ''}>M</option><option value="lg" ${block.width === 'lg' ? 'selected' : ''}>L</option><option value="full" ${block.width === 'full' ? 'selected' : ''}>Full</option></select></div>
            <div class="hp-field"><label>Altura</label><select data-index="${index}" data-field="height"><option value="sm" ${block.height === 'sm' ? 'selected' : ''}>S</option><option value="md" ${block.height === 'md' ? 'selected' : ''}>M</option><option value="lg" ${block.height === 'lg' ? 'selected' : ''}>L</option></select></div>
          </div>
        </div>`;
    }

    if (block.type === 'links') {
      const items = (block.items || []).map((item, itemIndex) => `
        <div class="hp-inline-item">
          <div class="hp-field"><label>Label</label><input data-index="${index}" data-collection="items" data-item-index="${itemIndex}" data-field="label" value="${escapeHtml(item.label || '')}"></div>
          <div class="hp-field"><label>URL</label><input data-index="${index}" data-collection="items" data-item-index="${itemIndex}" data-field="url" value="${escapeHtml(item.url || '')}"></div>
          <div class="hp-field"><label>Estilo</label><select data-index="${index}" data-collection="items" data-item-index="${itemIndex}" data-field="style"><option value="primary" ${(item.style || 'primary') === 'primary' ? 'selected' : ''}>Primario</option><option value="ghost" ${(item.style || '') === 'ghost' ? 'selected' : ''}>Ghost</option></select></div>
          <button type="button" class="hp-tool-btn danger" data-action="remove-item" data-index="${index}" data-item-index="${itemIndex}">Quitar</button>
        </div>`).join('');
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-inline-list">${items}</div>
          <button type="button" class="hp-tool-btn" data-action="add-item" data-index="${index}">Agregar link</button>
        </div>`;
    }

    if (block.type === 'faq') {
      const items = (block.items || []).map((item, itemIndex) => `
        <div class="hp-inline-item faq">
          <div class="hp-field"><label>Pregunta</label><input data-index="${index}" data-collection="items" data-item-index="${itemIndex}" data-field="question" value="${escapeHtml(item.question || '')}"></div>
          <button type="button" class="hp-tool-btn danger" data-action="remove-item" data-index="${index}" data-item-index="${itemIndex}">Quitar</button>
          <div class="hp-field compact"><label>Respuesta</label><textarea data-index="${index}" data-collection="items" data-item-index="${itemIndex}" data-field="answer">${escapeHtml(item.answer || '')}</textarea></div>
        </div>`).join('');
      return `
        <div class="hp-block-editor" data-block-index="${index}" data-block-id="${escapeHtml(block.id || '')}">
          ${commonTop}
          ${titleFields}
          <div class="hp-inline-list">${items}</div>
          <button type="button" class="hp-tool-btn" data-action="add-item" data-index="${index}">Agregar pregunta</button>
        </div>`;
    }

    return `<div class="hp-block-editor">${commonTop}</div>`;
  }

  function renderDynamicSectionReservations() {
    if (!state.settings.show_reservations) return '';
    if (!dynamicData.loggedIn) {
      return '<section class="hp-block hp-dynamic-block"><div class="hp-section-head"><h3>Tus reservaciones</h3><span>Resumen rapido de tu actividad</span></div><div class="hp-empty">Inicia sesion para ver tus reservaciones.</div></section>';
    }
    if (!dynamicData.reservations.length) {
      return '<section class="hp-block hp-dynamic-block"><div class="hp-section-head"><h3>Tus reservaciones</h3><span>Resumen rapido de tu actividad</span></div><div class="hp-empty">No tienes reservaciones registradas todavia.</div></section>';
    }
    return `
      <section class="hp-block hp-dynamic-block">
        <div class="hp-section-head"><h3>Tus reservaciones</h3><span>Resumen rapido de tu actividad</span></div>
        <div class="hp-card-grid">
          ${dynamicData.reservations.map((item) => `
            <article class="hp-card-mini">
              <div class="hp-card-media">${item.image ? `<img src="${toMediaUrl(item.image)}" alt="${escapeHtml(item.name)}">` : ''}</div>
              <div class="hp-card-copy">
                <strong>${escapeHtml(item.name)}</strong>
                <span>${escapeHtml(item.check_in)} - ${escapeHtml(item.check_out)}</span>
                <span>Estado: ${escapeHtml(item.status)}</span>
              </div>
              <a class="btn btn-ghost" href="${escapeHtml(item.url)}">Ver</a>
            </article>`).join('')}
        </div>
      </section>`;
  }

  function renderDynamicSectionProperties() {
    if (!state.settings.show_properties) return '';
    return `
      <section class="hp-block hp-dynamic-block">
        <div class="hp-section-head"><h3>Propiedades disponibles</h3><span>Accesos directos a las propiedades activas</span></div>
        <div class="hp-card-grid">
          ${dynamicData.properties.map((item) => `
            <article class="hp-card-mini">
              <div class="hp-card-media">${item.image ? `<img src="${toMediaUrl(item.image)}" alt="${escapeHtml(item.name)}">` : ''}</div>
              <div class="hp-card-copy">
                <strong>${escapeHtml(item.name)}</strong>
                <span>${escapeHtml(item.location || '')}</span>
                <span>$${Number(item.price || 0).toFixed(2)} / noche</span>
              </div>
              <div class="hp-inline-actions">
                <a class="btn btn-ghost" href="${escapeHtml(item.showUrl)}">Ver</a>
                <a class="btn btn-primary" href="${escapeHtml(item.reserveUrl)}">Reservar</a>
              </div>
            </article>`).join('')}
        </div>
      </section>`;
  }

  function renderBlock(block) {
    if (!block.enabled) return '';
    const align = ['left', 'center', 'right'].includes(block.align) ? block.align : 'left';
    const height = ['sm', 'md', 'lg'].includes(block.height) ? block.height : 'md';
    const width = ['sm', 'md', 'lg', 'full'].includes(block.width) ? block.width : 'md';
    const size = ['sm', 'md', 'lg', 'xl'].includes(block.size) ? block.size : 'md';
    const style = ['card', 'soft', 'plain'].includes(block.style) ? block.style : 'card';

    if (block.type === 'hero') {
      const heroBg = toMediaUrl(block.image);
      return `
        <section class="hp-block hp-block-hero hero-${height} align-${align}" style="--hero-overlay:${Math.max(0, Math.min(90, Number(block.overlay || 45))) / 100};${heroBg ? `background-image:url('${escapeHtml(heroBg)}')` : ''}">
          <div class="hp-hero-overlay"></div>
          <div class="hp-hero-inner">
            ${block.eyebrow ? `<div class="hp-eyebrow">${escapeHtml(block.eyebrow)}</div>` : ''}
            ${block.title ? `<h2>${escapeHtml(block.title)}</h2>` : ''}
            ${block.subtitle ? `<p class="hp-subtitle">${escapeHtml(block.subtitle)}</p>` : ''}
            ${block.body ? `<div class="hp-copy">${nl2brSafe(block.body)}</div>` : ''}
            <div class="hp-actions">
              ${block.primary_label ? `<a class="btn btn-primary" href="${escapeHtml(block.primary_url || '#')}">${escapeHtml(block.primary_label)}</a>` : ''}
              ${block.secondary_label ? `<a class="btn btn-ghost" href="${escapeHtml(block.secondary_url || '#')}">${escapeHtml(block.secondary_label)}</a>` : ''}
            </div>
          </div>
        </section>`;
    }

    if (block.type === 'banner') {
      return `
        <section class="hp-block hp-block-banner banner-${height}">
          ${block.image ? `${block.link ? `<a href="${escapeHtml(block.link)}">` : ''}<img src="${escapeHtml(toMediaUrl(block.image))}" alt="${escapeHtml(block.title || 'Banner')}">${block.link ? '</a>' : ''}` : ''}
          ${(block.title || block.subtitle) ? `<div class="hp-banner-copy">${block.title ? `<strong>${escapeHtml(block.title)}</strong>` : ''}${block.subtitle ? `<span>${escapeHtml(block.subtitle)}</span>` : ''}</div>` : ''}
        </section>`;
    }

    if (block.type === 'title') {
      return `<section class="hp-block hp-block-title align-${align} size-${size}">${block.title ? `<h3>${escapeHtml(block.title)}</h3>` : ''}${block.subtitle ? `<p>${escapeHtml(block.subtitle)}</p>` : ''}</section>`;
    }

    if (block.type === 'text') {
      return `<section class="hp-block hp-block-text style-${style} align-${align}">${block.title ? `<h3>${escapeHtml(block.title)}</h3>` : ''}${block.subtitle ? `<p class="hp-subtitle">${escapeHtml(block.subtitle)}</p>` : ''}${block.body ? `<div class="hp-copy">${nl2brSafe(block.body)}</div>` : ''}</section>`;
    }

    if (block.type === 'image') {
      return `<section class="hp-block hp-block-image width-${width} height-${height}">${block.image ? `${block.link ? `<a href="${escapeHtml(block.link)}">` : ''}<img src="${escapeHtml(toMediaUrl(block.image))}" alt="${escapeHtml(block.title || 'Imagen')}">${block.link ? '</a>' : ''}` : ''}${(block.title || block.subtitle) ? `<figcaption>${block.title ? `<strong>${escapeHtml(block.title)}</strong>` : ''}${block.subtitle ? `<span>${escapeHtml(block.subtitle)}</span>` : ''}</figcaption>` : ''}</section>`;
    }

    if (block.type === 'links') {
      return `<section class="hp-block hp-block-links">${block.title ? `<h3>${escapeHtml(block.title)}</h3>` : ''}${block.subtitle ? `<p class="hp-subtitle">${escapeHtml(block.subtitle)}</p>` : ''}<div class="hp-link-grid">${(block.items || []).map((item) => `<a class="btn ${(item.style || 'primary') === 'ghost' ? 'btn-ghost' : 'btn-primary'}" href="${escapeHtml(item.url || '#')}">${escapeHtml(item.label || '')}</a>`).join('')}</div></section>`;
    }

    if (block.type === 'faq') {
      return `<section class="hp-block hp-block-faq">${block.title ? `<h3>${escapeHtml(block.title)}</h3>` : ''}${block.subtitle ? `<p class="hp-subtitle">${escapeHtml(block.subtitle)}</p>` : ''}<div class="hp-faq-list">${(block.items || []).map((item) => `<details class="hp-faq-item"><summary>${escapeHtml(item.question || 'Pregunta')}</summary><div>${nl2brSafe(item.answer || '')}</div></details>`).join('')}</div></section>`;
    }

    return '';
  }

  function renderPreview() {
    syncLegacySettings();
    const html = `
      <div class="hp-public-content ${state.settings.container === 'narrow' ? 'is-narrow' : ''}">
        ${state.blocks.map(renderBlock).join('')}
        ${renderDynamicSectionReservations()}
        ${renderDynamicSectionProperties()}
      </div>`;
    previewRoot.innerHTML = html;
    metaInput.value = JSON.stringify({ settings: state.settings, blocks: state.blocks });
  }

  function setFieldValue(index, field, value) {
    if (!state.blocks[index]) return;
    state.blocks[index][field] = value;
    renderPreview();
  }

  blocksEditor.addEventListener('input', function (event) {
    const target = event.target;
    if (target.matches('.js-media-input')) {
      activeMediaInput = target;
    }
    const index = Number(target.dataset.index);
    if (Number.isNaN(index)) return;

    if (target.dataset.collection === 'items') {
      const itemIndex = Number(target.dataset.itemIndex);
      if (Number.isNaN(itemIndex) || !state.blocks[index] || !Array.isArray(state.blocks[index].items)) return;
      state.blocks[index].items[itemIndex][target.dataset.field] = target.value;
      renderPreview();
      return;
    }

    if (target.type === 'range') {
      setFieldValue(index, target.dataset.field, Number(target.value));
      return;
    }

    setFieldValue(index, target.dataset.field, target.value);
  });

  blocksEditor.addEventListener('change', function (event) {
    const target = event.target;
    const index = Number(target.dataset.index);
    if (target.matches('.js-media-input')) {
      activeMediaInput = target;
    }
    if (target.dataset.collection === 'items') {
      const itemIndex = Number(target.dataset.itemIndex);
      if (!Number.isNaN(index) && !Number.isNaN(itemIndex) && state.blocks[index] && Array.isArray(state.blocks[index].items)) {
        state.blocks[index].items[itemIndex][target.dataset.field] = target.value;
        renderPreview();
      }
      return;
    }
    if (target.dataset.field && !Number.isNaN(index)) {
      setFieldValue(index, target.dataset.field, target.type === 'checkbox' ? target.checked : target.value);
    }
  });

  blocksEditor.addEventListener('focusin', function (event) {
    if (event.target.matches('.js-media-input')) {
      activeMediaInput = event.target;
    }
  });

  blocksEditor.addEventListener('click', function (event) {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const action = button.dataset.action;
    const index = Number(button.dataset.index);

    if (action === 'toggle-enabled') {
      state.blocks[index].enabled = button.checked;
      renderPreview();
      return;
    }

    if (action === 'move-up' && index > 0) {
      animateEditorReorder(function () {
        [state.blocks[index - 1], state.blocks[index]] = [state.blocks[index], state.blocks[index - 1]];
      });
      renderPreview();
      return;
    }

    if (action === 'move-down' && index < state.blocks.length - 1) {
      animateEditorReorder(function () {
        [state.blocks[index + 1], state.blocks[index]] = [state.blocks[index], state.blocks[index + 1]];
      });
      renderPreview();
      return;
    }

    if (action === 'duplicate') {
      const clone = JSON.parse(JSON.stringify(state.blocks[index]));
      clone.id = uid(clone.type || 'block');
      state.blocks.splice(index + 1, 0, clone);
      renderBlocksEditor();
      renderPreview();
      return;
    }

    if (action === 'delete') {
      state.blocks.splice(index, 1);
      renderBlocksEditor();
      renderPreview();
      return;
    }

    if (action === 'add-item') {
      if (state.blocks[index].type === 'links') {
        state.blocks[index].items.push({ label: 'Nuevo link', url: '/propiedades', style: 'primary' });
      } else if (state.blocks[index].type === 'faq') {
        state.blocks[index].items.push({ question: 'Nueva pregunta', answer: 'Nueva respuesta' });
      }
      renderBlocksEditor();
      renderPreview();
      return;
    }

    if (action === 'remove-item') {
      const itemIndex = Number(button.dataset.itemIndex);
      if (!Number.isNaN(itemIndex) && Array.isArray(state.blocks[index].items)) {
        state.blocks[index].items.splice(itemIndex, 1);
        renderBlocksEditor();
        renderPreview();
      }
    }
  });

  document.querySelectorAll('[data-add-block]').forEach(function (button) {
    button.addEventListener('click', function () {
      state.blocks.push(defaultBlock(button.dataset.addBlock));
      renderBlocksEditor();
      renderPreview();
    });
  });

  document.getElementById('hp-company-name')?.addEventListener('input', function () {
    syncDefaultHeroFromBase(true);
    renderPreview();
  });
  document.getElementById('hp-location')?.addEventListener('input', function () {
    syncDefaultHeroFromBase(true);
    renderPreview();
  });
  document.getElementById('hp-slogan')?.addEventListener('input', function () {
    syncDefaultHeroFromBase(true);
    renderPreview();
  });
  document.getElementById('hp-banner-image')?.addEventListener('input', function (event) {
    activeMediaInput = event.target;
    syncDefaultHeroFromBase(true);
    renderPreview();
  });
  document.getElementById('hp-image-folder')?.addEventListener('input', renderPreview);
  document.getElementById('hp-show-reservations')?.addEventListener('change', renderPreview);
  document.getElementById('hp-show-properties')?.addEventListener('change', renderPreview);
  document.getElementById('hp-container-width')?.addEventListener('change', renderPreview);

  document.getElementById('hp-quick-media')?.addEventListener('click', function (event) {
    const thumb = event.target.closest('[data-media-path]');
    if (!thumb) return;
    if (!activeMediaInput) {
      activeMediaInput = document.getElementById('hp-banner-image');
    }
    activeMediaInput.value = thumb.dataset.mediaPath;
    activeMediaInput.dispatchEvent(new Event('input', { bubbles: true }));
  });

  if (previewToggle) {
    const stored = localStorage.getItem('homepage_preview_mode') === '1';
    previewToggle.checked = stored;
    shell.classList.toggle('preview-mode', stored);
    previewToggle.addEventListener('change', function () {
      shell.classList.toggle('preview-mode', previewToggle.checked);
      localStorage.setItem('homepage_preview_mode', previewToggle.checked ? '1' : '0');
    });
  }

  form.addEventListener('submit', function () {
    syncLegacySettings();
    metaInput.value = JSON.stringify({ settings: state.settings, blocks: state.blocks });
  });

  resetButton?.addEventListener('click', function () {
    if (!window.confirm('Se reiniciará la homepage personalizada. ¿Continuar?')) {
      return;
    }
    const resetForm = document.createElement('form');
    resetForm.method = 'POST';
    resetForm.action = @json(route('homepage.destroy', 1));

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const method = document.createElement('input');
    method.type = 'hidden';
    method.name = '_method';
    method.value = 'DELETE';

    resetForm.appendChild(token);
    resetForm.appendChild(method);
    document.body.appendChild(resetForm);
    resetForm.submit();
  });

  renderBlocksEditor();
  syncDefaultHeroFromBase(false);
  renderPreview();
});
</script>
@endif
@endpush

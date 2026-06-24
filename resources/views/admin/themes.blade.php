@extends('layouts.app')

@section('title','Temas')

@section('content')
@php
  $layoutMeta = is_array($theme->meta['layouts'] ?? null) ? $theme->meta['layouts'] : [];
  $layoutSidebarSide = in_array(($layoutMeta['sidebar_side'] ?? 'left'), ['left', 'right'], true) ? ($layoutMeta['sidebar_side'] ?? 'left') : 'left';
  $layoutSectionsMeta = is_array($layoutMeta['sections'] ?? null) ? $layoutMeta['sections'] : [];
  $layoutSections = $layoutSections ?? [
    'dashboard' => 'Dashboard',
    'reservations' => 'Reservaciones',
    'properties' => 'Propiedades',
    'notifications' => 'Notificaciones',
    'cards' => 'Tarjetas',
  ];
  $layoutVariants = $layoutVariants ?? [
    'card' => 'Carta',
    'elegant' => 'Elegante',
    'hyperminimal' => 'Hyperminimalista',
  ];
  $previewLinks = [
    'dashboard' => route('dashboard', ['preview_as' => 'user']),
    'reservations' => route('reservaciones.index', ['preview_as' => 'user']),
    'properties' => route('propiedades.index', ['preview_as' => 'user']),
    'notifications' => url('/notificaciones?preview_as=user'),
    'cards' => route('tarjetas.index', ['preview_as' => 'user']),
  ];
@endphp
<style>
  .ap-shell{max-width:1120px;margin:20px auto;padding:12px}
  .ap-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 18px}
  .ap-tab{border:1px solid var(--input-border,#d1d5db);background:var(--card,#fff);color:var(--text-color,#111827);padding:10px 14px;border-radius:999px;font-weight:800;cursor:pointer}
  .ap-tab.is-active{background:linear-gradient(90deg,var(--btn-primary,#2563eb),var(--btn-alt,#06b6d4));color:#fff;border-color:transparent}
  .ap-panel{display:none}
  .ap-panel.is-active{display:block}
  .ap-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .ap-grid label{display:flex;flex-direction:column;gap:6px;font-weight:700}
  .ap-grid fieldset{grid-column:1 / -1}
  .ap-fieldset{border:1px dashed var(--input-border,#d1d5db);padding:14px;border-radius:14px;margin-top:12px}
  .layout-preview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-top:16px}
  .layout-preview-card{border-radius:20px;border:1px solid rgba(148,163,184,.2);background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(248,250,252,.96));padding:14px;display:flex;flex-direction:column;gap:10px;min-height:190px}
  .layout-preview-card[data-variant="card"]{box-shadow:0 20px 48px rgba(2,6,23,.12)}
  .layout-preview-card[data-variant="elegant"]{border-radius:16px;box-shadow:0 10px 24px rgba(15,23,42,.08)}
  .layout-preview-card[data-variant="hyperminimal"]{box-shadow:none;background:var(--card,#fff)}
  .layout-preview-head{display:flex;justify-content:space-between;gap:8px;align-items:center}
  .layout-preview-badge{font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted,#6b7280)}
  .layout-preview-body{display:flex;flex-direction:column;gap:8px;flex:1}
  .layout-preview-line{height:10px;border-radius:999px;background:rgba(148,163,184,.22)}
  .layout-preview-line.short{width:52%}
  .layout-preview-list{display:flex;flex-direction:column;gap:8px}
  .layout-preview-item{padding:10px 12px;border-radius:14px;border:1px solid rgba(148,163,184,.18);background:rgba(255,255,255,.74)}
  .layout-preview-card[data-variant="hyperminimal"] .layout-preview-item{border-radius:10px;box-shadow:none;background:transparent}
  .layout-preview-card[data-variant="card"] .layout-preview-item{box-shadow:0 10px 24px rgba(2,6,23,.08)}
  .layout-preview-actions{display:flex;justify-content:space-between;gap:8px;align-items:center}
  .layout-preview-actions a{text-decoration:none}
  .sidebar-choice{display:flex;gap:8px;flex-wrap:wrap}
  .sidebar-choice label{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--input-border,#d1d5db);border-radius:999px;background:var(--card,#fff);font-weight:700}
  @media (max-width:900px){.ap-grid{grid-template-columns:1fr}}

  /* Helper styles (admin viewer) */
  .help-inline {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .help-q {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    border: 1px solid rgba(59, 130, 246, 0.35);
    color: #1d4ed8;
    background: rgba(59, 130, 246, 0.08);
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: transform .15s ease, background-color .15s ease;
  }
  .help-q:hover { transform: translateY(-1px); background: rgba(59, 130, 246, 0.16); }
  .help-link {
    color: #2563eb;
    text-decoration: underline;
    text-underline-offset: 2px;
    font-size: .93rem;
  }
  .help-viewer {
    position: fixed;
    inset: 0;
    display: none;
    z-index: 9999;
  }
  .help-viewer.open { display: block; }
  .help-viewer-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.42);
    backdrop-filter: blur(2px);
  }
  .help-viewer-panel {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: min(920px, 94vw);
    height: min(84vh, 760px);
    background: rgba(255, 255, 255, 0.98);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(15, 23, 42, 0.25);
    border: 1px solid rgba(148, 163, 184, 0.3);
    overflow: hidden;
    display: grid;
    grid-template-rows: auto 1fr;
  }
  .help-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 12px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.3);
    background: linear-gradient(90deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.95));
  }
  .help-toolbar strong { font-size: .92rem; color: #0f172a; }
  .help-controls { display: inline-flex; gap: 6px; }
  .help-btn {
    border: 1px solid rgba(148, 163, 184, 0.65);
    background: #ffffff;
    color: #0f172a;
    border-radius: 8px;
    min-width: 34px;
    height: 32px;
    padding: 0 10px;
    cursor: pointer;
    font-weight: 600;
  }
  .help-btn:hover { background: #f8fafc; }
  .help-stage {
    position: relative;
    overflow: hidden;
    background: #f8fafc;
    touch-action: none;
    cursor: grab;
  }
  .help-stage.dragging { cursor: grabbing; }
  .help-image {
    position: absolute;
    top: 50%;
    left: 50%;
    max-width: 100%;
    max-height: 100%;
    user-select: none;
    transform: translate(-50%, -50%) translate(0px, 0px) scale(1);
    transform-origin: center center;
    transition: transform .08s linear;
    will-change: transform;
  }
  .help-hint {
    position: absolute;
    right: 12px;
    bottom: 10px;
    color: #334155;
    font-size: .82rem;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid rgba(148, 163, 184, 0.4);
    padding: 4px 8px;
    border-radius: 999px;
  }
</style>
<div class="ap-shell">
  <h1>Personalización global</h1>
    <div class="help-inline">
      <button type="button" class="help-q" id="themes-help-open-btn-admin" aria-label="Abrir ayuda">?</button>
      <a href="javascript:void(0)" id="themes-help-open-link-admin" class="help-link">¿Necesitas ayuda para usar esta página?</a>
    </div>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <div style="display:flex;gap:16px;align-items:flex-start;">
    <div style="flex:1">
      <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <!-- Preset buttons with frontend-declared gradients -->
        <form method="POST" action="{{ route('admin.themes.apply') }}">@csrf<input type="hidden" name="preset_id" value="1"><button class="btn" type="submit" style="background:linear-gradient(90deg,#2563eb,#06b6d4);color:#ffffff;border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;">Usar Light</button></form>
        <form method="POST" action="{{ route('admin.themes.apply') }}">@csrf<input type="hidden" name="preset_id" value="2"><button class="btn" type="submit" style="background:linear-gradient(90deg,#7c3aed,#fb923c);color:#ffffff;border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;">Usar Dark</button></form>
        <form method="POST" action="{{ route('admin.themes.apply') }}">@csrf<input type="hidden" name="preset_id" value="3"><button class="btn" type="submit" style="background:linear-gradient(90deg,#f9a8d4,#ffd7b5);color:#111827;border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;">Usar Sakura</button></form>
        <form method="POST" action="{{ route('admin.themes.apply') }}">@csrf<input type="hidden" name="preset_id" value="4"><button class="btn" type="submit" style="background:linear-gradient(90deg,#6d28d9,#fb923c);color:#ffffff;border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;">Usar Abstract</button></form>
      </div>

      <div class="ap-tabs" role="tablist" aria-label="Panel de personalización">
        <button type="button" class="ap-tab is-active" data-tab="theme">Tema</button>
      </div>

      <div style="margin-top:8px">
        <button type="button" class="btn">Guardar personalización global</button>
      </div>

      <form method="POST" action="{{ route('admin.themes.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $theme->id ?? '' }}">
        <div class="ap-panel is-active" data-panel="theme">
          <div class="ap-grid">
            <fieldset class="ap-fieldset">
              <legend style="font-weight:700">Sidebar</legend>
              <div class="sidebar-choice">
                <label>
                  <input type="radio" name="meta[layouts][sidebar_side]" value="left" {{ $layoutSidebarSide === 'left' ? 'checked' : '' }}>
                  Izquierda
                </label>
                <label>
                  <input type="radio" name="meta[layouts][sidebar_side]" value="right" {{ $layoutSidebarSide === 'right' ? 'checked' : '' }}>
                  Derecha
                </label>
              </div>
            </fieldset>
            <label style="flex:1">Nombre <input name="name" value="custom" readonly></label>
            <label>Botón primario <input type="color" name="btn_primary" value="{{ old('btn_primary', $theme->btn_primary ?? '#6366f1') }}"></label>
            <label>Botón alterno <input type="color" name="btn_alt" value="{{ old('btn_alt', $theme->btn_alt ?? '#06b6d4') }}"></label>
            <label>Fondo (color) <input type="color" name="bg" value="{{ old('bg', $theme->bg ?? '#f8fafc') }}"></label>
            <label>Fondo gradiente inicio <input type="color" name="bg_gradient_start" value="{{ old('bg_gradient_start', $theme->bg_gradient_start ?? $theme->bg ?? '#ffffff') }}"></label>
            <label>Fondo gradiente fin <input type="color" name="bg_gradient_end" value="{{ old('bg_gradient_end', $theme->bg_gradient_end ?? $theme->bg ?? '#f8fafc') }}"></label>
            <label>Fondo gradiente angulo <input type="number" name="bg_gradient_angle" value="{{ $theme->bg_gradient_angle ?? 90 }}"></label>
            <label>Animar fondo <input type="checkbox" name="bg_animated" value="1" {{ ($theme->bg_animated ?? false) ? 'checked' : '' }}></label>
            <label>Sidebar bg (color) <input type="color" name="sidebar_bg" value="{{ old('sidebar_bg', $theme->sidebar_bg ?? '#0f172a') }}"></label>
            <label>Sidebar gradiente inicio <input type="color" name="sidebar_gradient_start" value="{{ old('sidebar_gradient_start', $theme->sidebar_gradient_start ?? $theme->sidebar_bg ?? '#ffffff') }}"></label>
            <label>Sidebar gradiente fin <input type="color" name="sidebar_gradient_end" value="{{ old('sidebar_gradient_end', $theme->sidebar_gradient_end ?? $theme->sidebar_bg ?? '#ffffff') }}"></label>
            <label>Sidebar gradiente angulo <input type="number" name="sidebar_gradient_angle" value="{{ $theme->sidebar_gradient_angle ?? 90 }}"></label>
            <label>Animar sidebar <input type="checkbox" name="sidebar_animated" value="1" {{ ($theme->sidebar_animated ?? false) ? 'checked' : '' }}></label>
            <label>Sidebar texto <input type="color" name="sidebar_text" value="{{ old('sidebar_text', $theme->sidebar_text ?? '#ffffff') }}"></label>
            <label>Gradiente inicio <input type="color" name="gradient_start" value="{{ old('gradient_start', $theme->gradient_start ?? $theme->btn_primary ?? '#6366f1') }}"></label>
            <label>Gradiente fin <input type="color" name="gradient_end" value="{{ old('gradient_end', $theme->gradient_end ?? $theme->btn_alt ?? '#06b6d4') }}"></label>
            <label>Ángulo <input type="number" name="gradient_angle" value="{{ $theme->gradient_angle ?? 90 }}"></label>
            <label>Animar gradiente <input type="checkbox" name="animated_gradient" value="1" {{ ($theme->animated_gradient ?? false) ? 'checked' : '' }}></label>
            <label>Velocidad (s) <input type="range" min="1" max="30" name="animation_speed" value="{{ $theme->animation_speed ?? 6 }}"></label>
            <label>Tamaño fuente (px) <input type="range" min="12" max="24" name="font_size" value="{{ $theme->font_size ?? 16 }}"></label>

            <label>Tipo animación hover
              <select name="hover_animation">
                @php $ha = $theme->hover_animation ?? 'none'; @endphp
                <option value="none" {{ $ha=='none' ? 'selected' : '' }}>Ninguna</option>
                <option value="lift" {{ $ha=='lift' ? 'selected' : '' }}>Elevación</option>
                <option value="scale" {{ $ha=='scale' ? 'selected' : '' }}>Escala</option>
                <option value="glow" {{ $ha=='glow' ? 'selected' : '' }}>Brillo</option>
              </select>
            </label>
            <label>Duración hover (s) <input type="number" step="0.01" name="hover_animation_duration" value="{{ $theme->hover_animation_duration ?? 0.18 }}"></label>

            <label>Tipo animación flotante (autoplay)
              <select name="float_animation">
                @php $fa = $theme->float_animation ?? 'none'; @endphp
                <option value="none" {{ $fa=='none' ? 'selected' : '' }}>Ninguna</option>
                <option value="float" {{ $fa=='float' ? 'selected' : '' }}>Flotar</option>
                <option value="pulse" {{ $fa=='pulse' ? 'selected' : '' }}>Pulso</option>
              </select>
            </label>
            <label>Duración flotante (s) <input type="number" step="0.1" name="float_animation_duration" value="{{ $theme->float_animation_duration ?? 6.0 }}"></label>

            <fieldset class="ap-fieldset">
              <legend style="font-weight:700">Topbar</legend>
              @php $topbar = $theme->meta['topbar'] ?? []; @endphp
              <label>Topbar color <input type="color" name="meta[topbar][bg]" value="{{ old('meta.topbar.bg', $topbar['bg'] ?? '#ffffff') }}"></label>
              <label>Topbar texto <input type="color" name="meta[topbar][text]" value="{{ old('meta.topbar.text', $topbar['text'] ?? '#0f172a') }}"></label>
              <label>Topbar gradiente inicio <input type="color" name="meta[topbar][gradient_start]" value="{{ old('meta.topbar.gradient_start', $topbar['gradient_start'] ?? ($theme->gradient_start ?? $theme->btn_primary ?? '#ffffff')) }}"></label>
              <label>Topbar gradiente fin <input type="color" name="meta[topbar][gradient_end]" value="{{ old('meta.topbar.gradient_end', $topbar['gradient_end'] ?? ($theme->gradient_end ?? $theme->btn_alt ?? '#ffffff')) }}"></label>
              <label>Animar topbar <input type="checkbox" name="meta[topbar][animated]" value="1" {{ ($topbar['animated'] ?? false) ? 'checked' : '' }}></label>
            </fieldset>

            <fieldset class="ap-fieldset">
              <legend style="font-weight:700">Personalizar botones</legend>
              @php $currentButtons = $theme->button_variants ?? []; $buttonList = $buttonVariants ?? ['default']; @endphp
              @foreach($buttonList as $b)
                @php $bv = $currentButtons[$b] ?? []; @endphp
                <div style="display:flex;gap:8px;align-items:center;padding:8px;border-radius:6px;border:1px solid #f3f4f6;margin-bottom:8px;flex-wrap:wrap;">
                  <div style="min-width:120px;font-weight:700">{{ $b }}</div>
                  <label>BG <input type="color" name="button_variants[{{ $b }}][bg]" value="{{ old('button_variants.' . $b . '.bg', $bv['bg'] ?? ($b=='btn-alt' ? ($theme->btn_alt ?? '#06b6d4') : ($theme->btn_primary ?? '#6366f1'))) }}"></label>
                  <label>Color <input type="color" name="button_variants[{{ $b }}][color]" value="{{ old('button_variants.' . $b . '.color', $bv['color'] ?? '#ffffff') }}"></label>
                  <label>Grad inicio <input type="color" name="button_variants[{{ $b }}][gradient_start]" value="{{ old('button_variants.' . $b . '.gradient_start', $bv['gradient_start'] ?? ($theme->gradient_start ?? $theme->btn_primary ?? '#6366f1')) }}"></label>
                  <label>Grad fin <input type="color" name="button_variants[{{ $b }}][gradient_end]" value="{{ old('button_variants.' . $b . '.gradient_end', $bv['gradient_end'] ?? ($theme->gradient_end ?? $theme->btn_alt ?? '#06b6d4')) }}"></label>
                  <label>Animación
                    <select name="button_variants[{{ $b }}][animation]">
                      @php $anim = $bv['animation'] ?? 'none'; @endphp
                      <option value="none" {{ $anim=='none' ? 'selected' : '' }}>Ninguna</option>
                      <option value="autoplay-float" {{ $anim=='autoplay-float' ? 'selected' : '' }}>Flotar</option>
                      <option value="autoplay-pulse" {{ $anim=='autoplay-pulse' ? 'selected' : '' }}>Pulso</option>
                    </select>
                  </label>
                </div>
              @endforeach
            </fieldset>
              
          </div>
        </div>

        <div style="margin-top:12px"><button class="btn">Guardar personalización global</button></div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
  const tabs = Array.from(document.querySelectorAll('[data-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-panel]'));
  tabs.forEach((tab) => {
    tab.addEventListener('click', function(){
      const target = tab.dataset.tab;
      tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
      panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.panel === target));
    });
  });

  // layout preview controls removed; no runtime listeners required
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  try{
    const openBtn = document.getElementById('themes-help-open-btn-admin');
    const openLink = document.getElementById('themes-help-open-link-admin');
    const viewerId = 'themes-help-viewer-admin';
    let viewer = document.getElementById(viewerId);
    if (!viewer) {
      // create viewer markup and append to body
      viewer = document.createElement('div');
      viewer.id = viewerId;
      viewer.className = 'help-viewer';
      viewer.setAttribute('aria-hidden','true');
      viewer.style.display = 'none';
      viewer.innerHTML = `
        <div class="help-viewer-backdrop" id="themes-help-backdrop-admin"></div>
        <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía Personalización (admin)">
          <div class="help-toolbar">
            <strong>Guía rápida — Personalización (admin)</strong>
            <div class="help-controls">
              <button type="button" class="help-btn" id="themes-help-zoom-out-admin" aria-label="Alejar">-</button>
              <button type="button" class="help-btn" id="themes-help-zoom-reset-admin" aria-label="Restablecer zoom">100%</button>
              <button type="button" class="help-btn" id="themes-help-zoom-in-admin" aria-label="Acercar">+</button>
              <button type="button" class="help-btn" id="themes-help-close-admin" aria-label="Cerrar ayuda">Cerrar</button>
            </div>
          </div>
          <div class="help-stage" id="themes-help-stage-admin">
            <img id="themes-help-image-admin" class="help-image" src="{{ asset('tutorial_imgs/admin/Personalizacion.png') }}" alt="Tutorial Personalización admin" draggable="false" />
            <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
          </div>
        </div>`;
      document.body.appendChild(viewer);
    }

    const backdrop = document.getElementById('themes-help-backdrop-admin');
    const closeBtn = document.getElementById('themes-help-close-admin');
    const zoomIn = document.getElementById('themes-help-zoom-in-admin');
    const zoomOut = document.getElementById('themes-help-zoom-out-admin');
    const zoomReset = document.getElementById('themes-help-zoom-reset-admin');
    const stage = document.getElementById('themes-help-stage-admin');
    const img = document.getElementById('themes-help-image-admin');

    console.log('Themes admin help init', { openBtn: !!openBtn, openLink: !!openLink, viewer: !!viewer, img: !!img });

    let isOpen = false; let scale = 1; let x = 0; let y = 0; let dragging = false; let sx = 0; let sy = 0;
    function apply(){ img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')'; if (zoomReset) zoomReset.textContent = Math.round(scale*100)+'%'; }
    function openViewer(){ if (isOpen) return; isOpen = true; viewer.classList.add('open'); viewer.setAttribute('aria-hidden','false'); viewer.style.display='block'; scale = 1; x=0; y=0; apply(); console.log('themes: viewer opened'); }
    function closeViewer(){ if (!isOpen) return; isOpen = false; viewer.classList.remove('open'); viewer.setAttribute('aria-hidden','true'); setTimeout(()=> viewer.style.display='none',180); console.log('themes: viewer closed'); }

    openBtn?.addEventListener('click', function(e){ e.preventDefault(); isOpen ? closeViewer() : openViewer(); });
    openLink?.addEventListener('click', function(e){ e.preventDefault(); isOpen ? closeViewer() : openViewer(); });
    closeBtn?.addEventListener('click', function(e){ e.preventDefault(); closeViewer(); });
    viewer.addEventListener('click', function(e){ if (e.target === viewer || e.target === backdrop) closeViewer(); });
    zoomIn?.addEventListener('click', function(){ scale = Math.min(4, scale + 0.2); apply(); });
    zoomOut?.addEventListener('click', function(){ scale = Math.max(1, scale - 0.2); apply(); });
    zoomReset?.addEventListener('click', function(){ scale = 1; x = 0; y = 0; apply(); });
    stage?.addEventListener('wheel', function(e){ if (!isOpen) return; e.preventDefault(); scale = Math.min(4, Math.max(1, scale + (e.deltaY < 0 ? 0.18 : -0.18))); apply(); }, { passive:false });
    stage?.addEventListener('mousedown', function(e){ if (scale <= 1) return; dragging = true; sx = e.clientX; sy = e.clientY; stage.classList.add('dragging'); });
    window.addEventListener('mousemove', function(e){ if (!dragging) return; x += e.clientX - sx; y += e.clientY - sy; sx = e.clientX; sy = e.clientY; apply(); });
    window.addEventListener('mouseup', function(){ if (dragging){ dragging = false; stage.classList.remove('dragging'); } });
    stage?.addEventListener('dblclick', function(){ scale = (scale > 1) ? 1 : 2; x = 0; y = 0; apply(); });
    document.addEventListener('keydown', function(e){ if (!isOpen) return; if (e.key === 'Escape') closeViewer(); if (e.key === '+' || e.key === '=') { scale = Math.min(4, scale + 0.2); apply(); } if (e.key === '-') { scale = Math.max(1, scale - 0.2); apply(); } });

  } catch (err) { console.error('Error init themes help', err); }
});
</script>
@endpush

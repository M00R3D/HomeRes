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
</style>
<div class="ap-shell">
  <h1>Personalización global</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <div style="display:flex;gap:16px;align-items:flex-start;">
    <div style="flex:1">
      <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        @php
          $presetNames = ['Light','Dark','Sakura','Abstract'];
          $presets = \App\Models\Theme::whereIn('name', $presetNames)->get()->keyBy('name');
          function _btn_style($p){
            if (! $p) return '';
            $bg = $p['gradient_start'] ?? ($p['btn_primary'] ?? '#6366f1');
            $bg2 = $p['gradient_end'] ?? ($p['btn_alt'] ?? '#06b6d4');
            $text = '#ffffff';
            if (!empty($p['button_variants']['primary']['color'])) $text = $p['button_variants']['primary']['color'];
            return "background: linear-gradient(90deg, $bg, $bg2); color: $text;";
          }

          function _safe_color($val, $fallback = '#ffffff'){
            if (! $val) return $fallback;
            $v = trim($val);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $v)) return strtolower($v);
            return $fallback;
          }

          $sbDefault = (isset($theme) && isset($theme->bg) && preg_match('/^#[0-9a-fA-F]{6}$/',$theme->bg)) ? '#0f172a' : '#ffffff';
        @endphp
        @php
          $presetGradients = [
            'Light'    => ['start' => '#2563eb', 'end' => '#06b6d4', 'text' => '#ffffff'],
            'Dark'     => ['start' => '#7c3aed', 'end' => '#fb923c', 'text' => '#ffffff'],
            'Sakura'   => ['start' => '#f9a8d4', 'end' => '#ffd7b5', 'text' => '#111827'],
            'Abstract' => ['start' => '#6d28d9', 'end' => '#fb923c', 'text' => '#ffffff'],
          ];
        @endphp
        @foreach($presetNames as $pn)
          @php
            $pg = $presetGradients[$pn] ?? ['start' => '#6366f1', 'end' => '#06b6d4', 'text' => '#ffffff'];
            $presetStyle = "background:linear-gradient(90deg,{$pg['start']},{$pg['end']});color:{$pg['text']};border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;";
          @endphp
          <form method="POST" action="{{ route('admin.themes.apply') }}">@csrf<input type="hidden" name="preset" value="{{ $pn }}"><button class="btn" type="submit" style="{{ $presetStyle }}">Usar {{ $pn }}</button></form>
        @endforeach
      </div>

      <div class="ap-tabs" role="tablist" aria-label="Panel de personalización">
        <button type="button" class="ap-tab is-active" data-tab="theme">Tema</button>
        <button type="button" class="ap-tab" data-tab="layouts">Layouts</button>
      </div>

      <form method="POST" action="{{ route('admin.themes.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $theme->id ?? '' }}">

        <div class="ap-panel is-active" data-panel="theme">
          <div class="ap-grid">
            <label style="flex:1">Nombre <input name="name" value="{{ $theme->name ?? 'custom' }}"></label>
            <label>Botón primario <input type="color" name="btn_primary" value="{{ _safe_color($theme->btn_primary ?? null, '#6366f1') }}"></label>
            <label>Botón alterno <input type="color" name="btn_alt" value="{{ _safe_color($theme->btn_alt ?? null, '#06b6d4') }}"></label>
            <label>Fondo (color) <input type="color" name="bg" value="{{ _safe_color($theme->bg ?? null, '#f8fafc') }}"></label>
            <label>Fondo gradiente inicio <input type="color" name="bg_gradient_start" value="{{ _safe_color($theme->bg_gradient_start ?? $theme->bg ?? null, '#ffffff') }}"></label>
            <label>Fondo gradiente fin <input type="color" name="bg_gradient_end" value="{{ _safe_color($theme->bg_gradient_end ?? $theme->bg ?? null, '#f8fafc') }}"></label>
            <label>Fondo gradiente angulo <input type="number" name="bg_gradient_angle" value="{{ $theme->bg_gradient_angle ?? 90 }}"></label>
            <label>Animar fondo <input type="checkbox" name="bg_animated" value="1" {{ ($theme->bg_animated ?? false) ? 'checked' : '' }}></label>
            <label>Sidebar bg (color) <input type="color" name="sidebar_bg" value="{{ _safe_color($theme->sidebar_bg ?? null, '#0f172a') }}"></label>
            <label>Sidebar gradiente inicio <input type="color" name="sidebar_gradient_start" value="{{ _safe_color($theme->sidebar_gradient_start ?? $theme->sidebar_bg ?? null, '#ffffff') }}"></label>
            <label>Sidebar gradiente fin <input type="color" name="sidebar_gradient_end" value="{{ _safe_color($theme->sidebar_gradient_end ?? $theme->sidebar_bg ?? null, '#ffffff') }}"></label>
            <label>Sidebar gradiente angulo <input type="number" name="sidebar_gradient_angle" value="{{ $theme->sidebar_gradient_angle ?? 90 }}"></label>
            <label>Animar sidebar <input type="checkbox" name="sidebar_animated" value="1" {{ ($theme->sidebar_animated ?? false) ? 'checked' : '' }}></label>
            <label>Sidebar texto <input type="color" name="sidebar_text" value="{{ _safe_color($theme->sidebar_text ?? null, $sbDefault) }}"></label>
            <label>Gradiente inicio <input type="color" name="gradient_start" value="{{ _safe_color($theme->gradient_start ?? $theme->btn_primary ?? null, '#6366f1') }}"></label>
            <label>Gradiente fin <input type="color" name="gradient_end" value="{{ _safe_color($theme->gradient_end ?? $theme->btn_alt ?? null, '#06b6d4') }}"></label>
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
              <label>Topbar color <input type="color" name="meta[topbar][bg]" value="{{ _safe_color($topbar['bg'] ?? null, '#ffffff') }}"></label>
              <label>Topbar texto <input type="color" name="meta[topbar][text]" value="{{ _safe_color($topbar['text'] ?? null, '#0f172a') }}"></label>
              <label>Topbar gradiente inicio <input type="color" name="meta[topbar][gradient_start]" value="{{ _safe_color($topbar['gradient_start'] ?? ($theme->gradient_start ?? $theme->btn_primary ?? null), '#ffffff') }}"></label>
              <label>Topbar gradiente fin <input type="color" name="meta[topbar][gradient_end]" value="{{ _safe_color($topbar['gradient_end'] ?? ($theme->gradient_end ?? $theme->btn_alt ?? null), '#ffffff') }}"></label>
              <label>Animar topbar <input type="checkbox" name="meta[topbar][animated]" value="1" {{ ($topbar['animated'] ?? false) ? 'checked' : '' }}></label>
            </fieldset>

            <fieldset class="ap-fieldset">
              <legend style="font-weight:700">Personalizar botones</legend>
              @php $currentButtons = $theme->button_variants ?? []; $buttonList = $buttonVariants ?? ['default']; @endphp
              @foreach($buttonList as $b)
                @php $bv = $currentButtons[$b] ?? []; @endphp
                <div style="display:flex;gap:8px;align-items:center;padding:8px;border-radius:6px;border:1px solid #f3f4f6;margin-bottom:8px;flex-wrap:wrap;">
                  <div style="min-width:120px;font-weight:700">{{ $b }}</div>
                  <label>BG <input type="color" name="button_variants[{{ $b }}][bg]" value="{{ _safe_color($bv['bg'] ?? ($b=='btn-alt' ? ($theme->btn_alt ?? null) : ($theme->btn_primary ?? null)), ($b=='btn-alt' ? '#06b6d4' : '#6366f1')) }}"></label>
                  <label>Color <input type="color" name="button_variants[{{ $b }}][color]" value="{{ _safe_color($bv['color'] ?? null, '#ffffff') }}"></label>
                  <label>Grad inicio <input type="color" name="button_variants[{{ $b }}][gradient_start]" value="{{ _safe_color($bv['gradient_start'] ?? ($theme->gradient_start ?? $theme->btn_primary ?? null), '#6366f1') }}"></label>
                  <label>Grad fin <input type="color" name="button_variants[{{ $b }}][gradient_end]" value="{{ _safe_color($bv['gradient_end'] ?? ($theme->gradient_end ?? $theme->btn_alt ?? null), '#06b6d4') }}"></label>
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

        <div class="ap-panel" data-panel="layouts">
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

            @foreach($layoutSections as $layoutKey => $layoutLabel)
              @php $currentVariant = $layoutSectionsMeta[$layoutKey]['variant'] ?? 'card'; @endphp
              <label>
                {{ $layoutLabel }}
                <select name="meta[layouts][sections][{{ $layoutKey }}][variant]" data-layout-select data-layout-target="{{ $layoutKey }}">
                  @foreach($layoutVariants as $variantKey => $variantLabel)
                    <option value="{{ $variantKey }}" {{ $currentVariant === $variantKey ? 'selected' : '' }}>{{ $variantLabel }}</option>
                  @endforeach
                </select>
              </label>
            @endforeach
          </div>

          <div class="layout-preview-grid">
            @foreach($layoutSections as $layoutKey => $layoutLabel)
              @php $currentVariant = $layoutSectionsMeta[$layoutKey]['variant'] ?? 'card'; @endphp
              <article class="layout-preview-card" data-preview-card="{{ $layoutKey }}" data-variant="{{ $currentVariant }}">
                <div class="layout-preview-head">
                  <div>
                    <div style="font-weight:800;">{{ $layoutLabel }}</div>
                    <div class="layout-preview-badge" data-preview-label>{{ $layoutVariants[$currentVariant] ?? 'Carta' }}</div>
                  </div>
                  <div class="layout-preview-badge">preview</div>
                </div>
                <div class="layout-preview-body">
                  <div class="layout-preview-line"></div>
                  <div class="layout-preview-line short"></div>
                  <div class="layout-preview-list">
                    <div class="layout-preview-item"></div>
                    <div class="layout-preview-item"></div>
                    <div class="layout-preview-item"></div>
                  </div>
                </div>
                <div class="layout-preview-actions">
                  <span class="small">Como usuario normal</span>
                  <a href="{{ $previewLinks[$layoutKey] ?? route('dashboard', ['preview_as' => 'user']) }}" class="btn-alt">Abrir</a>
                </div>
              </article>
            @endforeach
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

  const labels = @json($layoutVariants);
  document.querySelectorAll('[data-layout-select]').forEach((select) => {
    select.addEventListener('change', function(){
      const key = select.dataset.layoutTarget;
      const card = document.querySelector('[data-preview-card="' + key + '"]');
      if (!card) return;
      card.dataset.variant = select.value;
      const label = card.querySelector('[data-preview-label]');
      if (label) label.textContent = labels[select.value] || select.value;
    });
  });
})();
</script>
@endpush

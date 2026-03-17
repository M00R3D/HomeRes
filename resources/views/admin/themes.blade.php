@extends('layouts.app')

@section('title','Temas')

@section('content')
<div style="max-width:1000px;margin:20px auto;padding:12px;">
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
      <form method="POST" action="{{ route('admin.themes.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $theme->id ?? '' }}">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <label style="flex:1">Nombre <input name="name" value="{{ $theme->name ?? '' }}"></label>
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

          <fieldset style="flex-basis:100%;border:1px dashed #e6eef8;padding:10px;border-radius:8px;margin-top:8px">
            <legend style="font-weight:700">Topbar</legend>
            @php $topbar = $theme->meta['topbar'] ?? [];
            @endphp
            <label>Topbar color <input type="color" name="meta[topbar][bg]" value="{{ _safe_color($topbar['bg'] ?? null, '#ffffff') }}"></label>
            <label>Topbar texto <input type="color" name="meta[topbar][text]" value="{{ _safe_color($topbar['text'] ?? null, '#0f172a') }}"></label>
            <label>Topbar gradiente inicio <input type="color" name="meta[topbar][gradient_start]" value="{{ _safe_color($topbar['gradient_start'] ?? ($theme->gradient_start ?? $theme->btn_primary ?? null), '#ffffff') }}"></label>
            <label>Topbar gradiente fin <input type="color" name="meta[topbar][gradient_end]" value="{{ _safe_color($topbar['gradient_end'] ?? ($theme->gradient_end ?? $theme->btn_alt ?? null), '#ffffff') }}"></label>
            <label>Animar topbar <input type="checkbox" name="meta[topbar][animated]" value="1" {{ ($topbar['animated'] ?? false) ? 'checked' : '' }}></label>
          </fieldset>

          <fieldset style="flex-basis:100%;border:1px dashed #fde68a;padding:10px;border-radius:8px;margin-top:8px">
            <legend style="font-weight:700">Personalizar botones</legend>
            @php $currentButtons = $theme->button_variants ?? [];
            $buttonList = $buttonVariants ?? ['default'];
            @endphp
            @foreach($buttonList as $b)
              @php $bv = $currentButtons[$b] ?? []; @endphp
              <div style="display:flex;gap:8px;align-items:center;padding:8px;border-radius:6px;border:1px solid #f3f4f6;margin-bottom:8px">
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
        <div style="margin-top:12px"><button class="btn">Guardar personalización global</button></div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
// Admin edit page for the single global theme (no multiple presets UI)
</script>
@endpush

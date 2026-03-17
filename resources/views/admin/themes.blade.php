@extends('layouts.app')

@section('title','Temas')

@section('content')
<div style="max-width:1000px;margin:20px auto;padding:12px;">
  <h1>Personalización global</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <div style="display:flex;gap:16px;align-items:flex-start;">
    <div style="flex:1">
      <form method="POST" action="{{ route('admin.themes.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $theme->id ?? '' }}">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <label style="flex:1">Nombre <input name="name" value="{{ $theme->name ?? '' }}"></label>
          <label>Botón primario <input type="color" name="btn_primary" value="{{ $theme->btn_primary ?? '#6366f1' }}"></label>
          <label>Botón alterno <input type="color" name="btn_alt" value="{{ $theme->btn_alt ?? '#06b6d4' }}"></label>
          <label>Fondo <input type="color" name="bg" value="{{ $theme->bg ?? '#f8fafc' }}"></label>
          <label>Sidebar bg <input type="color" name="sidebar_bg" value="{{ $theme->sidebar_bg ?? '#0f172a' }}"></label>
          <label>Sidebar texto <input type="color" name="sidebar_text" value="{{ $theme->sidebar_text ?? '#ffffff' }}"></label>
          <label>Gradiente inicio <input type="color" name="gradient_start" value="{{ $theme->gradient_start ?? '' }}"></label>
          <label>Gradiente fin <input type="color" name="gradient_end" value="{{ $theme->gradient_end ?? '' }}"></label>
          <label>Ángulo <input type="number" name="gradient_angle" value="{{ $theme->gradient_angle ?? 90 }}"></label>
          <label>Animar gradiente <input type="checkbox" name="animated_gradient" value="1" {{ ($theme->animated_gradient ?? false) ? 'checked' : '' }}></label>
          <label>Velocidad (s) <input type="range" min="1" max="30" name="animation_speed" value="{{ $theme->animation_speed ?? 6 }}"></label>
          <label>Tamaño fuente (px) <input type="range" min="12" max="24" name="font_size" value="{{ $theme->font_size ?? 16 }}"></label>
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

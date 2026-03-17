@extends('layouts.app')

@section('title','Apariencia - Estilos globales')

@section('content')
<div style="max-width:900px;margin:20px auto;padding:12px;">
  <h1>Apariencia</h1>
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>@endif

  <form method="POST" action="{{ route('admin.styles.save') }}">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start;">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <label>Color botón primario
          <input type="color" name="btn_primary" id="btn_primary" value="{{ $style->btn_primary ?? '#6366f1' }}">
        </label>

        <label>Color botón alternativo
          <input type="color" name="btn_alt" id="btn_alt" value="{{ $style->btn_alt ?? '#06b6d4' }}">
        </label>

        <label>Fondo (bg)
          <input type="color" name="bg" id="bg" value="{{ $style->bg ?? '#f8fafc' }}">
        </label>

        <label>Sidebar fondo
          <input type="color" name="sidebar_bg" id="sidebar_bg" value="{{ $style->sidebar_bg ?? '#ffffff' }}">
        </label>

        <label>Sidebar texto
          <input type="color" name="sidebar_text" id="sidebar_text" value="{{ $style->sidebar_text ?? '#0f172a' }}">
        </label>

        <label>Transparencia general (0-100)
          <input type="range" name="transparency" id="transparency" min="0" max="100" value="{{ $style->transparency ?? 0 }}">
        </label>

        <label>Variant / Preset
          <select name="variant" id="variant">
            <option value="predeterminado" {{ ($style->variant ?? '')==='predeterminado' ? 'selected' : '' }}>Predeterminado</option>
            <option value="animado" {{ ($style->variant ?? '')==='animado' ? 'selected' : '' }}>Animado</option>
            <option value="robusto" {{ ($style->variant ?? '')==='robusto' ? 'selected' : '' }}>Robusto</option>
            <option value="transparencia" {{ ($style->variant ?? '')==='transparencia' ? 'selected' : '' }}>Transparencia</option>
            <option value="animacion_exotica" {{ ($style->variant ?? '')==='animacion_exotica' ? 'selected' : '' }}>Animación exótica</option>
          </select>
        </label>

        <label>Animación exótica (nombre)
          <input type="text" name="exotic_animation" id="exotic_animation" value="{{ $style->exotic_animation ?? '' }}">
        </label>

        <div style="margin-top:8px;">
          <button class="btn" type="submit">Guardar estilos</button>
        </div>
      </div>

      <div style="border-left:1px solid #eef2f7;padding-left:18px;">
        <h3>Previsualización</h3>
        <div id="preview" style="padding:16px;border-radius:8px;">
          <p style="margin:0 0 12px;">Botones, fondo y sidebar se aplican aquí.</p>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button class="btn">Botón primario</button>
            <button class="btn-alt">Alternativo</button>
          </div>
          <div style="height:20px"></div>
          <div style="padding:12px;border-radius:6px;background:rgba(0,0,0,0.03)">Contenido de muestra</div>
        </div>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
  (function(){
    function applyPreview() {
      const root = document.documentElement;
      const btn = document.getElementById('btn_primary').value;
      const alt = document.getElementById('btn_alt').value;
      const bg = document.getElementById('bg').value;
      const sbg = document.getElementById('sidebar_bg').value;
      const stext = document.getElementById('sidebar_text').value;
      const trans = document.getElementById('transparency').value;
      root.style.setProperty('--btn-primary', btn);
      root.style.setProperty('--btn-alt', alt);
      root.style.setProperty('--bg', bg);
      root.style.setProperty('--sidebar-bg', sbg);
      root.style.setProperty('--sidebar-text', stext);
      root.style.setProperty('--global-transparency', (trans/100).toString());
      // update preview container styles
      const preview = document.getElementById('preview');
      if (preview) {
        preview.style.background = bg;
        preview.querySelector('.btn') && (preview.querySelector('.btn').style.background = btn);
        preview.querySelector('.btn-alt') && (preview.querySelector('.btn-alt').style.background = alt);
      }
    }

    ['btn_primary','btn_alt','bg','sidebar_bg','sidebar_text','transparency','variant','exotic_animation'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', applyPreview);
      el.addEventListener('change', applyPreview);
    });

    // initial apply
    applyPreview();
  })();
</script>
@endpush

@endsection

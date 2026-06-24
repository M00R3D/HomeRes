@extends('layouts.app')
<style>
  .hp-help-inline {
  margin-bottom: 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.hp-help-q {
  width: 24px;
  height: 24px;
  border-radius: 999px;
  border: 1px solid rgba(59,130,246,.35);
  color: #1d4ed8;
  background: rgba(59,130,246,.08);
  font-weight: 700;
  cursor: pointer;
}

.hp-help-link {
  color: #2563eb;
  text-decoration: underline;
  font-size: .93rem;
}

.hp-help-viewer {
  position: fixed;
  inset: 0;
  display: none;
  z-index: 9999;
}

.hp-help-viewer.open {
  display: block;
}

.hp-help-viewer-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15,23,42,.45);
}

.hp-help-viewer-panel {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%,-50%);
  width: min(900px, 94vw);
  height: min(80vh, 700px);
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  display: grid;
  grid-template-rows: auto 1fr;
}

.hp-help-toolbar {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.hp-help-stage {
  position: relative;
  overflow: hidden;
  background: #f8fafc;
}

.hp-help-image {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(1);
  max-width: 100%;
  max-height: 100%;
  cursor: grab;
}

.hp-help-hint {
  position: absolute;
  bottom: 10px;
  right: 10px;
  font-size: 12px;
  opacity: .7;
}
</style>
@section('title', 'Crear propiedad')

@section('content')
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="hp-help-inline">
      <button type="button" class="hp-help-q" id="hp-open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="javascript:void(0)" id="hp-open-help-link" class="hp-help-link">
        ¿Necesitas ayuda para usar esta página?
      </a>
    </div>
    <h1 style="margin:0">Crear propiedad</h1>
    <a href="{{ route('propiedades.index') }}" class="link-button">Volver</a>
  </div>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div style="background:#fff5f5;color:#9b1c1c;padding:10px;border-radius:8px;margin-bottom:12px;">
      <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif
<div id="hp-help-viewer" class="hp-help-viewer" aria-hidden="true">

  <div id="hp-help-backdrop" class="hp-help-viewer-backdrop"></div>

  <div class="hp-help-viewer-panel" role="dialog" aria-modal="true">

    <div class="hp-help-toolbar">
      <strong>Guía rápida de crear propied</strong>

      <div class="hp-help-controls">
        <button type="button" class="hp-help-btn" id="hp-zoom-out">-</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-reset">100%</button>
        <button type="button" class="hp-help-btn" id="hp-zoom-in">+</button>
        <button type="button" class="hp-help-btn" id="hp-close-help">Cerrar</button>
      </div>
    </div>

    <div class="hp-help-stage" id="hp-help-stage">
      <img
        id="hp-help-image"
        class="hp-help-image"
        src="{{ asset('tutorial_imgs/admin/CrearPropiedades.png') }}"
        alt="Guía de creación de propiedad"
        draggable="false"
      />

      <span class="hp-help-hint">
        Rueda para zoom · arrastra para mover · clic fuera para salir
      </span>
    </div>

  </div>
</div>
  <form method="POST" action="{{ route('propiedades.store') }}" style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    @csrf

    <div style="display:grid;grid-template-columns:1fr 360px;gap:12px;">
      <div>
        <label class="small">Nombre <span style="color:#ef4444">*</span></label>
        <input name="nombre" required value="{{ old('nombre') }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;display:block;">Código <span style="color:#ef4444">*</span></label>
        <input id="codigo-input" name="codigo" required value="{{ old('codigo') }}" style="width:200px;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;display:block;">Tipo</label>
        <select name="tipo" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="casa" {{ old('tipo')==='casa' ? 'selected' : '' }}>Casa</option>
          <option value="cabaña" {{ old('tipo')==='cabaña' ? 'selected' : '' }}>Cabaña</option>
          <option value="departamento" {{ old('tipo')==='departamento' ? 'selected' : '' }}>Departamento</option>
        </select>

        <label class="small" style="margin-top:8px;display:block;">Descripción <span style="color:#ef4444">*</span></label>
        <textarea name="descripcion" required rows="5" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">{{ old('descripcion') }}</textarea>

        <div style="display:flex;gap:8px;margin-top:8px;">
          <div style="flex:1">
            <label class="small">Capacidad <span style="color:#ef4444">*</span></label>
            <input type="number" name="capacidad" required min="1" value="{{ old('capacidad', 1) }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          </div>
          <div style="flex:1">
            <label class="small">Precio noche <span style="color:#ef4444">*</span></label>
            <input type="number" name="precio_noche" required step="0.01" min="0" value="{{ old('precio_noche', 0) }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          </div>
        </div>

        <label class="small" style="margin-top:8px;display:block;">Ubicación <span style="color:#ef4444">*</span></label>
        <input name="ubicacion" required value="{{ old('ubicacion') }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

        <label class="small" style="margin-top:8px;display:block;">Servicios</label>
        <div id="servicios-tags" style="display:flex;flex-wrap:wrap;gap:6px;padding:8px;border:1px solid #e5e7eb;border-radius:8px;min-height:38px;background:#fff;"></div>
        <div style="display:flex;gap:6px;margin-top:4px;">
          <input id="servicio-input" type="text" placeholder="Agregar servicio..." style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <button type="button" id="servicio-add-btn" style="padding:8px 14px;border-radius:8px;border:0;background:#06b6d4;color:#fff;cursor:pointer;">+ Agregar</button>
        </div>
        <input type="hidden" name="servicios" id="servicios-hidden" value="{{ old('servicios') }}">

        <label class="small" style="margin-top:8px;display:block;">Estado</label>
        <select name="estado" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
          <option value="disponible" {{ old('estado')==='disponible' ? 'selected' : '' }}>Disponible</option>
          <option value="ocupada" {{ old('estado')==='ocupada' ? 'selected' : '' }}>Ocupada</option>
          <option value="mantenimiento" {{ old('estado')==='mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
        </select>

        <label class="small" style="margin-top:8px;display:block;">Ruta de imágenes (carpeta en public/)</label>
        <input id="ruta_img_input" name="ruta_img" value="{{ old('ruta_img') }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
      </div>

      <div>
        <div style="background:#fff;padding:10px;border-radius:8px;border:1px solid #eef2f7;">
          <div style="font-weight:800;margin-bottom:8px;">Seleccionar carpeta de imágenes</div>
          @php $folders = $imageFolders ?? []; @endphp
          @if(empty($folders))
            <div style="color:#6b7280;">No se encontraron carpetas de imágenes en <strong>public/</strong>.</div>
          @else
            <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
              <div id="folder-list" style="display:flex;flex-direction:column;gap:6px;flex:1;">
                @foreach($folders as $f)
                  <button type="button" class="folder-item" data-folder="{{ $f }}" style="text-align:left;padding:8px;border-radius:8px;border:1px solid #eef2f7;background:#fff;">{{ $f }}</button>
                @endforeach
              </div>
              <div style="margin-left:8px;">
                <button id="open-explorer-btn" type="button" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#06b6d4;color:#fff;">Abrir explorador</button>
              </div>
            </div>
            <div style="margin-top:8px;color:#6b7280;font-size:0.9rem;">Carpeta/imagen seleccionada: <strong id="selected-folder-display">{{ old('ruta_img') }}</strong></div>
          @endif
        </div>
        <div style="margin-top:12px;color:#6b7280;font-size:0.9rem;">Para subir nuevas imágenes, coloca los archivos en la carpeta indicada dentro de <strong>public/</strong>.</div>
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <button class="action-btn primary" type="submit">Crear</button>
      <a href="{{ route('propiedades.index') }}" class="action-btn" style="background:#fff;border:1px solid #e5e7eb;">Cancelar</a>
    </div>
  </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var items = document.querySelectorAll('.folder-item');
  var input = document.getElementById('ruta_img_input');
  var display = document.getElementById('selected-folder-display');
  items.forEach(function(b){
    b.addEventListener('click', function(){
      var f = this.getAttribute('data-folder');
      if (input) input.value = f;
      if (display) display.textContent = f;
      items.forEach(function(x){ x.style.boxShadow = ''; x.style.borderColor = '#eef2f7'; });
      this.style.boxShadow = '0 6px 18px rgba(2,6,23,0.06)';
      this.style.borderColor = '#06b6d4';
    });
  });

  // Open explorer button
  var openBtn = document.getElementById('open-explorer-btn');
  if (openBtn) openBtn.addEventListener('click', function(){ if (! document.getElementById('image-explorer-modal')) createExplorerModal(); var modal = document.getElementById('image-explorer-modal'); modal.style.display='flex'; (window.loadDirs ? window.loadDirs() : (typeof loadDirs === 'function' ? loadDirs() : Promise.resolve())); });

  function createExplorerModal(){
    // inject styles once
    if (!document.getElementById('image-explorer-styles')) {
      var s = document.createElement('style'); s.id = 'image-explorer-styles'; s.innerHTML = `
        .uploader { border:2px dashed #e5e7eb; border-radius:10px; padding:18px; display:flex; flex-direction:column; gap:10px; align-items:center; text-align:center; background:#fff; transition: all .18s ease; }
        .uploader.dragover { background:#ecfeff; border-color:#06b6d4; box-shadow: 0 8px 28px rgba(6,182,212,0.08); transform: translateY(-2px); }
        .preview-list { display:flex; gap:8px; flex-wrap:wrap; width:100%; }
        .preview { width:120px; height:90px; border-radius:8px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center; font-size:12px; color:#6b7280; position:relative; }
        .preview img { width:100%; height:100%; object-fit:cover; display:block; }
        .drop-hint { padding:6px 10px; border-radius:6px; background:rgba(6,182,212,0.06); color:#065f46; font-weight:700; display:none; }
        .uploader.dragover .drop-hint { display:block; }
      `; document.head.appendChild(s);
    }
    var modal = document.createElement('div');
    modal.id = 'image-explorer-modal';
    modal.style = 'display:none;position:fixed;inset:0;background:rgba(2,6,23,0.5);align-items:center;justify-content:center;z-index:9999;padding:12px;';
    modal.innerHTML = `
      <div style="width:900px;max-width:calc(100% - 40px);background:#fff;border-radius:10px;padding:12px;display:flex;gap:12px;">
        <div style="width:260px;">
          <div style="font-weight:800;margin-bottom:8px;">Carpetas</div>
          <div id="explorer-dirs" style="display:flex;flex-direction:column;gap:6px;max-height:520px;overflow:auto;padding-right:6px;"></div>
        </div>
        <div style="flex:1;display:flex;flex-direction:column;">
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <div style="flex:1;font-weight:800;">Archivos</div>
            <div style="display:flex;gap:8px;align-items:center;">
              <input id="explorer-folder-input" placeholder="Nueva carpeta (uploads/mi_carpeta)" style="padding:6px;border:1px solid #e5e7eb;border-radius:6px;">
              <input id="explorer-namebase" placeholder="Base nombre (opcional)" style="padding:6px;border:1px solid #e5e7eb;border-radius:6px;">
            </div>
          </div>
          <div id="explorer-dropzone" class="uploader" style="flex:0 0 140px;">
            <div>Arrastra y suelta imágenes aquí para subir</div>
            <div class="drop-hint">Suelta aquí para subir</div>
            <input id="explorer-file-input" type="file" accept="image/*" multiple style="display:block;">
            <div id="explorer-previews" class="preview-list"></div>
            <div style="display:flex;gap:8px;margin-top:8px;justify-content:flex-end;">
              <button id="explorer-upload-btn" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Subir</button>
              <button id="explorer-close-btn" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Cerrar</button>
            </div>
          </div>
          <div id="explorer-files" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:12px;overflow:auto;max-height:300px;padding-right:6px;"></div>
        </div>
        <div style="width:220px;">
          <div style="font-weight:800;margin-bottom:8px;">Seleccion</div>
          <div style="min-height:48px;" id="explorer-selection">Ninguno</div>
          <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">
            <button id="explorer-choose-folder" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#06b6d4;color:#fff;">Usar carpeta</button>
            <button id="explorer-choose-file" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#2563eb;color:#fff;">Usar imagen</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    attachExplorerHandlers(modal);
  }

  function attachExplorerHandlers(modal){
    var close = modal.querySelector('#explorer-close-btn');
    close.addEventListener('click', function(){ modal.style.display = 'none'; });
    var drop = modal.querySelector('#explorer-dropzone');
    var fileInput = modal.querySelector('#explorer-file-input');
    var previews = modal.querySelector('#explorer-previews');
    var uploadBtn = modal.querySelector('#explorer-upload-btn');
    var dirContainer = modal.querySelector('#explorer-dirs');
    var filesContainer = modal.querySelector('#explorer-files');
    var selection = modal.querySelector('#explorer-selection');
    var chooseFolderBtn = modal.querySelector('#explorer-choose-folder');
    var chooseFileBtn = modal.querySelector('#explorer-choose-file');
    var folderInput = modal.querySelector('#explorer-folder-input');
    var nameBase = modal.querySelector('#explorer-namebase');

    var staged = [];
    var currentFolder = '';
    var selectedFile = null;

    function renderPreviews(){ previews.innerHTML = ''; staged.forEach((f,i)=>{ const el=document.createElement('div'); el.className='preview'; const img=document.createElement('img'); img.src=URL.createObjectURL(f); el.appendChild(img); previews.appendChild(el); }); }

    drop.addEventListener('dragover', function(e){ e.preventDefault(); drop.classList.add('dragover'); });
    drop.addEventListener('dragleave', function(e){ const rect = drop.getBoundingClientRect(); const x = e.clientX, y = e.clientY; if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) drop.classList.remove('dragover'); });
    drop.addEventListener('drop', function(e){ e.preventDefault(); drop.classList.remove('dragover'); const dt = e.dataTransfer; if (dt && dt.files) { for(const f of dt.files) if (f.type && f.type.startsWith('image/')) staged.push(f); renderPreviews(); } });
    fileInput.addEventListener('change', function(){ for(const f of fileInput.files) if (f.type && f.type.startsWith('image/')) staged.push(f); renderPreviews(); });

    uploadBtn.addEventListener('click', async function(){
      if (staged.length === 0) { alert('Selecciona imágenes para subir'); return; }
      var folder = folderInput.value || currentFolder || 'uploads';
      var fd = new FormData(); staged.forEach(f=>fd.append('files[]', f)); fd.append('folder', folder); fd.append('filename_base', nameBase.value||'');
      var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
      uploadBtn.disabled = true; uploadBtn.textContent = 'Subiendo...';
      try {
        const resp = await fetch("{{ route('images.upload') }}", { method:'POST', headers:{'X-CSRF-TOKEN': token, 'Accept':'application/json'}, body: fd, credentials:'same-origin' });
        const data = await resp.json().catch(()=>({}));
        if (resp.ok) {
          staged = []; renderPreviews();
          await loadFiles(folder);
          alert('Subida completa');
        } else {
          alert((data && data.message) ? data.message : 'Error al subir');
        }
      } catch(err){ console.error(err); alert('Error de red'); }
      uploadBtn.disabled = false; uploadBtn.textContent = 'Subir';
    });

    async function loadDirs(){
      try {
        const resp = await fetch("{{ route('images.dirs') }}", { credentials:'same-origin' });
        const data = await resp.json().catch(()=>({})); dirContainer.innerHTML = '';
        // support two response shapes: { dirs: [...] } or { folders: [{name, images}, ...] }
        let dirs = [];
        if (Array.isArray(data.dirs)) dirs = data.dirs;
        else if (Array.isArray(data.folders)) dirs = data.folders.map(f=>f.name);
        dirs.forEach(d=>{ const b=document.createElement('button'); b.type='button'; b.textContent=d; b.className='folder-item'; b.setAttribute('data-folder', d); b.style='text-align:left;padding:8px;border-radius:8px;border:1px solid #eef2f7;background:#fff;'; b.addEventListener('click', function(){ currentFolder = d; folderInput.value = d; loadFiles(d); selection.textContent = d; }); dirContainer.appendChild(b); });
        if (Array.isArray(data.folders)) {
          data.folders.forEach(fobj=>{
            if (!fobj.name || !Array.isArray(fobj.images) || fobj.images.length===0) return;
            const btn = dirContainer.querySelector("button[data-folder='"+fobj.name+"']");
            if (btn) {
              const info = document.createElement('div'); info.style='font-size:12px;color:#6b7280;margin-top:4px;'; info.textContent = fobj.images.length + ' imagen(es)'; btn.appendChild(info);
            }
          });
        }
      } catch(e){ console.error('loadDirs error', e); }
    }

    async function loadFiles(folder){
      currentFolder = folder;
      try{
        const resp = await fetch("{{ route('images.list') }}?folder="+encodeURIComponent(folder), { credentials:'same-origin' });
        const data = await resp.json().catch(()=>({})); filesContainer.innerHTML = '';
        // render subfolders first
        if (Array.isArray(data.dirs) && data.dirs.length) {
          data.dirs.forEach(sd=>{
            const fwrap = document.createElement('div');
            fwrap.style = 'display:flex;align-items:center;justify-content:center;height:100px;border-radius:8px;background:#fff;border:1px dashed #e6eef6;cursor:pointer;';
            fwrap.textContent = sd;
            fwrap.addEventListener('click', function(){ loadFiles(folder + '/' + sd); selection.textContent = folder + '/' + sd; folderInput.value = folder + '/' + sd; });
            filesContainer.appendChild(fwrap);
          });
        }
        (Array.isArray(data.files) ? data.files : []).forEach(fname=>{
          const wrap = document.createElement('div'); wrap.style='position:relative;border-radius:8px;overflow:hidden;background:#f8fafc;';
          const img = document.createElement('img'); img.src = '/' + folder + '/' + fname; img.style='width:100%;height:100px;object-fit:cover;display:block;cursor:pointer;';
          img.addEventListener('click', function(){ selectedFile = folder + '/' + fname; selection.textContent = selectedFile; });
          img.addEventListener('error', function(){
            const err = document.createElement('div'); err.style='position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.06);color:#991b1b;font-weight:700;'; err.textContent='Archivo no encontrado';
            if (!wrap.querySelector('.err')) { err.className='err'; wrap.appendChild(err); }
          });
          wrap.appendChild(img);
          filesContainer.appendChild(wrap);
        });
      } catch(e){ console.error(e); }
    }

    // expose helper functions
    try { modal.loadDirs = loadDirs; modal.loadFiles = loadFiles; } catch(e){}
    window.loadDirs = function(){ var m = document.getElementById('image-explorer-modal'); if (m && m.loadDirs) return m.loadDirs(); };
    window.loadFiles = function(folder){ var m = document.getElementById('image-explorer-modal'); if (m && m.loadFiles) return m.loadFiles(folder); };

    chooseFolderBtn.addEventListener('click', function(){ var val = folderInput.value || currentFolder; if (!val) { alert('Selecciona o ingresa una carpeta'); return; } if (input) input.value = val; if (display) display.textContent = val; modal.style.display='none'; });
    chooseFileBtn.addEventListener('click', function(){ if (!selectedFile) { alert('Selecciona una imagen'); return; } if (input) input.value = selectedFile; if (display) display.textContent = selectedFile; modal.style.display='none'; });
  }

  // Auto-generate código on create if field is empty
  var codigoInput = document.getElementById('codigo-input');
  if (codigoInput && !codigoInput.value) {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    var code = 'P';
    for (var ci = 0; ci < 5; ci++) code += chars.charAt(Math.floor(Math.random() * chars.length));
    codigoInput.value = code;
  }

  // Servicios tags
  (function(){
    var tagsContainer = document.getElementById('servicios-tags');
    var tagInput = document.getElementById('servicio-input');
    var addBtn = document.getElementById('servicio-add-btn');
    var hiddenInput = document.getElementById('servicios-hidden');
    if (!tagsContainer || !tagInput || !addBtn || !hiddenInput) return;
    var tags = [];
    var existing = hiddenInput.value.trim();
    if (existing) { tags = existing.split(',').map(function(s){ return s.trim(); }).filter(Boolean); renderTags(); }
    function renderTags() {
      tagsContainer.innerHTML = '';
      tags.forEach(function(tag, i) {
        var chip = document.createElement('span');
        chip.style = 'display:inline-flex;align-items:center;gap:4px;background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:999px;font-size:0.875rem;';
        var txt = document.createTextNode(tag); chip.appendChild(txt);
        var x = document.createElement('button');
        x.type = 'button'; x.textContent = '×'; x.style = 'border:0;background:none;cursor:pointer;color:#0369a1;font-size:1.1rem;line-height:1;padding:0 0 0 4px;';
        (function(idx){ x.addEventListener('click', function(){ tags.splice(idx, 1); renderTags(); updateHidden(); }); })(i);
        chip.appendChild(x); tagsContainer.appendChild(chip);
      });
    }
    function updateHidden() { hiddenInput.value = tags.join(','); }
    function addTag() {
      var val = tagInput.value.trim(); if (!val) return;
      val.split(',').map(function(s){ return s.trim(); }).filter(Boolean).forEach(function(s){ if (tags.indexOf(s) === -1) tags.push(s); });
      tagInput.value = ''; renderTags(); updateHidden();
    }
    addBtn.addEventListener('click', addTag);
    tagInput.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); addTag(); } });
  })();
});
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
  const viewer = document.getElementById('hp-help-viewer');
  const openBtn = document.getElementById('hp-open-help-btn');
  const openLink = document.getElementById('hp-open-help-link');
  const backdrop = document.getElementById('hp-help-backdrop');
  const closeBtn = document.getElementById('hp-close-help');

  if (!viewer) return;

  const open = () => viewer.classList.add('open');
  const close = () => viewer.classList.remove('open');

  openBtn?.addEventListener('click', open);
  openLink?.addEventListener('click', open);
  closeBtn?.addEventListener('click', close);
  backdrop?.addEventListener('click', close);
});
</script>
@endsection

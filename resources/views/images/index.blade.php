@extends('layouts.app')

@section('title','Imágenes')

@section('content')
@php $isAdmin = auth()->check() && (auth()->user()->rol ?? '') === 'admin'; @endphp
<style>
.uploader { border:2px dashed #e5e7eb; border-radius:10px; padding:18px; display:flex; flex-direction:column; gap:10px; align-items:center; text-align:center; background:#fff; transition: all .18s ease; }
.uploader.dragover { background:#ecfeff; border-color:#06b6d4; box-shadow: 0 8px 28px rgba(6,182,212,0.08); transform: translateY(-2px); }
.preview-list { display:flex; gap:8px; flex-wrap:wrap; width:100%; }
.preview { width:120px; height:90px; border-radius:8px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center; font-size:12px; color:#6b7280; position:relative; }
.preview img { width:100%; height:100%; object-fit:cover; display:block; }
.dir-list { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
.dir-item { background:#f8fafc;padding:6px 10px;border-radius:8px;color:#374151;font-weight:600; }

.uploader .drop-hint { padding:6px 10px; border-radius:6px; background:rgba(6,182,212,0.06); color:#065f46; font-weight:700; display:none; }
.uploader.dragover .drop-hint { display:block; }
#file-input::-webkit-file-upload-button{ padding:8px 12px; border-radius:8px; background: linear-gradient(90deg,#06b6d4,#0ea5e9); color:#fff; border:0; cursor:pointer; font-weight:700; box-shadow: 0 8px 20px rgba(6,182,212,0.12); }
#file-input::file-selector-button{ padding:8px 12px; border-radius:8px; background: linear-gradient(90deg,#06b6d4,#0ea5e9); color:#fff; border:0; cursor:pointer; font-weight:700; box-shadow: 0 8px 20px rgba(6,182,212,0.12); }
</style>

<div style="max-width:1000px;margin:18px auto;padding:12px;">
  <h1>Gestión de imágenes</h1>

  <div style="display:flex;gap:16px;flex-wrap:wrap;">
    <div style="flex:1;min-width:320px;">
      <div class="uploader" id="dropzone">
        <div>Arrastra y suelta tus imágenes aquí</div>
        <div class="drop-hint">Suelta aquí para subir</div>
        <div style="font-size:0.9rem;color:#6b7280;">o</div>
        <input id="file-input" type="file" accept="image/*" multiple style="display:block;">
        <div style="width:100%;display:flex;gap:8px;justify-content:center;margin-top:8px;">
          <input id="folder-input" placeholder="Nombre carpeta (ej: imgs, uploads)" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;width:60%;">
          <input id="namebase-input" placeholder="Nombre base (opcional)" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;width:35%;">
        </div>
        <div class="preview-list" id="previews"></div>
        <div style="display:flex;gap:8px;margin-top:8px;">
          <button id="btn-upload" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Subir</button>
          <button id="btn-clear" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Limpiar</button>
        </div>
        <div id="status" style="margin-top:8px;color:#6b7280;font-weight:700;"></div>
      </div>
    </div>

    <div style="width:320px;">
      <h3>Carpetas públicas</h3>
      <div class="dir-list" id="dir-list">
        @foreach($folders as $f)
          <div class="dir-item" data-folder="{{ $f['name'] }}" style="display:flex;flex-direction:column;gap:6px;padding:8px;">
            <div style="font-weight:800;">{{ $f['name'] }}</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              @if(!empty($f['images']))
                @foreach($f['images'] as $img)
                  <div style="width:40px;height:30px;border-radius:6px;overflow:hidden;background:#fff;border:1px solid #eef2f7;">
                    <img src="/{{ $img }}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.opacity=0.4;this.style.filter='grayscale(60%)';">
                  </div>
                @endforeach
              @else
                <div style="color:#9ca3af;font-size:12px;">Sin imágenes</div>
              @endif
            </div>
          </div>
        @endforeach
      </div>
      <div style="margin-top:12px;">
        <div style="font-weight:800;margin-bottom:6px;">Navegador</div>
        <div id="browser-current" style="font-size:0.9rem;color:#6b7280;margin-bottom:6px;">Carpeta: <strong id="browser-current-path">/</strong></div>
        <div id="browser-files" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:8px;background:#fff;min-height:160px;"></div>
        <div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <div id="browser-selection" style="flex:1;color:#374151;">Seleccionado: <span id="browser-selection-val">Ninguno</span></div>
          <button id="browser-copy" style="padding:6px 10px;border-radius:8px;border:0;background:#06b6d4;color:#fff;">Copiar ruta</button>
          @if($isAdmin)
          <button id="browser-delete-file" style="padding:6px 10px;border-radius:8px;border:0;background:#ef4444;color:#fff;cursor:pointer;">Eliminar archivo</button>
          @endif
        </div>
        @if($isAdmin)
        <div id="browser-delete-status" style="font-size:0.82rem;margin-top:4px;color:#6b7280;min-height:1.2em;"></div>
        @endif
        <div style="margin-top:10px;border-top:1px solid #eef2f7;padding-top:10px;">
          <div style="font-weight:700;margin-bottom:6px;font-size:0.9rem;">Nueva carpeta</div>
          <div style="display:flex;gap:6px;align-items:center;">
            <input id="mkdir-prefix" readonly placeholder="(ruta actual)" style="width:110px;padding:6px 8px;border-radius:6px;border:1px solid #e5e7eb;background:#f8fafc;font-size:0.82rem;color:#6b7280;">
            <span style="color:#9ca3af;font-size:0.9rem;">/</span>
            <input id="mkdir-name" placeholder="nombre" style="flex:1;padding:6px 8px;border-radius:6px;border:1px solid #e5e7eb;font-size:0.9rem;">
            <button id="mkdir-btn" style="padding:6px 12px;border-radius:6px;border:0;background:#059669;color:#fff;cursor:pointer;white-space:nowrap;">+ Crear</button>
          </div>
          <div id="mkdir-status" style="font-size:0.82rem;margin-top:4px;color:#6b7280;"></div>
        </div>
      </div>
      <h3 style="margin-top:12px;">Enlaces subidos</h3>
      <div id="uploaded-list" style="display:flex;flex-direction:column;gap:6px;"></div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
  const _deleteFileUrl   = "{{ route('images.deleteFile') }}";
  const _deleteFolderUrl = "{{ route('images.deleteFolder') }}";
  const drop = document.getElementById('dropzone');
  const fileInput = document.getElementById('file-input');
  const previews = document.getElementById('previews');
  const btnUpload = document.getElementById('btn-upload');
  const btnClear = document.getElementById('btn-clear');
  const folderInput = document.getElementById('folder-input');
  const namebaseInput = document.getElementById('namebase-input');
  const status = document.getElementById('status');
  const uploadedList = document.getElementById('uploaded-list');
  const dirList = document.getElementById('dir-list');
  const browserFiles = document.getElementById('browser-files');
  const browserCurrentPath = document.getElementById('browser-current-path');
  const browserSelectionVal = document.getElementById('browser-selection-val');
  const browserCopyBtn = document.getElementById('browser-copy');

  let currentBrowserFolder = '';
  let browserSelected = null;

  async function loadDirs(){
    try{
      const resp = await fetch("{{ route('images.dirs') }}", { credentials: 'same-origin' });
      const data = await resp.json();
      dirList.innerHTML = '';
      (data.folders||[]).forEach(f=>{
        const el = document.createElement('div'); el.className='dir-item'; el.style.display='flex'; el.style.flexDirection='column'; el.style.gap='6px'; el.style.padding='8px';
        const title = document.createElement('div'); title.style.fontWeight='800'; title.textContent = f.name; el.appendChild(title);
        const thumbs = document.createElement('div'); thumbs.style.display='flex'; thumbs.style.gap='6px'; thumbs.style.flexWrap='wrap';
        if (f.images && f.images.length){
          f.images.forEach(img=>{
            const box = document.createElement('div'); box.style.width='40px'; box.style.height='30px'; box.style.borderRadius='6px'; box.style.overflow='hidden'; box.style.background='#fff'; box.style.border='1px solid #eef2f7';
            const im = document.createElement('img'); im.src = '/' + img; im.style.width='100%'; im.style.height='100%'; im.style.objectFit='cover'; im.addEventListener('error', ()=>{ im.style.opacity=0.4; im.style.filter='grayscale(60%)'; });
            box.appendChild(im); thumbs.appendChild(box);
          });
        } else {
          const no = document.createElement('div'); no.style.color='#9ca3af'; no.style.fontSize='12px'; no.textContent='Sin imágenes'; thumbs.appendChild(no);
        }
        el.appendChild(thumbs);
        el.setAttribute('data-folder', f.name);
        el.style.cursor = 'pointer'; el.style.position = 'relative';
        el.addEventListener('click', function(){ loadFiles(f.name); });
        if (isAdmin) {
          const xb = document.createElement('button');
          xb.textContent='×'; xb.title='Eliminar carpeta';
          xb.style='position:absolute;top:3px;right:3px;width:20px;height:20px;line-height:1;border-radius:50%;border:0;background:rgba(239,68,68,0.85);color:#fff;cursor:pointer;font-size:14px;padding:0;display:flex;align-items:center;justify-content:center;';
          xb.addEventListener('click', async function(e){ e.stopPropagation(); if (!confirm('¿Eliminar carpeta "'+f.name+'" y todo su contenido?')) return; await _deleteFolderReq(f.name, el); });
          el.appendChild(xb);
        }
        dirList.appendChild(el);
      });
    } catch(err){ console.error(err); }
  }

  async function loadFiles(folder){
    currentBrowserFolder = folder;
    browserCurrentPath.textContent = folder;
    updateMkdirPrefix();
    try{
      const resp = await fetch("{{ route('images.list') }}?folder="+encodeURIComponent(folder), { credentials:'same-origin' });
      const data = await resp.json();
      browserFiles.innerHTML = '';
      if (data.dirs && data.dirs.length){
        data.dirs.forEach(sd=>{
          const fwrap = document.createElement('div'); fwrap.style='position:relative;display:flex;align-items:center;justify-content:center;height:100px;border-radius:8px;background:#fff;border:1px dashed #e6eef6;cursor:pointer;'; fwrap.textContent = sd;
          fwrap.addEventListener('click', ()=> loadFiles(folder + '/' + sd));
          if (isAdmin) {
            const xb = document.createElement('button');
            xb.textContent='×'; xb.title='Eliminar carpeta';
            xb.style='position:absolute;top:3px;right:3px;width:20px;height:20px;line-height:1;border-radius:50%;border:0;background:rgba(239,68,68,0.85);color:#fff;cursor:pointer;font-size:14px;padding:0;display:flex;align-items:center;justify-content:center;';
            xb.addEventListener('click', async function(e){ e.stopPropagation(); if (!confirm('¿Eliminar carpeta "'+sd+'" y todo su contenido?')) return; await _deleteFolderReq(folder+'/'+sd, fwrap); });
            fwrap.appendChild(xb);
          }
          browserFiles.appendChild(fwrap);
        });
      }
      (data.files||[]).forEach(fname=>{
        const wrap = document.createElement('div'); wrap.style='position:relative;border-radius:8px;overflow:hidden;background:#f8fafc;';
        const img = document.createElement('img'); img.src = '/' + folder + '/' + fname; img.style='width:100%;height:100px;object-fit:cover;display:block;cursor:pointer;';
        img.addEventListener('click', function(){ browserSelected = folder + '/' + fname; browserSelectionVal.textContent = browserSelected; });
        img.addEventListener('error', function(){ const err = document.createElement('div'); err.style='position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.06);color:#991b1b;font-weight:700;'; err.textContent='Archivo no encontrado'; if (!wrap.querySelector('.err')){ err.className='err'; wrap.appendChild(err);} });
        wrap.appendChild(img);
        if (isAdmin) {
          const xb = document.createElement('button');
          xb.textContent='×'; xb.title='Eliminar imagen';
          xb.style='position:absolute;top:3px;right:3px;width:22px;height:22px;line-height:1;border-radius:50%;border:0;background:rgba(239,68,68,0.85);color:#fff;cursor:pointer;font-size:15px;padding:0;display:flex;align-items:center;justify-content:center;';
          xb.addEventListener('click', async function(e){ e.stopPropagation(); if (!confirm('¿Eliminar imagen "'+fname+'"?')) return; await _deleteFileReq(folder+'/'+fname, wrap); });
          wrap.appendChild(xb);
        }
        browserFiles.appendChild(wrap);
      });
    } catch(err){ console.error(err); }
  }

  // expose for other pages
  try{ window.loadDirs = loadDirs; window.loadFiles = loadFiles; } catch(e){}

  browserCopyBtn.addEventListener('click', function(){ if (!browserSelected){ alert('Selecciona un archivo primero'); return; } navigator.clipboard?.writeText(browserSelected).then(()=> alert('Ruta copiada')).catch(()=>{ alert('No se pudo copiar'); }); });

  // ── delete helpers ─────────────────────────────────────────────────────────
  const _browserDeleteFileBtn = document.getElementById('browser-delete-file');
  const _browserDeleteStatus  = document.getElementById('browser-delete-status');

  function _showDelStatus(msg, ok){
    if (!_browserDeleteStatus) return;
    _browserDeleteStatus.textContent = msg;
    _browserDeleteStatus.style.color = ok ? '#059669' : '#b91c1c';
    setTimeout(()=>{ if (_browserDeleteStatus) _browserDeleteStatus.textContent = ''; }, 4000);
  }

  async function _deleteFileReq(path, domEl){
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    try {
      const resp = await fetch(_deleteFileUrl, { method:'DELETE', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'}, body:JSON.stringify({path}), credentials:'same-origin' });
      const data = await resp.json().catch(()=>({}));
      if (resp.ok) {
        if (domEl) domEl.remove();
        if (browserSelected === path){ browserSelected = null; browserSelectionVal.textContent = 'Ninguno'; }
        _showDelStatus('Imagen eliminada', true);
      } else { _showDelStatus(data.message || 'Error al eliminar', false); }
    } catch(e){ _showDelStatus('Error de red', false); }
  }

  async function _deleteFolderReq(path, domEl){
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    try {
      const resp = await fetch(_deleteFolderUrl, { method:'DELETE', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'}, body:JSON.stringify({path}), credentials:'same-origin' });
      const data = await resp.json().catch(()=>({}));
      if (resp.ok) {
        if (domEl) domEl.remove();
        if (currentBrowserFolder && (currentBrowserFolder === path || currentBrowserFolder.startsWith(path+'/'))){
          currentBrowserFolder = ''; browserCurrentPath.textContent = '/'; browserFiles.innerHTML = ''; updateMkdirPrefix();
        }
        _showDelStatus('Carpeta eliminada', true);
        await loadDirs();
      } else { _showDelStatus(data.message || 'Error al eliminar carpeta', false); }
    } catch(e){ _showDelStatus('Error de red', false); }
  }

  if (_browserDeleteFileBtn) {
    _browserDeleteFileBtn.addEventListener('click', async function(){
      if (!browserSelected){ alert('Selecciona un archivo primero'); return; }
      if (!confirm('¿Eliminar "' + browserSelected + '"?')) return;
      await _deleteFileReq(browserSelected, null);
      if (currentBrowserFolder) await loadFiles(currentBrowserFolder);
    });
  }

  // ── mkdir ──────────────────────────────────────────────────────────────────
  const mkdirPrefixInput = document.getElementById('mkdir-prefix');
  const mkdirNameInput   = document.getElementById('mkdir-name');
  const mkdirBtn         = document.getElementById('mkdir-btn');
  const mkdirStatus      = document.getElementById('mkdir-status');

  function updateMkdirPrefix(){ const el = document.getElementById('mkdir-prefix'); if (el) el.value = currentBrowserFolder || ''; }

  mkdirBtn.addEventListener('click', async function(){
    const name = (mkdirNameInput.value || '').trim();
    if (!name){ mkdirStatus.textContent = 'Escribe un nombre'; mkdirStatus.style.color='#b91c1c'; return; }
    const prefix = (mkdirPrefixInput.value || '').trim();
    const fullPath = prefix ? (prefix.replace(/\/+$/,'') + '/' + name) : name;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    mkdirBtn.disabled = true;
    try {
      const resp = await fetch("{{ route('images.mkdir') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ path: fullPath }),
        credentials: 'same-origin'
      });
      const data = await resp.json().catch(()=>({}));
      if (resp.ok || resp.status === 200) {
        mkdirStatus.style.color = '#059669';
        mkdirStatus.textContent = data.message || 'Carpeta creada';
        mkdirNameInput.value = '';
        // reload current browser view
        if (currentBrowserFolder) await loadFiles(currentBrowserFolder);
        else await loadDirs();
      } else {
        mkdirStatus.style.color = '#b91c1c';
        mkdirStatus.textContent = data.message || 'Error al crear carpeta';
      }
    } catch(err){ mkdirStatus.style.color='#b91c1c'; mkdirStatus.textContent='Error de red'; console.error(err); }
    finally { mkdirBtn.disabled = false; setTimeout(()=>mkdirStatus.textContent='', 4000); }
  });

  // initialize browser dirs
  loadDirs();

  let files = [];

  function renderPreviews(){
    previews.innerHTML = '';
    files.forEach((f, i) => {
      const el = document.createElement('div');
      el.className = 'preview';
      const img = document.createElement('img');
      img.alt = f.name;
      img.src = URL.createObjectURL(f);
      el.appendChild(img);
      const lbl = document.createElement('div');
      lbl.style.position='absolute'; lbl.style.bottom='4px'; lbl.style.left='4px'; lbl.style.right='4px'; lbl.style.fontSize='11px'; lbl.style.background='rgba(0,0,0,0.28)'; lbl.style.color='#fff'; lbl.style.padding='2px 4px'; lbl.style.borderRadius='6px';
      lbl.textContent = f.name;
      el.appendChild(lbl);
      previews.appendChild(el);
    });
  }

  drop.addEventListener('dragover', function(e){
    e.preventDefault();
    drop.classList.add('dragover');
  });
  drop.addEventListener('dragleave', function(e){
    // evitar que al mover sobre elementos hijos se quite la clase
    const rect = drop.getBoundingClientRect();
    const x = e.clientX, y = e.clientY;
    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
      drop.classList.remove('dragover');
    }
  });
  drop.addEventListener('drop', function(e){
    e.preventDefault(); drop.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt && dt.files) {
      for(const f of dt.files) {
        if (f.type && f.type.startsWith('image/')) files.push(f);
      }
      renderPreviews();
    }
  });

  fileInput.addEventListener('change', function(){
    drop.classList.remove('dragover');
    for(const f of fileInput.files) {
      if (f.type && f.type.startsWith('image/')) files.push(f);
    }
    renderPreviews();
  });

  btnClear.addEventListener('click', function(){
    files = []; previews.innerHTML = ''; status.textContent = ''; uploadedList.innerHTML = '';
    fileInput.value = '';
  });

  btnUpload.addEventListener('click', async function(){
    if (files.length === 0) { alert('Selecciona archivos primero'); return; }
    const fd = new FormData();
    files.forEach(f => fd.append('files[]', f));
    fd.append('folder', folderInput.value || 'uploads');
    fd.append('filename_base', namebaseInput.value || '');
    status.textContent = 'Subiendo...';
    btnUpload.disabled = true;
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const resp = await fetch("{{ route('images.upload') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: fd,
        credentials: 'same-origin'
      });
      const data = await resp.json().catch(()=>({}));
      if (resp.ok) {
        status.textContent = data.message || 'Subida completada';
        if (data.files && Array.isArray(data.files)) {
          data.files.forEach(f => {
            const a = document.createElement('a');
            a.href = f.url; a.target = '_blank'; a.textContent = f.path;
            uploadedList.appendChild(a);
          });
        }
        files = []; previews.innerHTML = ''; fileInput.value = '';
      } else {
        status.textContent = (data && data.message) ? data.message : 'Error al subir';
        alert(status.textContent);
      }
    } catch(err) {
      console.error(err);
      alert('Error de red');
    } finally {
      btnUpload.disabled = false;
      setTimeout(()=> status.textContent = '', 4000);
    }
  });
});
</script>
@endsection
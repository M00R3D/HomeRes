@extends('layouts.app')

@section('title','Imágenes')

@section('content')
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
        @foreach($dirs as $d)
          <div class="dir-item">{{ $d }}</div>
        @endforeach
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
  const drop = document.getElementById('dropzone');
  const fileInput = document.getElementById('file-input');
  const previews = document.getElementById('previews');
  const btnUpload = document.getElementById('btn-upload');
  const btnClear = document.getElementById('btn-clear');
  const folderInput = document.getElementById('folder-input');
  const namebaseInput = document.getElementById('namebase-input');
  const status = document.getElementById('status');
  const uploadedList = document.getElementById('uploaded-list');

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
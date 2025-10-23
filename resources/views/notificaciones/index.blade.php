@extends('layouts.app')

@section('title','Notificaciones')

@section('content')
@php
    $current = auth()->user() ?? null;
    $isAdmin = $current && ($current->rol === 'admin');
    use Carbon\Carbon;
    Carbon::setLocale('es');
    $estadoOrder = ['cerrada' => 0, 'abierta' => 1, 'vista' => 2];
    $tmp = $notificaciones->sortByDesc('fecha_creacion');
    $notificaciones_sorted = $tmp->sortBy(function($n) use ($estadoOrder) { return $estadoOrder[$n->estado] ?? 99; })->values();
@endphp

<style>
/* estilos mínimos embebidos; puedes mover a public/css/notificaciones.css */
.modal-card { transition: transform .28s, opacity .28s, max-height .28s, padding .28s; transform-origin: top center; opacity:1; max-height:1200px; overflow:hidden; }
.modal-card.collapsed { transform: scaleY(.98); opacity:0; max-height:0; padding-top:0; padding-bottom:0; overflow:hidden; }
.list-card{background:#fff;border-radius:10px;padding:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);margin-bottom:12px;overflow-x:auto}
.btn-edit{background:linear-gradient(90deg,#6366f1,#06b6d4);color:#fff;padding:6px 8px;border-radius:8px;border:0;font-weight:700;margin-right:6px;cursor:pointer}
</style>

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;font-size:1.25rem;">Notificaciones</h1>
            @if($current)
                <div style="font-weight:700;color:#111;">Conectado: {{ $current->nombre }} {{ $current->apellido }}</div>
                <div style="font-size:0.9rem;color:#6b7280;">{{ $current->email }}</div>
            @endif
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            @if($isAdmin)
                <button id="btn-new" style="background:#6366f1;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700;">Nueva notificación</button>
            @endif
        </div>
    </header>

    @if(session('success'))
        <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
            {{ session('success') }}
        </div>
    @endif

    @php
        $cerradas = $notificaciones_sorted->filter(fn($x)=> ($x->estado ?? '') === 'cerrada')->values();
        $abiertas = $notificaciones_sorted->filter(fn($x)=> ($x->estado ?? '') === 'abierta')->values();
        $vistas   = $notificaciones_sorted->filter(fn($x)=> ($x->estado ?? '') === 'vista')->values();
        $icons = ['prueba'=>'🧪','aprobada'=>'✅','rechazada'=>'❌','otra'=>'🔔','info'=>'🔔','confirmacion'=>'✅','pago'=>'💳','alerta'=>'⚠️','mantenimiento'=>'🛠️'];
    @endphp

    <h3 style="margin-top:8px;margin-bottom:6px;color:#374151;">Cerradas</h3>
    <div class="list-card">
        <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead>
                <tr style="text-align:left;color:#374151;border-bottom:1px solid #e5e7eb;">
                    @if($isAdmin)<th style="padding:10px 12px;">ID</th>@endif
                    <th style="padding:10px 12px;">Tipo</th>
                    <th style="padding:10px 12px;">Descripción</th>
                    <th style="padding:10px 12px;">Fecha creación</th>
                    @if($isAdmin)<th style="padding:10px 12px;">Usuario</th>@endif
                    @if($isAdmin)<th style="padding:10px 12px;width:190px;">Acciones</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($cerradas as $n)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        @if($isAdmin)<td style="padding:10px 12px;">{{ $n->id }}</td>@endif
                        <td style="padding:10px 12px;">
                            <span style="display:inline-flex;gap:8px;align-items:center;">
                                <span aria-hidden="true">{{ $icons[$n->tipo] ?? '🔔' }}</span>
                                <span style="font-weight:700;text-transform:capitalize;">{{ $n->tipo }}</span>
                            </span>
                        </td>
                        <td style="padding:10px 12px;">{{ Str::limit($n->descripcion, 120) }}</td>
                        <td style="padding:10px 12px;">
                            @if(!empty($n->fecha_creacion))
                                {{ ucfirst(\Carbon\Carbon::parse($n->fecha_creacion)->locale('es')->isoFormat('dddd, D [de] MMMM YYYY, HH:mm')) }}
                            @else - @endif
                        </td>
                        @if($isAdmin)
                            <td style="padding:10px 12px;">{{ optional($n->user)->nombre ? optional($n->user)->nombre . ' ' . optional($n->user)->apellido : 'Todos' }}</td>
                            <td style="padding:10px 12px;width:190px;">
                                <button type="button" class="btn-edit" data-notif='@json($n)'>Editar</button>
                                <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:6px 8px;border-radius:8px;border:0;cursor:pointer;">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAdmin ? 6 : 4 }}" style="padding:12px;">No hay notificaciones cerradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 style="margin-top:8px;margin-bottom:6px;color:#374151;">Abiertas</h3>
    <div class="list-card">
        <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead>
                <tr style="text-align:left;color:#374151;border-bottom:1px solid #e5e7eb;">
                    @if($isAdmin)<th style="padding:10px 12px;">ID</th>@endif
                    <th style="padding:10px 12px;">Tipo</th>
                    <th style="padding:10px 12px;">Descripción</th>
                    <th style="padding:10px 12px;">Fecha creación</th>
                    @if($isAdmin)<th style="padding:10px 12px;">Usuario</th>@endif
                    @if($isAdmin)<th style="padding:10px 12px;width:190px;">Acciones</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($abiertas as $n)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        @if($isAdmin)<td style="padding:10px 12px;">{{ $n->id }}</td>@endif
                        <td style="padding:10px 12px;">
                            <span style="display:inline-flex;gap:8px;align-items:center;">
                                <span aria-hidden="true">{{ $icons[$n->tipo] ?? '🔔' }}</span>
                                <span style="font-weight:700;text-transform:capitalize;">{{ $n->tipo }}</span>
                            </span>
                        </td>
                        <td style="padding:10px 12px;">{{ Str::limit($n->descripcion, 120) }}</td>
                        <td style="padding:10px 12px;">
                            @if(!empty($n->fecha_creacion))
                                {{ ucfirst(\Carbon\Carbon::parse($n->fecha_creacion)->locale('es')->isoFormat('dddd, D [de] MMMM YYYY, HH:mm')) }}
                            @else - @endif
                        </td>
                        @if($isAdmin)
                            <td style="padding:10px 12px;">{{ optional($n->user)->nombre ? optional($n->user)->nombre . ' ' . optional($n->user)->apellido : 'Todos' }}</td>
                            <td style="padding:10px 12px;width:190px;">
                                <button type="button" class="btn-edit" data-notif='@json($n)'>Editar</button>
                                <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:6px 8px;border-radius:8px;border:0;cursor:pointer;">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAdmin ? 6 : 4 }}" style="padding:12px;">No hay notificaciones abiertas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 style="margin-top:8px;margin-bottom:6px;color:#374151;">Vistas</h3>
    <div class="list-card">
        <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead>
                <tr style="text-align:left;color:#374151;border-bottom:1px solid #e5e7eb;">
                    @if($isAdmin)<th style="padding:10px 12px;">ID</th>@endif
                    <th style="padding:10px 12px;">Tipo</th>
                    <th style="padding:10px 12px;">Descripción</th>
                    <th style="padding:10px 12px;">Fecha creación</th>
                    @if($isAdmin)<th style="padding:10px 12px;">Usuario</th>@endif
                    @if($isAdmin)<th style="padding:10px 12px;width:190px;">Acciones</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($vistas as $n)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        @if($isAdmin)<td style="padding:10px 12px;">{{ $n->id }}</td>@endif
                        <td style="padding:10px 12px;">
                            <span style="display:inline-flex;gap:8px;align-items:center;">
                                <span aria-hidden="true">{{ $icons[$n->tipo] ?? '🔔' }}</span>
                                <span style="font-weight:700;text-transform:capitalize;">{{ $n->tipo }}</span>
                            </span>
                        </td>
                        <td style="padding:10px 12px;">{{ Str::limit($n->descripcion, 120) }}</td>
                        <td style="padding:10px 12px;">
                            @if(!empty($n->fecha_creacion))
                                {{ ucfirst(\Carbon\Carbon::parse($n->fecha_creacion)->locale('es')->isoFormat('dddd, D [de] MMMM YYYY, HH:mm')) }}
                            @else - @endif
                        </td>
                        @if($isAdmin)
                            <td style="padding:10px 12px;">{{ optional($n->user)->nombre ? optional($n->user)->nombre . ' ' . optional($n->user)->apellido : 'Todos' }}</td>
                            <td style="padding:10px 12px;width:190px;">
                                <button type="button" class="btn-edit" data-notif='@json($n)'>Editar</button>
                                <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:6px 8px;border-radius:8px;border:0;cursor:pointer;">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAdmin ? 6 : 4 }}" style="padding:12px;">No hay notificaciones vistas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal / formulario para crear / editar --}}
<div id="notif-form-card" class="modal-card collapsed" style="display:none;background:#fff;border-radius:10px;padding:12px;box-shadow:0 6px 18px rgba(0,0,0,0.06);margin:16px auto;max-width:1100px;">
    <h2 id="notif-form-title" style="margin:0 0 8px 0;font-size:1.05rem;">Nueva notificación</h2>
    <form id="notif-form" method="POST" action="{{ url('/notificaciones') }}">
        @csrf
        <input type="hidden" name="_method" id="notif-form-method" value="POST">
        <input type="hidden" name="id" id="notif-id" value="">

        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <div style="flex:1 1 220px;min-width:180px;">
                <label>Para (usuario)</label>
                <select name="id_usuario" id="f-id_usuario" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }} ({{ $u->email }})</option>
                    @endforeach
                </select>
            </div>

            <div style="flex:1 1 180px;min-width:160px;">
                <label>Estado</label>
                <select name="estado" id="f-estado" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                    <option value="cerrada">cerrada</option>
                    <option value="abierta">abierta</option>
                    <option value="vista">vista</option>
                </select>
            </div>

            <div style="flex:1 1 180px;min-width:160px;">
                <label>Tipo</label>
                <select name="tipo" id="f-tipo" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                    <option value="prueba">prueba</option>
                    <option value="aprobada">aprobada</option>
                    <option value="rechazada">rechazada</option>
                    <option value="otra">otra</option>
                </select>
            </div>

            <div style="flex:1 1 260px;min-width:200px;">
                <label>Ruta (opcional)</label>
                <input name="ruta" id="f-ruta" type="text" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            </div>

            <div style="flex:1 1 420px;min-width:220px;">
                <label>Descripción</label>
                <textarea name="descripcion" id="f-descripcion" rows="4" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;"></textarea>
            </div>

            <div id="f-fecha_visto_row" style="flex:1 1 220px;min-width:180px;display:none;">
                <label>Fecha visto</label>
                <input name="fecha_visto" id="f-fecha_visto" type="datetime-local" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:12px;">
            <button type="submit" id="notif-save" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700;">Guardar</button>
            <button id="notif-cancel" type="button" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Cancelar</button>
        </div>
    </form>
</div>

<div id="confirm-overlay" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.45);align-items:center;justify-content:center;z-index:9999;padding:1rem;">
  <div style="background:#fff;padding:14px;border-radius:12px;box-shadow:0 8px 28px rgba(15,23,42,0.06);width:clamp(280px,420px,520px);text-align:left;">
    <h3 id="confirm-title" style="margin:0 0 8px 0;font-weight:700;font-size:1.05rem;">Confirmar</h3>
    <p id="confirm-msg" style="color:#6b7280;margin-bottom:12px;font-size:0.98rem;">¿Estás seguro?</p>
    <div style="display:flex;gap:.5rem;justify-content:flex-end;">
      <button type="button" id="confirm-cancel" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Cancelar</button>
      <button type="button" id="confirm-ok" style="background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Eliminar</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const card = document.getElementById('notif-form-card');
    const btnNew = document.getElementById('btn-new');
    const btnCancel = document.getElementById('notif-cancel');
    const form = document.getElementById('notif-form');
    const methodInput = document.getElementById('notif-form-method');
    const idInput = document.getElementById('notif-id');
    const title = document.getElementById('notif-form-title');
    const fechaVistoRow = document.getElementById('f-fecha_visto_row');
    const fechaVistoInput = document.getElementById('f-fecha_visto');

    function openCreate() {
        title.textContent = 'Nueva notificación';
        form.action = "{{ url('/notificaciones') }}";
        methodInput.value = 'POST';
        idInput.value = '';
        form.querySelectorAll('input, textarea, select').forEach(i => { if(i.tagName==='SELECT') i.selectedIndex = 0; else i.value = ''; });
        fechaVistoRow.style.display = 'none';
        card.style.display = 'block';
        card.classList.add('collapsed');
        requestAnimationFrame(()=> card.classList.remove('collapsed'));
        card.scrollIntoView({behavior:'smooth', block:'center'});
    }

    function openEdit(notif) {
        title.textContent = 'Editar notificación — ID ' + notif.id;
        form.action = "{{ url('/notificaciones') }}/" + notif.id;
        methodInput.value = 'PUT';
        idInput.value = notif.id || '';
        document.getElementById('f-id_usuario').value = notif.usuario_id || notif.id_usuario || '';
        document.getElementById('f-estado').value = notif.estado || 'cerrada';
        document.getElementById('f-tipo').value = notif.tipo || 'prueba';
        document.getElementById('f-ruta').value = notif.ruta || '';
        document.getElementById('f-descripcion').value = notif.descripcion || '';
        if (notif.fecha_visto) {
            fechaVistoRow.style.display = 'block';
            try {
                const d = new Date(notif.fecha_visto);
                const pad = (n)=> String(n).padStart(2,'0');
                const yyyy = d.getFullYear();
                const mm = pad(d.getMonth()+1);
                const dd = pad(d.getDate());
                const hh = pad(d.getHours());
                const mi = pad(d.getMinutes());
                fechaVistoInput.value = `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
            } catch(e) {
                fechaVistoRow.style.display = 'block';
            }
        } else {
            fechaVistoRow.style.display = 'none';
            fechaVistoInput.value = '';
        }
        card.style.display = 'block';
        card.classList.add('collapsed');
        requestAnimationFrame(()=> card.classList.remove('collapsed'));
        card.scrollIntoView({behavior:'smooth', block:'center'});
    }

    if (btnNew) btnNew.addEventListener('click', openCreate);
    if (btnCancel) btnCancel.addEventListener('click', function(){
        card.classList.add('collapsed');
        card.addEventListener('transitionend', function handler(){
            card.style.display = 'none';
            card.classList.remove('collapsed');
            form.reset();
            methodInput.value = 'POST';
            idInput.value = '';
            card.removeEventListener('transitionend', handler);
        });
    });

    document.querySelectorAll('.btn-edit').forEach(btn=>{
        btn.addEventListener('click', function(){
            try {
                const notif = JSON.parse(this.getAttribute('data-notif'));
                openEdit(notif);
            } catch(e){
                console.error(e);
                alert('Datos inválidos');
            }
        });
    });

    if (form) {
        form.addEventListener('submit', async function(evt){
            evt.preventDefault();
            const submitBtn = document.getElementById('notif-save');
            const original = submitBtn ? submitBtn.textContent : null;
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Guardando...'; }
            const fd = new FormData(form);
            const method = methodInput.value || 'POST';
            if (method.toUpperCase() === 'PUT') fd.append('_method','PUT');
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resp = await fetch(form.action, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrfToken},
                    body: fd,
                    credentials: 'include'
                });
                if (resp.ok) {
                    window.location.href = "{{ url('/notificaciones') }}";
                    return;
                }
                const ct = resp.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await resp.json() : await resp.text();
                if (resp.status === 422 && data && data.errors) {
                    alert(Object.values(data.errors).flat().join('\n'));
                } else {
                    alert((data && data.message) ? data.message : 'Error al guardar');
                }
            } catch(e){
                console.error(e);
                alert('Error de red');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; if (original) submitBtn.textContent = original; }
            }
        });
    }

    // confirm overlay (global)
    (function(){
      let pending = null;
      const overlay = document.getElementById('confirm-overlay');
      const msgEl = document.getElementById('confirm-msg');
      const btnOk = document.getElementById('confirm-ok');
      const btnCancelConfirm = document.getElementById('confirm-cancel');

      function showConfirm(text, onConfirm){
        msgEl.textContent = text || '¿Estás seguro?';
        overlay.style.display = 'flex';
        pending = onConfirm;
        btnCancelConfirm.focus();
      }
      function hideConfirm(){
        overlay.style.display = 'none';
        pending = null;
      }

      btnCancelConfirm.addEventListener('click', hideConfirm);
      btnOk.addEventListener('click', function(){
        if(typeof pending === 'function') pending();
        hideConfirm();
      });

      overlay.addEventListener('click', function(e){
        if(e.target === overlay) hideConfirm();
      });
      document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') hideConfirm();
      });

      document.addEventListener('click', function(e){
        const el = e.target.closest('[data-confirm]');
        if(!el) return;
        e.preventDefault();
        const text = el.getAttribute('data-confirm') || '¿Estás seguro?';
        const form = el.closest('form');
        showConfirm(text, function(){
          if(form) form.submit();
          else {
            const a = el.closest('a');
            if(a && a.href) window.location.href = a.href;
            else el.click();
          }
        });
      }, true);
    })();
});
</script>
@endsection
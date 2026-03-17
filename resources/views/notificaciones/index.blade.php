@extends('layouts.app')

@section('title','Notificaciones')

@section('content')
@php
    $current = auth()->user() ?? null;
    $layoutPreviewMode = ($current && ($current->rol ?? '') === 'admin') ? session('layout_preview_as', 'admin') : 'user';
    $isAdmin = ($current && ($current->rol === 'admin')) && $layoutPreviewMode !== 'user';
    use Carbon\Carbon;
    Carbon::setLocale('es');
    $estadoOrder = ['cerrada' => 0, 'abierta' => 1, 'vista' => 2];
    $tmp = $notificaciones->sortByDesc('fecha_creacion');
    $notificaciones_sorted = $tmp->sortBy(function($n) use ($estadoOrder) { return $estadoOrder[$n->estado] ?? 99; })->values();
@endphp

<style>
.modal-card { transition: transform .28s, opacity .28s, max-height .28s, padding .28s; transform-origin: top center; opacity:1; max-height:1200px; overflow:hidden; }
.modal-card.collapsed { transform: scaleY(.98); opacity:0; max-height:0; padding-top:0; padding-bottom:0; overflow:hidden; }
.list-card{background:#fff;border-radius:10px;padding:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);margin-bottom:12px;overflow-x:auto}
.btn-edit{background:linear-gradient(90deg,#6366f1,#06b6d4);color:#fff;padding:6px 8px;border-radius:8px;border:0;font-weight:700;margin-right:6px;cursor:pointer}

.action-btn.edit{background:linear-gradient(90deg,#3b82f6,#06b6d4);color:#fff;padding:6px 10px;border-radius:8px;border:0;font-weight:700;cursor:pointer}
.action-btn.delete{background:linear-gradient(90deg,#ef4444,#f97316);color:#fff;padding:6px 10px;border-radius:8px;border:0;font-weight:700;cursor:pointer}
.btn-group{display:inline-flex;gap:8px;align-items:center}
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
                                <div class="btn-group">
                                  <button
                                    class="action-btn edit"
                                    data-edit
                                    data-id="{{ $n->id }}"
                                    data-usuario_id="{{ $n->usuario_id ?? '' }}"
                                    data-estado="{{ $n->estado ?? 'cerrada' }}"
                                    data-tipo="{{ $n->tipo ?? 'prueba' }}"
                                    data-ruta="{{ e($n->ruta) }}"
                                    data-descripcion="{{ e($n->descripcion) }}"
                                    data-fecha_visto="{{ $n->fecha_visto ?? '' }}"
                                    data-update-url="{{ url('/notificaciones/'.$n->id) }}"
                                    type="button"
                                  >Editar</button>

                                  <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                      @csrf @method('DELETE')
                                      <button class="action-btn delete" type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?">Eliminar</button>
                                  </form>
                                </div>
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
                                <div class="btn-group">
                                  <button
                                    class="action-btn edit"
                                    data-edit
                                    data-id="{{ $n->id }}"
                                    data-usuario_id="{{ $n->usuario_id ?? '' }}"
                                    data-estado="{{ $n->estado ?? 'abierta' }}"
                                    data-tipo="{{ $n->tipo ?? 'prueba' }}"
                                    data-ruta="{{ e($n->ruta) }}"
                                    data-descripcion="{{ e($n->descripcion) }}"
                                    data-fecha_visto="{{ $n->fecha_visto ?? '' }}"
                                    data-update-url="{{ url('/notificaciones/'.$n->id) }}"
                                    type="button"
                                  >Editar</button>

                                  <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                      @csrf @method('DELETE')
                                      <button class="action-btn delete" type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?">Eliminar</button>
                                  </form>
                                </div>
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
                                <div class="btn-group">
                                  <button
                                    class="action-btn edit"
                                    data-edit
                                    data-id="{{ $n->id }}"
                                    data-usuario_id="{{ $n->usuario_id ?? '' }}"
                                    data-estado="{{ $n->estado ?? 'vista' }}"
                                    data-tipo="{{ $n->tipo ?? 'prueba' }}"
                                    data-ruta="{{ e($n->ruta) }}"
                                    data-descripcion="{{ e($n->descripcion) }}"
                                    data-fecha_visto="{{ $n->fecha_visto ?? '' }}"
                                    data-update-url="{{ url('/notificaciones/'.$n->id) }}"
                                    type="button"
                                  >Editar</button>

                                  <form action="{{ url('/notificaciones/'.$n->id) }}" method="POST" style="display:inline">
                                      @csrf @method('DELETE')
                                      <button class="action-btn delete" type="submit" data-confirm="¿Eliminar notificación #{{ $n->id }}?">Eliminar</button>
                                  </form>
                                </div>
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

<div id="modal-new" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;z-index:1200;">
    <button class="modal-close" data-close>✕</button>
    <h3>Nueva notificación</h3>

    <form id="form-new" method="POST" action="{{ url('/notificaciones') }}" class="form">
      @csrf
      <label class="field"><span class="label-text">Para (usuario)</span>
        <select name="usuario_id">
          <option value="">Todos</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }} ({{ $u->email }})</option>
          @endforeach
        </select>
      </label>

      <label class="field"><span class="label-text">Estado</span>
        <select name="estado" required>
          <option value="cerrada">cerrada</option>
          <option value="abierta">abierta</option>
          <option value="vista">vista</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Tipo</span>
        <select name="tipo" required>
          <option value="prueba">prueba</option>
          <option value="aprobada">aprobada</option>
          <option value="rechazada">rechazada</option>
          <option value="otra">otra</option>
          <option value="info">info</option>
          <option value="confirmacion">confirmacion</option>
          <option value="pago">pago</option>
          <option value="alerta">alerta</option>
          <option value="mantenimiento">mantenimiento</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Ruta (opcional)</span><input name="ruta" type="text" /></label>
      <label class="field"><span class="label-text">Descripción</span><textarea name="descripcion" rows="4" required></textarea></label>
      <label class="field"><span class="label-text">Fecha visto (opcional)</span><input name="fecha_visto" type="datetime-local" /></label>

      <div class="actions" style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Crear</button>
        <button type="button" class="btn btn-danger" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-edit" class="modal" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-backdrop" data-close style="position:absolute;inset:0;background:rgba(2,6,23,0.45);z-index:1000;"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;z-index:1200;">
    <button class="modal-close" data-close>✕</button>
    <h3>Editar notificación</h3>

    <form id="form-edit" method="POST" action="#" class="form">
      @csrf
      @method('PUT')
      <input type="hidden" name="id" id="e-id" />
      <label class="field"><span class="label-text">Para (usuario)</span>
        <select id="e-usuario_id" name="usuario_id">
          <option value="">Todos</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }} ({{ $u->email }})</option>
          @endforeach
        </select>
      </label>

      <label class="field"><span class="label-text">Estado</span>
        <select id="e-estado" name="estado" required>
          <option value="cerrada">cerrada</option>
          <option value="abierta">abierta</option>
          <option value="vista">vista</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Tipo</span>
        <select id="e-tipo" name="tipo" required>
          <option value="prueba">prueba</option>
          <option value="aprobada">aprobada</option>
          <option value="rechazada">rechazada</option>
          <option value="otra">otra</option>
          <option value="info">info</option>
          <option value="confirmacion">confirmacion</option>
          <option value="pago">pago</option>
          <option value="alerta">alerta</option>
          <option value="mantenimiento">mantenimiento</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Ruta (opcional)</span><input id="e-ruta" name="ruta" type="text" /></label>
      <label class="field"><span class="label-text">Descripción</span><textarea id="e-descripcion" name="descripcion" rows="4"></textarea></label>
      <label class="field"><span class="label-text">Fecha visto (opcional)</span><input id="e-fecha_visto" name="fecha_visto" type="datetime-local" /></label>

      <div class="actions" style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Guardar</button>
        <button type="button" class="btn btn-danger" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="confirm-overlay" class="confirm-overlay" aria-hidden="true" style="display:none;align-items:center;justify-content:center;">
  <div class="confirm-card" role="dialog" aria-modal="true" aria-labelledby="confirm-title" style="background:#fff;padding:14px;border-radius:12px;box-shadow:0 8px 28px rgba(15,23,42,0.06);width:clamp(280px,420px,520px);text-align:left;">
    <h3 id="confirm-title" class="confirm-title" style="margin:0 0 8px 0;font-weight:700;font-size:1.05rem;">Confirmar eliminación</h3>
    <p id="confirm-msg" class="confirm-msg" style="color:#6b7280;margin-bottom:12px;font-size:0.98rem;">¿Estás seguro?</p>
    <div class="confirm-actions" style="display:flex;gap:.5rem;justify-content:flex-end;">
      <button type="button" id="confirm-cancel" class="btn btn-alt">Cancelar</button>
      <button type="button" id="confirm-ok" class="btn btn-danger">Eliminar</button>
    </div>
  </div>
</div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function(){
  function show(modal){ if(!modal) return; modal.setAttribute('aria-hidden','false'); modal.style.display = 'flex'; setTimeout(()=> modal.classList.add('open'),20); }
  function hide(modal){ if(!modal) return; modal.setAttribute('aria-hidden','true'); modal.classList.remove('open'); setTimeout(()=> modal.style.display = 'none',180); }

  const modalNew = document.getElementById('modal-new');
  const modalEdit = document.getElementById('modal-edit');
  const btnNew = document.getElementById('btn-new');

  if (btnNew) btnNew.addEventListener('click', function(){ show(modalNew); });

  document.querySelectorAll('.modal [data-close]').forEach(el => {
    el.addEventListener('click', function(e){
      e.stopPropagation();
      const modal = this.closest('.modal');
      hide(modal);
    });
  });

  document.querySelectorAll('.modal').forEach(modal => {
    const panel = modal.querySelector('.modal-panel');
    if (!modal) return;
    modal.addEventListener('click', function(e){
      if (!panel || !panel.contains(e.target)) {
        hide(modal);
      }
    });
    if (panel) panel.addEventListener('click', function(e){ e.stopPropagation(); });
  });

  document.querySelectorAll('[data-edit]').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id || '';
      const usuario_id = this.dataset.usuario_id || '';
      const estado = this.dataset.estado || 'cerrada';
      const tipo = this.dataset.tipo || 'prueba';
      const ruta = this.dataset.ruta || '';
      const descripcion = this.dataset.descripcion || '';
      const fecha_visto = this.dataset.fecha_visto || '';
      const updateUrl = this.dataset.updateUrl || '#';

      document.getElementById('e-id').value = id;
      document.getElementById('e-usuario_id').value = usuario_id;
      document.getElementById('e-estado').value = estado;
      document.getElementById('e-tipo').value = tipo;
      document.getElementById('e-ruta').value = ruta;
      document.getElementById('e-descripcion').value = descripcion;

      const eFecha = document.getElementById('e-fecha_visto');
      if (fecha_visto) {
        try {
          const d = new Date(fecha_visto);
          const pad = (n)=> String(n).padStart(2,'0');
          const yyyy = d.getFullYear();
          const mm = pad(d.getMonth()+1);
          const dd = pad(d.getDate());
          const hh = pad(d.getHours());
          const mi = pad(d.getMinutes());
          eFecha.value = `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
        } catch(e) { eFecha.value = ''; }
      } else eFecha.value = '';

      const formEdit = document.getElementById('form-edit');
      formEdit.action = updateUrl;
      show(modalEdit);
    });
  });

  (function(){
    let pendingForm = null;
    const overlay = document.getElementById('confirm-overlay');
    const msgEl = document.getElementById('confirm-msg');
    const btnOk = document.getElementById('confirm-ok');
    const btnCancel = document.getElementById('confirm-cancel');

    function showConfirm(text, form){ msgEl.textContent = text || '¿Estás seguro?'; overlay.style.display = 'flex'; pendingForm = form; btnCancel.focus(); }
    function hideConfirm(){ overlay.style.display = 'none'; pendingForm = null; }

    document.addEventListener('click', function(e){
      const el = e.target.closest('[data-confirm]');
      if(!el) return;
      e.preventDefault();
      const text = el.getAttribute('data-confirm') || '¿Estás seguro?';
      const form = el.closest('form');
      showConfirm(text, form);
    }, true);

    btnCancel.addEventListener('click', hideConfirm);
    btnOk.addEventListener('click', function(){ if(pendingForm){ pendingForm.submit(); } hideConfirm(); });
    overlay.addEventListener('click', function(e){ if(e.target === overlay) hideConfirm(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') hideConfirm(); });
  })();
});
</script>
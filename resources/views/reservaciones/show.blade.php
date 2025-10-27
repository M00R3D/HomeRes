@extends('layouts.app')

@section('title','Reservación #' . ($r->id ?? ''))

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0">Reservación #{{ $r->id }}</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('reservaciones.index') }}" class="link-button">Volver a la lista</a>
      <button id="btn-edit" class="btn-edit" type="button">Editar</button>
    </div>
  </div>

  @php
    $imgPath = $r->propiedad->ruta_img ?? ($r->ruta_img ?? null);
    use App\Models\Comentario;
    $comentarios = Comentario::with('user')->where('reservacion_id', $r->id)->orderByDesc('fecha_creacion')->get();
  @endphp

  <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;">
    <div style="flex:0 0 240px;">
      @if($imgPath)
        <img src="{{ asset($imgPath) }}" alt="Imagen propiedad" style="width:240px;height:160px;object-fit:cover;border-radius:8px;box-shadow:0 8px 20px rgba(2,6,23,0.06);">
      @else
        <div style="width:240px;height:160px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:8px;color:#9ca3af;">Sin imagen</div>
      @endif
    </div>

    <div style="flex:1;min-width:260px;">
      <h3 style="margin:0 0 8px 0;">Comentarios ({{ $comentarios->count() }})</h3>
      @if($comentarios->isEmpty())
        <div style="color:#6b7280;">No hay comentarios para esta reservación.</div>
      @else
        <div style="display:flex;flex-direction:column;gap:8px;">
          @foreach($comentarios as $c)
            <div style="background:#fff;padding:10px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,0.04);">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div style="font-weight:700;">{{ $c->user->nombre ?? 'Usuario' }} {{ $c->user->apellido ?? '' }}</div>
                <div style="font-size:0.9rem;color:#6b7280;">{{ \Carbon\Carbon::parse($c->fecha_creacion ?? $c->created_at ?? now())->format('d M Y H:i') }}</div>
              </div>
              <div style="margin-top:6px;color:#374151;">
                <div style="font-weight:700;margin-bottom:6px;">Calificación: {{ $c->calificacion ?? '-' }}/5</div>
                <div style="white-space:pre-wrap;">{{ $c->comentario }}</div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 28px rgba(2,6,23,0.06);">
    <dl style="display:grid;grid-template-columns:150px 1fr;gap:8px 18px;">
      <dt class="small">Propiedad</dt><dd>{{ $r->propiedad->nombre ?? ($r->propiedad_nombre ?? '-') }}</dd>
      <dt class="small">Cliente</dt><dd>{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</dd>
      <dt class="small">Check-in</dt><dd>{{ \Carbon\Carbon::parse($r->check_in)->format('d M Y') }}</dd>
      <dt class="small">Check-out</dt><dd>{{ \Carbon\Carbon::parse($r->check_out)->format('d M Y') }}</dd>
      <dt class="small">Personas</dt><dd>{{ $r->num_personas }}</dd>
      <dt class="small">Total</dt><dd>${{ number_format($r->total ?? 0, 2, ',', '.') }}</dd>
      <dt class="small">Estado</dt>
      <dd>
        @if(($r->estado ?? '') === 'pendiente') <span class="badge badge-pendiente">Pendiente</span>
        @elseif(($r->estado ?? '') === 'confirmada') <span class="badge badge-confirmada">Confirmada</span>
        @elseif(($r->estado ?? '') === 'cancelada') <span class="badge badge-cancelada">Cancelada</span>
        @else <span class="badge">{{ $r->estado }}</span>
        @endif
      </dd>
      <dt class="small">Nota</dt><dd>{{ $r->nota ?? '-' }}</dd>
      <dt class="small">Creada</dt><dd>{{ $r->created_at }}</dd>
      <dt class="small">Actualizada</dt><dd>{{ $r->updated_at }}</dd>
    </dl>
  </div>
</div>

<div id="rv-modal" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.45);align-items:center;justify-content:center;z-index:9999;padding:12px;">
  <div style="background:#fff;border-radius:10px;padding:12px;max-width:680px;width:100%;max-height:90vh;overflow:auto;">
    <button id="rv-close" style="float:right;border:0;background:transparent;font-size:20px;">✕</button>
    <h2 id="rv-title">Editar reservación #{{ $r->id }}</h2>

    <form id="rv-form" method="POST" action="{{ route('reservaciones.update', $r->id) }}">
      @csrf
      <input type="hidden" name="_method" value="PUT">
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:260px;">
          <label class="small">Propiedad</label>
          <select id="rv-propiedad" name="propiedad_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar --</option>
            @foreach($propiedades as $p)
              <option value="{{ $p->id }}" {{ ($r->propiedad_id == $p->id) ? 'selected' : '' }}>{{ $p->nombre }}</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Check-in</label>
          <input id="rv-checkin" name="check_in" type="date" value="{{ $r->check_in }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Check-out</label>
          <input id="rv-checkout" name="check_out" type="date" value="{{ $r->check_out }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
        </div>

        <div style="flex:1;min-width:260px;">
          <label class="small">Usuario</label>
          <select id="rv-usuario" name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar cliente --</option>
            @foreach($usuarios as $u)
              <option value="{{ $u->id }}" {{ ($r->usuario_id == $u->id) ? 'selected' : '' }}>{{ $u->nombre }} {{ $u->apellido }}</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Número de personas</label>
          <input id="rv-num" name="num_personas" type="number" min="1" value="{{ $r->num_personas }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Total</label>
          <input id="rv-total" name="total" type="number" step="0.01" value="{{ $r->total }}" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Estado</label>
          <select id="rv-estado" name="estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="pendiente" {{ $r->estado==='pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="confirmada" {{ $r->estado==='confirmada' ? 'selected' : '' }}>Confirmada</option>
            <option value="cancelada" {{ $r->estado==='cancelada' ? 'selected' : '' }}>Cancelada</option>
            <option value="completada" {{ $r->estado==='completada' ? 'selected' : '' }}>Completada</option>
          </select>

          <label class="small" style="margin-top:8px;">Nota</label>
          <textarea id="rv-nota" name="nota" rows="3" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">{{ $r->nota }}</textarea>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
        <button id="rv-save" type="submit" class="btn-edit">Guardar</button>
        <button type="button" id="rv-cancel" class="btn btn-alt" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;">Cancelar</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){

  const modal = document.getElementById('rv-modal');
  const btnEdit = document.getElementById('btn-edit');
  const btnClose = document.getElementById('rv-close');
  const btnCancel = document.getElementById('rv-cancel');
  const form = document.getElementById('rv-form');

  function show(){ if(modal){ modal.style.display = 'flex'; modal.setAttribute('aria-hidden','false'); } }
  function hide(){ if(modal){ modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); } }

  btnEdit?.addEventListener('click', function(){ show(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  btnClose?.addEventListener('click', hide);
  btnCancel?.addEventListener('click', hide);

  form?.addEventListener('submit', async function(e){
    e.preventDefault();
    const url = form.getAttribute('action');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const fd = new FormData(form);
    fd.append('_method', 'PUT');

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: fd,
        credentials: 'same-origin'
      });
      const ct = res.headers.get('content-type') || '';
      const data = ct.includes('application/json') ? await res.json().catch(()=>({})) : {};
      if (!res.ok) {
        const msg = (data && data.message) ? data.message : ('Error ' + res.status);
        alert('No se pudo guardar: ' + msg);
        return;
      }
      hide();
      setTimeout(()=> location.reload(), 180);
    } catch (err) {
      console.error(err);
      alert('Error de red al guardar');
    }
  });
});
</script>
@endsection
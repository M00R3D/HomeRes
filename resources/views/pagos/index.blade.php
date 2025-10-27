@extends('layouts.app')

@section('title','Pagos')

@section('content')
<style>
.card { background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06); }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; vertical-align:middle; }
.btn { background: linear-gradient(90deg,#6366f1,#06b6d4); color:#fff; padding:8px 12px; border-radius:8px; border:0; font-weight:700; cursor:pointer; }
.btn-alt { background:#f3f4f6; color:#0f172a; padding:8px 10px; border-radius:8px; border:0; cursor:pointer; }
.link-button { background:transparent;border:0;color:#2563eb;cursor:pointer;padding:6px 8px;font-weight:700;border-radius:6px; }
.link-button.danger { color:#ef4444; }
.small { font-size:0.9rem;color:#6b7280; }
.input-inline { display:flex; gap:8px; align-items:center; }
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999; }
.modal .modal-backdrop { position:absolute; inset:0; background:rgba(2,6,23,0.45); }
.modal-panel { position:relative; z-index:1200; background:#fff; border-radius:12px; padding:18px; width:560px; max-width:calc(100% - 32px); box-shadow:0 18px 40px rgba(2,6,23,0.08); }
.field { display:block; margin-bottom:12px; }
.field .label-text { display:block; font-weight:700; margin-bottom:6px; color:#111827; }
.input-inline input, .input-inline select { padding:8px 10px; border-radius:8px; border:1px solid #e6e9ee; background:#fff; }
.form-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }
.delete-modal-panel { width:460px; max-width:calc(100% - 32px); padding:18px; border-radius:12px; background:#fff; box-shadow:0 18px 40px rgba(2,6,23,0.08); }
#modal-delete .modal-backdrop { z-index:1000; }
#modal-delete .delete-modal-panel { position:relative; z-index:1200; pointer-events:auto; }

.muted { color:#6b7280; }
.row-actions { text-align:right; white-space:nowrap; }
</style>

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">Pagos</h1>
      <div class="small" style="margin-top:6px;">CRUD de pagos vinculados a reservaciones y tarjetas</div>
    </div>
    <div>
      <button id="btn-new" class="btn">Nuevo pago</button>
    </div>
  </div>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      {{ session('success') }}
    </div>
  @endif

  <div class="card">
    <table class="table" style="min-width:900px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Reservación</th>
          <th>Tarjeta</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Estado</th>
          <th>Fecha pago</th>
          <th style="text-align:right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>
              @if($p->reservation)
                #{{ $p->reservation->id }} — {{ $p->reservation->user->nombre ?? '-' }}
              @else
                #{{ $p->reservacion_id ?? '-' }}
              @endif
            </td>
            <td>
              @if($p->tarjeta)
                {{ $p->tarjeta->nombre ?? 'Tarjeta' }} ••••{{ substr($p->tarjeta->numero_tarjeta, -4) }}
              @else
                -
              @endif
            </td>
            <td style="font-weight:800">${{ number_format($p->monto,2,',','.') }}</td>
            <td>{{ $p->metodo_pago }}</td>
            <td>{{ ucfirst($p->estado) }}</td>
            <td>{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d M Y H:i') : '-' }}</td>
            <td class="row-actions">
              <form method="POST" action="{{ route('pagos.destroy',$p->id) }}" style="display:inline;" data-payment-id="{{ $p->id }}">
                @csrf
                @method('DELETE')

                <button type="button"
                        class="link-button"
                        data-edit
                        data-id="{{ $p->id }}"
                        data-reservacion="{{ $p->reservacion_id }}"
                        data-tarjeta="{{ $p->tarjeta_id }}"
                        data-monto="{{ number_format($p->monto,2,'.','') }}"
                        data-metodo="{{ $p->metodo_pago }}"
                        data-estado="{{ $p->estado }}"
                        data-fecha="{{ $p->fecha_pago ?? '' }}"
                        data-update-url="{{ route('pagos.update',$p->id) }}">Editar</button>

                <button type="button" class="link-button" data-open-delete data-id="{{ $p->id }}" data-monto="{{ number_format($p->monto,2,'.','') }}" data-reservacion="{{ $p->reservacion_id }}" data-tarjeta="{{ $p->tarjeta_id }}">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="small">No hay pagos.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:12px;">{{ $payments->links() }}</div>
  </div>
</div>
<div id="modal-pago" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3 id="modal-pago-title">Nuevo pago</h3>

    <form id="form-pago" method="POST" action="{{ route('pagos.store') }}">
      @csrf
      <label class="field"><span class="label-text">Reservación</span>
        <select name="reservacion_id" id="p-reservacion" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee">
          <option value="">-- ninguna --</option>
          @foreach($reservaciones as $r)
            <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->user->nombre ?? '-' }} — {{ $r->propiedad->nombre ?? '' }}</option>
          @endforeach
        </select>
      </label>

      <label class="field"><span class="label-text">Tarjeta (opcional)</span>
        <select name="tarjeta_id" id="p-tarjeta" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee">
          <option value="">-- ninguna --</option>
          @foreach($tarjetas as $t)
            <option value="{{ $t->id }}">{{ $t->nombre }} ••••{{ substr($t->numero_tarjeta, -4) }}</option>
          @endforeach
        </select>
      </label>

      <label class="field"><span class="label-text">Monto</span>
        <div class="input-inline">
          <div style="font-weight:700;color:#6b7280;">$</div>
          <input id="p-pesos" inputmode="numeric" placeholder="0" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;width:120px;text-align:right" />
          <div style="color:#6b7280">.</div>
          <input id="p-centavos" inputmode="numeric" placeholder="00" maxlength="2" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;width:64px;text-align:center" />
        </div>
        <input type="hidden" name="monto" id="p-monto-hidden" />
      </label>

      <label class="field"><span class="label-text">Método</span>
        <select name="metodo_pago" id="p-metodo" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee">
          <option value="tarjeta">tarjeta</option>
          <option value="efectivo">efectivo</option>
          <option value="transferencia">transferencia</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Estado</span>
        <select name="estado" id="p-estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee">
          <option value="pendiente">pendiente</option>
          <option value="pagado">pagado</option>
          <option value="cancelado">cancelado</option>
        </select>
      </label>

      <label class="field"><span class="label-text">Fecha pago (opcional)</span>
        <input id="p-fecha" name="fecha_pago" type="datetime-local" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
      </label>

      <div class="form-actions">
        <button class="btn" type="submit">Guardar</button>
        <button type="button" class="btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>
<div id="modal-delete" class="modal" aria-hidden="true">
  <div class="modal-backdrop"></div>
  <div class="delete-modal-panel" role="dialog" aria-modal="true">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3 id="delete-title">Confirmar eliminación</h3>
    <p class="muted" id="delete-desc">Vas a eliminar este pago. Esta acción no se puede deshacer.</p>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;">
      <div>
        <div style="font-weight:700;">Reservación</div>
        <div id="delete-reservacion" class="muted">-</div>
      </div>
      <div style="text-align:right;">
        <div class="muted">Monto</div>
        <div class="delete-amount" id="delete-monto">$0.00</div>
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
      <button type="button" id="delete-cancel" class="btn-alt" data-close>Cancelar</button>
      <button type="button" id="delete-confirm" class="btn" style="background:linear-gradient(90deg,#ef4444,#f97316);">Eliminar</button>
    </div>
  </div>
  <form id="delete-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
  </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const modal = document.getElementById('modal-pago');
  const form = document.getElementById('form-pago');
  const deleteModal = document.getElementById('modal-delete');
  let deleteTargetForm = null;

  function show(m){ if(!m) return; m.setAttribute('aria-hidden','false'); m.style.display='flex'; setTimeout(()=> m.classList.add('open'),20); }
  function hide(m){ if(!m) return; m.setAttribute('aria-hidden','true'); m.classList.remove('open'); setTimeout(()=> m.style.display='none',180); }
  document.getElementById('btn-new')?.addEventListener('click', function(){
    document.getElementById('modal-pago-title').textContent = 'Nuevo pago';
    form.action = "{{ route('pagos.store') }}";
    form.querySelector('[name="_method"]')?.remove();
    form.reset();
    document.getElementById('p-pesos').value = '';
    document.getElementById('p-centavos').value = '00';
    document.getElementById('p-monto-hidden').value = '';
    show(modal);
  });
  document.querySelectorAll('[data-edit]').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const id = this.dataset.id;
      form.action = this.dataset.updateUrl || ('/pagos/' + id);
      form.querySelector('[name="_method"]')?.remove();
      const method = document.createElement('input'); method.type='hidden'; method.name='_method'; method.value='PUT'; form.appendChild(method);

      document.getElementById('modal-pago-title').textContent = 'Editar pago #' + id;
      document.getElementById('p-reservacion').value = this.dataset.reservacion || '';
      document.getElementById('p-tarjeta').value = this.dataset.tarjeta || '';

      const monto = String(this.dataset.monto || '0');
      const parts = monto.indexOf('.') > -1 ? monto.split('.') : [monto,'00'];
      document.getElementById('p-pesos').value = parts[0] || '0';
      document.getElementById('p-centavos').value = (parts[1] || '00').padEnd(2,'0').slice(0,2);

      document.getElementById('p-metodo').value = this.dataset.metodo || 'tarjeta';
      document.getElementById('p-estado').value = this.dataset.estado || 'pendiente';

      if (this.dataset.fecha) {
        try {
          const dt = new Date(this.dataset.fecha);
          const iso = dt.toISOString();
          document.getElementById('p-fecha').value = iso.slice(0,16);
        } catch (err) {
          document.getElementById('p-fecha').value = '';
        }
      } else {
        document.getElementById('p-fecha').value = '';
      }

      show(modal);
    });
  });

  form?.addEventListener('submit', function(e){
    const pesos = (document.getElementById('p-pesos').value || '0').replace(/[^\d]/g,'') || '0';
    let cents = (document.getElementById('p-centavos').value || '00').replace(/\D/g,'');
    cents = (cents + '00').slice(0,2);
    document.getElementById('p-monto-hidden').value = Number(pesos + '.' + cents).toFixed(2);
  });

  document.querySelectorAll('[data-open-delete]').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const id = this.dataset.id;
      const monto = Number(this.dataset.monto || 0).toFixed(2);
      const reserv = this.dataset.reservacion || '-';
      const tarjeta = this.dataset.tarjeta || null;

      document.getElementById('delete-reservacion').textContent = reserv ? ('#' + reserv + (tarjeta ? ' · tarjeta: ' + tarjeta : '')) : '-';
      document.getElementById('delete-monto').textContent = '$' + Number(monto).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

      const deleteForm = document.getElementById('delete-form');
      if (deleteForm) {
        deleteForm.action = '/pagos/' + encodeURIComponent(id);
      }

      deleteTargetForm = document.querySelector('form[data-payment-id="'+id+'"]') || null;

      show(deleteModal);
    });
  });

  document.getElementById('delete-confirm')?.addEventListener('click', function(){
    const deleteForm = document.getElementById('delete-form');

    if (deleteForm && deleteForm.action) {
      deleteForm.submit();
      return;
    }

    if (deleteTargetForm) {
      deleteTargetForm.submit();
      return;
    }

    hide(deleteModal);
  });

  document.querySelectorAll('#modal-delete [data-close]').forEach(el=>{
    el.addEventListener('click', function(){ hide(deleteModal); });
  });

  document.querySelectorAll('[data-close]').forEach(el=> el.addEventListener('click', function(){ hide(this.closest('.modal')); }));

  document.addEventListener('click', function(e){
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    const msg = el.getAttribute('data-confirm') || '¿Estás seguro?';
    const pId = el.closest('form')?.getAttribute('data-payment-id');
    if (pId) {
      const btn = document.querySelector('[data-open-delete][data-id="'+pId+'"]');
      if (btn) { btn.click(); return; }
    }
    if (!confirm(msg)) return;
    const form = el.closest('form');
    if (form) form.submit();
  });
});
</script>
@endsection
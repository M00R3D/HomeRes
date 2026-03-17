@extends('layouts.app')

@section('title','Reservación #' . ($r->id ?? ''))

@section('content')
<div style="max-width:980px;margin:20px auto;padding:12px;">
  @php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = ($currentUser && ($currentUser->rol ?? '') === 'admin');
  @endphp
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0">Reservación #{{ $r->id }}</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('reservaciones.index') }}" class="link-button">Volver a la lista</a>
      @if($isAdmin)
        <button id="btn-edit" class="btn-edit" type="button">Editar</button>
      @endif

      @php $canPay = (($r->estado ?? '') === 'pendiente' || ($r->estado ?? '') === 'confirmada') && (($r->estado_pago ?? '') !== 'pagado'); @endphp
      @if($canPay && ( $isAdmin || ($currentUser && ($currentUser->id ?? null) === ($r->usuario_id ?? null)) ))
        <a href="{{ route('pagos.form', $r->id) }}" class="action-btn primary">Pagar</a>
      @endif
    </div>

    {{-- Payment status strip (smooth, colorful) --}}
    @php
      $ep = strtolower(trim((string)($r->estado_pago ?? 'pendiente')));
      if ($ep === 'pagado') { $stripBg = 'linear-gradient(90deg,#10b981,#059669)'; $epLabel = 'Pagado'; }
      elseif ($ep === 'pendiente') { $stripBg = 'linear-gradient(90deg,#f59e0b,#f97316)'; $epLabel = 'Pendiente'; }
      elseif ($ep === 'fallido' || $ep === 'failed') { $stripBg = 'linear-gradient(90deg,#ef4444,#dc2626)'; $epLabel = 'Fallido'; }
      elseif ($ep === 'parcial') { $stripBg = 'linear-gradient(90deg,#6366f1,#06b6d4)'; $epLabel = 'Parcial'; }
      else { $stripBg = '#6b7280'; $epLabel = ucfirst($ep ?: 'Pendiente'); }
    @endphp

    <div style="margin-bottom:12px;">
      <div style="height:12px;border-radius:12px;overflow:hidden;background:transparent;box-shadow:0 6px 18px rgba(2,6,23,0.04);transition:all .36s ease;">
        <div style="height:12px;width:100%;background:{{ $stripBg }};transition:background .36s ease;"></div>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:8px;">
        <span style="display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:rgba(0,0,0,0.04);font-weight:800;color:#0f172a;font-size:0.9rem;">Estado pago: <span class="payment-status-badge" data-state="{{ $ep }}" style="margin-left:8px;padding:6px 10px;border-radius:999px;font-weight:900;">{{ $epLabel }}</span></span>
      </div>
    </div>
  </div>

  @php
    $imgPathRaw = $r->propiedad->ruta_img ?? ($r->ruta_img ?? null);
    $thumbUrl = null;
    if (!empty($imgPathRaw)) {
      $ruta = ltrim($imgPathRaw, '/\\');
      $full = public_path($ruta);
      if (is_dir($full)) {
        $files = @scandir($full) ?: [];
        foreach ($files as $f) {
          $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
          if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $thumbUrl = asset($ruta . '/' . $f); break; }
        }
      } elseif (is_file($full)) {
        $thumbUrl = asset($ruta);
      }
    }
    use App\Models\Comentario;
    $comentarios = Comentario::with('user')->where('reservacion_id', $r->id)->orderByDesc('fecha_creacion')->get();
  @endphp

  @php
    $estado = $r->estado ?? '';
    $label = $estado ? ucfirst($estado) : '-';
    if ($estado === 'confirmada') {
      $estadoStyle = 'background:#10b981;color:#ffffff'; 
      $priceColor = '#065f46';
    } elseif ($estado === 'pendiente') {
      $estadoStyle = 'background:#f59e0b;color:#ffffff'; 
      $priceColor = '#92400e';
    } elseif ($estado === 'cancelada') {
      $estadoStyle = 'background:#ef4444;color:#ffffff';
      $priceColor = '#7f1d1d';
    } else {
      $estadoStyle = 'background:#6b7280;color:#ffffff'; 
      $priceColor = '#374151';
    }
  @endphp

  <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;">
    <div style="flex:0 0 40%;max-width:480px;min-width:220px;">
      @if($thumbUrl)
        <div style="width:100%;aspect-ratio:16/9;overflow:hidden;border-radius:8px;box-shadow:0 8px 20px rgba(2,6,23,0.06);">
          <img src="{{ $thumbUrl }}" alt="Imagen propiedad" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
      @else
        <div style="width:100%;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:8px;color:#9ca3af;">Sin imagen</div>
      @endif
    </div>

    <div style="flex:1;min-width:260px;display:flex;flex-direction:column;gap:10px;">
      <div style="background:#fff;padding:12px;border-radius:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);display:flex;flex-direction:column;gap:12px;">
        <div>
          <div style="font-size:1.25rem;font-weight:900;color:#0f172a;">
            {{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}
          </div>
          <div style="color:#6b7280;margin-top:6px;">Cliente de la reservación</div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:0.95rem;color:#6b7280;">Total</div>
            <div>
              <span style="{{ $estadoStyle }};padding:6px 10px;border-radius:999px;font-weight:800;">{{ $label }}</span>
            </div>
          </div>

          <div class="price-amount" data-estado="{{ $estado }}" style="font-size:1.6rem;font-weight:900;">
            ${{ number_format($r->total ?? 0, 2, ',', '.') }}
          </div>

          <div style="background:linear-gradient(180deg,rgba(15,23,42,0.02),rgba(15,23,42,0.01));border-radius:10px;padding:10px;color:#0f172a;box-shadow:inset 0 1px 0 rgba(255,255,255,0.6);">
            <div style="font-weight:800;margin-bottom:6px;">Nota</div>
            <div style="color:#374151;white-space:pre-wrap;">{{ $r->nota ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @php
    use Carbon\Carbon;
    $checkIn = Carbon::parse($r->check_in);
    $checkOut = Carbon::parse($r->check_out);
    $today = Carbon::today();

    $daysArray = [];
    if ($checkIn && $checkOut) {
      $d = $checkIn->copy();
      while ($d->lt($checkOut)) {
        $daysArray[] = $d->copy();
        $d->addDay();
        if (count($daysArray) > 10000) break;
      }
    }
    $totalDays = count($daysArray);
    $maxVisibleDays = 30;
    $showAllDays = $totalDays <= $maxVisibleDays;

    if ($checkIn->greaterThan($today)) {
      $summary = 'Falta ' . $today->diffInDays($checkIn) . ' día' . ($today->diffInDays($checkIn) !== 1 ? 's' : '') . ' para el check-in';
    } elseif ($checkOut->lessThan($today)) {
      $summary = 'El check-out fue hace ' . $checkOut->diffInDays($today) . ' día' . ($checkOut->diffInDays($today) !== 1 ? 's' : '');
    } else {
      $daysToCheckout = $today->lte($checkOut) ? $today->diffInDays($checkOut) : 0;
      $summary = $checkIn->isToday() ? 'Check-in hoy' : ('Check-in ' . ($checkIn->isPast() ? 'hace ' . $checkIn->diffInDays($today) . ' día(s)' : 'en ' . $today->diffInDays($checkIn) . ' día(s)'));
      $summary .= ' · ' . ($daysToCheckout > 0 ? 'Quedan ' . $daysToCheckout . ' día(s) para el check-out' : 'Check-out hoy o pasado');
    }
  @endphp

  <div style="margin-bottom:12px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
      <div style="font-weight:700">Calendario</div>
      <div style="color:#6b7280;font-size:0.95rem;">{{ $summary }}</div>
    </div>

    <style>
      .rv-days { display:flex;gap:8px;flex-wrap:wrap; }
      .rv-day { min-width:64px;flex:1 0 64px;padding:10px;border-radius:8px;text-align:center;background:#f8fafc;border:1px solid #eef2f7;box-shadow:0 4px 12px rgba(2,6,23,0.04); position:relative; }
      .rv-day .date { font-weight:800; font-size:0.95rem; display:block; margin-bottom:6px; }
      .rv-day .label { font-size:0.8rem; color:#374151; }
      .rv-badge { position:absolute; top:8px; right:8px; padding:4px 8px; border-radius:999px; font-size:0.72rem; font-weight:800; color:#fff; }
      .rv-badge.checkin { background:#0ea5e9; } 
      .rv-badge.checkout { background:#fb7185; } 
      .rv-day.past { background:#f3f4f6;color:#6b7280;border-color:#e6e9ee; }
      .rv-day.today { background:linear-gradient(90deg,#e0f2fe,#bae6fd); color:#0c4a6e; border-color:#7dd3fc; }
      .rv-day.upcoming { background:linear-gradient(90deg,#ecfdf5,#bbf7d0); color:#064e3b; border-color:#86efac; }
      @media (max-width:600px){ .rv-day{min-width:56px;padding:8px;font-size:0.9rem} }
    </style>

    <div class="rv-days" role="list" aria-label="Fechas reservación">
      @if($showAllDays)
        @foreach($daysArray as $d)
          @php
            $cls = 'rv-day';
            if ($d->isToday()) $cls .= ' today';
            elseif ($d->lt(Carbon::today())) $cls .= ' past';
          @endphp
          <div class="{{ $cls }}" title="{{ $d->toDateString() }}">
            <div class="date">{{ $d->format('d') }}</div>
            <div style="font-size:11px;color:#6b7280;">{{ $d->format('M') }}</div>
          </div>
        @endforeach
      @else
        {{-- demasiados días: mostrar compactado (check-in → check-out) --}}
        <div style="display:flex;gap:10px;align-items:center;width:100%;flex-wrap:wrap;">
          <div class="rv-day" title="{{ ($daysArray[0] ?? $checkIn)->toDateString() }}" style="min-width:140px;padding:10px;text-align:center;">
            <div style="font-weight:800">{{ ($daysArray[0] ?? $checkIn)->format('d M Y') }}</div>
            <div class="small">Check-in</div>
          </div>

          <div style="font-weight:900;color:#6b7280;font-size:20px;">→</div>

          <div class="rv-day" title="{{ ($daysArray[$totalDays-1] ?? $checkOut->copy()->subDay())->toDateString() }}" style="min-width:140px;padding:10px;text-align:center;">
            <div style="font-weight:800">{{ ($daysArray[$totalDays-1] ?? $checkOut->copy()->subDay())->format('d M Y') }}</div>
            <div class="small">Check-out</div>
          </div>

          <div class="small-muted" style="margin-left:auto;">Total días: {{ $totalDays }}</div>
        </div>
      @endif
    </div>
  </div>

  @php
    $estado = $r->estado ?? '';
    $label = $estado ? ucfirst($estado) : '-';
    if ($estado === 'confirmada') {
      $estadoStyle = 'background:#10b981;color:#ffffff';
      $priceColor = '#065f46';
    } elseif ($estado === 'pendiente') {
      $estadoStyle = 'background:#f59e0b;color:#ffffff';
      $priceColor = '#92400e';
    } elseif ($estado === 'cancelada') {
      $estadoStyle = 'background:#ef4444;color:#ffffff'; 
      $priceColor = '#7f1d1d';
    } else {
      $estadoStyle = 'background:#6b7280;color:#ffffff';
      $priceColor = '#374151';
    }
  @endphp

  <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-start;">
    <div style="flex:1;min-width:320px;">
      <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 28px rgba(2,6,23,0.06);">
        <dl style="display:grid;grid-template-columns:150px 1fr;gap:8px 18px;">
          <dt class="small">Propiedad</dt><dd>{{ $r->propiedad->nombre ?? ($r->propiedad_nombre ?? '-') }}</dd>
          <dt class="small">Cliente</dt>
          <dd>
            <div style="font-weight:800;font-size:1.05rem;">{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</div>
          </dd>
          <dt class="small">Check-in</dt>
          <dd>
            <span style="display:inline-flex;align-items:center;gap:8px;">
              <strong>{{ \Carbon\Carbon::parse($r->check_in)->format('d M Y') }}</strong>
              <span style="background:#0ea5e9;color:#fff;padding:4px 8px;border-radius:999px;font-weight:800;font-size:0.8rem;">IN</span>
            </span>
          </dd>
          <dt class="small">Check-out</dt>
          <dd>
            <span style="display:inline-flex;align-items:center;gap:8px;">
              <strong>{{ \Carbon\Carbon::parse($r->check_out)->format('d M Y') }}</strong>
              <span style="background:#fb7185;color:#fff;padding:4px 8px;border-radius:999px;font-weight:800;font-size:0.8rem;">OUT</span>
            </span>
          </dd>
          <dt class="small">Personas</dt><dd>{{ $r->num_personas }}</dd>
          <dt class="small">Creada</dt><dd>{{ $r->created_at }}</dd>
          <dt class="small">Actualizada</dt><dd>{{ $r->updated_at }}</dd>
        </dl>
      </div>
    </div>

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
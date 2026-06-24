@extends('layouts.app')

@section('title','Pagar reservación')
@php
  use App\Models\Comentario;
  use Illuminate\Support\Str;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp

@section('content')
<style>
  /* inline helper styles for pagar view */
  .help-inline{margin-top:12px;display:inline-flex;align-items:center;gap:8px}
  .help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,0.35);color:#1d4ed8;background:rgba(59,130,246,0.08);font-weight:700;line-height:1;cursor:pointer}
  .help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem}
  .help-viewer{position:fixed;inset:0;display:none;z-index:9999}
  .help-viewer.open{display:block}
  .help-viewer-backdrop{position:absolute;inset:0;background:rgba(15,23,42,0.42);backdrop-filter:blur(2px)}
  .help-viewer-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(920px,94vw);height:min(84vh,760px);background:rgba(255,255,255,0.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,0.25);border:1px solid rgba(148,163,184,0.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
  .help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,0.3);background:linear-gradient(90deg,rgba(248,250,252,0.95),rgba(241,245,249,0.95))}
  .help-controls{display:inline-flex;gap:6px}
  .help-btn{border:1px solid rgba(148,163,184,0.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
  .help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
  .help-stage.dragging{cursor:grabbing}
  .help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear}
  .help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,0.86);border:1px solid rgba(148,163,184,0.4);padding:4px 8px;border-radius:999px}
</style>

<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Pagar reservación</h1>

  <div class="help-inline">
    <button type="button" class="help-q" id="pagar-help-open-btn" aria-label="Abrir ayuda">?</button>
    <a href="javascript:void(0)" id="pagar-help-open-link" class="help-link">¿Necesitas ayuda para usar esta página?</a>
  </div>

  @if($currentUser && !$isAdmin && !empty($currentUser->bloqueo_tarjetas))
    <div style="background:#fee2e2;color:#7f1d1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      Tu cuenta está bloqueada para operaciones con tarjetas. Contacta a soporte@ejemplo.com para pedir que un administrador desbloquee tu cuenta.
    </div>
  @endif

  @if($errors->any())
    <div style="background:#fee2e2;padding:10px;border-radius:8px;margin-bottom:12px;color:#991b1b;">
      <ul>
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('pagos.procesar') }}">
    @csrf
    @php
      $currentUser = auth()->user();
      $isAdmin = $currentUser && ($currentUser->rol ?? '') === 'admin';
      $total = $reservacion->total ?? 0;
    @endphp
    <input type="hidden" name="reservacion_id" value="{{ $reservacion->id ?? old('reservacion_id') }}">
    <input type="hidden" name="monto" value="{{ $total }}">
    <input type="hidden" name="usuario_id" value="{{ $reservacion->usuario_id ?? (auth()->id() ?? old('usuario_id')) }}">
    <label class="small">Tarjeta</label>
    @if(!$isAdmin)
      @if(($tarjetas ?? collect())->isEmpty())
        <div style="margin-bottom:8px;color:#6b7280;">No tienes una tarjeta asociada. <a href="{{ route('tarjetas.create') }}">Crear tarjeta</a> o <a href="{{ route('tarjetas.index') }}">Agregar tarjeta</a>.</div>
        <input type="hidden" name="tarjeta_id" value="">
      @else
        @php $t = $tarjetas->first(); @endphp
        <div style="margin-bottom:8px;padding:10px;border-radius:8px;border:1px solid #e5e7eb;">{{ $t->nombre }} · ****{{ substr($t->numero_tarjeta ?? '', -4) }} · Saldo: ${{ number_format($t->saldo,2) }}</div>
        <input type="hidden" name="tarjeta_id" id="tarjeta_id" value="{{ $t->id }}">
      @endif
    @else
      <select name="tarjeta_id" id="tarjeta_select" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:8px;">
        <option value="">-- seleccionar tarjeta --</option>
        @foreach($tarjetas ?? [] as $t)
            <option value="{{ $t->id }}" data-saldo="{{ $t->saldo }}" data-cvv="{{ $t->cvv ?? '' }}" {{ (string)old('tarjeta_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->nombre }} · ****{{ substr($t->numero_tarjeta ?? '', -4) }} · Saldo: ${{ number_format($t->saldo,2) }}</option>
          @endforeach
      </select>
    @endif

    <div id="tarjeta-warning" style="display:none;color:#b91c1c;margin-bottom:8px;font-weight:700;">Saldo insuficiente en la tarjeta seleccionada.</div>

    <label class="small">Método de pago</label>
    <div style="background:#eef2ff;padding:10px;border-radius:8px;margin-bottom:8px;color:#0f172a;font-weight:700;">Solo están disponibles pagos con tarjeta.</div>
    <select name="metodo_pago" id="metodo_pago" required style="display:none; width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:8px;">
      <option value="tarjeta">Tarjeta</option>
    </select>

    <div id="cvv-block" style="display:none;margin-top:6px;margin-bottom:6px;">
      <label class="small">CVV</label>
      <input type="password" name="cvv" id="cvv" maxlength="4" placeholder="CVV" style="width:160px;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
    </div>

    @if($isAdmin)
      <div id="admin-cvv" style="background:#fef3c7;padding:10px;border-radius:8px;margin-bottom:12px;color:#92400e;">CVV: <span id="tarjeta-cvv-display">N/A</span></div>
    @endif

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
      <a href="{{ route('reservaciones.index') }}" class="action-btn view">Cancelar</a>
      <button type="submit" id="pay-submit" class="action-btn primary" @if($currentUser && !$isAdmin && !empty($currentUser->bloqueo_tarjetas)) disabled @endif>Pagar ${{ number_format($total,2,',','.') }}</button>
    </div>
  </form>
</div>
<!-- payer helper viewer (visible to all) -->
<div id="pagar-help-viewer" class="help-viewer" aria-hidden="true" style="display:none;">
  <div class="help-viewer-backdrop" id="pagar-help-backdrop"></div>
  <div class="help-viewer-panel" role="dialog" aria-modal="true" aria-label="Guía Pagar">
    <div class="help-toolbar">
      <strong>Guía rápida — Pagar reservación</strong>
      <div class="help-controls">
        <button type="button" class="help-btn" id="pagar-help-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="help-btn" id="pagar-help-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="help-btn" id="pagar-help-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="help-btn" id="pagar-help-close" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="help-stage" id="pagar-help-stage">
      <img id="pagar-help-image" class="help-image" src="{{ asset('tutorial_imgs/admin/Pagar.png') }}" alt="Tutorial Pagar" draggable="false" />
      <span class="help-hint">Rueda para zoom, arrastra para mover, clic fuera para salir</span>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const total = parseFloat({{ json_encode((float)$total) }});
  const metodo = document.getElementById('metodo_pago');
  const cvvBlock = document.getElementById('cvv-block');
  const tarjetaSelect = document.getElementById('tarjeta_select');
  const tarjetaCvvDisplay = document.getElementById('tarjeta-cvv-display');
  const tarjetaWarning = document.getElementById('tarjeta-warning');
  const submitBtn = document.getElementById('pay-submit');

  function checkSaldoForSelected(){
    tarjetaWarning.style.display = 'none';
    if(!tarjetaSelect) return true;
    const opt = tarjetaSelect.options[tarjetaSelect.selectedIndex];
    if(!opt || !opt.dataset) return true;
    const saldo = parseFloat(opt.dataset.saldo || 0);
    if(isNaN(saldo)) return true;
    if(saldo < total){
      tarjetaWarning.style.display = '';
      submitBtn.disabled = true;
      tarjetaSelect.style.border = '1px solid #fca5a5';
      return false;
    }
    submitBtn.disabled = false;
    tarjetaSelect.style.border = '';
    return true;
  }

  if(metodo){
    metodo.addEventListener('change', function(){
      if(this.value === 'tarjeta') cvvBlock.style.display = '';
      else cvvBlock.style.display = 'none';
    });
    // init
    if(metodo.value === 'tarjeta') cvvBlock.style.display = '';
  }

  if(tarjetaSelect){
    tarjetaSelect.addEventListener('change', function(){
      checkSaldoForSelected();
      if(tarjetaCvvDisplay){
        const opt = tarjetaSelect.options[tarjetaSelect.selectedIndex];
        tarjetaCvvDisplay.textContent = (opt && opt.dataset && opt.dataset.cvv) ? opt.dataset.cvv : 'N/A';
      }
    });
    // init
    checkSaldoForSelected();
    if(tarjetaCvvDisplay){
      const opt0 = tarjetaSelect.options[tarjetaSelect.selectedIndex];
      tarjetaCvvDisplay.textContent = (opt0 && opt0.dataset && opt0.dataset.cvv) ? opt0.dataset.cvv : 'N/A';
    }
  }

});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  try{
    const openBtn = document.getElementById('pagar-help-open-btn');
    const openLink = document.getElementById('pagar-help-open-link');
    const viewer = document.getElementById('pagar-help-viewer');
    const backdrop = document.getElementById('pagar-help-backdrop');
    const closeBtn = document.getElementById('pagar-help-close');
    const zoomIn = document.getElementById('pagar-help-zoom-in');
    const zoomOut = document.getElementById('pagar-help-zoom-out');
    const zoomReset = document.getElementById('pagar-help-zoom-reset');
    const stage = document.getElementById('pagar-help-stage');
    const img = document.getElementById('pagar-help-image');
    console.log('Pagar help init', { openBtn: !!openBtn, openLink: !!openLink, viewer: !!viewer, img: !!img });
    if(!viewer || !img) return;
    let isOpen = false; let scale = 1; let x = 0; let y = 0; let dragging = false; let sx = 0; let sy = 0;
    function apply(){ img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')'; if (zoomReset) zoomReset.textContent = Math.round(scale*100)+'%'; }
    function openViewer(){ if (isOpen) return; isOpen = true; viewer.classList.add('open'); viewer.setAttribute('aria-hidden','false'); viewer.style.display='block'; scale = 1; x=0; y=0; apply(); console.log('pagar: viewer opened'); }
    function closeViewer(){ if (!isOpen) return; isOpen = false; viewer.classList.remove('open'); viewer.setAttribute('aria-hidden','true'); setTimeout(()=> viewer.style.display='none',180); console.log('pagar: viewer closed'); }
    openBtn?.addEventListener('click', function(e){ e.preventDefault(); isOpen ? closeViewer() : openViewer(); });
    openLink?.addEventListener('click', function(e){ e.preventDefault(); isOpen ? closeViewer() : openViewer(); });
    closeBtn?.addEventListener('click', function(e){ e.preventDefault(); closeViewer(); });
    viewer.addEventListener('click', function(e){ if (e.target === viewer || e.target === backdrop) closeViewer(); });
    zoomIn?.addEventListener('click', function(){ scale = Math.min(4, scale + 0.2); apply(); });
    zoomOut?.addEventListener('click', function(){ scale = Math.max(1, scale - 0.2); apply(); });
    zoomReset?.addEventListener('click', function(){ scale = 1; x = 0; y = 0; apply(); });
    stage?.addEventListener('wheel', function(e){ if (!isOpen) return; e.preventDefault(); scale = Math.min(4, Math.max(1, scale + (e.deltaY < 0 ? 0.18 : -0.18))); apply(); }, { passive:false });
    stage?.addEventListener('mousedown', function(e){ if (scale <= 1) return; dragging = true; sx = e.clientX; sy = e.clientY; stage.classList.add('dragging'); });
    window.addEventListener('mousemove', function(e){ if (!dragging) return; x += e.clientX - sx; y += e.clientY - sy; sx = e.clientX; sy = e.clientY; apply(); });
    window.addEventListener('mouseup', function(){ if (dragging){ dragging = false; stage.classList.remove('dragging'); } });
    stage?.addEventListener('dblclick', function(){ scale = (scale > 1) ? 1 : 2; x = 0; y = 0; apply(); });
    document.addEventListener('keydown', function(e){ if (!isOpen) return; if (e.key === 'Escape') closeViewer(); if (e.key === '+' || e.key === '=') { scale = Math.min(4, scale + 0.2); apply(); } if (e.key === '-') { scale = Math.max(1, scale - 0.2); apply(); } });
  } catch(err){ console.error('Error init pagar help', err); }
});
</script>
@endpush

@extends('layouts.app')

@section('title','Pagar reservación')
@php
  use App\Models\Comentario;
  use Illuminate\Support\Str;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp

@section('content')
<div style="max-width:700px;margin:20px auto;padding:12px;">
  <h1>Pagar reservación</h1>

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

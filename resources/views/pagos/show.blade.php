@extends('layouts.app')

@section('title','Pago #' . ($payment->id ?? ($p->id ?? '')))

@section('content')
@php
  $payment = $payment ?? ($p ?? null);
  use Carbon\Carbon;
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp

<div style="max-width:800px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1>Pago #{{ $payment->id ?? '-' }}</h1>
    <div>
      <a href="{{ url('/pagos') }}" class="btn">Volver</a>
    </div>
  </div>

  <div class="card" style="padding:16px;">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start;">
      <div>
        <h3 style="margin:0 0 8px 0;">Información del pago</h3>
        <p><strong>ID:</strong> {{ $payment->id ?? '-' }}</p>
        <p><strong>Monto:</strong> ${{ number_format($payment->monto ?? 0,2,',','.') }}</p>
        <p><strong>Método:</strong> {{ $payment->metodo_pago ?? '-' }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($payment->estado ?? '-') }}</p>
        <p><strong>Fecha pago:</strong> {{ $payment->fecha_pago ? Carbon::parse($payment->fecha_pago)->format('d M Y H:i') : '-' }}</p>
        <hr />
        <h4 style="margin:8px 0">Reservación</h4>
        @if($payment && $payment->reservation)
          <p><a href="{{ route('reservaciones.show', $payment->reservation->id) }}">Reservación #{{ $payment->reservation->id }}</a></p>
          <p class="small">Cliente: {{ $payment->reservation->user->nombre ?? '-' }} {{ $payment->reservation->user->apellido ?? '' }}</p>
        @else
          <p>Reservación: #{{ $payment->reservacion_id ?? '-' }}</p>
        @endif
      </div>

      <div>
        <h4 style="margin:0 0 8px 0">Tarjeta</h4>
        @if($payment && $payment->tarjeta)
          <p><strong>Nombre:</strong> {{ $payment->tarjeta->nombre ?? '-' }}</p>
          <p><strong>Número:</strong> ••••{{ substr($payment->tarjeta->numero_tarjeta ?? '', -4) }}</p>
          <p class="small">Asignada a: {{ optional($payment->tarjeta->assignedUser)->nombre ? trim(optional($payment->tarjeta->assignedUser)->nombre . ' ' . optional($payment->tarjeta->assignedUser)->apellido) : 'No asignada' }}</p>
        @else
          <p>No hay tarjeta asociada.</p>
        @endif

        @if(($payment->estado ?? '') === 'pagado' && !empty($payment->codigo_qr))
          @php
            $qrPayload = 'HOMERES|RES:' . ($payment->reservacion_id ?? '-') . '|PAGO:' . ($payment->id ?? '-') . '|COD:' . ($payment->codigo_qr ?? '');
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($qrPayload);
          @endphp
          <hr />
          <h4 style="margin:8px 0">Código de llegada</h4>
          <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
            <img src="{{ $qrUrl }}" alt="QR reservación pagada" style="width:220px;height:220px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;padding:8px;">
            <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;color:#0f172a;">{{ $payment->codigo_qr }}</div>
            <div class="small">Muestra este QR o código al llegar a la propiedad.</div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@endsection

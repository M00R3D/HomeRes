@extends('layouts.app')

@section('title', 'Código de reservación #' . ($payment->reservacion_id ?? '-'))

@section('content')
@php
  $qrPayload = 'HOMERES|RES:' . ($payment->reservacion_id ?? '-') . '|PAGO:' . ($payment->id ?? '-') . '|COD:' . ($payment->codigo_qr ?? '');
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . rawurlencode($qrPayload);
@endphp

<div style="max-width:900px;margin:20px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">Detalle de código QR</h1>
    <a href="{{ route('pagos.codes') }}" class="btn">Volver a códigos</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;">
    <div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
      <h3 style="margin:0 0 10px 0;">Información del código</h3>
      <p><strong>Código:</strong> <span style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;">{{ $payment->codigo_qr }}</span></p>
      <p><strong>Pago:</strong> #{{ $payment->id }}</p>
      <p><strong>Reservación:</strong> #{{ $payment->reservacion_id }}</p>
      <p><strong>Cliente:</strong> {{ $payment->reservation->user->nombre ?? '-' }} {{ $payment->reservation->user->apellido ?? '' }}</p>
      <p><strong>Generado:</strong> {{ $payment->codigo_qr_generado_en ? $payment->codigo_qr_generado_en->format('d M Y H:i') : '-' }}</p>

      <div style="margin-top:14px;padding:14px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;">
        <div style="font-size:1rem;line-height:1.5;color:#0f172a;">
          Este es el código que debes mostrar para hacer tu check-in al llegar a la propiedad. El personal escaneará este QR para validar que la reservación está pagada y activa.
        </div>
      </div>
    </div>

    <div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 24px rgba(2,6,23,0.06);text-align:center;">
      <img src="{{ $qrUrl }}" alt="QR de check-in" style="width:100%;max-width:360px;height:auto;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fff;">
    </div>
  </div>
</div>
@endsection

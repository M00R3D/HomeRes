@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos')

@section('content')
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">{{ ($isAdmin ?? false) ? 'Pagos (todos)' : 'Mis pagos' }}</h1>
    <a href="{{ route('pagos.codes') }}" class="btn">{{ ($isAdmin ?? false) ? 'Ver códigos QR' : 'Mis códigos' }}</a>
  </div>

  <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th style="padding:8px;">ID</th>
          <th style="padding:8px;">Reservación</th>
          <th style="padding:8px;">Cliente</th>
          <th style="padding:8px;">Monto</th>
          <th style="padding:8px;">Método</th>
          <th style="padding:8px;">Estado</th>
          <th style="padding:8px;">Fecha</th>
          <th style="padding:8px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr style="border-bottom:1px solid #f6f6f6;">
            <td style="padding:8px;vertical-align:top;">#{{ $p->id }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->reservacion_id ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->reservation->user->nombre ?? '-' }} {{ $p->reservation->user->apellido ?? '' }}</td>
            <td style="padding:8px;vertical-align:top;font-weight:700;">${{ number_format($p->monto ?? 0, 2, ',', '.') }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->metodo_pago ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ ucfirst($p->estado ?? '-') }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d M Y H:i') : '-' }}</td>
            <td style="padding:8px;vertical-align:top;">
              <a href="{{ route('pagos.show', $p->id) }}" class="link-button">Ver pago</a>
              @if(($p->estado ?? '') === 'pagado' && !empty($p->codigo_qr))
                <a href="{{ route('pagos.codes.show', $p->id) }}" class="link-button" style="margin-left:6px;">Ver código</a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" style="padding:12px;color:#6b7280;">No hay pagos para mostrar.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:12px;display:flex;justify-content:center;">
      {{ $payments->links() }}
    </div>
  </div>
</div>
@endsection

@extends('layouts.app')

@section('title', ($isAdmin ?? false) ? 'Códigos QR (todos)' : 'Mis códigos')

@section('content')
<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">{{ ($isAdmin ?? false) ? 'Códigos QR (todos)' : 'Mis códigos' }}</h1>
    <a href="{{ route('pagos.mine') }}" class="btn">{{ ($isAdmin ?? false) ? 'Ver pagos' : 'Mis pagos' }}</a>
  </div>

  <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th style="padding:8px;">Código</th>
          <th style="padding:8px;">Pago</th>
          <th style="padding:8px;">Reservación</th>
          <th style="padding:8px;">Cliente</th>
          <th style="padding:8px;">Generado</th>
          <th style="padding:8px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($codes as $p)
          <tr style="border-bottom:1px solid #f6f6f6;">
            <td style="padding:8px;vertical-align:top;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;">{{ $p->codigo_qr }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->id }}</td>
            <td style="padding:8px;vertical-align:top;">#{{ $p->reservacion_id ?? '-' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->reservation->user->nombre ?? '-' }} {{ $p->reservation->user->apellido ?? '' }}</td>
            <td style="padding:8px;vertical-align:top;">{{ $p->codigo_qr_generado_en ? $p->codigo_qr_generado_en->format('d M Y H:i') : '-' }}</td>
            <td style="padding:8px;vertical-align:top;">
              <a href="{{ route('pagos.codes.show', $p->id) }}" class="link-button">Ver detalle código</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="padding:12px;color:#6b7280;">No hay códigos disponibles.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:12px;display:flex;justify-content:center;">
      {{ $codes->links() }}
    </div>
  </div>
</div>
@endsection

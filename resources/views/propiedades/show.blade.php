@extends('layouts.app')

@section('title', 'Propiedad - ' . ($propiedad->nombre ?? ''))

@section('content')
<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">{{ $propiedad->nombre }}</h1>
      <div style="color:#6b7280;margin-top:6px;">Código: {{ $propiedad->codigo ?? '-' }} · {{ ucfirst($propiedad->tipo) }}</div>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('propiedades.index') }}" class="link-button">Volver</a>
      <a href="{{ route('propiedades.index', ['edit' => $propiedad->id]) }}" class="btn" title="Abrir editor">Editar</a>
    </div>
  </div>

  <div style="display:flex;gap:16px;flex-wrap:wrap;">
    <div style="flex:0 0 360px;">
      @php
        $gallery = $gallery ?? [];
        $main = $gallery[0] ?? $propiedad->ruta_img ?? null;
      @endphp

      <div style="position:relative;border-radius:10px;overflow:hidden;box-shadow:0 12px 30px rgba(2,6,23,0.06);">
        @if($main)
          <img id="pr-main-img" src="{{ asset($main) }}" alt="{{ $propiedad->nombre }}" style="width:100%;height:320px;object-fit:cover;display:block;">
        @else
          <div style="width:100%;height:320px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;">Sin imagen</div>
        @endif
      </div>

      @if(!empty($gallery))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
          @foreach($gallery as $g)
            <button type="button" class="pr-thumb" data-src="{{ asset($g) }}" style="border:0;padding:0;background:transparent;cursor:pointer;width:72px;height:56px;border-radius:8px;overflow:hidden;">
              <img src="{{ asset($g) }}" style="width:100%;height:100%;object-fit:cover;display:block;">
            </button>
          @endforeach
        </div>
      @else
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
          @foreach(collect(explode(',', $propiedad->servicios ?? ''))->map(fn($s)=>trim($s))->filter()->values() as $s)
            <span style="background:#f3f4f6;padding:6px 10px;border-radius:999px;font-weight:700;">{{ $s }}</span>
          @endforeach
        </div>
      @endif
    </div>

    <div style="flex:1;min-width:320px;">
      <div style="background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 28px rgba(2,6,23,0.06);">
        <dl style="display:grid;grid-template-columns:140px 1fr;gap:10px 18px;">
          <dt class="small">Tipo</dt><dd>{{ $propiedad->tipo }}</dd>
          <dt class="small">Código</dt><dd>{{ $propiedad->codigo ?? '-' }}</dd>
          <dt class="small">Precio / noche</dt><dd>${{ number_format($propiedad->precio_noche ?? 0, 2, ',', '.') }}</dd>
          <dt class="small">Capacidad</dt><dd>{{ $propiedad->capacidad ?? '-' }}</dd>
          <dt class="small">Ubicación</dt><dd>{{ $propiedad->ubicacion ?? '-' }}</dd>
          <dt class="small">Estado</dt>
          <dd><span class="pr-estado {{ $propiedad->estado }}">{{ $propiedad->estado }}</span></dd>
          <dt class="small">Descripción</dt><dd style="white-space:pre-wrap;">{{ $propiedad->descripcion ?? '-' }}</dd>
        </dl>
      </div>
    </div>
  </div>

  @php
    use App\Models\Comentario;
    use App\Models\Reservation as Resv;
    $reservIds = Resv::where('propiedad_id', $propiedad->id)->pluck('id')->all();
  @endphp

  @if(isset($comentarios) || (!empty($reservIds) && Comentario::whereIn('reservacion_id', $reservIds)->exists()))
    @php
      if (!isset($comentarios)) {
        $comentarios = empty($reservIds)
          ? collect([])
          : Comentario::with('user')->whereIn('reservacion_id', $reservIds)->orderByDesc('fecha_creacion')->get();
      }
    @endphp
    <div style="margin-top:16px;">
      <h3 style="margin-bottom:8px;">Comentarios ({{ $comentarios->count() }})</h3>
      @if($comentarios->isEmpty())
        <div style="color:#6b7280;">No hay comentarios públicos para esta propiedad.</div>
      @else
        <div style="display:flex;flex-direction:column;gap:8px;">
          @foreach($comentarios as $c)
            <div style="background:#fff;padding:10px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,0.04);">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div style="font-weight:700;">{{ $c->user->nombre ?? 'Usuario' }} {{ $c->user->apellido ?? '' }}</div>
                <div style="font-size:0.9rem;color:#6b7280;">{{ \Carbon\Carbon::parse($c->fecha_creacion ?? now())->format('d M Y H:i') }}</div>
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
  @endif

</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.pr-thumb').forEach(btn => {
    btn.addEventListener('click', function(){
      const src = this.getAttribute('data-src');
      const main = document.getElementById('pr-main-img');
      if(main && src) main.setAttribute('src', src);
    });
  });
});
</script>
@endpush

@endsection
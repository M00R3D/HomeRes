@extends('layouts.app')

@section('title', 'Propiedad - ' . ($propiedad->nombre ?? ''))

@section('content')
<link rel="stylesheet" href="{{ asset('css/propiedades.css') }}">
@php
  $currentUser = $currentUser ?? auth()->user();
@endphp
<style>
.pd-carousel{position:relative;border-radius:14px;overflow:hidden;box-shadow:0 16px 34px rgba(2,6,23,0.12);background:#f3f4f6;--mx:50%;--my:50%}
.pd-track{display:flex;transition:transform .72s cubic-bezier(.22,.61,.36,1)}
.pd-slide{flex:0 0 100%;height:360px;position:relative;overflow:hidden}
.pd-slide img{width:100%;height:100%;object-fit:cover;display:block;transform:scale(1.03);transition:transform .9s ease, filter .5s ease;filter:saturate(1.04)}
.pd-slide::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at var(--mx) var(--my), rgba(255,255,255,0.22), rgba(255,255,255,0) 44%);opacity:0;transition:opacity .25s ease}
.pd-slide::after{content:'';position:absolute;inset:auto 0 0 0;height:42%;background:linear-gradient(to top, rgba(2,6,23,.44), rgba(2,6,23,0));pointer-events:none}
.pd-carousel:hover .pd-slide img{transform:scale(1.09);filter:saturate(1.15) contrast(1.07)}
.pd-carousel:hover .pd-slide::before{opacity:1}
.pd-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:3;border:0;width:38px;height:38px;border-radius:999px;background:rgba(2,6,23,.52);color:#fff;font-size:1.35rem;line-height:1;cursor:pointer;transition:background .2s ease, opacity .2s ease;opacity:.92}
.pd-nav:hover{background:rgba(2,6,23,.84)}
.pd-nav.prev{left:10px}.pd-nav.next{right:10px}
.pd-dots{position:absolute;left:50%;bottom:10px;transform:translateX(-50%);display:flex;gap:7px;z-index:3}
.pd-dot{width:8px;height:8px;border-radius:999px;border:0;padding:0;background:rgba(255,255,255,.6);cursor:pointer}
.pd-dot.active{width:22px;background:#fff}
.pd-counter{position:absolute;right:10px;top:10px;z-index:3;background:rgba(2,6,23,.62);color:#fff;padding:4px 9px;border-radius:999px;font-size:.8rem;font-weight:700}
.pd-thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(78px,1fr));gap:8px;margin-top:10px}
.pd-thumb{border:0;padding:0;background:#fff;border-radius:9px;overflow:hidden;height:62px;cursor:pointer;box-shadow:0 4px 14px rgba(2,6,23,.08);outline:2px solid transparent;transition:transform .2s ease, outline-color .2s ease, box-shadow .2s ease}
.pd-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.pd-thumb:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(2,6,23,.16)}
.pd-thumb.active{outline-color:#06b6d4}
.pd-reserve-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:48px;padding:0 16px;border-radius:12px;text-decoration:none;color:#fff;font-weight:800;letter-spacing:.2px;background:linear-gradient(90deg,#06b6d4,#3b82f6);box-shadow:0 12px 28px rgba(59,130,246,.26);transition:transform .18s ease, box-shadow .22s ease, filter .2s ease}
.pd-reserve-btn:hover{transform:translateY(-2px);filter:saturate(1.08);box-shadow:0 18px 34px rgba(59,130,246,.34)}
.pd-reserve-btn:focus-visible{outline:3px solid rgba(14,165,233,.32);outline-offset:2px}
@media (max-width: 820px){
  .pd-slide{height:280px}
  .pd-nav{width:34px;height:34px}
}
</style>
    .pd-reserve-btn {
      background: linear-gradient(90deg,#2274e1,#1a6fdb);
      color: #fff;
      padding: 8px 12px;
      border-radius: 8px;
      text-decoration: none;
      box-shadow: 0 2px 8px rgba(34,116,225,0.18);
    }
    .pd-reserve-btn:hover{opacity:0.95}


<div style="max-width:1100px;margin:18px auto;padding:12px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">{{ $propiedad->nombre }}</h1>
      <div style="color:#6b7280;margin-top:6px;">Código: {{ $propiedad->codigo ?? '-' }} · {{ ucfirst($propiedad->tipo) }}</div>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('propiedades.index') }}" class="link-button">Volver</a>
      @if($currentUser && ( ($currentUser->rol ?? '') === 'admin' || $currentUser->id === $propiedad->owner_id ))
      <a href="{{ route('propiedades.index', ['edit' => $propiedad->id]) }}" class="btn" title="Abrir editor">Editar</a>
      @endif
    </div>
      <div class="property-actions" style="margin-top:14px;">
        <a href="{{ route('reservaciones.create_for_propiedad', $propiedad->id) }}" class="btn btn-primary pd-reserve-btn" style="margin-left:8px;">Solicitar reserva</a>
      </div>
  </div>

  <div style="display:flex;gap:16px;flex-wrap:wrap;">
    <div style="flex:0 0 360px;">
      @php
        $gallery = collect($gallery ?? [])->filter()->values()->all();
      @endphp

      @if(!empty($gallery))
        <div class="pd-carousel" id="pd-carousel" aria-label="Galería de {{ $propiedad->nombre }}">
          <div class="pd-track" id="pd-track">
            @foreach($gallery as $i => $g)
              <div class="pd-slide">
                <img src="{{ asset($g) }}" alt="{{ $propiedad->nombre }} imagen {{ $i + 1 }}">
              </div>
            @endforeach
          </div>
          @if(count($gallery) > 1)
            <button type="button" class="pd-nav prev" id="pd-prev" aria-label="Imagen anterior">‹</button>
            <button type="button" class="pd-nav next" id="pd-next" aria-label="Siguiente imagen">›</button>
            <div class="pd-dots" id="pd-dots"></div>
            <div class="pd-counter" id="pd-counter">1 / {{ count($gallery) }}</div>
          @endif
        </div>

        @if(count($gallery) > 1)
          <div class="pd-thumbs" id="pd-thumbs">
            @foreach($gallery as $i => $g)
              <button type="button" class="pd-thumb {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}" aria-label="Ir a imagen {{ $i + 1 }}">
                <img src="{{ asset($g) }}" alt="Miniatura {{ $i + 1 }}">
              </button>
            @endforeach
          </div>
        @endif
      @else
        <div style="position:relative;border-radius:10px;overflow:hidden;box-shadow:0 12px 30px rgba(2,6,23,0.06);">
          @if(!empty($propiedad->ruta_img))
            <img src="{{ asset($propiedad->ruta_img) }}" alt="{{ $propiedad->nombre }}" style="width:100%;height:320px;object-fit:cover;display:block;">
          @else
            <div style="width:100%;height:320px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;">Sin imagen</div>
          @endif
        </div>

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
        <div style="margin-top:14px;">
          <a href="{{ route('reservaciones.create_for_propiedad', $propiedad->id) }}" class="pd-reserve-btn" aria-label="Solicitar reserva de {{ $propiedad->nombre }}">
            <span>Solicitar reserva</span>
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const car = document.getElementById('pd-carousel');
  const track = document.getElementById('pd-track');
  const prev = document.getElementById('pd-prev');
  const next = document.getElementById('pd-next');
  const dotsWrap = document.getElementById('pd-dots');
  const counter = document.getElementById('pd-counter');
  const thumbs = Array.from(document.querySelectorAll('#pd-thumbs .pd-thumb'));
  if(!car || !track) return;

  const total = Number(track.children.length || 0);
  if(total <= 1) return;

  let index = 0;
  let timer = null;
  const dots = [];

  function render(){
    track.style.transform = 'translateX(' + (-index * 100) + '%)';
    dots.forEach((d,i)=> d.classList.toggle('active', i === index));
    thumbs.forEach((t,i)=> t.classList.toggle('active', i === index));
    if(counter) counter.textContent = (index + 1) + ' / ' + total;
  }

  function go(i){
    index = (i + total) % total;
    render();
  }

  function start(){
    stop();
    timer = setInterval(()=> go(index + 1), 4300);
  }

  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  if (dotsWrap){
    for(let i=0;i<total;i++){
      const d = document.createElement('button');
      d.type = 'button';
      d.className = 'pd-dot' + (i === 0 ? ' active' : '');
      d.setAttribute('aria-label', 'Ir a imagen ' + (i + 1));
      d.addEventListener('click', function(){ go(i); start(); });
      dotsWrap.appendChild(d);
      dots.push(d);
    }
  }

  prev?.addEventListener('click', function(){ go(index - 1); start(); });
  next?.addEventListener('click', function(){ go(index + 1); start(); });
  thumbs.forEach(function(t){ t.addEventListener('click', function(){ go(Number(this.dataset.index || 0)); start(); }); });

  car.addEventListener('mouseenter', stop);
  car.addEventListener('mouseleave', start);
  car.addEventListener('focusin', stop);
  car.addEventListener('focusout', start);
  car.addEventListener('mousemove', function(e){
    const r = car.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * 100;
    const y = ((e.clientY - r.top) / r.height) * 100;
    car.style.setProperty('--mx', x.toFixed(2) + '%');
    car.style.setProperty('--my', y.toFixed(2) + '%');
  });

  start();
});
</script>
@endpush

@endsection
@extends('layouts.app')

@section('title', 'Código de reservación #' . ($payment->reservacion_id ?? '-'))

@section('content')
@php
  $qrPayload = 'HOMERES|RES:' . ($payment->reservacion_id ?? '-') . '|PAGO:' . ($payment->id ?? '-') . '|COD:' . ($payment->codigo_qr ?? '');
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . rawurlencode($qrPayload);

  $rawImg = $payment->reservation->propiedad->ruta_img ?? null;
  $galleryUrls = [];
  $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  if (is_string($rawImg) && $rawImg !== '') {
    $normalized = trim(str_replace('\\', '/', $rawImg), '/');
    $publicTarget = public_path($normalized);

    if (preg_match('/\.([a-zA-Z0-9]+)$/', $normalized, $m)) {
      $ext = strtolower($m[1]);
      if (in_array($ext, $allowedExt, true)) {
        $galleryUrls[] = asset($normalized);
      }
    } elseif (is_dir($publicTarget)) {
      $entries = @scandir($publicTarget) ?: [];
      foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        $galleryUrls[] = asset($normalized . '/' . $entry);
      }
      natcasesort($galleryUrls);
      $galleryUrls = array_values($galleryUrls);
    }
  }
@endphp

<style>
  .code-prop-square {
    width: min(100%, 320px);
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    overflow: hidden;
    position: relative;
    border: 1px solid #e5e7eb;
    background: linear-gradient(145deg, #f8fafc, #e2e8f0);
    box-shadow: 0 14px 30px rgba(2,6,23,0.1);
    transform: rotate(-1.5deg);
  }
  .code-prop-square::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at var(--mx, 50%) var(--my, 50%), rgba(255,255,255,0.32), rgba(255,255,255,0) 50%);
    opacity: 0;
    transition: opacity .24s ease;
  }
  .code-prop-square:hover::before { opacity: 1; }
  .code-prop-track {
    width: 100%;
    height: 100%;
    display: flex;
    transition: transform .72s cubic-bezier(.22,.61,.36,1);
  }
  .code-prop-slide { flex: 0 0 100%; width: 100%; height: 100%; }
  .code-prop-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1.03);
    transition: transform .95s ease, filter .35s ease;
    filter: saturate(1.06);
  }
  .code-prop-square:hover .code-prop-slide img {
    transform: scale(1.1);
    filter: saturate(1.18) contrast(1.05);
  }
  .code-prop-dots {
    position: absolute;
    left: 10px;
    bottom: 10px;
    display: flex;
    gap: 6px;
    z-index: 2;
  }
  .code-prop-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: rgba(255,255,255,0.58);
    border: 1px solid rgba(2,6,23,0.22);
  }
  .code-prop-dot.is-active {
    width: 16px;
    background: #fff;
  }
</style>

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

      <div style="margin-top:10px;display:flex;justify-content:center;">
        @if(!empty($galleryUrls))
          <div class="code-prop-square" data-code-prop-carousel>
            <div class="code-prop-track" data-code-prop-track>
              @foreach($galleryUrls as $i => $url)
                <div class="code-prop-slide" data-code-prop-slide>
                  <img src="{{ $url }}" alt="Preview {{ $i + 1 }} de {{ $payment->reservation->propiedad->nombre ?? 'propiedad' }}">
                </div>
              @endforeach
            </div>
            @if(count($galleryUrls) > 1)
              <div class="code-prop-dots" data-code-prop-dots>
                @foreach($galleryUrls as $i => $url)
                  <span class="code-prop-dot {{ $i === 0 ? 'is-active' : '' }}"></span>
                @endforeach
              </div>
            @endif
          </div>
        @else
          <div class="code-prop-square" style="display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700;">Sin preview</div>
        @endif
      </div>

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

<script>
  (function() {
    const carousel = document.querySelector('[data-code-prop-carousel]');
    if (!carousel) return;

    const track = carousel.querySelector('[data-code-prop-track]');
    const slides = carousel.querySelectorAll('[data-code-prop-slide]');
    const dots = carousel.querySelectorAll('.code-prop-dot');
    if (!track || slides.length < 2) return;

    let idx = 0;
    const show = function(next) {
      idx = (next + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-idx * 100) + '%)';
      dots.forEach(function(dot, i) { dot.classList.toggle('is-active', i === idx); });
    };

    let timer = setInterval(function() { show(idx + 1); }, 2600);
    carousel.addEventListener('mouseenter', function() { clearInterval(timer); });
    carousel.addEventListener('mouseleave', function() {
      clearInterval(timer);
      timer = setInterval(function() { show(idx + 1); }, 2600);
    });

    carousel.addEventListener('mousemove', function(ev) {
      const rect = carousel.getBoundingClientRect();
      const x = ((ev.clientX - rect.left) / rect.width) * 100;
      const y = ((ev.clientY - rect.top) / rect.height) * 100;
      carousel.style.setProperty('--mx', x.toFixed(1) + '%');
      carousel.style.setProperty('--my', y.toFixed(1) + '%');
    });
  })();
</script>
@endsection

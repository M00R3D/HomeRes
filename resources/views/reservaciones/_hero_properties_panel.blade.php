<section class="hero-properties-panel" aria-label="Propiedades destacadas">
  <div class="hero-properties-shell">
    <div class="hero-properties-inner">
      <div class="hero-properties-header">
        <div class="hero-properties-copy">
          <span class="hero-properties-eyebrow">{{ $heroEyebrow ?? 'Explora HomeRes' }}</span>
          <h2 class="hero-properties-title">{{ $heroTitle ?? 'Propiedades destacadas' }}</h2>
          <p class="hero-properties-subtitle">{{ $heroSubtitle ?? 'Encuentra una estadía ideal para tus próximas fechas.' }}</p>
        </div>
        <a href="{{ route('propiedades.index') }}" class="hero-properties-cta">Ir a propiedades</a>
      </div>

      @if(!empty($propertyCarouselSlides))
        <div class="hero-properties-carousel" data-properties-carousel>
          <div class="hero-properties-viewport">
            <div class="hero-properties-track" data-carousel-track>
              @foreach($propertyCarouselSlides as $slide)
                <a href="{{ route('propiedades.show', $slide['property_id']) }}" class="hero-properties-slide" data-carousel-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                  <div class="hero-properties-media">
                    <img src="{{ $slide['image'] }}" alt="Preview de {{ $slide['name'] }}">
                  </div>
                  <div class="hero-properties-meta">
                    <div class="hero-properties-meta-head">
                      <span class="hero-properties-code">{{ $slide['code'] ?: 'Propiedad destacada' }}</span>
                      <h3 class="hero-properties-name">{{ $slide['name'] }}</h3>
                      <span class="hero-properties-location">{{ $slide['location'] ?: 'Ubicacion por confirmar' }}</span>
                    </div>

                    <div class="hero-properties-meta-line">
                      @if(!is_null($slide['price']))
                        <span class="hero-properties-price">${{ number_format((float) $slide['price'], 0, ',', '.') }} <span style="opacity:.78;">/ noche</span></span>
                      @else
                        <span class="hero-properties-price">Consultar tarifa</span>
                      @endif
                      <span class="hero-properties-counter"><span data-carousel-current>{{ str_pad((string)($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span> / {{ str_pad((string)count($propertyCarouselSlides), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                  </div>
                </a>
              @endforeach
            </div>
          </div>

          @if(count($propertyCarouselSlides) > 1)
            <div class="hero-properties-controls">
              <button type="button" class="hero-properties-control" data-carousel-prev aria-label="Imagen anterior">&#10094;</button>
              <button type="button" class="hero-properties-control" data-carousel-next aria-label="Imagen siguiente">&#10095;</button>
            </div>
          @endif
        </div>

        @if(count($propertyCarouselSlides) > 1)
          <div class="hero-properties-dots" data-carousel-dots>
            @foreach($propertyCarouselSlides as $slide)
              <button type="button" class="hero-properties-dot {{ $loop->first ? 'is-active' : '' }}" data-carousel-dot data-slide-index="{{ $loop->index }}" aria-label="Ir al slide {{ $loop->iteration }}"></button>
            @endforeach
          </div>
        @endif
      @else
        <div class="hero-properties-empty">No se encontraron imágenes válidas dentro de las carpetas asignadas a las propiedades.</div>
      @endif
    </div>
  </div>
</section>

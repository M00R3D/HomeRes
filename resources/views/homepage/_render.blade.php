<div class="hp-public-content {{ ($settings['container'] ?? 'wide') === 'narrow' ? 'is-narrow' : '' }}">
  @foreach($blocks as $block)
    @continue(empty($block['enabled']))

    @switch($block['type'] ?? 'text')
      @case('hero')
        @php
          $heroImage = $resolveMedia($block['image'] ?? null);
          $heroOverlay = max(0, min(90, (int) ($block['overlay'] ?? 45))) / 100;
          $heroHeight = in_array(($block['height'] ?? 'lg'), ['sm', 'md', 'lg'], true) ? $block['height'] : 'lg';
          $heroAlign = in_array(($block['align'] ?? 'left'), ['left', 'center', 'right'], true) ? $block['align'] : 'left';
        @endphp
        <section class="hp-block hp-block-hero hero-{{ $heroHeight }} align-{{ $heroAlign }}" style="--hero-overlay: {{ $heroOverlay }};{{ $heroImage ? 'background-image:url(' . e($heroImage) . ');' : '' }}">
          <div class="hp-hero-overlay"></div>
          <div class="hp-hero-inner">
            @if(!empty($block['eyebrow']))
              <div class="hp-eyebrow">{{ $block['eyebrow'] }}</div>
            @endif
            @if(!empty($block['title']))
              <h2>{{ $block['title'] }}</h2>
            @endif
            @if(!empty($block['subtitle']))
              <p class="hp-subtitle">{{ $block['subtitle'] }}</p>
            @endif
            @if(!empty($block['body']))
              <div class="hp-copy">{!! nl2br(e($block['body'])) !!}</div>
            @endif
            @if(!empty($block['primary_label']) || !empty($block['secondary_label']))
              <div class="hp-actions">
                @if(!empty($block['primary_label']))
                  <a class="btn btn-primary" href="{{ $block['primary_url'] ?: '#' }}">{{ $block['primary_label'] }}</a>
                @endif
                @if(!empty($block['secondary_label']))
                  <a class="btn btn-ghost" href="{{ $block['secondary_url'] ?: '#' }}">{{ $block['secondary_label'] }}</a>
                @endif
              </div>
            @endif
          </div>
        </section>
        @break

      @case('banner')
        @php
          $bannerImage = $resolveMedia($block['image'] ?? null);
          $bannerHeight = in_array(($block['height'] ?? 'md'), ['sm', 'md', 'lg'], true) ? $block['height'] : 'md';
        @endphp
        <section class="hp-block hp-block-banner banner-{{ $bannerHeight }}">
          @if($bannerImage)
            @if(!empty($block['link']))<a href="{{ $block['link'] }}">@endif
            <img src="{{ $bannerImage }}" alt="{{ $block['title'] ?? 'Banner' }}">
            @if(!empty($block['link']))</a>@endif
          @endif
          @if(!empty($block['title']) || !empty($block['subtitle']))
            <div class="hp-banner-copy">
              @if(!empty($block['title']))<strong>{{ $block['title'] }}</strong>@endif
              @if(!empty($block['subtitle']))<span>{{ $block['subtitle'] }}</span>@endif
            </div>
          @endif
        </section>
        @break

      @case('title')
        <section class="hp-block hp-block-title align-{{ in_array(($block['align'] ?? 'left'), ['left', 'center', 'right'], true) ? $block['align'] : 'left' }} size-{{ in_array(($block['size'] ?? 'md'), ['sm', 'md', 'lg', 'xl'], true) ? $block['size'] : 'md' }}">
          @if(!empty($block['title']))<h3>{{ $block['title'] }}</h3>@endif
          @if(!empty($block['subtitle']))<p>{{ $block['subtitle'] }}</p>@endif
        </section>
        @break

      @case('text')
        <section class="hp-block hp-block-text style-{{ in_array(($block['style'] ?? 'card'), ['card', 'soft', 'plain'], true) ? $block['style'] : 'card' }} align-{{ in_array(($block['align'] ?? 'left'), ['left', 'center', 'right'], true) ? $block['align'] : 'left' }}">
          @if(!empty($block['title']))<h3>{{ $block['title'] }}</h3>@endif
          @if(!empty($block['subtitle']))<p class="hp-subtitle">{{ $block['subtitle'] }}</p>@endif
          @if(!empty($block['body']))<div class="hp-copy">{!! nl2br(e($block['body'])) !!}</div>@endif
        </section>
        @break

      @case('image')
        @php $imageUrl = $resolveMedia($block['image'] ?? null); @endphp
        <section class="hp-block hp-block-image width-{{ in_array(($block['width'] ?? 'md'), ['sm', 'md', 'lg', 'full'], true) ? $block['width'] : 'md' }} height-{{ in_array(($block['height'] ?? 'md'), ['sm', 'md', 'lg'], true) ? $block['height'] : 'md' }}">
          @if($imageUrl)
            @if(!empty($block['link']))<a href="{{ $block['link'] }}">@endif
            <img src="{{ $imageUrl }}" alt="{{ $block['title'] ?? 'Imagen' }}">
            @if(!empty($block['link']))</a>@endif
          @endif
          @if(!empty($block['title']) || !empty($block['subtitle']))
            <figcaption>
              @if(!empty($block['title']))<strong>{{ $block['title'] }}</strong>@endif
              @if(!empty($block['subtitle']))<span>{{ $block['subtitle'] }}</span>@endif
            </figcaption>
          @endif
        </section>
        @break

      @case('links')
        <section class="hp-block hp-block-links">
          @if(!empty($block['title']))<h3>{{ $block['title'] }}</h3>@endif
          @if(!empty($block['subtitle']))<p class="hp-subtitle">{{ $block['subtitle'] }}</p>@endif
          <div class="hp-link-grid">
            @foreach(($block['items'] ?? []) as $item)
              @continue(empty($item['label']))
              <a class="btn {{ ($item['style'] ?? 'primary') === 'ghost' ? 'btn-ghost' : 'btn-primary' }}" href="{{ $item['url'] ?: '#' }}">{{ $item['label'] }}</a>
            @endforeach
          </div>
        </section>
        @break

      @case('faq')
        <section class="hp-block hp-block-faq">
          @if(!empty($block['title']))<h3>{{ $block['title'] }}</h3>@endif
          @if(!empty($block['subtitle']))<p class="hp-subtitle">{{ $block['subtitle'] }}</p>@endif
          <div class="hp-faq-list">
            @foreach(($block['items'] ?? []) as $item)
              @continue(empty($item['question']) && empty($item['answer']))
              <details class="hp-faq-item">
                <summary>{{ $item['question'] ?? 'Pregunta' }}</summary>
                <div>{!! nl2br(e($item['answer'] ?? '')) !!}</div>
              </details>
            @endforeach
          </div>
        </section>
        @break
    @endswitch
  @endforeach

  @if(!empty($settings['show_reservations']))
    <section class="hp-block hp-dynamic-block">
      <div class="hp-section-head">
        <h3>Tus reservaciones</h3>
        <span>Resumen rapido de tu actividad</span>
      </div>
      @if($currentUser && $userReservs->isNotEmpty())
        <div class="hp-card-grid">
          @foreach($userReservs as $rv)
            <article class="hp-card-mini">
              <div class="hp-card-media">
                @if(optional($rv->propiedad)->ruta_img)
                  <img src="{{ $resolveMedia($rv->propiedad->ruta_img) }}" alt="{{ $rv->propiedad->nombre ?? 'Propiedad' }}">
                @endif
              </div>
              <div class="hp-card-copy">
                <strong>{{ $rv->propiedad->nombre ?? ('Propiedad #' . $rv->propiedad_id) }}</strong>
                <span>{{ \Carbon\Carbon::parse($rv->check_in)->format('d M Y') }} - {{ \Carbon\Carbon::parse($rv->check_out)->format('d M Y') }}</span>
                <span>Estado: {{ ucfirst($rv->estado) }}</span>
              </div>
              <a class="btn btn-ghost" href="{{ route('reservaciones.show', $rv->id) }}">Ver</a>
            </article>
          @endforeach
        </div>
      @else
        <div class="hp-empty">{{ $currentUser ? 'No tienes reservaciones registradas todavia.' : 'Inicia sesion para ver tus reservaciones.' }}</div>
      @endif
    </section>
  @endif

  @if(!empty($settings['show_properties']))
    <section class="hp-block hp-dynamic-block">
      <div class="hp-section-head">
        <h3>Propiedades disponibles</h3>
        <span>Accesos directos a las propiedades activas</span>
      </div>
      <div class="hp-card-grid">
        @foreach($allProps as $p)
          <article class="hp-card-mini">
            <div class="hp-card-media">
              @if($p->ruta_img)
                <img src="{{ $resolveMedia($p->ruta_img) }}" alt="{{ $p->nombre }}">
              @endif
            </div>
            <div class="hp-card-copy">
              <strong>{{ $p->nombre }}</strong>
              <span>{{ $p->ubicacion }}</span>
              <span>${{ number_format($p->precio_noche ?? 0, 2, ',', '.') }} / noche</span>
            </div>
            <div class="hp-inline-actions">
              <a class="btn btn-ghost" href="{{ route('propiedades.show', $p->id) }}">Ver</a>
              <a class="btn btn-primary" href="{{ route('reservaciones.create_for_propiedad', $p->id) }}">Reservar</a>
            </div>
          </article>
        @endforeach
      </div>
    </section>
  @endif
</div>

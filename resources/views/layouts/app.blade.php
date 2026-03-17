<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ config('app.name', 'HomeRes') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" >
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="icon" type="image/png" href="{{ asset('logos/logoHomeRes.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('logos/logoHomeRes.png') }}">
  <?php $__appStyle = \App\Models\Style::first(); ?>
  @php
    use App\Models\Comentario;
    use Illuminate\Support\Str;
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');

    // Load global theme (single customization controlled by admins)
    use App\Models\Theme;

    $appliedTheme = null;
    try {
      $t = Theme::find(1); // explicitly read the single global theme at id=1
      $appliedTheme = $t ? $t->toArray() : null;
    } catch (\Throwable $e) {
      $appliedTheme = null; // fail-safe
    }
  @endphp
  <style>
    :root {
      --btn-primary: {{ $appliedTheme['btn_primary'] ?? $__appStyle->btn_primary ?? '#6366f1' }};
      --btn-alt: {{ $appliedTheme['btn_alt'] ?? $__appStyle->btn_alt ?? '#06b6d4' }};
      --bg: {{ $appliedTheme['bg'] ?? $__appStyle->bg ?? '#f8fafc' }};
      --sidebar-bg: {{ $appliedTheme['sidebar_bg'] ?? $__appStyle->sidebar_bg ?? '#ffffff' }};
      --sidebar-text: {{ $appliedTheme['sidebar_text'] ?? $__appStyle->sidebar_text ?? '#0f172a' }};
      --global-transparency: {{ isset($appliedTheme['transparency']) ? ($appliedTheme['transparency']/100) : (isset($__appStyle->transparency) ? ($__appStyle->transparency/100) : 0) }};
      --font-base-size: {{ $appliedTheme['font_size'] ?? 16 }}px;
      --gradient-start: {{ $appliedTheme['gradient_start'] ?? ($__appStyle->btn_primary ?? 'transparent') }};
      --gradient-end: {{ $appliedTheme['gradient_end'] ?? ($__appStyle->btn_alt ?? 'transparent') }};
      --gradient-angle: {{ $appliedTheme['gradient_angle'] ?? 90 }}deg;
      --animated-gradient: {{ ($appliedTheme['animated_gradient'] ?? false) ? 1 : 0 }};
      --animation-speed: {{ $appliedTheme['animation_speed'] ?? 6 }}s;
    }

    @keyframes animatedGradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    body{ background: var(--bg); font-size: var(--font-base-size); }
    .btn{ background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important; color: #fff; border:0; }
    .btn-alt{ background: linear-gradient(90deg,var(--btn-alt),var(--btn-primary)) !important; color: #fff; border:0; }
    .sidebar{ background: var(--sidebar-bg); color: var(--sidebar-text); }
    .sidebar .nav .nav-item{ color: var(--sidebar-text); }
    .topbar{ background: rgba(255,255,255, calc(1 - var(--global-transparency))); }

    /* global gradient utility */
    .global-gradient {
      background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
      background-size: 200% 200%;
      {{ ($appliedTheme['animated_gradient'] ?? false) ? "animation: animatedGradient var(--animation-speed) ease infinite;" : '' }}
    }
  </style>
  <style>
    .content{
      padding: 24px;
      box-sizing: border-box;
      max-width: 1200px;
      margin: 0 auto; 
    }
    .sidebar .brand, .sidebar .nav { padding-left: 14px; padding-right: 14px; }
    .topbar { padding: 8px 16px; box-sizing: border-box; }
    @media (max-width:900px){
      .content{ padding: 16px; max-width: 100%; }
      .sidebar .brand, .sidebar .nav { padding-left: 8px; padding-right: 8px; }
    }
  </style>
</head>
@php
  // $currentUser and $isAdmin are already initialised above in head
  $currentUser = $currentUser ?? auth()->user();
  $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
@endphp
<body class="app-root">
  <aside id="sidebar" class="sidebar">
    <div class="brand">
      <a class="brand-link" href="{{ route('homepage.index') }}">
        <img src="{{ asset('logos/logoHomeRes.png') }}" alt="logo" class="brand-logo" />
        <span class="brand-text">HomeRes</span>
      </a>
      <button id="sidebar-close" class="icon-btn hide-desktop" aria-label="Cerrar menú">✕</button>
    </div>

    <nav class="nav">
      <a class="nav-item" href="{{ route('homepage.index') }}">Inicio</a>
      <div style="height:8px;"></div>
      <a class="nav-item" href="{{ route('dashboard') ?? '/dashboard' }}">Dashboard</a>
      <a class="nav-item" href="/reservaciones">Reservaciones</a>
      <a class="nav-item" href="/propiedades">Propiedades</a>
      <a class="nav-item" href="/notificaciones">Notificaciones</a>
      <a class="nav-item" href="{{ route('tarjetas.index') }}">Tarjetas</a>
      @if($isAdmin)
        <a class="nav-item" href="/users">Usuarios</a>
        <a class="nav-item" href="{{ route('admin.logs') }}">Logs</a>
        <a class="nav-item" href="{{ route('images.index') }}">Imágenes</a>
        <a class="nav-item" href="{{ route('pagos.index') }}">Pagos</a>
        <a class="nav-item" href="{{ route('admin.themes') }}">Apariencia</a>
      @endif
      <form method="POST" action="{{ route('logout') }}" class="nav-item logout-form" style="display:flex;">
        @csrf
        <button class="link-button" type="submit">Cerrar sesión</button>
      </form>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button id="sidebar-toggle" class="icon-btn show-desktop" aria-label="Abrir menú">☰</button>
      <div class="topbar-right">
        @include('partials.notification-bell')
        <a href="{{ route('notifications.preferences') }}" class="top-action">Perfil</a>
        @if($isAdmin)
          <span class="top-action" style="color:#ef4444;font-weight:700;">Usted inicio sesion como Administrador</span>
        @endif
      </div>
    </header>

    <main class="content">
      @yield('content')
    </main>

    <footer class="footer">
      <div>© {{ date('Y') }} HomeRes</div>
    </footer>
  </div>

  <script>
    (function () {
      const sidebar = document.getElementById('sidebar');
      const toggle = document.getElementById('sidebar-toggle');
      const closeBtn = document.getElementById('sidebar-close');
      const body = document.body;

      const isMobile = () => window.innerWidth <= 900;

      function setCollapsed(collapsed) {
        if (collapsed) body.classList.add('sidebar-collapsed');
        else body.classList.remove('sidebar-collapsed');
        try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch(e){}
      }

      // Initialize from preference
      try {
        const saved = localStorage.getItem('sidebar-collapsed');
        if (saved === '1') setCollapsed(true);
      } catch(e){}

      // Toggle behavior: on mobile open/close the overlay menu, on desktop collapse
      toggle && toggle.addEventListener('click', () => {
        if (isMobile()) {
          sidebar.classList.toggle('open');
        } else {
          const collapsed = body.classList.toggle('sidebar-collapsed');
          try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch(e){}
        }
      });

      closeBtn && closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));

      document.addEventListener('click', (e) => {
        if (isMobile() && !sidebar.contains(e.target) && !e.target.closest('#sidebar-toggle')) {
          sidebar.classList.remove('open');
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') sidebar.classList.remove('open');
      });

      // Respond to window resize: ensure mobile state doesn't keep collapsed class
      window.addEventListener('resize', () => {
        if (isMobile()) {
          sidebar.classList.remove('open');
        }
      });
    })();
  </script>
  
  @section('scripts')
  @show
   @stack('scripts')
 </body>
 </html>
<?php>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ config('app.name', 'HomeRes') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body class="app-root">
  <aside id="sidebar" class="sidebar">
    <div class="brand">
      <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('logos/logoHomeRes.png') }}" alt="logo" class="brand-logo" />
        <span class="brand-text">HomeRes</span>
      </a>
      <button id="sidebar-close" class="icon-btn hide-desktop" aria-label="Cerrar menú">✕</button>
    </div>

    <nav class="nav">
      <a class="nav-item" href="{{ route('dashboard') ?? '/dashboard' }}">Dashboard</a>
      <a class="nav-item" href="/reservaciones">Reservaciones</a>
      <a class="nav-item" href="/propiedades">Propiedades</a>
      <a class="nav-item" href="/notificaciones">Notificaciones</a>
      <a class="nav-item" href="/users">Usuarios</a>
      @if(auth()->check() && auth()->user()->rol === 'admin')
        <a class="nav-item" href="{{ route('images.index') }}">Imágenes</a>
        <a class="nav-item" href="{{ route('tarjetas.index') }}">Tarjetas</a>
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
        <a href="#" class="top-action">Notificaciones</a>
        <a href="#" class="top-action">Perfil</a>
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

      toggle && toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
      closeBtn && closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));

      document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && window.innerWidth < 900) {
          sidebar.classList.remove('open');
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') sidebar.classList.remove('open');
      });
    })();
  </script>

  @yield('scripts')
</body>
</html>
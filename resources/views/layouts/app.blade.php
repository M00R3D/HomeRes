<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ config('app.name', 'HomeRes') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" >
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/components.css') }}">
  <link rel="icon" type="image/png" href="{{ asset('logos/logoHomeRes.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('logos/logoHomeRes.png') }}">
  @php
    use App\Models\Comentario;
    use Illuminate\Support\Str;
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');

    // Load global theme (single customization controlled by admins)
    use App\Models\Theme;

    $appliedTheme = null;
    try {
      $t = Theme::find(5) ?? Theme::find(1); // use editable custom theme (id=5), fallback to preset id=1
      $appliedTheme = $t ? $t->toArray() : null;
    } catch (\Throwable $e) {
      $appliedTheme = null; // fail-safe
    }

    $routeName = request()->route()?->getName() ?? '';
    $layoutMeta = is_array($appliedTheme['meta']['layouts'] ?? null) ? $appliedTheme['meta']['layouts'] : [];
    $dbSectionsMeta = is_array($layoutMeta['sections'] ?? null) ? $layoutMeta['sections'] : [];
    $layoutSidebarSide = in_array(($layoutMeta['sidebar_side'] ?? 'left'), ['left', 'right'], true)
      ? ($layoutMeta['sidebar_side'] ?? 'left')
      : 'left';
    $layoutSection = 'default';
    if ($routeName === 'dashboard') {
      $layoutSection = 'dashboard';
    } elseif (str_starts_with($routeName, 'reservaciones.')) {
      $layoutSection = 'reservations';
    } elseif (str_starts_with($routeName, 'propiedades.')) {
      $layoutSection = 'properties';
    } elseif (str_starts_with($routeName, 'notificaciones.') || str_starts_with($routeName, 'notifications.')) {
      $layoutSection = 'notifications';
    } elseif (str_starts_with($routeName, 'tarjetas.')) {
      $layoutSection = 'cards';
    }
    // Layout variants and admin 'preview as' feature removed — only sidebar side remains.
    $effectiveIsAdmin = $isAdmin;
    // helper to compute readable text color for CSS variables
    function __pick_text_color_for_var($hex) {
      if (! $hex) return '#111827';
      $h = ltrim($hex, '#');
      if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
      if (strlen($h) !== 6) return '#111827';
      $r = hexdec(substr($h,0,2)); $g = hexdec(substr($h,2,2)); $b = hexdec(substr($h,4,2));
      $lum = (0.2126*$r + 0.7152*$g + 0.0722*$b) / 255;
      return ($lum < 0.5) ? '#ffffff' : '#111827';
    }
  @endphp
  <style>
    :root {
      --btn-primary: {{ $appliedTheme['btn_primary'] ?? '#6366f1' }};
      --btn-alt: {{ $appliedTheme['btn_alt'] ?? '#06b6d4' }};
      --bg: {{ $appliedTheme['bg'] ?? '#f8fafc' }};
      --sidebar-bg: {{ $appliedTheme['sidebar_bg'] ?? '#ffffff' }};
      --sidebar-text: {{ $appliedTheme['sidebar_text'] ?? '#0f172a' }};
      --global-transparency: {{ isset($appliedTheme['transparency']) ? ($appliedTheme['transparency']/100) : 0 }};
      --font-base-size: {{ $appliedTheme['font_size'] ?? 16 }}px;
      --gradient-start: {{ $appliedTheme['gradient_start'] ?? 'transparent' }};
      --gradient-end: {{ $appliedTheme['gradient_end'] ?? 'transparent' }};
      --gradient-angle: {{ $appliedTheme['gradient_angle'] ?? 90 }}deg;
      --animated-gradient: {{ ($appliedTheme['animated_gradient'] ?? false) ? 1 : 0 }};
      --animation-speed: {{ $appliedTheme['animation_speed'] ?? 6 }}s;
      --bg-gradient-start: {{ $appliedTheme['bg_gradient_start'] ?? '' }};
      --bg-gradient-end: {{ $appliedTheme['bg_gradient_end'] ?? '' }};
      --bg-gradient-angle: {{ $appliedTheme['bg_gradient_angle'] ?? 90 }}deg;
      --bg-animated: {{ ($appliedTheme['bg_animated'] ?? false) ? 1 : 0 }};
      --sidebar-gradient-start: {{ $appliedTheme['sidebar_gradient_start'] ?? '' }};
      --sidebar-gradient-end: {{ $appliedTheme['sidebar_gradient_end'] ?? '' }};
      --sidebar-gradient-angle: {{ $appliedTheme['sidebar_gradient_angle'] ?? 90 }}deg;
      --sidebar-animated: {{ ($appliedTheme['sidebar_animated'] ?? false) ? 1 : 0 }};
      --hover-animation: {{ $appliedTheme['hover_animation'] ?? 'none' }};
      --hover-animation-duration: {{ $appliedTheme['hover_animation_duration'] ?? 0.18 }}s;
      --float-animation: {{ $appliedTheme['float_animation'] ?? 'none' }};
      --float-animation-duration: {{ $appliedTheme['float_animation_duration'] ?? 6 }}s;
      --topbar-bg: {{ $appliedTheme['meta']['topbar']['bg'] ?? '#ffffff' }};
      --topbar-text: {{ $appliedTheme['meta']['topbar']['text'] ?? '#0f172a' }};
      --topbar-accent: {{ $appliedTheme['meta']['topbar']['accent'] ?? ($appliedTheme['btn_alt'] ?? '#ef4444') }};
      --notif-badge-bg: {{ $appliedTheme['meta']['notif']['bg'] ?? ($appliedTheme['meta']['payment']['fallido']['bg'] ?? ($appliedTheme['btn_alt'] ?? '#ef4444')) }};
      --notif-badge-text: {{ $appliedTheme['meta']['notif']['text'] ?? '#ffffff' }};
      --topbar-gradient-start: {{ $appliedTheme['meta']['topbar']['gradient_start'] ?? '' }};
      --topbar-gradient-end: {{ $appliedTheme['meta']['topbar']['gradient_end'] ?? '' }};
      --topbar-animated: {{ ($appliedTheme['meta']['topbar']['animated'] ?? false) ? 1 : 0 }};
      --btn-primary-text: {{ __pick_text_color_for_var($appliedTheme['btn_primary'] ?? '#6366f1') }};
      /* payment badge colors per state (defaults provided) */
      --payment-badge-bg-pagado: {{ $appliedTheme['meta']['payment']['pagado']['bg'] ?? '#10b981' }};
      --payment-badge-text-pagado: {{ $appliedTheme['meta']['payment']['pagado']['text'] ?? '#ffffff' }};
      --payment-badge-bg-pendiente: {{ $appliedTheme['meta']['payment']['pendiente']['bg'] ?? '#f59e0b' }};
      --payment-badge-text-pendiente: {{ $appliedTheme['meta']['payment']['pendiente']['text'] ?? '#ffffff' }};
      --payment-badge-bg-fallido: {{ $appliedTheme['meta']['payment']['fallido']['bg'] ?? '#ef4444' }};
      --payment-badge-text-fallido: {{ $appliedTheme['meta']['payment']['fallido']['text'] ?? '#ffffff' }};
      --payment-badge-bg-parcial: {{ $appliedTheme['meta']['payment']['parcial']['bg'] ?? '#6366f1' }};
      --payment-badge-text-parcial: {{ $appliedTheme['meta']['payment']['parcial']['text'] ?? '#ffffff' }};
      /* price colors by reservation estado */
      --price-color-confirmada: {{ $appliedTheme['meta']['price']['confirmada'] ?? '#065f46' }};
      --price-color-pendiente: {{ $appliedTheme['meta']['price']['pendiente'] ?? '#92400e' }};
      --price-color-cancelada: {{ $appliedTheme['meta']['price']['cancelada'] ?? '#7f1d1d' }};
      --price-color-default: {{ $appliedTheme['meta']['price']['default'] ?? '#374151' }};
      /* global text color (Dark theme forces white) */
      --text-color: {{ ($appliedTheme['name'] ?? '') === 'Dark' ? '#ffffff' : (__pick_text_color_for_var($appliedTheme['bg'] ?? '#f8fafc')) }};

      @php
        // Determine if bg is dark to auto-derive card/input/badge colours
        $_bgHex = $appliedTheme['bg'] ?? '#f8fafc';
        $_bgH = ltrim($_bgHex, '#');
        if (strlen($_bgH) === 3) { $_bgH = $_bgH[0].$_bgH[0].$_bgH[1].$_bgH[1].$_bgH[2].$_bgH[2]; }
        $_bgLum = (strlen($_bgH) === 6)
          ? (0.2126*hexdec(substr($_bgH,0,2)) + 0.7152*hexdec(substr($_bgH,2,2)) + 0.0722*hexdec(substr($_bgH,4,2))) / 255
          : 0.95;
        $_isDark = $_bgLum < 0.45;
      @endphp

      /* Card / surface */
      --card: {{ $_isDark ? '#1e293b' : '#ffffff' }};
      --card-bg: {{ $_isDark ? '#1e293b' : '#ffffff' }};
      --card-text: {{ $_isDark ? '#f1f5f9' : '#111827' }};
      /* Inputs */
      --input-bg: {{ $_isDark ? '#0f172a' : '#ffffff' }};
      --input-border: {{ $_isDark ? '#334155' : '#e6e9ee' }};
      /* Table helpers */
      --table-header-bg: {{ $_isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.03)' }};
      --table-row-odd: {{ $_isDark ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.01)' }};
      /* Badges */
      --badge-bg: {{ $_isDark ? '#334155' : '#f3f4f6' }};
      --badge-text: {{ $_isDark ? '#f1f5f9' : '#111827' }};
      /* Muted */
      --muted: {{ $_isDark ? '#94a3b8' : '#6b7280' }};
      /* Thumb placeholder */
      --thumb-bg: {{ $_isDark ? '#1e293b' : '#f3f4f6' }};
      /* Danger button */
      --btn-danger: {{ $appliedTheme['button_variants']['danger']['bg'] ?? '#dc2626' }};
      --btn-danger-text: {{ $appliedTheme['button_variants']['danger']['color'] ?? '#ffffff' }};
      /* Alt button text */
      --btn-alt-text: {{ __pick_text_color_for_var($appliedTheme['btn_alt'] ?? '#06b6d4') }};

      /* ensure topbar accent and notif badge readable in dark preset */
      @if(($appliedTheme['name'] ?? '') === 'Dark')
        --topbar-accent: #ffffff;
        --notif-badge-text: #ffffff;
      @endif
    }

    @keyframes animatedGradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes floatY {
      0% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
      100% { transform: translateY(0); }
    }

    @keyframes pulseScale {
      0% { transform: scale(1); box-shadow: none; }
      50% { transform: scale(1.03); }
      100% { transform: scale(1); }
    }

    @keyframes glow {
      0% { box-shadow: 0 0 0 rgba(99,102,241,0); }
      50% { box-shadow: 0 8px 24px rgba(99,102,241,0.12); }
      100% { box-shadow: 0 0 0 rgba(99,102,241,0); }
    }

    body{ font-size: var(--font-base-size); color: var(--text-color); }

    /* Card / surface theming — override hardcoded backgrounds */
    .card, .card.table-card, .card-wide, .modal-panel, .confirm-card {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    .card h1, .card h2, .card h3, .card-wide h1, .card-wide h2, .card-wide h3,
    .card p, .card-wide p, .card span, .card-wide span, .card label, .card-wide label,
    .card .small, .card-wide .small {
      color: var(--text-color) !important;
    }
    .table, .table th, .table td { color: var(--text-color) !important; }
    .table th { background: var(--table-header-bg) !important; }
    h1, h2, h3, h4, h5 { color: var(--text-color); }

    /* Override hardcoded inline background:#fff on generic containers */
    div[style*="background:#fff"],
    form[style*="background:#fff"],
    div[style*="background: #fff"],
    form[style*="background: #fff"] {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    /* In-page <style> class overrides for cards without .card class */
    .list-card, .pr-card, .field-card, .uploader, .calendar, .comment,
    .person-selector, .modal-panel, .confirm-card, .delete-modal-panel,
    .picker-panel {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    /* Table border colour for dark */
    .table th, .table td { border-bottom-color: var(--input-border) !important; }
    /* Input/select inline override */
    .input-inline input, .input-inline select {
      background: var(--input-bg) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    /* Form inputs override */
    .form input, .form select, .form textarea {
      background: var(--input-bg) !important;
      color: var(--text-color) !important;
    }

    /* Body background: prefer gradient when configured */
    @if(!empty($appliedTheme['bg_gradient_start']) && !empty($appliedTheme['bg_gradient_end']))
      body{ background: linear-gradient({{ $appliedTheme['bg_gradient_angle'] ?? 90 }}deg, {{ $appliedTheme['bg_gradient_start'] }}, {{ $appliedTheme['bg_gradient_end'] }}); background-size:200% 200%; {{ ($appliedTheme['bg_animated'] ?? false) ? "animation: animatedGradient var(--animation-speed) ease infinite;" : '' }} }
    @else
      body{ background: var(--bg); }
    @endif
    .btn{ background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important; color: #fff; border:0; }
    .btn-alt{ background: linear-gradient(90deg,var(--btn-alt),var(--btn-primary)) !important; color: #fff; border:0; }
    /* Unified theme rules: catch common custom button classes and plain buttons */
    [class*="btn"], [class*="button"], a[class*="btn"], a[class*="button"], .action-btn, .link-button, .icon-btn {
      background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important;
      color: var(--btn-primary-text) !important;
      border: 0 !important;
    }
    /* make the logout button larger and prominent */
    .logout-form .link-button {
      padding: 8px 14px !important;
      border-radius: 10px !important;
      font-weight: 800 !important;
      background: linear-gradient(90deg,var(--btn-alt),var(--btn-primary)) !important;
      color: var(--btn-primary-text) !important;
    }

    /* Topbar accent (notifications, admin badge, bell icon) */
    .top-action { color: var(--topbar-accent) !important; }
    .notification-bell, .notification-bell svg { color: var(--topbar-accent) !important; }
    #notif-badge { background: var(--notif-badge-bg) !important; color: var(--notif-badge-text) !important; padding:4px 8px;border-radius:999px;font-weight:700;display:inline-block; }
    /* primary/alt helpers */
    .action-btn.primary, .btn-primary { background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important; color: var(--btn-primary-text) !important; }
    .action-btn.alt, .btn-alt { background: linear-gradient(90deg,var(--btn-alt),var(--btn-primary)) !important; color: var(--btn-primary-text) !important; }
    @if(!empty($appliedTheme['sidebar_gradient_start']) && !empty($appliedTheme['sidebar_gradient_end']))
      .sidebar{ background: linear-gradient({{ $appliedTheme['sidebar_gradient_angle'] ?? 90 }}deg, {{ $appliedTheme['sidebar_gradient_start'] }}, {{ $appliedTheme['sidebar_gradient_end'] }}); color: var(--sidebar-text); background-size:200% 200%; {{ ($appliedTheme['sidebar_animated'] ?? false) ? "animation: animatedGradient var(--animation-speed) ease infinite;" : '' }} }
    @else
      .sidebar{ background: var(--sidebar-bg); color: var(--sidebar-text); }
    @endif
    .sidebar .nav .nav-item{ color: var(--sidebar-text); transition: background .18s ease, transform .12s ease; }
    @php
      $_sbHex = $appliedTheme['sidebar_bg'] ?? '#0f172a';
      $_sbH = ltrim($_sbHex, '#');
      if (strlen($_sbH) === 3) { $_sbH = $_sbH[0].$_sbH[0].$_sbH[1].$_sbH[1].$_sbH[2].$_sbH[2]; }
      $_sbLum = (strlen($_sbH) === 6)
        ? (0.2126*hexdec(substr($_sbH,0,2)) + 0.7152*hexdec(substr($_sbH,2,2)) + 0.0722*hexdec(substr($_sbH,4,2))) / 255
        : 0.95;
      $_sbIsDark = $_sbLum < 0.45;
    @endphp
    .sidebar .nav .nav-item:hover {
      background: {{ $_sbIsDark ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.08)' }} !important;
      transform: translateX(4px);
    }
    .sidebar .nav .nav-item:active {
      background: {{ $_sbIsDark ? 'rgba(255,255,255,0.18)' : 'rgba(0,0,0,0.12)' }} !important;
    }
    /* Topbar: use configured topbar meta when present */
    @if(!empty($appliedTheme['meta']['topbar']['gradient_start']) && !empty($appliedTheme['meta']['topbar']['gradient_end']))
      .topbar { background: linear-gradient({{ $appliedTheme['meta']['topbar']['gradient_angle'] ?? 90 }}deg, {{ $appliedTheme['meta']['topbar']['gradient_start'] }}, {{ $appliedTheme['meta']['topbar']['gradient_end'] }}); background-size:200% 200%; {{ ($appliedTheme['meta']['topbar']['animated'] ?? false) ? "animation: animatedGradient var(--animation-speed) ease infinite;" : '' }}; color: var(--topbar-text); }
    @else
      .topbar{ background: var(--topbar-bg); color: var(--topbar-text); }
    @endif

    /* global gradient utility */
    .global-gradient {
      background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
      background-size: 200% 200%;
      {{ ($appliedTheme['animated_gradient'] ?? false) ? "animation: animatedGradient var(--animation-speed) ease infinite;" : '' }}
    }

    /* Per-button variant rules from theme.button_variants (JSON) */
    @php
      // Merge explicit button_variants with any meta-defined buttons
      $__btnVars = $appliedTheme['button_variants'] ?? [];
      if (!empty($appliedTheme['meta']['buttons']) && is_array($appliedTheme['meta']['buttons'])) {
        $__btnVars = array_merge($__btnVars, $appliedTheme['meta']['buttons']);
      }

      // helper to pick readable text color based on background hex
      function _pickTextColor($hex) {
        if (! $hex) return '#111827';
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
        if (strlen($h) !== 6) return '#111827';
        $r = hexdec(substr($h,0,2)); $g = hexdec(substr($h,2,2)); $b = hexdec(substr($h,4,2));
        // relative luminance
        $lum = (0.2126*$r + 0.7152*$g + 0.0722*$b) / 255;
        return ($lum < 0.5) ? '#ffffff' : '#111827';
      }
    @endphp
    @if(!empty($__btnVars) && is_array($__btnVars))
      @foreach($__btnVars as $vname => $v)
        @php
          $bg = $v['bg'] ?? null;
          $color = $v['color'] ?? null;
          $gstart = $v['gradient_start'] ?? null;
          $gend = $v['gradient_end'] ?? null;
          if (empty($color)) {
            // if gradient present use start for contrast check, else bg
            $sample = $gstart ?? $bg ?? null;
            $color = _pickTextColor($sample);
          }
        @endphp
        .btn-{{ $vname }}{
          @if(!empty($gstart) && !empty($gend))
            background: linear-gradient(90deg, {{ $gstart }}, {{ $gend }}) !important;
          @elseif(!empty($bg))
            background: {{ $bg }} !important;
          @endif
          color: {{ $color }} !important;
        }
        /* map common two-class patterns like "action-btn danger" to this variant */
        .action-btn.{{ $vname }}, .link-button.{{ $vname }}, .{{ $vname }}.action-btn {
          @if(!empty($gstart) && !empty($gend))
            background: linear-gradient(90deg, {{ $gstart }}, {{ $gend }}) !important;
          @elseif(!empty($bg))
            background: {{ $bg }} !important;
          @endif
          color: {{ $color }} !important;
        }
      @endforeach
    @endif

    /* Price and payment badge hooks (templates can add these classes) */
    .price-amount { color: var(--price-color, #111827) !important; }
    .payment-status-badge { color: var(--payment-badge-text, #ffffff) !important; background: var(--payment-badge-bg, #6b7280) !important; }

    /* payment badge states mapping */
    .payment-status-badge[data-state="pagado"]{ background: var(--payment-badge-bg-pagado) !important; color: var(--payment-badge-text-pagado) !important; }
    .payment-status-badge[data-state="pendiente"]{ background: var(--payment-badge-bg-pendiente) !important; color: var(--payment-badge-text-pendiente) !important; }
    .payment-status-badge[data-state="fallido"], .payment-status-badge[data-state="failed"]{ background: var(--payment-badge-bg-fallido) !important; color: var(--payment-badge-text-fallido) !important; }
    .payment-status-badge[data-state="parcial"]{ background: var(--payment-badge-bg-parcial) !important; color: var(--payment-badge-text-parcial) !important; }

    /* price amount colors by reservation estado */
    .price-amount[data-estado="confirmada"]{ color: var(--price-color-confirmada) !important; }
    .price-amount[data-estado="pendiente"]{ color: var(--price-color-pendiente) !important; }
    .price-amount[data-estado="cancelada"]{ color: var(--price-color-cancelada) !important; }
    .price-amount[data-estado] { color: var(--price-color-default) !important; }

    /* Button interaction animations */
    .btn { transition: transform var(--hover-animation-duration), box-shadow var(--hover-animation-duration), filter var(--hover-animation-duration); }
    .btn:hover, .btn:focus {
      @if(!empty($appliedTheme['hover_animation']) && $appliedTheme['hover_animation'] == 'lift')
        transform: translateY(-4px);
      @elseif(!empty($appliedTheme['hover_animation']) && $appliedTheme['hover_animation'] == 'scale')
        transform: scale(1.03);
      @elseif(!empty($appliedTheme['hover_animation']) && $appliedTheme['hover_animation'] == 'glow')
        animation: glow var(--hover-animation-duration) ease-in-out;
      @endif
    }

    /* Float / autoplay animations applied via variant classes */
    .autoplay-float { animation: floatY var(--float-animation-duration) ease-in-out infinite; }
    .autoplay-pulse { animation: pulseScale var(--float-animation-duration) ease-in-out infinite; }

    body.sidebar-right .sidebar{left:auto !important;right:0 !important}
    body.sidebar-right .main{margin-left:0 !important;margin-right:260px !important}
    body.sidebar-right .topbar{position:relative}
    body.sidebar-right .topbar .topbar-right{right:52px !important;margin-right:0 !important}
    body.sidebar-right #sidebar-toggle{
      position:fixed !important;
      top:14px !important;
      right:12px !important;
      left:auto !important;
      transform:none !important;
      margin-left:0 !important;
      z-index:120 !important;
    }
    body.sidebar-right.sidebar-collapsed .main{margin-right:0 !important}
    body.sidebar-right.sidebar-collapsed .sidebar{transform:translateX(260px) !important}
    body.sidebar-right .sidebar .nav-item:hover{transform:translateX(-4px)}
    body.sidebar-right.sidebar-collapsed .icon-btn.show-desktop{left:auto;right:12px}
    /* Layout variant CSS removed — only sidebar side controls layout now. */

    @media (max-width:900px){
      body.sidebar-right .main{margin-right:0}
      body.sidebar-right .sidebar{left:auto;right:0;transform:translateX(110%)}
      body.sidebar-right .sidebar.open{transform:translateX(0)}
      body.sidebar-right #sidebar-toggle{top:12px !important;right:10px !important}
      body.sidebar-right .topbar .topbar-right{margin-right:46px}
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
<body class="app-root {{ $layoutSidebarSide === 'right' ? 'sidebar-right' : 'sidebar-left' }} layout-section-{{ $layoutSection }}">
  <script>
    // Show session error messages stored by interceptors (persist across redirect)
    (function(){
      try {
        const msg = localStorage.getItem('session_error');
        if (!msg) return;
        localStorage.removeItem('session_error');
        const el = document.createElement('div');
        el.className = 'session-error-toast';
        el.style.position = 'fixed';
        el.style.top = '18px';
        el.style.right = '18px';
        el.style.zIndex = 1600;
        el.style.padding = '10px 14px';
        el.style.borderRadius = '8px';
        el.style.boxShadow = '0 10px 30px rgba(0,0,0,0.12)';
        el.style.background = 'var(--card)';
        el.style.color = 'var(--text-color)';
        el.textContent = msg;
        document.addEventListener('DOMContentLoaded', function(){ document.body.appendChild(el); setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); }, 6500); });
      } catch(e) {}
    })();
  </script>
  @if(auth()->check() && session('session_browser_token'))
  <script>
    (function(){
      const serverToken = @json(session('session_browser_token'));
      try {
        const params = new URLSearchParams(window.location.search || '');
        if (params.get('session_init') === '1') {
          try { sessionStorage.setItem('hr_session_token', serverToken); } catch(e) {}
          // remove the flag from the URL without reloading
          try { const u = new URL(window.location.href); u.searchParams.delete('session_init'); history.replaceState({}, '', u.toString()); } catch(e) {}
        } else {
          try {
            if (sessionStorage.getItem('hr_session_token') !== serverToken) {
              // Invalidate server session first to avoid the guest->auth redirect loop
              try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch("{{ route('logout') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                  .finally(() => { window.location = "{{ route('login') }}?session_closed=1"; });
              } catch(e) { window.location = "{{ route('login') }}?session_closed=1"; }
            }
          } catch(e) {
            try {
              const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
              fetch("{{ route('logout') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .finally(() => { window.location = "{{ route('login') }}?session_closed=1"; });
            } catch(err) { window.location = "{{ route('login') }}?session_closed=1"; }
          }
        }
      } catch(e) {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          fetch("{{ route('logout') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .finally(() => { window.location = "{{ route('login') }}?session_closed=1"; });
        } catch(err) { try { window.location = "{{ route('login') }}?session_closed=1"; } catch(_) {} }
      }
    })();
  </script>
  @endif
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
      <a class="nav-item" href="{{ route('dashboard', ['panel' => 'calendar']) }}">{{ in_array(($currentUser->rol ?? ''), ['admin', 'recepcionista'], true) ? 'Calendario' : 'Mi calendario' }}</a>
      <a class="nav-item" href="/reservaciones">Reservaciones</a>
      <a class="nav-item" href="/propiedades">Propiedades</a>
      <a class="nav-item" href="/notificaciones">Notificaciones</a>
      <a class="nav-item" href="{{ route('pagos.codes') }}">{{ $effectiveIsAdmin ? 'Códigos QR' : 'Mis códigos' }}</a>
      @if(!($effectiveIsAdmin))
      <a class="nav-item" href="{{ route('pagos.mine') }}">Mis Pagos</a>
      <a class="nav-item" href="{{ route('tarjetas.index') }}">Tarjetas</a>
      @endif
      @if($effectiveIsAdmin)
        <a class="nav-item" href="/users">Usuarios</a>
        <a class="nav-item" href="{{ route('admin.logs') }}">Logs</a>
        <a class="nav-item" href="{{ route('images.index') }}">Imágenes</a>
        <a class="nav-item" href="{{ route('pagos.index') }}">Pagos</a>
        <a class="nav-item" href="{{ route('tarjetas.index') }}">Tarjetas</a>
        <a class="nav-item" href="{{ route('admin.themes') }}">Apariencia</a>
      @endif
      <form method="POST" action="{{ route('logout') }}" class="nav-item logout-form" style="display:flex;">
        @csrf
        <button class="link-button danger" type="submit">Cerrar sesión</button>
      </form>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button id="sidebar-toggle" class="icon-btn show-desktop sidebar-toggle-btn" aria-label="Abrir menú">☰</button>
      <div class="topbar-right">
        @include('partials.notification-bell')
        <a href="{{ route('notifications.preferences') }}" class="top-action">Perfil</a>
        @if($effectiveIsAdmin)
          <span class="top-action preview-admin-only" style="color:#ef4444;font-weight:700;">Usted inicio sesion como Administrador</span>
        @endif
      </div>
    </header>

    <main class="content">
      @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
      @endif

      @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
      @endif

      @if($errors->any())
        <x-alert type="error">
          {{ $errors->first() }}
        </x-alert>
      @endif

      @yield('content')
    </main>

    <footer class="footer">
      <div>© {{ date('Y') }} HomeRes</div>
    </footer>
  </div>

  <script>
    (function () {
      const sidebar = document.getElementById('sidebar');
      const toggles = document.querySelectorAll('.sidebar-toggle-btn');
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
      toggles.forEach((toggleBtn) => {
        toggleBtn.addEventListener('click', () => {
          if (isMobile()) {
            sidebar.classList.toggle('open');
          } else {
            const collapsed = body.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch(e){}
          }
        });
      });

      closeBtn && closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));

      document.addEventListener('click', (e) => {
        if (isMobile() && !sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle-btn')) {
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
  
  <script>
    (function(){
      // Take server-side detected button variant keys and at runtime
      // add `btn-<slug>` classes to elements whose visible text matches.
      try {
        var btnVars = @json(array_keys($__btnVars ?? []));
      } catch(e){ var btnVars = []; }

      function slugify(s){
        return String(s || '').toLowerCase().trim()
          .replace(/\s+/g,'-')
          .replace(/[^a-z0-9\-]/g,'')
          .replace(/\-+/g,'-');
      }

      function elementText(el){
        if(!el) return '';
        if(el.tagName === 'INPUT') return (el.value || '').trim();
        return (el.textContent || '').trim();
      }

      document.addEventListener('DOMContentLoaded', function(){
        if(!btnVars || !btnVars.length) return;
        var known = new Set(btnVars.map(function(b){ return String(b); }));
        var sel = 'button, a, input[type=submit], input[type=button]';
        var els = Array.prototype.slice.call(document.querySelectorAll(sel));
        els.forEach(function(el){
          // if already has a custom btn- class, skip
          var hasVariant = Array.prototype.slice.call(el.classList || []).some(function(c){ return c.indexOf('btn-')===0; });
          if(hasVariant) return;
          var txt = elementText(el);
          if(!txt) return;
          var key = slugify(txt);
          if(known.has(key)) {
            el.classList.add('btn-' + key);
          }
        });
      });
    })();
  </script>

  @section('scripts')
  @show
   @stack('scripts')

  {{-- Last-priority theme overrides: these load AFTER any in-page <style> blocks --}}
  <style>
    /* Cards with inline or in-page background:#fff */
    .card, .card.table-card, .card-wide,
    .list-card, .pr-card, .field-card, .uploader, .calendar,
    .comment, .person-selector, .modal-panel, .confirm-card,
    .delete-modal-panel, .picker-panel {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    div[style*="background:#fff"],
    form[style*="background:#fff"],
    div[style*="background: #fff"],
    form[style*="background: #fff"] {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    .table, .table th, .table td {
      color: var(--text-color) !important;
      border-bottom-color: var(--input-border) !important;
    }
    .table th { background: var(--table-header-bg) !important; }
    /* In-page .card overrides from tarjetas/pagos <style> blocks */
    .card { background: var(--card) !important; color: var(--text-color) !important; }
    .table th, .table td { color: var(--text-color) !important; }
    .input-inline input, .input-inline select {
      background: var(--input-bg) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    .modal-panel { background: var(--card) !important; color: var(--text-color) !important; }
    input:not([type="color"]), select, textarea {
      background: var(--input-bg) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    /* Color picker: restore native swatch */
    input[type="color"] {
      -webkit-appearance: color !important;
      appearance: auto !important;
      background: none !important;
      border: 1px solid var(--input-border, #ccc) !important;
      padding: 2px !important;
      min-width: 40px !important;
      min-height: 28px !important;
      cursor: pointer !important;
      border-radius: 4px !important;
    }

    /* ===== Reservaciones: table, rv-day, btn-edit ===== */
    .table { background: var(--card) !important; }
    .rv-day {
      background: var(--card) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    .rv-day.past {
      background: var(--badge-bg) !important;
      color: var(--muted) !important;
      border-color: var(--input-border) !important;
    }
    .rv-day.today {
      background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important;
      color: var(--btn-primary-text) !important;
    }
    .rv-day .date { color: var(--text-color) !important; }
    .rv-day .label { color: var(--text-color) !important; }
    .rv-day div[style*="color:#6b7280"] { color: var(--muted) !important; }
    .btn-edit {
      background: linear-gradient(90deg,var(--btn-primary),var(--btn-alt)) !important;
      color: var(--btn-primary-text) !important;
      border: 0 !important;
      padding: 8px 14px !important;
      border-radius: 8px !important;
      font-weight: 700 !important;
      cursor: pointer !important;
    }

    /* ===== Propiedades: pr-card, pr-comments, pr-thumb, pr-title ===== */
    .pr-card {
      background: var(--card) !important;
      color: var(--text-color) !important;
    }
    .pr-card .pr-title { color: var(--text-color) !important; }
    .pr-card .pr-meta { color: var(--muted) !important; }
    .pr-comments {
      background: var(--card) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    .pr-thumb {
      background: var(--thumb-bg) !important;
    }
    .service-chip {
      background: var(--badge-bg) !important;
      color: var(--text-color) !important;
    }

    /* ===== Generic: override in-page :root redefinitions ===== */
    :root {
      --card: {{ $_isDark ? '#1e293b' : '#ffffff' }};
      --card-bg: {{ $_isDark ? '#1e293b' : '#ffffff' }};
      --muted: {{ $_isDark ? '#94a3b8' : '#6b7280' }};
      --shadow: 0 12px 34px rgba(2,6,23,{{ $_isDark ? '0.18' : '0.06' }});
    }

    /* Row cancelled in dark: softer look */
    .table tbody tr.row-cancelled {
      background: var(--table-row-odd) !important;
    }

    /* rv-thumb inline style override */
    .rv-thumb div[style*="background:#f3f4f6"] {
      background: var(--thumb-bg) !important;
      color: var(--muted) !important;
    }

    /* ===== Status badges — universal semantic colours ===== */
    .badge-pendiente { background: linear-gradient(90deg,#f59e0b,#f97316) !important; color: #fff !important; }
    .badge-confirmada { background: linear-gradient(90deg,#10b981,#059669) !important; color: #fff !important; }
    .badge-cancelada { background: linear-gradient(90deg,#ef4444,#dc2626) !important; color: #fff !important; }
    .badge-pagada { background: linear-gradient(90deg,#6366f1,#06b6d4) !important; color: #fff !important; }
    /* Payment status badges — keep gradient colours always */
    .pay-pagado { background: linear-gradient(90deg,#10b981,#059669) !important; color: #fff !important; }
    .pay-pendiente { background: linear-gradient(90deg,#f59e0b,#f97316) !important; color: #fff !important; }
    .pay-fallido { background: linear-gradient(90deg,#ef4444,#dc2626) !important; color: #fff !important; }
    .pay-parcial { background: linear-gradient(90deg,#6366f1,#06b6d4) !important; color: #fff !important; }
    .pay-unknown { background: #6b7280 !important; color: #fff !important; }

    /* ===== btn-group-col: transparent so it blends with table row ===== */
    .btn-group-col {
      background: transparent !important;
    }

    /* ===== Delete buttons — always red across all themes ===== */
    .action-btn.delete, .action-btn.danger, .link-button.danger, .btn.danger, .btn-danger,
    .link-button[data-open-delete] {
      background: linear-gradient(90deg, #ef4444, #dc2626) !important;
      color: #fff !important;
    }

    /* ===== Imagenes: dir-item folder cards ===== */
    .dir-item {
      background: var(--card) !important;
      color: var(--text-color) !important;
      border: 1px solid var(--input-border) !important;
      border-radius: 8px !important;
    }
    .dir-item div[style*="font-weight"] {
      color: var(--text-color) !important;
    }
    .dir-item div[style*="background: rgb(255, 255, 255)"],
    .dir-item div[style*="background:rgb(255, 255, 255)"],
    .dir-item div[style*="background: rgb(255"] {
      background: var(--card) !important;
      border-color: var(--input-border) !important;
    }
    .dir-list {
      background: transparent !important;
    }
    .uploader {
      background: var(--card) !important;
      color: var(--text-color) !important;
      border-color: var(--input-border) !important;
    }
    .preview {
      background: var(--badge-bg) !important;
      color: var(--muted) !important;
    }

    /* ===== Toast notifications ===== */
    .notification-toast {
      background: var(--card) !important;
      color: var(--text-color) !important;
      box-shadow: 0 12px 36px rgba(2,6,23,{{ $_isDark ? '0.3' : '0.12' }}) !important;
    }
    .notification-toast .toast-icon-placeholder {
      background: var(--badge-bg) !important;
    }
    .notification-toast .toast-body-text {
      color: var(--muted) !important;
    }
    .notification-toast button {
      color: var(--muted) !important;
      background: transparent !important;
    }
    #notif-toast-container .notification-toast div[style*="background:#f3f4f6"] {
      background: var(--badge-bg) !important;
    }
    #notif-toast-container .notification-toast div[style*="color:#6b7280"] {
      color: var(--muted) !important;
    }
    #notif-toast-container .notification-toast button {
      color: var(--muted) !important;
      background: transparent !important;
    }

    /* ===== Notification dropdown panel ===== */
    #notif-dropdown {
      background: var(--card) !important;
      color: var(--text-color) !important;
      box-shadow: 0 8px 30px rgba(2,6,23,{{ $_isDark ? '0.3' : '0.08' }}) !important;
    }
    #notif-dropdown strong {
      color: var(--text-color) !important;
    }
    /* header & footer borders */
    #notif-dropdown > div {
      border-color: var(--input-border) !important;
    }
    /* notification items */
    #notif-dropdown .notif-item {
      border-bottom-color: var(--input-border) !important;
      color: var(--text-color) !important;
    }
    #notif-dropdown .notif-item div {
      color: var(--text-color) !important;
    }
    /* icon placeholder circles */
    #notif-dropdown .notif-item .notif-icon-placeholder {
      background: var(--badge-bg) !important;
    }
    /* body / subtitle text */
    #notif-dropdown .notif-item .notif-body-text {
      color: var(--muted) !important;
    }
    /* empty-state text */
    #notif-dropdown #notif-list > div {
      color: var(--muted) !important;
    }
    /* "Marcar todas" button */
    #notif-dropdown #notif-mark-all {
      background: var(--badge-bg) !important;
      color: var(--text-color) !important;
      border: 1px solid var(--input-border) !important;
    }
    /* "Ver todas" link */
    #notif-dropdown a[href*="notification"] {
      color: var(--btn-primary, #06b6d4) !important;
    }
    /* "Marcar" per-item button */
    #notif-dropdown .notif-mark-read {
      background: transparent !important;
      color: var(--btn-primary, #06b6d4) !important;
      border: 0 !important;
    }
    /* "ver recurso" action hint button */
    #notif-dropdown .notif-open-resource {
      background: var(--badge-bg) !important;
      color: var(--text-color) !important;
    }
    /* "Ver más" button */
    #notif-dropdown #notif-load-more {
      background: var(--badge-bg) !important;
      color: var(--text-color) !important;
      border: 1px solid var(--input-border) !important;
    }
    /* tooltip */
    #notif-dropdown .notif-tooltip {
      background: var(--badge-bg) !important;
      color: var(--text-color) !important;
    }
  </style>
 </body>
 </html>
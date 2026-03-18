<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cuenta baneada</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <style>
    .banned-wrap{width:100%;max-width:520px}
    .banned-card{background:#fff;border-radius:12px;padding:26px;box-shadow:0 6px 24px rgba(30,30,40,0.08);border:1px solid #fecaca}
    .banned-title{margin:0 0 8px;font-size:22px;color:#991b1b}
    .banned-text{margin:0 0 12px;color:#374151;line-height:1.5}
    .banned-email{font-size:13px;color:#6b7280;margin-bottom:12px}
    .banned-actions{display:flex;gap:8px;flex-wrap:wrap}
  </style>
</head>
<body>
  <main class="auth-page banned-wrap">
    <section class="banned-card">
      <h1 class="banned-title">Tu cuenta fue baneada</h1>
      <p class="banned-text">No puedes iniciar sesión porque esta cuenta fue suspendida por un administrador.</p>
      @if(!empty($email))
        <p class="banned-email">Cuenta: {{ $email }}</p>
      @endif
      <p class="banned-text">Si crees que esto es un error, contacta al administrador del sistema.</p>
      <div class="banned-actions">
        <a href="{{ route('login') }}" class="btn">Volver al login</a>
      </div>
    </section>
  </main>
</body>
</html>

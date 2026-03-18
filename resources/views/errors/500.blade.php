<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Error del servidor — HomeRes</title>
  <style>
    *{box-sizing:border-box}
    body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:16px}
    .box{background:#fff;border-radius:14px;padding:40px 36px;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(2,6,23,0.08)}
    .code{font-size:4rem;font-weight:800;color:#e5e7eb;line-height:1;margin-bottom:4px}
    h1{color:#1e293b;margin:0 0 8px;font-size:1.4rem}
    p{color:#6b7280;margin:0 0 24px;line-height:1.6}
    .btn{display:inline-block;background:linear-gradient(90deg,#6366f1,#06b6d4);color:#fff;padding:10px 24px;border-radius:9px;text-decoration:none;font-weight:700;transition:opacity .15s}
    .btn:hover{opacity:.88}
  </style>
</head>
<body>
  <div class="box">
    <div class="code">500</div>
    <h1>Error del servidor</h1>
    <p>Ocurrió un error inesperado. Nuestro equipo ha sido notificado. Por favor intenta de nuevo en unos momentos.</p>
    <a class="btn" href="{{ url('/') }}">Ir al inicio</a>
  </div>
</body>
</html>

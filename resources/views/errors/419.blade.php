<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sesión expirada — HomeRes</title>
  <style>
    *{box-sizing:border-box}
    body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:16px}
    .box{background:#fff;border-radius:14px;padding:40px 36px;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(2,6,23,0.08)}
    .icon{font-size:2.8rem;margin-bottom:12px}
    h1{color:#1e293b;margin:0 0 8px;font-size:1.4rem}
    p{color:#6b7280;margin:0 0 24px;line-height:1.6}
    .btn{display:inline-block;background:linear-gradient(90deg,#6366f1,#06b6d4);color:#fff;padding:10px 24px;border-radius:9px;text-decoration:none;font-weight:700;transition:opacity .15s}
    .btn:hover{opacity:.88}
    .back{display:block;margin-top:14px;color:#6366f1;text-decoration:none;font-size:0.9rem}
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">419</div>
    <h1>Sesión expirada</h1>
    <p>Tu token de seguridad ha caducado (código 419). Esto ocurre cuando la página lleva mucho tiempo abierta. Regresa e intenta la acción de nuevo.</p>
    <a class="btn" href="{{ url()->previous() ?: url('/') }}">Volver</a>
    <a class="back" href="{{ url('/') }}">Ir al inicio</a>
  </div>
</body>
</html>

@extends('layouts.app')

@section('title','Reservaciones')

@section('content')
@php
  use Carbon\Carbon;
  $currentUser = $currentUser ?? auth()->user();
  $layoutPreviewMode = ($currentUser && ($currentUser->rol ?? '') === 'admin') ? session('layout_preview_as', 'admin') : 'user';
  $isAdmin = ($isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin')) && $layoutPreviewMode !== 'user';
  $propertyCarouselSlides = [];
  $seenPropertyImages = [];
  foreach (($propiedades ?? []) as $propiedadPreview) {
    if (!$propiedadPreview) {
      continue;
    }

    $rutaPreview = trim((string) data_get($propiedadPreview, 'ruta_img', ''), '/\\');
    if ($rutaPreview === '') {
      continue;
    }

    $previewFolder = null;
    $previewFullPath = public_path($rutaPreview);
    if (is_dir($previewFullPath)) {
      $previewFolder = $rutaPreview;
    } elseif (is_file($previewFullPath)) {
      $candidateFolder = trim(dirname($rutaPreview), '/\\.');
      if ($candidateFolder !== '') {
        $candidateFullPath = public_path($candidateFolder);
        if (is_dir($candidateFullPath)) {
          $previewFolder = $candidateFolder;
        }
      }
    }

    if (!$previewFolder) {
      continue;
    }

    $galleryFiles = @scandir(public_path($previewFolder)) ?: [];
    foreach ($galleryFiles as $galleryFile) {
      $extension = strtolower(pathinfo($galleryFile, PATHINFO_EXTENSION));
      if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $relativeImagePath = trim($previewFolder, '/\\') . '/' . $galleryFile;
        if (isset($seenPropertyImages[$relativeImagePath])) {
          continue;
        }

        $seenPropertyImages[$relativeImagePath] = true;
        $propertyCarouselSlides[] = [
          'id' => 'property-slide-' . md5($relativeImagePath),
          'property_id' => data_get($propiedadPreview, 'id'),
          'name' => data_get($propiedadPreview, 'nombre', 'Propiedad'),
          'code' => data_get($propiedadPreview, 'codigo'),
          'location' => data_get($propiedadPreview, 'ubicacion'),
          'price' => data_get($propiedadPreview, 'precio_noche'),
          'image' => asset($relativeImagePath),
        ];
      }
    }
  }

  $today = Carbon::today()->startOfDay();
  $hasRelevantUserReservations = (bool) ($hasRelevantUserReservations ?? collect($reservaciones ?? [])->contains(function ($reservation) use ($currentUser, $isAdmin, $today) {
    if ($isAdmin || ! $currentUser) {
      return false;
    }

    $reservationUserId = (int) (data_get($reservation, 'usuario_id') ?: data_get($reservation, 'user.id'));
    if ($reservationUserId !== (int) $currentUser->id) {
      return false;
    }

    if (strtolower(trim((string) data_get($reservation, 'estado', ''))) === 'cancelada') {
      return false;
    }

    $checkOutRaw = data_get($reservation, 'check_out');
    if (empty($checkOutRaw)) {
      return true;
    }

    try {
      return Carbon::parse($checkOutRaw)->startOfDay()->gte($today);
    } catch (\Throwable $e) {
      return true;
    }
  }));

  $shouldShowHeroPropertiesPanel = ! $isAdmin;
  $showHeroPropertiesPanelFirst = $shouldShowHeroPropertiesPanel && (! $currentUser || ! $hasRelevantUserReservations);
  $heroEyebrow = ! $isAdmin && ! $hasRelevantUserReservations ? 'Sin reservaciones vigentes' : 'Propiedades destacadas';
  $heroTitle = ! $isAdmin && ! $hasRelevantUserReservations
    ? 'Reserva tu próxima estadía'
    : 'Explora más opciones disponibles';
  $heroSubtitle = ! $isAdmin && ! $hasRelevantUserReservations
    ? 'Como no tienes reservaciones activas ni pendientes para fechas actuales o futuras, aquí puedes descubrir propiedades para reservar.'
    : 'Tus reservaciones vigentes aparecen primero y debajo puedes revisar más propiedades.';
@endphp

<style>
:root{
  --accent-1: #6366f1;
  --accent-2: #06b6d4;
  --danger-1: #ef4444;
  --danger-2: #f97316;
  --muted: #6b7280;
  --card-bg: #fff;
  --shadow: 0 12px 34px rgba(2,6,23,0.06);
  --radius: 10px;
  --btn-radius: 8px;
  --transition: .16s ease;
}

.content{
  max-width: 1680px !important;
}

.container{max-width:1580px;margin:0 auto;padding:18px;}
.split { display:flex; gap:18px; align-items:flex-start; }
.left { flex:1.6; min-width:420px; }
.right { width:360px; }
.card-wide{ background:var(--card-bg);padding:14px;border-radius:12px;box-shadow:var(--shadow); }

.table { width:100%; border-collapse:collapse; background:var(--card-bg); border-radius:8px; padding:8px; }
.table th, .table td{ padding:8px 10px; text-align:left; border-bottom:1px solid #f3f4f6; vertical-align:top; }

.rv-thumb{ width:80px;height:56px;border-radius:8px;overflow:hidden;border:1px solid #eef2f7; display:flex; align-items:center; justify-content:center; background:#fbfdff; }
.rv-thumb img{ width:100%;height:100%;object-fit:cover;display:block; transition: transform var(--transition), filter var(--transition), opacity var(--transition); }
.rv-days { display:flex;gap:6px;flex-wrap:wrap;margin-top:8px; }
.rv-day { min-width:64px;padding:8px;border-radius:8px;text-align:center;background:#f8fafc;border:1px solid #eef2f7;font-size:12px;color:#374151; }
.rv-day .date { font-weight:800; display:block; margin-bottom:6px; }

.action-btn{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 10px;
  border-radius:var(--btn-radius);
  border:0;
  font-weight:700;
  font-size:0.92rem;
  line-height:1;
  cursor:pointer;
  transition: transform var(--transition), box-shadow var(--transition), opacity var(--transition);
  text-decoration:none;
  color: #0f172a;
  background: transparent;
  border:1px solid #e6e9ee;
  box-shadow:none;
}

.action-btn.view{
  background:transparent;
  color:#2563eb;
  border:1px solid rgba(37,99,235,0.12);
}
.action-btn.view:hover{ transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.08); }

.action-btn.primary{
  color:#fff;
  background: linear-gradient(90deg,var(--accent-1),var(--accent-2));
  border: 0;
  box-shadow: 0 8px 20px rgba(99,102,241,0.12);
}
.action-btn.primary:hover{ transform: translateY(-2px); box-shadow: 0 14px 40px rgba(6,182,212,0.12); }

@keyframes reservationListPayPulse {
  0% {
    background: linear-gradient(135deg, #6b8e23, #7ea63a, #8cff3a);
    box-shadow: 0 14px 32px rgba(107,142,35,0.24), 0 0 0 rgba(132,255,58,0);
  }
  50% {
    background: linear-gradient(135deg, #7fa129, #9adf28, #7fff00);
    box-shadow: 0 18px 38px rgba(127,255,0,0.3), 0 0 22px rgba(132,255,58,0.26);
  }
  100% {
    background: linear-gradient(135deg, #6b8e23, #7ea63a, #8cff3a);
    box-shadow: 0 14px 32px rgba(107,142,35,0.24), 0 0 0 rgba(132,255,58,0);
  }
}

@keyframes reservationListPayShine {
  to {
    transform: translateX(120%);
  }
}

.action-btn.pay-cta {
  position: relative;
  justify-content: center;
  width: 100%;
  min-height: 58px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(224,255,196,0.48);
  background: linear-gradient(135deg, #6b8e23, #7ea63a, #8cff3a);
  color: #fff;
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: .03em;
  text-transform: uppercase;
  text-shadow: 0 2px 12px rgba(12,24,8,0.34);
  box-shadow: 0 14px 32px rgba(107,142,35,0.24);
  overflow: hidden;
  animation: reservationListPayPulse 3.2s ease-in-out infinite;
}

.action-btn.pay-cta::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,0.14) 35%, rgba(255,255,255,0.26) 50%, transparent 68%);
  transform: translateX(-120%);
  animation: reservationListPayShine 2.8s linear infinite;
  pointer-events: none;
}

.action-btn.pay-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 42px rgba(127,255,0,0.34), 0 0 28px rgba(132,255,58,0.24);
}

.action-btn.pay-cta .pay-cta-symbol,
.action-btn.pay-cta .pay-cta-label {
  position: relative;
  z-index: 1;
}

.action-btn.pay-cta .pay-cta-symbol {
  font-size: 1.18rem;
  color: #f4ffe4;
  filter: drop-shadow(0 0 8px rgba(201,255,122,0.42));
}

.action-btn.danger{
  color:#fff;
  background: linear-gradient(90deg,var(--danger-1),var(--danger-2));
  border:0;
}
.action-btn.danger:hover{ transform: translateY(-2px); box-shadow: 0 10px 30px rgba(239,68,68,0.12); }

.action-btn.small{ padding:6px 8px; font-size:0.85rem; }

.action-btn[disabled], .action-btn.disabled {
  opacity:0.56;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}

.btn-group-col{ display:flex;flex-direction:column;gap:8px;align-items:flex-start; }

.modal, #rv-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999; padding:12px; }
.modal.open, #rv-modal.open { display:flex; }
.modal .modal-backdrop, #rv-modal > .modal-backdrop { position:absolute; inset:0; background:rgba(2,6,23,0.45); }

.modal-panel, #rv-modal > div, #rv-change-confirm-modal .modal-panel {
  position:relative;
  background:var(--card-bg);
  border-radius:var(--radius);
  padding:14px;
  width:100%;
  max-width:720px;
  max-height:90vh;
  overflow:auto;
  box-shadow: 0 18px 48px rgba(2,6,23,0.12);
  transform: translateY(6px);
  transition: transform var(--transition), opacity var(--transition);
}
.modal.open .modal-panel, #rv-modal.open > div, #rv-change-confirm-modal.open .modal-panel { transform:none; }

#rv-change-confirm-modal .modal-panel { max-width:480px; padding:18px; }

.modal-close, #rv-close { position:absolute; right:12px; top:8px; border:0; background:transparent; font-size:18px; cursor:pointer; padding:6px; border-radius:8px; transition: background var(--transition); }
.modal-close:hover, #rv-close:hover { background: rgba(15,23,42,0.04); }

.modal-panel h2, .modal-panel h3 { margin:0 0 8px 0; font-size:1.05rem; }

.modal-panel .modal-actions, #rv-change-confirm-modal .modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }

@media (max-width:720px){
  .modal-panel, #rv-modal > div { max-width:calc(100% - 24px); padding:12px; }
  .action-btn { font-size:0.88rem; padding:7px 9px; }
}

.action-btn:focus, .modal-close:focus, .link-button:focus { outline: 3px solid rgba(99,102,241,0.14); outline-offset:2px; }

.table tbody tr.row-inactive {
  position:relative;
  background:linear-gradient(180deg, rgba(254, 226, 226, 0.72), rgba(255, 245, 245, 0.96));
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}
.table tbody tr.row-inactive td { color:#7f1d1d; }
.table tbody tr.row-inactive .rv-thumb{
  border-color:rgba(239,68,68,.28);
  background:#fff5f5;
}
.table tbody tr.row-inactive .rv-thumb img{ filter:saturate(.82); opacity:.9; transform:scale(1); }
.rv-inactive-hint{
  display:inline-flex;
  align-items:center;
  gap:6px;
  margin-top:6px;
  padding:4px 8px;
  border-radius:999px;
  background:rgba(239,68,68,.12);
  color:#b91c1c;
  font-size:.76rem;
  font-weight:800;
  letter-spacing:.01em;
  opacity:0;
  transform:translateY(4px);
  transition:opacity .18s ease, transform .18s ease;
  pointer-events:none;
}
@media (min-width:901px){
  .table tbody tr.row-inactive:hover {
    transform:translateY(-1px);
    background:linear-gradient(180deg, rgba(254, 202, 202, 0.88), rgba(255, 237, 237, 1));
    box-shadow:inset 3px 0 0 #ef4444;
  }
  .table tbody tr.row-inactive:hover .rv-thumb img{ filter:none; opacity:1; transform:scale(1.03); }
  .table tbody tr.row-inactive:hover .rv-inactive-hint{ opacity:1; transform:translateY(0); }
}
@media (max-width:900px){
  .table tbody tr.row-inactive { background:linear-gradient(180deg, rgba(254, 226, 226, 0.78), rgba(255, 241, 242, 1)); }
  .table tbody tr.row-inactive .rv-inactive-hint{ opacity:1; transform:translateY(0); }
}

.muted{ color:var(--muted); }
.small{ font-size:0.9rem;color:var(--muted); }

/* Payment status badges */
.pay-badge{ display:inline-block;padding:6px 10px;border-radius:999px;font-weight:800;color:#fff;font-size:0.85rem; }
.pay-pagado{ background:linear-gradient(90deg,#10b981,#059669); }
.pay-pendiente{ background:linear-gradient(90deg,#f59e0b,#f97316); }
.pay-fallido{ background:linear-gradient(90deg,#ef4444,#dc2626); }
.pay-parcial{ background:linear-gradient(90deg,#6366f1,#06b6d4); }
.pay-unknown{ background:#6b7280; }

.hero-properties-panel{ margin:0 0 16px; }

.hero-properties-shell{
  position:relative;
  overflow:hidden;
  border-radius:20px;
  padding:16px;
  background:
    radial-gradient(circle at 16% 14%, rgba(255,255,255,0.42), transparent 20%),
    radial-gradient(circle at 84% 82%, rgba(255,255,255,0.14), transparent 22%),
    linear-gradient(135deg, var(--btn-primary, #6366f1) 0%, var(--btn-alt, #06b6d4) 100%);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.72),
    0 24px 56px rgba(2,6,23,0.14);
  transition:transform var(--transition), box-shadow var(--transition);
}

.hero-properties-shell::before{
  content:'';
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, rgba(255,255,255,0.18), rgba(255,255,255,0.02) 42%, rgba(5,37,70,0.12) 100%);
  pointer-events:none;
}

.hero-properties-shell:hover{
  transform:translateY(-2px);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.8),
    0 28px 64px rgba(2,6,23,0.18);
}

.hero-properties-inner,
.hero-properties-header,
.hero-properties-copy,
.hero-properties-carousel,
.hero-properties-controls,
.hero-properties-dots{
  position:relative;
  z-index:1;
}

.hero-properties-inner{
  display:flex;
  flex-direction:column;
  gap:12px;
}

.hero-properties-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}

.hero-properties-copy{
  display:flex;
  flex-direction:column;
  gap:6px;
  min-width:0;
}

.hero-properties-eyebrow{
  display:inline-flex;
  align-items:center;
  width:max-content;
  padding:6px 11px;
  border-radius:999px;
  background:rgba(255,255,255,0.22);
  color:var(--btn-primary-text, #ffffff);
  font-size:0.68rem;
  font-weight:800;
  letter-spacing:0.11em;
  text-transform:uppercase;
  box-shadow:inset 0 1px 0 rgba(255,255,255,0.55);
}

.hero-properties-title{
  margin:0;
  color:var(--btn-primary-text, #ffffff);
  font-size:clamp(1.28rem, 2.2vw, 1.7rem);
  line-height:1.05;
  letter-spacing:-0.04em;
  text-shadow:0 1px 2px rgba(6,35,64,0.24);
}

.hero-properties-subtitle{
  color:rgba(255,255,255,0.9);
  font-size:0.86rem;
  font-weight:700;
  max-width:48ch;
}

.hero-properties-cta,
.hero-properties-control{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:18px;
  background:var(--card, #ffffff);
  border:1px solid rgba(255,255,255,0.36);
  color:var(--text-color, #111827);
  box-shadow:0 12px 24px rgba(2,6,23,0.1);
}

.hero-properties-cta{
  flex:0 0 auto;
  min-width:180px;
  padding:11px 14px;
  font-size:0.88rem;
  font-weight:800;
  text-decoration:none;
}

.hero-properties-carousel{
  display:grid;
  grid-template-columns:minmax(0,1fr) auto;
  gap:10px;
  align-items:end;
  padding-top:2px;
}

.hero-properties-viewport{
  overflow:hidden;
  border-radius:18px;
}

.hero-properties-track{
  display:flex;
  width:100%;
  transition:transform .6s cubic-bezier(.22,.61,.36,1);
}

.hero-properties-slide{
  flex:0 0 100%;
  min-width:100%;
  display:grid;
  grid-template-columns:minmax(220px, 1.2fr) minmax(180px, .88fr);
  align-items:stretch;
  overflow:hidden;
  border-radius:18px;
  background:linear-gradient(180deg, rgba(255,255,255,0.18), rgba(255,255,255,0.08));
  border:1px solid rgba(255,255,255,0.24);
  box-shadow:inset 0 1px 0 rgba(255,255,255,0.35);
  text-decoration:none;
}

.hero-properties-media{
  position:relative;
  min-height:180px;
  background:rgba(255,255,255,0.12);
}

.hero-properties-media img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.hero-properties-media::after{
  content:'';
  position:absolute;
  inset:auto 0 0 0;
  height:40%;
  background:linear-gradient(to top, rgba(2,6,23,0.34), rgba(2,6,23,0));
  pointer-events:none;
}

.hero-properties-price{
  position:relative;
  display:inline-flex;
  align-items:center;
  width:max-content;
  gap:4px;
  padding:7px 10px;
  border-radius:999px;
  background:rgba(2,6,23,0.56);
  color:#fff;
  font-size:0.82rem;
  font-weight:800;
}

.hero-properties-meta{
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  gap:10px;
  padding:14px 14px 13px;
  background:linear-gradient(180deg, rgba(255,255,255,0.2), rgba(255,255,255,0.08));
}

.hero-properties-meta-head{
  display:flex;
  flex-direction:column;
  gap:5px;
}

.hero-properties-name{
  color:var(--btn-primary-text, #ffffff);
  font-size:clamp(1rem, 1.7vw, 1.2rem);
  font-weight:800;
  line-height:1.06;
}

.hero-properties-meta-line,
.hero-properties-code,
.hero-properties-location,
.hero-properties-counter{
  color:rgba(255,255,255,0.84);
}

.hero-properties-code,
.hero-properties-location{
  font-size:0.76rem;
  font-weight:700;
}

.hero-properties-code{ text-transform:uppercase; letter-spacing:0.06em; }

.hero-properties-meta-line{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:8px;
  flex-wrap:wrap;
}

.hero-properties-slide:hover .hero-properties-name,
.hero-properties-slide:focus-visible .hero-properties-name{
  text-decoration:underline;
  text-decoration-thickness:2px;
}

.hero-properties-slide:focus-visible{
  outline:2px solid rgba(255,255,255,0.58);
  outline-offset:-2px;
}

.hero-properties-controls{
  display:flex;
  flex-direction:column;
  gap:8px;
}

.hero-properties-control{
  width:40px;
  height:40px;
  cursor:pointer;
  font-size:1rem;
  font-weight:900;
}

.hero-properties-control[disabled]{
  opacity:.48;
  cursor:not-allowed;
}

.hero-properties-dots{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
}

.hero-properties-dot{
  width:8px;
  height:8px;
  border-radius:999px;
  border:0;
  cursor:pointer;
  background:rgba(255,255,255,0.34);
  transition:transform var(--transition), background var(--transition), opacity var(--transition);
}

.hero-properties-dot.is-active{
  background:var(--card, #ffffff);
  transform:scale(1.2);
}

.hero-properties-empty{
  padding:18px;
  border-radius:20px;
  background:rgba(255,255,255,0.12);
  border:1px solid rgba(255,255,255,0.2);
  color:rgba(255,255,255,0.9);
}

.rv-help-inline{display:inline-flex;align-items:center;gap:8px;margin:2px 0 10px}
.rv-help-q{width:24px;height:24px;border-radius:999px;border:1px solid rgba(59,130,246,.35);color:#1d4ed8;background:rgba(59,130,246,.08);font-weight:700;line-height:1;cursor:pointer;transition:transform .15s ease,background-color .15s ease;flex-shrink:0}
.rv-help-q:hover{transform:translateY(-1px);background:rgba(59,130,246,.16)}
.rv-help-link{color:#2563eb;text-decoration:underline;text-underline-offset:2px;font-size:.93rem;font-weight:700}
.rv-help-note{font-size:.8rem;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 8px;border-radius:999px}
.rv-help-viewer{position:fixed;inset:0;display:none;z-index:70}
.rv-help-viewer.open{display:block}
.rv-help-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.42);backdrop-filter:blur(2px)}
.rv-help-panel{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(940px,95vw);height:min(86vh,780px);background:rgba(255,255,255,.98);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.25);border:1px solid rgba(148,163,184,.3);overflow:hidden;display:grid;grid-template-rows:auto 1fr}
.rv-help-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(148,163,184,.3);background:linear-gradient(90deg,rgba(248,250,252,.95),rgba(241,245,249,.95))}
.rv-help-toolbar strong{font-size:.92rem;color:#0f172a}
.rv-help-controls{display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
.rv-help-btn{border:1px solid rgba(148,163,184,.65);background:#fff;color:#0f172a;border-radius:8px;min-width:34px;height:32px;padding:0 10px;cursor:pointer;font-weight:600}
.rv-help-btn:hover{background:#f8fafc}
.rv-help-step{font-size:.82rem;color:#334155;background:#eef2ff;border:1px solid #c7d2fe;padding:5px 9px;border-radius:999px;font-weight:700}
.rv-help-stage{position:relative;overflow:hidden;background:#f8fafc;touch-action:none;cursor:grab}
.rv-help-stage.dragging{cursor:grabbing}
.rv-help-image{position:absolute;top:50%;left:50%;max-width:100%;max-height:100%;user-select:none;transform:translate(-50%,-50%) translate(0px,0px) scale(1);transform-origin:center center;transition:transform .08s linear;will-change:transform}
.rv-help-hint{position:absolute;right:12px;bottom:10px;color:#334155;font-size:.82rem;background:rgba(255,255,255,.86);border:1px solid rgba(148,163,184,.4);padding:4px 8px;border-radius:999px}

@media (max-width:720px){
  .hero-properties-shell{ padding:14px; border-radius:18px; }
  .hero-properties-header{ flex-direction:column; align-items:flex-start; }
  .hero-properties-cta{ width:100%; min-width:0; }
  .hero-properties-subtitle{ max-width:none; }
  .hero-properties-carousel{ grid-template-columns:1fr; }
  .hero-properties-controls{ flex-direction:row; justify-content:space-between; }
  .hero-properties-slide{ grid-template-columns:1fr; }
  .hero-properties-media{ min-height:170px; }
  .hero-properties-meta{ padding:13px; }
}
</style>

<div class="container">
  <h1>Reservaciones</h1>

  @if(!$isAdmin)
    <div class="rv-help-inline">
      <button type="button" class="rv-help-q" id="rv-open-help-btn" aria-label="Abrir ayuda">?</button>
      <a href="#" class="rv-help-link" id="rv-open-help-link">¿Necesitas ayuda para usar esta página?</a>
      <span class="rv-help-note">Guía visual (2 imágenes)</span>
    </div>
  @endif

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:8px 0;font-weight:700;">{{ session('success') }}</div>
  @endif

  @if($shouldShowHeroPropertiesPanel && $showHeroPropertiesPanelFirst)
    @include('reservaciones._hero_properties_panel')
  @endif
  <div style="margin-top:0;">
    <div class="card-wide" style="margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2 style="margin:0;font-size:1.05rem">Lista de reservaciones</h2>
        @if($isAdmin)
          <button id="btn-new" style="background:#06b6d4;color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;">Nueva reservación</button>
        @endif
      </div>

      <form id="rv-filters" method="GET" action="{{ url('/reservaciones') }}" style="margin-top:10px;">
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
          @if($isAdmin)
            <input name="id" placeholder="ID" value="{{ request('id') }}" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;width:80px;">

            <select name="usuario_id" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
              <option value="">-- Cliente --</option>
              @foreach($usuarios ?? [] as $u)
                <option value="{{ $u->id }}" {{ (string)request('usuario_id') === (string)$u->id ? 'selected' : '' }}>
                  {{ $u->nombre }} {{ $u->apellido }}
                </option>
              @endforeach
            </select>
          @endif

          <select name="propiedad_id" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
            <option value="">-- Propiedad --</option>
            @foreach($propiedades ?? [] as $p)
              <option value="{{ $p->id }}" {{ (string)request('propiedad_id') === (string)$p->id ? 'selected' : '' }}>
                {{ $p->nombre }} {{ $p->codigo ? '· ' . $p->codigo : '' }}
              </option>
            @endforeach
          </select>

          <select name="estado" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
            <option value="">-- Estado --</option>
            @foreach(['pendiente','confirmada','cancelada','completada'] as $st)
              <option value="{{ $st }}" {{ request('estado') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
          </select>

          <label style="display:flex;align-items:center;gap:6px;">
            <span class="small" style="margin-right:4px;">Desde</span>
            <input type="date" name="check_in" value="{{ request('check_in') }}" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
          </label>
          <label style="display:flex;align-items:center;gap:6px;">
            <span class="small" style="margin-right:4px;">Hasta</span>
            <input type="date" name="check_out" value="{{ request('check_out') }}" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
          </label>

          @if($isAdmin)
            <select name="estado_pago" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;">
              <option value="">-- Estado pago --</option>
              @foreach(['pendiente','pagado','parcial','fallido'] as $ep)
                <option value="{{ $ep }}" {{ request('estado_pago') === $ep ? 'selected' : '' }}>{{ ucfirst($ep) }}</option>
              @endforeach
            </select>
          @endif

          <div style="margin-left:auto;display:flex;gap:8px;">
            <button type="submit" class="action-btn primary">Buscar</button>
            <button type="button" id="rv-filters-clear" class="action-btn view">Limpiar</button>
          </div>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;">
        <table class="table" aria-label="Reservaciones">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>ID</th>
              <th>Propiedad</th>
              <th>Cliente</th>
              <th>Fechas</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Estado pago</th>
              <th>Acciones</th>
              @if($isAdmin)
                <th style="width:200px">Cambiar estado</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @forelse($reservaciones ?? [] as $r)
              @php
                $imgPathRaw = optional($r->propiedad)->ruta_img ?? ($r->ruta_img ?? null);
                $thumbUrl = null;
                if (!empty($imgPathRaw)) {
                  $ruta = ltrim($imgPathRaw, '/\\');
                  $full = public_path($ruta);
                  if (is_dir($full)) {
                    $files = @scandir($full) ?: [];
                    foreach ($files as $f) {
                      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                      if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $thumbUrl = asset($ruta . '/' . $f); break; }
                    }
                  } elseif (is_file($full)) {
                    $thumbUrl = asset($ruta);
                  } else {
                    if (pathinfo($ruta, PATHINFO_EXTENSION)) { $thumbUrl = null; }
                  }
                }
                $checkIn = $r->check_in ? Carbon::parse($r->check_in) : null;
                $checkOut = $r->check_out ? Carbon::parse($r->check_out) : null;
                $daysArray = [];
                if ($checkIn && $checkOut) {
                  $d = $checkIn->copy();
                  while ($d->lt($checkOut)) {
                    $daysArray[] = $d->copy();
                    $d->addDay();
                    if (count($daysArray) > 10000) break;
                  }
                }
                $totalDays = count($daysArray);
                $maxVisible = 25;
                $showAll = $totalDays <= $maxVisible;
                $displayDays = $showAll ? $daysArray : [($daysArray[0] ?? $checkIn), ($daysArray[$totalDays-1] ?? ($checkOut ? $checkOut->copy()->subDay() : $checkIn))];
                $isInactiveReservation = (($r->estado ?? '') === 'cancelada') || ($checkOut && $checkOut->copy()->startOfDay()->lt($today));
                $inactiveMessage = 'Esta reservación ya no está vigente o está cancelada.';
              @endphp

              <tr class="{{ $isInactiveReservation ? 'row-inactive' : 'rv-row' }}" title="{{ $isInactiveReservation ? $inactiveMessage : '' }}">
                <td style="width:120px;">
                  <div class="rv-thumb" aria-hidden="true">
                    @if($thumbUrl)
                      <img src="{{ $thumbUrl }}" alt="Imagen propiedad">
                    @else
                      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;font-size:12px;">Sin imagen</div>
                    @endif
                  </div>
                </td>

                <td style="vertical-align:middle;">{{ $r->id }}</td>

                <td style="vertical-align:middle;">
                  <div style="font-weight:700;">{{ $r->propiedad->nombre ?? ($r->propiedad_nombre ?? ($r->propiedad_id ?? '-')) }}</div>
                  <div class="small">{{ optional($r->propiedad)->codigo ?? '' }}</div>
                  @if($isInactiveReservation)
                    <div class="rv-inactive-hint">Ya no está vigente o fue cancelada</div>
                  @endif
                </td>

                <td style="vertical-align:middle;">{{ $r->user->nombre ?? '-' }} {{ $r->user->apellido ?? '' }}</td>

                <td>
                  <div style="font-weight:700;">
                    {{ $checkIn ? $checkIn->format('d M Y') : '-' }} — {{ $checkOut ? $checkOut->format('d M Y') : '-' }}
                  </div>

                  <div class="rv-days" role="list" aria-label="Fechas reserva {{ $r->id }}">
                    @if($showAll)
                      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;width:100%;">
                        @foreach($displayDays as $d)
                          @php
                            $cls = 'rv-day';
                            if ($d->isToday()) $cls .= ' today';
                            elseif ($d->lt(Carbon::today())) $cls .= ' past';
                          @endphp
                          <div class="{{ $cls }}" title="{{ $d->toDateString() }}">
                            <div class="date">{{ $d->format('d') }}</div>
                            <div style="font-size:11px;color:#6b7280;">{{ $d->format('M') }}</div>
                          </div>
                        @endforeach
                        @php
                          $cells = count($displayDays);
                          $fill = (5 - ($cells % 5)) % 5;
                        @endphp
                        @for($i=0;$i<$fill;$i++)
                          <div style="background:transparent;height:56px;border-radius:8px"></div>
                        @endfor
                      </div>
                    @else
                      <div style="display:flex;gap:8px;align-items:center;">
                        <div class="rv-day" title="{{ $displayDays[0]->toDateString() }}" style="min-width:120px;padding:10px;text-align:center;">
                          <div style="font-weight:800">{{ $displayDays[0]->format('d M Y') }}</div>
                          <div class="small">Check-in</div>
                        </div>

                        <div style="font-weight:900;color:#6b7280;">…</div>

                        <div class="rv-day" title="{{ $displayDays[1]->toDateString() }}" style="min-width:120px;padding:10px;text-align:center;">
                          <div style="font-weight:800">{{ $displayDays[1]->format('d M Y') }}</div>
                          <div class="small">Check-out</div>
                        </div>

                        <div class="small-muted" style="margin-left:auto;">Total días: {{ $totalDays }}</div>
                      </div>
                    @endif
                  </div>
                </td>

                <td style="vertical-align:middle;">${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>

                <td style="vertical-align:middle;">
                  @if(($r->estado ?? '') === 'cancelada')
                    <span class="badge badge-cancelada">Cancelada</span>
                  @elseif(($r->estado ?? '') === 'pendiente')
                    <span class="badge badge-pendiente">Pendiente</span>
                  @elseif(($r->estado ?? '') === 'confirmada')
                    <span class="badge badge-confirmada">Confirmada</span>
                  @else
                    <span class="badge">{{ $r->estado }}</span>
                  @endif
                </td>

                <td style="vertical-align:middle;">
                  @php
                    $ep = strtolower(trim((string)($r->estado_pago ?? 'pendiente')));
                    if (($r->estado ?? '') === 'cancelada') {
                      $ep = 'cancelado';
                    }
                  @endphp
                  @if($ep === 'cancelado')
                    <span class="pay-badge pay-fallido">Cancelado</span>
                  @elseif($ep === 'pagado')
                    <span class="pay-badge pay-pagado">Pagado</span>
                  @elseif($ep === 'pendiente')
                    <span class="pay-badge pay-pendiente">Pendiente</span>
                  @elseif($ep === 'fallido' || $ep === 'failed')
                    <span class="pay-badge pay-fallido">Fallido</span>
                  @elseif($ep === 'parcial')
                    <span class="pay-badge pay-parcial">Parcial</span>
                  @else
                    <span class="pay-badge pay-unknown">{{ ucfirst($ep ?: 'Pendiente') }}</span>
                  @endif
                </td>

                <td style="vertical-align:middle;">
                  @if($isAdmin)
                    <div class="btn-group-col">
                      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>

                      @if(! $isInactiveReservation && in_array(($r->estado ?? ''), ['pendiente','confirmada']) && !($r->isExpired() ?? false) && !($r->isPaid() ?? false))
                        <a href="{{ route('pagos.form', $r->id) }}" class="action-btn pay-cta"><span class="pay-cta-symbol">$$</span><span class="pay-cta-label">Pagar reservación</span><span class="pay-cta-symbol">$$</span></a>
                      @endif

                      <a href="{{ route('reservaciones.edit', $r->id) }}" class="action-btn primary">Editar</a>

                      <form method="POST" action="{{ route('reservaciones.destroy', $r->id) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="action-btn danger" type="button" data-confirm="¿Eliminar reservación {{ addslashes($r->id) }}?">Eliminar</button>
                      </form>
                    </div>
                  @else
                    @php
                      $canPayReservation = ! $isInactiveReservation && in_array(($r->estado ?? ''), ['pendiente','confirmada']) && !($r->isPaid() ?? false) && (($currentUser->id ?? null) === ($r->usuario_id ?? null));
                      $canRequestCancellation = ! $isInactiveReservation && in_array(($r->estado ?? ''), ['pendiente','confirmada']) && (($currentUser->id ?? null) === ($r->usuario_id ?? null));
                    @endphp
                    <div class="btn-group-col">
                      <a href="{{ route('reservaciones.show', $r->id) }}" class="action-btn view">Ver</a>
                      @if($canPayReservation)
                        <a href="{{ route('pagos.form', $r->id) }}" class="action-btn pay-cta"><span class="pay-cta-symbol">$$</span><span class="pay-cta-label">Pagar reservación</span><span class="pay-cta-symbol">$$</span></a>
                      @endif

                      @if($canRequestCancellation)
                        <form method="POST" action="{{ route('reservaciones.changeEstado', $r->id) }}" class="request-cancel-form" style="display:inline;">
                          @csrf
                          <input type="hidden" name="estado" value="cancelada" />
                          <button type="button" class="action-btn danger request-cancel-btn" data-id="{{ $r->id }}">Solicitar cancelación</button>
                        </form>
                      @endif
                    </div>
                  @endif
                </td>

                @if($isAdmin)
                  <td style="vertical-align:middle;">
                    <form id="form-change-{{ $r->id }}" action="{{ route('reservaciones.changeEstado', $r->id) }}" method="POST" style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                      @csrf
                      <input type="hidden" name="estado" value="">
                      @if(($r->estado ?? '') !== 'confirmada')
                        <button type="button" class="action-btn primary" data-change data-id="{{ $r->id }}" data-estado="confirmada">Confirmar</button>
                      @endif
                      @if(($r->estado ?? '') !== 'pendiente')
                        <button type="button" class="action-btn view" data-change data-id="{{ $r->id }}" data-estado="pendiente">Pendiente</button>
                      @endif
                      @if(($r->estado ?? '') !== 'cancelada')
                        <button type="button" class="action-btn danger" data-change data-id="{{ $r->id }}" data-estado="cancelada">Cancelar</button>
                      @endif
                    </form>
                  </td>
                @endif
              </tr>
            @empty
              <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="muted">No hay reservaciones aún.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if($shouldShowHeroPropertiesPanel && ! $showHeroPropertiesPanelFirst)
    @include('reservaciones._hero_properties_panel')
  @endif
</div>

<div id="rv-modal" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.45);align-items:center;justify-content:center;z-index:9999;padding:12px;">
  <div style="background:#fff;border-radius:10px;padding:12px;max-width:980px;width:100%;max-height:90vh;overflow:auto;">
    <button id="rv-close" style="float:right;border:0;background:transparent;font-size:20px;">✕</button>
    <h2 id="rv-title">Nueva reservación</h2>

    <form id="rv-form" method="POST" action="{{ url('/reservaciones') }}">
      @csrf
      <input type="hidden" name="_method" id="rv-method" value="POST">
      <input type="hidden" name="id" id="rv-id" value="">

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:320px;">
          <label class="small">Propiedad</label>
          <select id="rv-propiedad" name="propiedad_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar --</option>
            @foreach($propiedades ?? [] as $p)
              <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo ?? '' }})</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Check-in</label>
          <input id="rv-checkin" name="check_in" type="date" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Check-out</label>
          <input id="rv-checkout" name="check_out" type="date" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Número de personas</label>
          <input id="rv-num" name="num_personas" type="number" min="1" value="1" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
        </div>

        <div style="flex:1;min-width:260px;">
          <label class="small">Usuario</label>
          <select id="rv-usuario" name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="">-- seleccionar cliente --</option>
            @foreach($usuarios ?? [] as $u)
              <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }}</option>
            @endforeach
          </select>

          <label class="small" style="margin-top:8px;">Total</label>
          <input id="rv-total" name="total" type="number" step="0.01" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">

          <label class="small" style="margin-top:8px;">Estado</label>
          <select id="rv-estado" name="estado" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
            <option value="pendiente">Pendiente</option>
            <option value="confirmada">Confirmada</option>
            <option value="cancelada">Cancelada</option>
            <option value="completada">Completada</option>
          </select>

          <label class="small" style="margin-top:8px;">Nota</label>
          <textarea id="rv-nota" name="nota" rows="3" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e5e7eb;"></textarea>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
        <button id="rv-save" type="submit" class="action-btn primary">Guardar</button>
        <button type="button" id="rv-cancel" class="action-btn view" style="background:#fff;border:1px solid #e5e7eb;">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="rv-change-confirm-modal" class="modal" aria-hidden="true" style="display:none;">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="max-width:480px;">
    <button class="modal-close" data-close>✕</button>
    <h3 id="rv-cc-title">Confirmar acción</h3>
    <p id="rv-cc-msg" style="color:#6b7280;margin-top:8px;"></p>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
      <button id="rv-cc-cancel" class="action-btn view" type="button">Cancelar</button>
      <button id="rv-cc-ok" class="action-btn danger" type="button">Confirmar</button>
    </div>
  </div>
</div>

@if(!$isAdmin)
<div id="rv-help-viewer" class="rv-help-viewer" aria-hidden="true">
  <div class="rv-help-backdrop" id="rv-help-backdrop"></div>
  <div class="rv-help-panel" role="dialog" aria-modal="true" aria-label="Guía de reservaciones">
    <div class="rv-help-toolbar">
      <strong>Guía rápida de reservaciones y cancelación</strong>
      <div class="rv-help-controls">
        <span class="rv-help-step" id="rv-help-step">Paso 1 de 2</span>
        <button type="button" class="rv-help-btn" id="rv-help-prev" aria-label="Imagen anterior">◀</button>
        <button type="button" class="rv-help-btn" id="rv-help-next" aria-label="Imagen siguiente">▶</button>
        <button type="button" class="rv-help-btn" id="rv-zoom-out" aria-label="Alejar">-</button>
        <button type="button" class="rv-help-btn" id="rv-zoom-reset" aria-label="Restablecer zoom">100%</button>
        <button type="button" class="rv-help-btn" id="rv-zoom-in" aria-label="Acercar">+</button>
        <button type="button" class="rv-help-btn" id="rv-close-help" aria-label="Cerrar ayuda">Cerrar</button>
      </div>
    </div>
    <div class="rv-help-stage" id="rv-help-stage">
      <img id="rv-help-image" class="rv-help-image" src="{{ asset('tutorial_imgs/no-admin/Reservaciones.png') }}" alt="Tutorial de reservaciones" draggable="false" />
      <span class="rv-help-hint">Rueda para zoom · arrastra para mover · clic fuera para salir</span>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const clearBtn = document.getElementById('rv-filters-clear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function(){
      const form = document.getElementById('rv-filters');
      if (!form) return;
      Array.from(form.elements).forEach(el => {
        if (!el.name) return;
        if (el.type === 'select-one' || el.type === 'text' || el.type === 'date' || el.type === 'number' || el.tagName.toLowerCase() === 'input') {
          el.value = '';
        }
      });
      form.submit();
    });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){

  function initPropertiesCarousel(){
    document.querySelectorAll('[data-properties-carousel]').forEach(function(carousel){
      const track = carousel.querySelector('[data-carousel-track]');
      const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
      const dotsWrap = carousel.parentElement.querySelector('[data-carousel-dots]');
      const dots = dotsWrap ? Array.from(dotsWrap.querySelectorAll('[data-carousel-dot]')) : [];
      const prevBtn = carousel.querySelector('[data-carousel-prev]');
      const nextBtn = carousel.querySelector('[data-carousel-next]');
      if (!track || slides.length === 0) return;

      let currentIndex = 0;
      let autoplayId = null;
      let touchStartX = null;
      const canLoop = slides.length > 1;

      function render(){
        track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
        slides.forEach(function(slide, index){
          slide.setAttribute('aria-hidden', index === currentIndex ? 'false' : 'true');
        });
        dots.forEach(function(dot, index){
          const active = index === currentIndex;
          dot.classList.toggle('is-active', active);
          dot.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (prevBtn) prevBtn.disabled = !canLoop;
        if (nextBtn) nextBtn.disabled = !canLoop;
      }

      function goTo(index){
        if (!slides.length) return;
        currentIndex = (index + slides.length) % slides.length;
        render();
      }

      function stopAutoplay(){
        if (autoplayId) {
          window.clearInterval(autoplayId);
          autoplayId = null;
        }
      }

      function startAutoplay(){
        stopAutoplay();
        if (!canLoop) return;
        autoplayId = window.setInterval(function(){
          goTo(currentIndex + 1);
        }, 4800);
      }

      prevBtn?.addEventListener('click', function(){ goTo(currentIndex - 1); });
      nextBtn?.addEventListener('click', function(){ goTo(currentIndex + 1); });
      dots.forEach(function(dot){
        dot.addEventListener('click', function(){
          goTo(Number(dot.getAttribute('data-slide-index') || '0'));
        });
      });

      carousel.addEventListener('mouseenter', stopAutoplay);
      carousel.addEventListener('mouseleave', startAutoplay);
      carousel.addEventListener('focusin', stopAutoplay);
      carousel.addEventListener('focusout', function(){
        if (!carousel.contains(document.activeElement)) startAutoplay();
      });

      carousel.addEventListener('touchstart', function(event){
        touchStartX = event.changedTouches[0]?.clientX ?? null;
      }, { passive: true });

      carousel.addEventListener('touchend', function(event){
        if (touchStartX === null) return;
        const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX;
        const delta = touchEndX - touchStartX;
        if (Math.abs(delta) > 35) {
          goTo(delta > 0 ? currentIndex - 1 : currentIndex + 1);
        }
        touchStartX = null;
      }, { passive: true });

      render();
      startAutoplay();
    });
  }

  initPropertiesCarousel();

  function openModal(mode='create', data=null){
    const modal = document.getElementById('rv-modal');
    const form = document.getElementById('rv-form');
    if(!modal || !form) return;
    modal.style.display = 'flex';
    const methodInput = document.getElementById('rv-method');
    const idInput = document.getElementById('rv-id');
    if(methodInput) methodInput.value = mode === 'create' ? 'POST' : 'PUT';
    if(idInput) idInput.value = data ? data.id : '';
    form.action = mode === 'create' ? "{{ url('/reservaciones') }}" : "{{ url('/reservaciones') }}/" + (data?.id || '');
    if(data){
      try {
        const set = (id, val) => { const el = document.getElementById(id); if(el) el.value = val ?? ''; };
        set('rv-propiedad', data.propiedad_id ?? '');
        set('rv-checkin', data.check_in ?? '');
        set('rv-checkout', data.check_out ?? '');
        set('rv-num', data.num_personas ?? 1);
        set('rv-total', data.total ?? '');
        set('rv-estado', data.estado ?? 'pendiente');
        set('rv-nota', data.nota ?? '');
        set('rv-usuario', data.usuario_id ?? '');
      } catch(e){ console.error(e); }
    } else { form.reset(); }
    const saveBtn = document.getElementById('rv-save');
    const disabled = (mode === 'view');
    Array.from(form.querySelectorAll('input,select,textarea,button')).forEach(el=>{
      if(el === saveBtn) return;
      el.disabled = disabled;
    });
    if(saveBtn) saveBtn.style.display = disabled ? 'none' : '';
  }
  function closeModal(){ const modal = document.getElementById('rv-modal'); if(modal) modal.style.display = 'none'; }

  document.getElementById('rv-close')?.addEventListener('click', closeModal);
  document.getElementById('rv-cancel')?.addEventListener('click', closeModal);
  document.getElementById('btn-new')?.addEventListener('click', function(){ openModal('create', null); });

  const changeModal = document.getElementById('rv-change-confirm-modal');
  const changeTitle = document.getElementById('rv-cc-title');
  const changeMsg = document.getElementById('rv-cc-msg');
  const changeCancel = document.getElementById('rv-cc-cancel');
  const changeOk = document.getElementById('rv-cc-ok');
  let pendingAction = null;

  function showChangeModal(title, msg, opts = {}) {
    if(!changeModal) return;
    changeTitle.textContent = title || 'Confirmar acción';
    changeMsg.textContent = msg || '';
    changeOk.textContent = opts.okLabel || 'Confirmar';
    changeCancel.textContent = opts.cancelLabel || 'Cancelar';
    changeModal.style.display = 'flex';
    changeModal.setAttribute('aria-hidden','false');
    setTimeout(()=> changeModal.classList.add('open'), 10);
  }
  function hideChangeModal(){ if(!changeModal) return; changeModal.classList.remove('open'); changeModal.setAttribute('aria-hidden','true'); setTimeout(()=> changeModal.style.display = 'none', 180); }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-edit], [data-view], [data-change], [data-confirm], .request-cancel-btn');
    if(!btn) return;

    if(btn.matches('[data-edit]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('edit', data);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch(err){ console.error(err); }
      return;
    }

    if(btn.matches('[data-view]')){
      e.preventDefault();
      try {
        const data = JSON.parse(btn.getAttribute('data-res') || '{}');
        openModal('view', data);
      } catch(err){ console.error(err); }
      return;
    }

    if(btn.matches('[data-change]')){
      e.preventDefault();
      const id = btn.getAttribute('data-id');
      const estado = btn.getAttribute('data-estado');
      const form = document.getElementById('form-change-' + id);
      if(!form){ alert('Formulario no encontrado'); return; }
      showChangeModal('Confirmar', 'Cambiar estado a ' + estado + '?', { okLabel: 'Confirmar', cancelLabel: 'Cancelar' });
      pendingAction = { type: 'state', action: form.action, estado: estado };
      return;
    }

    if(btn.matches('[data-confirm]') || btn.matches('.request-cancel-btn')){
      e.preventDefault();
      if(btn.matches('[data-confirm]')){
        const form = btn.closest('form');
        showChangeModal('Confirmar eliminación', btn.getAttribute('data-confirm') || '¿Eliminar?', { okLabel: 'Eliminar', cancelLabel: 'Cancelar' });
        pendingAction = { type: 'delete', form: form };
        return;
      }
      if(btn.matches('.request-cancel-btn')){
        const id = btn.getAttribute('data-id');
        const form = btn.closest('form');
        showChangeModal('Solicitar cancelación', '¿Deseas solicitar la cancelación de la reservación #' + id + '?', { okLabel: 'Solicitar', cancelLabel: 'Cancelar' });
        pendingAction = { type: 'delete-like', form: form };
        return;
      }
    }
  });

  changeCancel?.addEventListener('click', function(){ pendingAction = null; hideChangeModal(); });

  changeOk?.addEventListener('click', async function(){
    if(!pendingAction){ hideChangeModal(); return; }
    changeOk.disabled = true;
    try {
      if(pendingAction.type === 'delete' || pendingAction.type === 'delete-like'){
        const f = pendingAction.form;
        if(f) f.submit();
        pendingAction = null;
        hideChangeModal();
        return;
      }
      if(pendingAction.type === 'state'){
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const payload = new URLSearchParams();
        payload.append('estado', pendingAction.estado);
        const res = await fetch(pendingAction.action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: payload.toString(),
          credentials: 'same-origin'
        });
        if(!res.ok){
          alert('No se pudo cambiar estado');
          pendingAction = null;
          return;
        }
        hideChangeModal();
        setTimeout(()=> window.location.reload(), 200);
        pendingAction = null;
        return;
      }
    } catch(err){
      console.error(err);
      alert('Error procesando la solicitud');
    } finally {
      changeOk.disabled = false;
    }
  });

  document.querySelectorAll('form[id^="form-change-"]').forEach(f => { f.addEventListener('submit', function(e){ e.preventDefault(); }); });

});
</script>
@if(!$isAdmin)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var viewer = document.getElementById('rv-help-viewer');
  var stage = document.getElementById('rv-help-stage');
  var img = document.getElementById('rv-help-image');
  var openBtn = document.getElementById('rv-open-help-btn');
  var openLink = document.getElementById('rv-open-help-link');
  var closeBtn = document.getElementById('rv-close-help');
  var backdrop = document.getElementById('rv-help-backdrop');
  var prevBtn = document.getElementById('rv-help-prev');
  var nextBtn = document.getElementById('rv-help-next');
  var stepBadge = document.getElementById('rv-help-step');
  var zoomIn = document.getElementById('rv-zoom-in');
  var zoomOut = document.getElementById('rv-zoom-out');
  var zoomReset = document.getElementById('rv-zoom-reset');
  if (!viewer || !stage || !img) return;

  var sources = [
    { src: '{{ asset('tutorial_imgs/no-admin/Reservaciones.png') }}', label: 'Paso 1 de 2' },
    { src: '{{ asset('tutorial_imgs/no-admin/ReservacionesCancel.png') }}', label: 'Paso 2 de 2' }
  ];
  var current = 0;
  var isOpen = false;
  var pushedHistory = false;
  var scale = 1;
  var x = 0;
  var y = 0;
  var dragging = false;
  var startX = 0;
  var startY = 0;

  function applyTransform() {
    img.style.transform = 'translate(-50%,-50%) translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
    if (zoomReset) zoomReset.textContent = Math.round(scale * 100) + '%';
  }

  function setZoom(next) {
    scale = Math.max(1, Math.min(4, next));
    if (scale === 1) { x = 0; y = 0; }
    applyTransform();
  }

  function renderSlide() {
    var slide = sources[current] || sources[0];
    img.src = slide.src;
    img.alt = 'Tutorial de reservaciones';
    if (stepBadge) stepBadge.textContent = slide.label;
    if (prevBtn) prevBtn.disabled = sources.length <= 1;
    if (nextBtn) nextBtn.disabled = sources.length <= 1;
    setZoom(1);
  }

  function openViewer() {
    if (isOpen) return;
    isOpen = true;
    viewer.classList.add('open');
    viewer.setAttribute('aria-hidden', 'false');
    renderSlide();
    try {
      if (!history.state || !history.state.rvHelpOpen) {
        history.pushState({ rvHelpOpen: true }, '');
        pushedHistory = true;
      } else { pushedHistory = false; }
    } catch (e) { pushedHistory = false; }
  }

  function closeViewer(fromPop) {
    if (!isOpen) return;
    isOpen = false;
    viewer.classList.remove('open');
    viewer.setAttribute('aria-hidden', 'true');
    dragging = false;
    stage.classList.remove('dragging');
    if (!fromPop && pushedHistory) {
      pushedHistory = false;
      try { history.back(); } catch (e) {}
    }
  }

  function changeSlide(dir) {
    current = (current + dir + sources.length) % sources.length;
    renderSlide();
  }

  function beginDrag(cx, cy) {
    if (scale <= 1) return;
    dragging = true;
    startX = cx;
    startY = cy;
    stage.classList.add('dragging');
  }

  function moveDrag(cx, cy) {
    if (!dragging) return;
    x += cx - startX;
    y += cy - startY;
    startX = cx;
    startY = cy;
    applyTransform();
  }

  function endDrag() {
    dragging = false;
    stage.classList.remove('dragging');
  }

  [openBtn, openLink].forEach(function (el) {
    if (!el) return;
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openViewer();
    });
  });

  closeBtn && closeBtn.addEventListener('click', function () { closeViewer(false); });
  backdrop && backdrop.addEventListener('click', function () { closeViewer(false); });
  prevBtn && prevBtn.addEventListener('click', function () { changeSlide(-1); });
  nextBtn && nextBtn.addEventListener('click', function () { changeSlide(1); });
  zoomIn && zoomIn.addEventListener('click', function () { setZoom(scale + 0.2); });
  zoomOut && zoomOut.addEventListener('click', function () { setZoom(scale - 0.2); });
  zoomReset && zoomReset.addEventListener('click', function () { setZoom(1); });

  stage.addEventListener('wheel', function (e) {
    if (!isOpen) return;
    e.preventDefault();
    setZoom(scale + (e.deltaY < 0 ? 0.18 : -0.18));
  }, { passive: false });

  stage.addEventListener('mousedown', function (e) { beginDrag(e.clientX, e.clientY); });
  window.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
  window.addEventListener('mouseup', endDrag);

  stage.addEventListener('touchstart', function (e) {
    if (e.touches && e.touches[0]) beginDrag(e.touches[0].clientX, e.touches[0].clientY);
  }, { passive: true });
  stage.addEventListener('touchmove', function (e) {
    if (dragging && e.touches && e.touches[0]) moveDrag(e.touches[0].clientX, e.touches[0].clientY);
  }, { passive: true });
  stage.addEventListener('touchend', endDrag, { passive: true });
  stage.addEventListener('dblclick', function () { setZoom(scale > 1 ? 1 : 2); });

  document.addEventListener('keydown', function (e) {
    if (!isOpen) return;
    if (e.key === 'Escape') closeViewer(false);
    if (e.key === '+' || e.key === '=') setZoom(scale + 0.2);
    if (e.key === '-') setZoom(scale - 0.2);
    if (e.key === 'ArrowRight') changeSlide(1);
    if (e.key === 'ArrowLeft') changeSlide(-1);
  });

  window.addEventListener('popstate', function () {
    if (isOpen) {
      pushedHistory = false;
      closeViewer(true);
    }
  });

  applyTransform();
});
</script>
@endif
@endpush
@endsection

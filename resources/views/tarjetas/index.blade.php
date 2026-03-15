@extends('layouts.app')

@section('title','Tarjetas')

@section('content')
<style>
.card { background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06); }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; vertical-align:middle; }
.btn { background: linear-gradient(90deg,#6366f1,#06b6d4); color:#fff; padding:8px 12px; border-radius:8px; border:0; font-weight:700; cursor:pointer; }
.btn-alt { background:#f3f4f6; color:#0f172a; padding:8px 10px; border-radius:8px; border:0; cursor:pointer; }
.link-button { background:transparent;border:0;color:#2563eb;cursor:pointer;padding:6px 8px;font-weight:700;border-radius:6px; }
.link-button.danger { color:#ef4444; }
.btn-ghost { background:transparent;color:#374151;border:1px solid #e6e9ee;padding:8px 10px;border-radius:8px; }
.small { font-size:0.9rem;color:#6b7280; }
.mask { letter-spacing:2px;font-family:monospace; }
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999; }
.modal .modal-backdrop { position:absolute; inset:0; background:rgba(2,6,23,0.45); }
.modal-panel { position:relative; z-index:1200; background:#fff; border-radius:12px; padding:16px; width:520px; max-width:calc(100% - 32px); box-shadow:0 18px 40px rgba(2,6,23,0.08); }
.field { display:block; margin-bottom:10px; }
.field .label-text { display:block; font-weight:700; margin-bottom:6px; color:#111827; }
.input-inline { display:flex; gap:8px; }
.input-inline input, .input-inline select { padding:8px 10px; border-radius:8px; border:1px solid #e6e9ee; background:#fff; }
.table-actions { text-align:right; white-space:nowrap; }
.badge { display:inline-block;padding:6px 10px;border-radius:999px;font-weight:800;color:#fff; }
.badge.positive { background:#065f46; }
.badge.negative { background:#7f1d1d; }

.card-3d { position:relative; width:320px; height:200px; perspective:1000px; }
.card-3d-inner { width:100%; height:100%; position:relative; transform-style:preserve-3d; transition: transform 0.7s ease; }
.card-3d.flipped .card-3d-inner { transform: rotateY(180deg); }
.card-face { position:absolute; inset:0; backface-visibility:hidden; -webkit-backface-visibility:hidden; border-radius:12px; overflow:hidden; }
.card-back { transform: rotateY(180deg); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-family:monospace; font-size:32px; }
.card-flip-btn { position:absolute; top:8px; right:8px; background: rgba(255,255,255,0.12); color:#fff; border-radius:8px; padding:6px 8px; border:0; cursor:pointer; display:none; font-weight:700; }
.card-3d:hover .card-flip-btn { display:block; }
.card-flip-btn:hover { background: rgba(255,255,255,0.18); }

.card-flip-btn::after { content: attr(title); position:absolute; white-space:nowrap; top:-30px; right:0; transform:translateX(0); background:rgba(0,0,0,0.7); color:#fff; padding:6px 8px; border-radius:6px; font-size:12px; display:none; }
.card-flip-btn:hover::after { display:block; }
</style>

<div style="max-width:1100px;margin:18px auto;padding:12px;">
  @php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = $isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin');
    $myTarjeta = null;
    if (!$isAdmin && $currentUser) {
      // Prefer the direct foreign key on usuarios.id_tarjeta when present on the current user
      $idTarjeta = $currentUser->id_tarjeta ?? null;
      if ($idTarjeta) {
        $myTarjeta = \App\Models\TarjetaSimulada::find($idTarjeta);
      } else {
        // If tarjetas_simuladas.usuario_id exists, use it; otherwise use the assignedUser relation
        if (\Illuminate\Support\Facades\Schema::hasColumn('tarjetas_simuladas', 'usuario_id')) {
          $myTarjeta = \App\Models\TarjetaSimulada::where('usuario_id', $currentUser->id)->first();
        } else {
          $myTarjeta = \App\Models\TarjetaSimulada::whereHas('assignedUser', function($q) use ($currentUser) { $q->where('id', $currentUser->id); })->first();
        }
      }
    }
  @endphp
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0">Tarjetas</h1>
      <div class="small" style="margin-top:6px;">Gestión de tarjetas simuladas</div>
    </div>
    <div>
      @if (($isAdmin || $myTarjeta) && !((($currentUser->bloqueo_tarjetas ?? false) && !$isAdmin)))
      <button id="btn-new" class="btn">Nueva tarjeta</button>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div style="background:#fee2e2;color:#7f1d1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      {{ session('error') }}
    </div>
  @endif

  @if($errors->any())
    <div style="background:#fee2e2;padding:10px;border-radius:8px;margin-bottom:12px;color:#991b1b;">
      <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if($currentUser && !$isAdmin && !empty($currentUser->bloqueo_tarjetas))
    <div style="background:#fee2e2;color:#7f1d1b;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;">
      Tu cuenta está bloqueada para operaciones con tarjetas. Para agregar o asignar tarjetas debes contactar a soporte@ejemplo.com para que un administrador desbloquee tu cuenta.
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        // disable actions for blocked users
        var ids = ['btn-new','btn-create-random','btn-assign-existing','btn-assign-confirm'];
        ids.forEach(function(id){ var el = document.getElementById(id); if(el) el.disabled = true; });
        // hide inline assign panels if present
        var found = document.getElementById('found-card'); if(found) found.style.display = 'none';
      });
    </script>
  @endif

  @if($isAdmin)
    <div class="card">
      <div style="overflow:auto;">
        <table class="table" style="min-width:780px;">
          <thead>
            <tr>
              <th>Número</th>
              <th>Nombre</th>
              <th>Expiración</th>
              <th style="width:160px">Saldo</th>
              <th>Asignada a</th>
              <th style="width:240px;text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tarjetas as $t)
              <tr>
                <td>
                  @php
                    $raw = preg_replace('/\D/','', $t->numero_tarjeta ?? '');
                    $last = (int) (strlen($raw) ? substr($raw, -1) : 0);
                    $penult = (int) (strlen($raw) >= 2 ? substr($raw, -2, 1) : 0);
                    $colors = ['#0ea5e9','#06b6d4','#7c3aed','#ef4444','#f59e0b','#10b981','#f97316','#8b5cf6','#0f172a','#065f46'];
                    $bg = $colors[$last % count($colors)];
                    $patternId = 'p_' . $penult . '_' . $t->id;
                    $display = preg_replace('/\s+/', '', $raw);
                    if (strlen($display) >= 4) {
                      $last4 = substr($display, -4);
                      $maskLen = max(0, strlen($display) - 4);
                      $masked = str_repeat('•', $maskLen) . $last4;
                    } else {
                      $masked = str_repeat('•', max(0, 16 - strlen($display))) . $display;
                    }
                    $groups = [];
                    $len = mb_strlen($masked ?? '', 'UTF-8');
                    $chunks = (int) ceil($len / 4);
                    for ($i = 0; $i < $chunks; $i++) {
                        $groups[] = mb_substr($masked, $i * 4, 4, 'UTF-8');
                    }
                    $xs = [276, 212, 148, 84]; // rightmost -> leftmost
                  @endphp

                  <div class="card-3d" data-card-id="{{ $t->id }}">
                    <div class="card-3d-inner">
                      <div class="card-face card-front">
                        <svg width="100%" height="100%" viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Tarjeta">
                          <defs>
                            <pattern id="{{ $patternId }}" patternUnits="userSpaceOnUse" width="12" height="12">
                              @switch($penult % 6)
                                @case(0)
                                  <rect width="12" height="12" fill="rgba(255,255,255,0.04)"/>
                                  @break
                                @case(1)
                                  <path d="M0 12 L12 0" stroke="rgba(255,255,255,0.06)" stroke-width="2"/>
                                  @break
                                @case(2)
                                  <circle cx="6" cy="6" r="1.5" fill="rgba(255,255,255,0.06)"/>
                                  @break
                                @case(3)
                                  <rect width="6" height="6" x="0" y="0" fill="rgba(255,255,255,0.04)"/>
                                  @break
                                @case(4)
                                  <path d="M0 0 L12 0 L12 12" stroke="rgba(255,255,255,0.05)" stroke-width="1" fill="none"/>
                                  @break
                                @default
                                  <path d="M0 6 L12 6" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                              @endswitch
                            </pattern>
                          </defs>

                          <rect x="0" y="0" width="320" height="200" rx="12" ry="12" fill="{{ $bg }}"/>
                          <rect x="0" y="0" width="320" height="200" rx="12" ry="12" fill="url(#{{ $patternId }})" style="mix-blend-mode:overlay;opacity:0.9"/>

                          <g>
                            <rect x="24" y="36" width="52" height="36" rx="6" fill="rgba(255,255,255,0.18)"/>
                            <rect x="30" y="42" width="40" height="24" rx="4" fill="rgba(255,255,255,0.07)"/>
                          </g>

                          <g transform="translate(240,28)">
                            <circle cx="18" cy="18" r="18" fill="rgba(255,255,255,0.12)"/>
                            <circle cx="36" cy="18" r="18" fill="rgba(255,255,255,0.06)"/>
                          </g>

                          <g font-family="monospace" font-size="18" fill="#ffffff" font-weight="700" text-anchor="end">
                            @php $y_numbers = 170; @endphp
                            @foreach($groups as $i => $g)
                              @php $xi = $xs[$i] ?? ($xs[count($xs)-1] - ($i*64)); @endphp
                              <text x="{{ $xi }}" y="{{ $y_numbers }}" style="letter-spacing:3px;">{{ $g }}</text>
                            @endforeach
                          </g>

                          <g font-family="sans-serif" font-size="13" fill="rgba(255,255,255,0.95)">
                            <text x="24" y="20" font-weight="800" style="letter-spacing:0.6px;">{{ Str::limit($t->nombre, 24) }}</text>
                          </g>
                          <g font-family="sans-serif" font-size="12" fill="rgba(255,255,255,0.9)\'" text-anchor="end">
                            <text x="296" y="20" font-weight="700">{{ __('Exp.') }} {{ $t->expiracion }}</text>
                          </g>
                        </svg>
                      </div>

                      <div class="card-face card-back" style="background:{{ $bg }};">
                        <div style="text-align:center;width:100%;padding:12px;">
                          <div style="font-size:13px;color:rgba(255,255,255,0.85);font-weight:700;margin-bottom:8px;">Código de seguridad</div>
                          <div style="font-size:36px;letter-spacing:6px;">{{ $t->cvv ?? '***' }}</div>
                        </div>
                      </div>
                    </div>

                    <button class="card-flip-btn" title="Clic para girar" aria-label="Girar tarjeta">🔁</button>
                  </div>
                </td>
                <td>{{ $t->nombre }}</td>
                <td>{{ $t->expiracion }}</td>
                <td>
                  <div style="display:flex;align-items:center;justify-content:flex-start;gap:8px;">
                    <div style="font-weight:900;color:{{ $t->saldo >= 0 ? '#065f46' : '#7f1d1d' }};">
                      ${{ number_format($t->saldo,2,',','.') }}
                    </div>
                    @if($t->saldo >= 0)
                      <span class="badge positive">OK</span>
                    @else
                      <span class="badge negative">Débito</span>
                    @endif
                  </div>
                </td>
                <td>{{ $t->usuario_asignado_nombre ?? '-' }}</td>
                <td class="table-actions">
                  <form method="POST" action="{{ route('tarjetas.destroy', $t->id) }}" class="form-delete" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            class="link-button"
                            data-edit
                            data-id="{{ $t->id }}"
                            data-numero="{{ $t->numero_tarjeta }}"
                            data-nombre="{{ $t->nombre }}"
                            data-exp="{{ $t->expiracion }}"
                            data-saldo="{{ $t->saldo }}"
                            data-cvv="{{ $t->cvv ?? '' }}"
                            data-update-url="{{ route('tarjetas.update', $t->id) }}">Editar</button>

                    <button type="button" class="link-button" data-deposit data-id="{{ $t->id }}">+ Saldo</button>
                    <button type="button" class="link-button" data-withdraw data-id="{{ $t->id }}">- Saldo</button>

                    <button type="button" class="link-button" data-assign data-id="{{ $t->id }}">Asignar</button>

                    <button class="link-button danger" type="submit" data-confirm="¿Eliminar tarjeta {{ $t->nombre }}?">Eliminar</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="small">No hay tarjetas registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px;">{{ $tarjetas->links() }}</div>
    </div>
  @else
    <div class="card">
      <div style="padding:12px;" id="tarjetas-root" data-current-user-id="{{ $currentUser->id ?? '' }}">
        @if($myTarjeta)
          <h3 style="margin-top:0">Tu tarjeta</h3>
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="flex:1;">
              <div style="font-weight:800">{{ Str::limit($myTarjeta->nombre,40) }}</div>
              <div class="small">Número: <span class="mask">{{  $myTarjeta->numero_tarjeta }}</span></div>
              <div class="small">Expiración: {{ $myTarjeta->expiracion ?? '-' }}</div>
              <div class="small">Saldo: ${{ number_format($myTarjeta->saldo ?? 0,2,',','.') }}</div>
              <div class="small" style="margin-top:8px;color:#6b7280;">Si necesitas más saldo, un administrador puede agregarlo a tu tarjeta.</div>
              <div class="small" style="margin-top:8px;color:#6b7280;">El CVV de tu tarjeta es {{ $myTarjeta->cvv ?? 'N/A' }}.</div>
            </div>
            <div style="min-width:220px;text-align:right">
              <button id="btn-edit-my" class="btn">Cambiar tarjeta</button>
            </div>
          </div>

          <form id="form-edit-my" method="POST" action="#" style="margin-top:12px;display:none;">
            @csrf
            <input type="hidden" name="assign_to_user" value="1" />
            <label class="field"><span class="label-text">Ingresar número completo (16) de la tarjeta existente</span>
              <input id="input-assign-numero" name="numero_check" placeholder="0000111122223333" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee"/>
              <div id="assign-error" class="small" style="color:#ef4444;display:none;margin-top:6px;">No se encontró una tarjeta con esos datos.</div>
            </label>
            <div id="found-card" style="display:none;margin-top:8px;border-top:1px dashed #eef2f6;padding-top:10px;">
              <div class="small" style="margin-bottom:6px;font-weight:700">Tarjeta encontrada — confirme antes de asignar</div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input id="found-numero" readonly style="flex:1;padding:8px;border-radius:8px;border:1px solid #e6e9ee;background:#f8fafc" />
                <input id="found-nombre" placeholder="Nombre en tarjeta" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
                <input id="found-exp" placeholder="MM/YY" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
                <input id="found-cvv" placeholder="CVV" maxlength="3" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
              </div>
              <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                <button id="btn-assign-confirm" type="button" class="btn">Asignar tarjeta a mi cuenta</button>
                <button id="btn-assign-cancel" type="button" class="btn-alt">Cancelar</button>
              </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
              <button id="btn-assign-existing" class="btn" type="button">Asignar tarjeta existente</button>
              <button type="button" class="btn-ghost" id="btn-create-random">No tienes tarjeta? clic para crear una</button>
              <button type="button" class="btn-alt" id="cancel-edit-my">Cancelar</button>
            </div>
          </form>

        @else
          <h3 style="margin-top:0">Registrar o asignar una tarjeta</h3>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div>
              <button id="btn-create-random" class="btn">No tienes tarjeta? clic para crear una</button>
            </div>
            <div style="color:#6b7280;">O asigna una tarjeta existente introduciendo su número:</div>
            <div style="display:flex;gap:8px;align-items:center;">
              <input id="input-assign-numero" placeholder="0000111122223333" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
              <button id="btn-assign-existing" class="btn">Asignar</button>
            </div>
            <div id="assign-error" class="small" style="color:#ef4444;display:none;">No se encontró una tarjeta con esos datos.</div>
            <div id="found-card" style="display:none;margin-top:8px;border-top:1px dashed #eef2f6;padding-top:10px;">
              <div class="small" style="margin-bottom:6px;font-weight:700">Tarjeta encontrada — confirme antes de asignar</div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input id="found-numero" readonly style="flex:1;padding:8px;border-radius:8px;border:1px solid #e6e9ee;background:#f8fafc" />
                <input id="found-nombre" placeholder="Nombre en tarjeta" style="flex:1;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
                <input id="found-exp" placeholder="MM/YY" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
                <input id="found-cvv" placeholder="CVV" maxlength="3" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
              </div>
              <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                <button id="btn-assign-confirm" type="button" class="btn">Asignar tarjeta a mi cuenta</button>
                <button id="btn-assign-cancel" type="button" class="btn-alt">Cancelar</button>
              </div>
            </div>
            <div class="small" style="margin-top:6px;color:#6b7280;">Un administrador podrá agregar saldo a tu tarjeta si es necesario.</div>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>

<div id="modal-tarjeta" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3 id="modal-title">Nueva tarjeta</h3>

    <form id="form-tarjeta" method="POST" action="{{ route('tarjetas.store') }}" class="form">
      @csrf
      <label class="field">
        <span class="label-text">Número (16)</span>
        <div class="input-inline">
          <input id="t-numero-1" inputmode="numeric" pattern="\d{4}" maxlength="4" style="width:84px;text-align:center" required />
          <input id="t-numero-2" inputmode="numeric" pattern="\d{4}" maxlength="4" style="width:84px;text-align:center" required />
          <input id="t-numero-3" inputmode="numeric" pattern="\d{4}" maxlength="4" style="width:84px;text-align:center" required />
          <input id="t-numero-4" inputmode="numeric" pattern="\d{4}" maxlength="4" style="width:84px;text-align:center" required />
        </div>
        <input type="hidden" name="numero_tarjeta" id="t-numero-hidden" />
      </label>

      <label class="field"><span class="label-text">Nombre</span><input id="t-nombre" name="nombre" required style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee" /></label>

      <label class="field">
        <span class="label-text">Expiración</span>
        <div class="input-inline">
          <input id="t-exp-mm" inputmode="numeric" pattern="\d{2}" maxlength="2" placeholder="MM" style="width:64px;text-align:center" required />
          <div style="align-self:center;color:#6b7280;">/</div>
          <input id="t-exp-yy" inputmode="numeric" pattern="\d{2}" maxlength="2" placeholder="YY" style="width:64px;text-align:center" required />
        </div>
        <input type="hidden" name="expiracion" id="t-exp-hidden" />
      </label>

      <label class="field"><span class="label-text">CVV</span><input id="t-cvv" name="cvv" maxlength="3" inputmode="numeric" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" required /></label>

      <label class="field">
        <span class="label-text">Saldo inicial</span>
        <div class="input-inline" style="align-items:center;">
          <div style="font-weight:700;color:#6b7280;padding-left:6px;">$</div>
          <input id="t-saldo-pesos" inputmode="numeric" pattern="\d*" maxlength="10" placeholder="0" style="padding:8px;border-radius:8px;border:1px solid #e6e9ee;text-align:right" />
          <div style="color:#6b7280;">.</div>
          <input id="t-saldo-centavos" inputmode="numeric" pattern="\d{1,2}" maxlength="2" placeholder="00" style="width:64px;padding:8px;border-radius:8px;border:1px solid #e6e9ee;text-align:center" />
        </div>
        <input type="hidden" name="saldo" id="t-saldo-hidden" />
      </label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Guardar</button>
        <button type="button" class="btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-amount" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="width:420px;">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3 id="modal-amount-title">Modificar saldo</h3>

    <form id="form-amount" method="POST" action="#">
      @csrf
      <input type="hidden" id="amt-tarjeta-id" name="tarjeta_id" />

      <label class="field"><span class="label-text">Pesos</span>
        <input id="amt-pesos" name="pesos" inputmode="numeric" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
      </label>

      <label class="field"><span class="label-text">Centavos</span>
        <input id="amt-centavos" name="centavos" maxlength="2" inputmode="numeric" style="width:120px;padding:8px;border-radius:8px;border:1px solid #e6e9ee" />
      </label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" id="amt-submit" type="submit">Aceptar</button>
        <button type="button" class="btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-assign" class="modal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true" style="width:480px;">
    <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;">✕</button>
    <h3>Asignar tarjeta a usuario</h3>

    <form id="form-assign" method="POST" action="#">
      @csrf
      <input type="hidden" id="assign-tarjeta-id" name="tarjeta_id" />
      <label class="field"><span class="label-text">Usuario</span>
        <select id="assign-usuario" name="usuario_id" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e6e9ee" required>
          <option value="">-- seleccionar usuario --</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}">{{ $u->nombre }} {{ $u->apellido }} ({{ $u->email }})</option>
          @endforeach
        </select>
      </label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn" type="submit">Asignar</button>
        <button type="button" class="btn-alt" data-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const modalTar = document.getElementById('modal-tarjeta');
  const modalAmt = document.getElementById('modal-amount');
  const modalAssign = document.getElementById('modal-assign');

  function show(modal){ if(!modal) return; modal.setAttribute('aria-hidden','false'); modal.style.display='flex'; setTimeout(()=> modal.classList.add('open'),20); }
  function hide(modal){ if(!modal) return; modal.setAttribute('aria-hidden','true'); modal.classList.remove('open'); setTimeout(()=> modal.style.display='none',160); }

  document.getElementById('btn-new')?.addEventListener('click', function(){
    document.getElementById('modal-title').textContent = 'Nueva tarjeta';
    const f = document.getElementById('form-tarjeta');
    f.action = "{{ route('tarjetas.store') }}";
    ['t-numero-1','t-numero-2','t-numero-3','t-numero-4','t-exp-mm','t-exp-yy','t-cvv','t-nombre','t-saldo-pesos','t-saldo-centavos'].forEach(id=>{
      const el = document.getElementById(id); if(el) el.value = '';
    });
    document.getElementById('t-saldo-centavos').value = '00';
    const methodEl = document.querySelector('#form-tarjeta [name="_method"]'); if(methodEl) methodEl.remove();
    document.getElementById('t-numero-hidden').value = '';
    document.getElementById('t-exp-hidden').value = '';
    document.getElementById('t-saldo-hidden').value = '';
    show(modalTar);
  });

  document.querySelectorAll('[data-edit]').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const id = this.dataset.id;
      document.getElementById('modal-title').textContent = 'Editar tarjeta';
      const f = document.getElementById('form-tarjeta');
      f.action = this.dataset.updateUrl || ('/tarjetas/' + id);
      f.querySelector('[name="_method"]')?.remove();
      const method = document.createElement('input'); method.type='hidden'; method.name='_method'; method.value='PUT'; f.appendChild(method);

      const rawNum = String(this.dataset.numero || '').replace(/\D/g,'').padEnd(16,'0').slice(0,16);
      document.getElementById('t-numero-1').value = rawNum.slice(0,4);
      document.getElementById('t-numero-2').value = rawNum.slice(4,8);
      document.getElementById('t-numero-3').value = rawNum.slice(8,12);
      document.getElementById('t-numero-4').value = rawNum.slice(12,16);
      document.getElementById('t-numero-hidden').value = rawNum;

      document.getElementById('t-nombre').value = this.dataset.nombre || '';

      const exp = String(this.dataset.exp || '');
      const parts = exp.split('/');
      const mmVal = parts[0] ? parts[0].trim().slice(0,2) : '';
      const yyVal = parts[1] ? parts[1].trim().slice(-2) : '';
      document.getElementById('t-exp-mm').value = mmVal;
      document.getElementById('t-exp-yy').value = yyVal;
      document.getElementById('t-exp-hidden').value = mmVal && yyVal ? (mmVal + '/' + yyVal) : '';

      const saldo = parseFloat(this.dataset.saldo) || 0;
      const pesos = Math.trunc(saldo);
      const centavos = Math.round((saldo - pesos) * 100).toString().padStart(2,'0');
      document.getElementById('t-saldo-pesos').value = pesos;
      document.getElementById('t-saldo-centavos').value = centavos;
      document.getElementById('t-saldo-hidden').value = Number(pesos + '.' + centavos).toFixed(2);

      document.getElementById('t-cvv').value = this.dataset.cvv || '';

      show(modalTar);
    });
  });

  document.querySelectorAll('.card-flip-btn').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const container = this.closest('.card-3d');
      if (!container) return;
      container.classList.add('flipped');
      setTimeout(()=> {
        container.classList.remove('flipped');
      }, 3000);
    });
  });

  document.querySelectorAll('[data-deposit], [data-withdraw]').forEach(btn=>{
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      const isDeposit = this.hasAttribute('data-deposit');
      document.getElementById('modal-amount-title').textContent = isDeposit ? 'Agregar saldo' : 'Retirar saldo';
      const f = document.getElementById('form-amount');
      f.action = '/tarjetas/' + id + (isDeposit ? '/deposit' : '/withdraw');
      document.getElementById('amt-tarjeta-id').value = id;
      document.getElementById('amt-pesos').value = '';
      document.getElementById('amt-centavos').value = '00';
      show(modalAmt);
    });
  });

  document.querySelectorAll('[data-assign]').forEach(btn=>{
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      document.getElementById('form-assign').action = '/tarjetas/' + id + '/assign';
      document.getElementById('assign-tarjeta-id').value = id;
      show(modalAssign);
    });
  });

  document.querySelectorAll('[data-close]').forEach(el=> el.addEventListener('click', function(){ hide(this.closest('.modal')); }));

  document.getElementById('form-tarjeta')?.addEventListener('submit', function(e){
    const n1 = (document.getElementById('t-numero-1')?.value || '').replace(/\D/g,'').padStart(4,'0').slice(0,4);
    const n2 = (document.getElementById('t-numero-2')?.value || '').replace(/\D/g,'').padStart(4,'0').slice(0,4);
    const n3 = (document.getElementById('t-numero-3')?.value || '').replace(/\D/g,'').padStart(4,'0').slice(0,4);
    const n4 = (document.getElementById('t-numero-4')?.value || '').replace(/\D/g,'').padStart(4,'0').slice(0,4);
    const fullNum = (n1 + n2 + n3 + n4).slice(0,16);
    document.getElementById('t-numero-hidden').value = fullNum;

    const mm = (document.getElementById('t-exp-mm')?.value || '').replace(/\D/g,'').slice(0,2).padStart(2,'0');
    const yy = (document.getElementById('t-exp-yy')?.value || '').replace(/\D/g,'').slice(0,2).padStart(2,'0');
    document.getElementById('t-exp-hidden').value = mm && yy ? (mm + '/' + yy) : '';

    const pesos = (document.getElementById('t-saldo-pesos')?.value || '0').replace(/[^\d]/g,'') || '0';
    let cents = (document.getElementById('t-saldo-centavos')?.value || '00').replace(/[^\d]/g,'');
    cents = (cents + '00').slice(0,2);
    document.getElementById('t-saldo-hidden').value = Number(pesos + '.' + cents).toFixed(2);
  });

  document.getElementById('form-amount')?.addEventListener('submit', function(e){
    const pesos = (document.getElementById('amt-pesos')?.value || '0').replace(/[^\d]/g,'') || '0';
    let cents = (document.getElementById('amt-centavos')?.value || '00').replace(/[^\d]/g,'');
    cents = (cents + '00').slice(0,2);
    const monto = Number(pesos + '.' + cents).toFixed(2);

    let m = this.querySelector('[name="monto"]');
    if (!m) {
      m = document.createElement('input'); m.type = 'hidden'; m.name = 'monto'; this.appendChild(m);
    }
    m.value = monto;
  });

  document.addEventListener('click', function(e){
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    const msg = el.getAttribute('data-confirm') || '¿Estás seguro?';
    if (!confirm(msg)) return;
    const form = el.closest('form');
    if (form) form.submit();
  });

  // Inline edit for non-admin users
  document.getElementById('btn-edit-my')?.addEventListener('click', function(e){
    e.preventDefault();
    const f = document.getElementById('form-edit-my'); if(f) f.style.display = 'block';
    this.style.display = 'none';
  });
  document.getElementById('cancel-edit-my')?.addEventListener('click', function(e){
    e.preventDefault();
    const f = document.getElementById('form-edit-my'); if(f) f.style.display = 'none';
    const btn = document.getElementById('btn-edit-my'); if(btn) btn.style.display = 'inline-block';
  });

  // Assign existing tarjeta by entering its full number
  const root = document.getElementById('tarjetas-root');
  const currentUserId = root?.dataset.currentUserId || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  async function lookupByNumber(numero) {
    try {
      const res = await fetch('/tarjetas/check?numero=' + encodeURIComponent(numero), { credentials: 'same-origin' });
      if (!res.ok) return null;
      return await res.json();
    } catch (err) { return null; }
  }

  async function assignTarjetaToCurrent(tarjetaId) {
    try {
      const payload = { usuario_id: currentUserId };
      try {
        const cvvEl = document.getElementById('found-cvv');
        if (cvvEl && (cvvEl.value || '').trim()) payload.cvv = (cvvEl.value || '').trim();
      } catch (e) {}
      const res = await fetch('/tarjetas/' + tarjetaId + '/assign', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });
      return res;
    } catch (err) { return null; }
  }

  document.querySelectorAll('#btn-assign-existing').forEach(btn => btn.addEventListener('click', async function(e){
    e.preventDefault();
    const input = document.getElementById('input-assign-numero');
    if (!input) return;
    const raw = (input.value || '').replace(/\D/g,'').slice(0,16);
    const errEl = document.getElementById('assign-error'); if(errEl) { errEl.style.display='none'; }
    if (raw.length !== 16) { if(errEl) { errEl.textContent = 'Ingresa un número de 16 dígitos.'; errEl.style.display='block'; } input.focus(); return; }

    const found = await lookupByNumber(raw);
    if (!found || !found.found) {
      if(errEl) { errEl.textContent = 'No se encontró una tarjeta con esos datos.'; errEl.style.display='block'; }
      input.focus();
      return;
    }

    const tarjetaId = found.tarjeta.id;
    // populate confirmation UI with tarjeta data and show fields for nombre/exp/cvv
    const foundCard = document.getElementById('found-card');
    if (!foundCard) {
      // fallback: assign directly
      const res = await assignTarjetaToCurrent(tarjetaId);
      if (res && res.ok) location.reload();
      else if(errEl) { errEl.textContent = 'Error al asignar la tarjeta. Intenta de nuevo.'; errEl.style.display='block'; }
      return;
    }
    document.getElementById('found-numero').value = (found.tarjeta.numero_tarjeta || '').replace(/(\d{4})(?=\d)/g, '$1 ');
    document.getElementById('found-nombre').value = found.tarjeta.nombre || '';
    // Do not auto-fill expiration so user must enter/confirm it manually
    document.getElementById('found-exp').value = '';
    document.getElementById('found-cvv').value = found.tarjeta.cvv || '';
    foundCard.dataset.tarjetaId = tarjetaId;
    foundCard.style.display = 'block';
    // hide the action buttons while confirmation panel is open
    document.querySelectorAll('#btn-assign-existing, #btn-create-random, #cancel-edit-my').forEach(el=>{ if(el) el.style.display='none'; });
    // attach handlers for confirm / cancel
    document.getElementById('btn-assign-confirm').onclick = async function(){
      const tid = foundCard.dataset.tarjetaId;
      const res = await assignTarjetaToCurrent(tid);
      if (res && res.ok) location.reload();
      else if(errEl) { errEl.textContent = 'Error al asignar la tarjeta. Intenta de nuevo.'; errEl.style.display='block'; }
    };
    document.getElementById('btn-assign-cancel').onclick = function(){
      foundCard.style.display='none';
      // restore action buttons when cancelling confirmation
      document.querySelectorAll('#btn-assign-existing, #btn-create-random, #cancel-edit-my').forEach(el=>{ if(el) el.style.display='inline-block'; });
    };
  }));

  document.querySelectorAll('#btn-create-random').forEach(btn => btn.addEventListener('click', async function(e){
    e.preventDefault();
    try {
      const res = await fetch('/tarjetas/create-random', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ assign_to_user: 1 })
      });
      if (res && res.ok) return location.reload();
      alert('No se pudo crear la tarjeta. Intenta más tarde.');
    } catch (err) { alert('Error de red'); }
  }));
});
</script>
@endsection
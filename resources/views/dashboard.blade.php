@extends('layouts.app')
@php
  use App\Models\Comentario;
  use Illuminate\Support\Str;
  $currentUser = $currentUser ?? auth()->user();
  $layoutPreviewMode = ($currentUser && ($currentUser->rol ?? '') === 'admin') ? session('layout_preview_as', 'admin') : 'user';
  $isAdmin = ($isAdmin ?? ($currentUser && ($currentUser->rol ?? '') === 'admin')) && $layoutPreviewMode !== 'user';
@endphp
@section('content')
  @php $dashboardReservaciones = collect($reservaciones ?? []); @endphp
  <div class="page-header">
    <h1>Reservaciones</h1>
    @if ($isAdmin)
    <div class="actions">
      <button id="open-new" class="btn-primary">Nueva Reservación</button>
    </div>
    @else
    <div class="actions">
      <a href="/propiedades" class="btn-primary">Reservar una propiedad</a>
    </div>
    @endif
  </div>

  <div class="card table-card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Estado</th>
            <th>Propiedad</th>
            <th>Costo</th>
            <th>Llegada</th>
            <th>Salida</th>
            
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($dashboardReservaciones as $r)
            <tr class="reserv-row @if(($r->estado ?? '') === 'pendiente') pending @endif @if(($r->estado ?? '') === 'cancelada') cancelled @endif"
                data-checkin="{{ $r->check_in }}" data-checkout="{{ $r->check_out }}" data-id="{{ $r->id }}">
              <td class="estado {{ \Illuminate\Support\Str::slug($r->estado ?? 'pendiente') }}">{{ $r->estado ?? 'pendiente' }}</td>
              <td>{{ $r->propiedad->nombre ?? $r->propiedad_id ?? '-' }}</td>
              <td>${{ number_format($r->total ?? 0, 2, ',', '.') }}</td>
              <td>{{ isset($r->check_in) ? \Carbon\Carbon::parse($r->check_in)->format('d M Y') : '-' }}</td>
              <td>{{ isset($r->check_out) ? \Carbon\Carbon::parse($r->check_out)->format('d M Y') : '-' }}</td>
              <td>
                @if (($r->estado ?? '') === 'pendiente' || ($r->estado ?? '') === 'confirmada' || ($r->estado ?? '') === 'finalizada' || ($r->estado ?? '') === 'en progreso')
                  <button class="open-schedule link-button">Ver cronograma</button>
                @endif
                @if ($isAdmin)
                  @if(in_array(($r->estado ?? ''), ['pendiente','confirmada']) && (($r->estado_pago ?? '') !== 'pagado'))
                    <a href="{{ route('pagos.form', $r->id) }}" class="open-schedule link-button">Pagar</a>
                  @endif
                  <a href="{{ route('reservaciones.edit', $r->id) }}" class="open-schedule link-button">Editar</a>
                  <a class="link-button danger" href="#">Borrar</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="muted">No hay reservaciones aún.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @php
    $dashPayments = $dashboardPayments ?? collect();
  @endphp

  @if($dashPayments->isNotEmpty())
    <div style="margin-top:16px;">
      <h2 style="margin:0 0 10px 0;">{{ $isAdmin ? 'Pagos y códigos (todos)' : 'Mis pagos y códigos' }}</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(460px,1fr));gap:14px;">
        @foreach($dashPayments as $p)
          @php
            $rawImg = $p->reservation->propiedad->ruta_img ?? null;
            $imgUrl = null;
            if (is_string($rawImg) && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $rawImg)) {
              $imgUrl = asset(ltrim($rawImg, '/\\'));
            }
            $qrPayload = 'HOMERES|RES:' . ($p->reservacion_id ?? '-') . '|PAGO:' . ($p->id ?? '-') . '|COD:' . ($p->codigo_qr ?? '');
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($qrPayload);
          @endphp
          <div style="background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(2,6,23,0.06);padding:12px;display:grid;grid-template-columns:1fr 220px;gap:12px;align-items:start;">
            <div>
              <div style="font-weight:800;margin-bottom:6px;">Pago #{{ $p->id }} · Reservación #{{ $p->reservacion_id }}</div>
              <div style="color:#374151;margin-bottom:4px;"><strong>Cliente:</strong> {{ $p->reservation->user->nombre ?? '-' }} {{ $p->reservation->user->apellido ?? '' }}</div>
              <div style="color:#374151;margin-bottom:4px;"><strong>Monto:</strong> ${{ number_format($p->monto ?? 0,2,',','.') }}</div>
              <div style="color:#374151;margin-bottom:4px;"><strong>Método:</strong> {{ $p->metodo_pago ?? '-' }}</div>
              <div style="color:#374151;margin-bottom:8px;"><strong>Estado:</strong> {{ ucfirst($p->estado ?? '-') }}</div>
              @if($imgUrl)
                <img src="{{ $imgUrl }}" alt="preview propiedad" style="width:100%;max-width:280px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;">
              @else
                <div style="width:100%;max-width:280px;height:120px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e5e7eb;background:#f8fafc;color:#94a3b8;">Sin preview</div>
              @endif
            </div>

            <div style="text-align:center;">
              <img src="{{ $qrUrl }}" alt="QR pago {{ $p->id }}" style="width:200px;height:200px;border:1px solid #e5e7eb;border-radius:10px;padding:8px;background:#fff;">
              <div style="margin-top:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;font-size:12px;word-break:break-all;">{{ $p->codigo_qr }}</div>
              <button type="button" class="btn" style="margin-top:8px;" data-qr-open data-qr-src="{{ $qrUrl }}" data-qr-code="{{ $p->codigo_qr }}">Ver código grande</button>
              <a href="{{ route('pagos.codes.show', $p->id) }}" class="link-button" style="display:inline-block;margin-top:6px;">Detalle de código</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <div id="modal-new" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <button class="modal-close" data-close>✕</button>
      <h3>Nueva Reservación</h3>
      <form method="POST" action="#" class="form-grid">
        @csrf
        <label><span>Propiedad</span><input name="propiedad_id" /></label>
        <label><span>Check-in</span><input type="date" name="check_in" /></label>
        <label><span>Check-out</span><input type="date" name="check_out" /></label>
        <label><span>Personas</span><input type="number" name="num_personas" min="1" /></label>
        <label class="full"><button type="button" id="save-new" class="btn-primary">Guardar</button></label>
      </form>
    </div>
  </div>

  <script>
    (function () {
      const open = document.getElementById('open-new');
      const modal = document.getElementById('modal-new');
      const closes = modal && modal.querySelectorAll('[data-close]');
      function show(){ modal && modal.setAttribute('aria-hidden','false'); modal && modal.classList.add('open'); }
      function hide(){ modal && modal.setAttribute('aria-hidden','true'); modal && modal.classList.remove('open'); }
      open && open.addEventListener('click', show);
      closes && closes.forEach(c => c.addEventListener('click', hide));
    })();
  </script>

    

    <div id="schedule-modal" class="modal" aria-hidden="true" style="display:none;position:fixed;inset:0;width:100vw;height:100vh;z-index:20000;">
      <div class="modal-backdrop" data-close></div>
      <div class="modal-panel modal-card" style="max-width:820px;margin:0 auto;">
        <button class="modal-close" data-close style="position:absolute;right:12px;top:12px;background:none;border:0;font-size:18px;">✕</button>
        <div style="padding:18px;">
          <h3 id="schedule-title">Cronograma</h3>
          <div id="schedule-today" style="margin-top:6px;color:#065f46;font-weight:700;font-size:13px;display:flex;align-items:center;gap:8px;">
            <svg width="10" height="10" viewBox="0 0 10 10" style="flex:0 0 auto;">
              <circle cx="5" cy="5" r="5" fill="#06b6d4"></circle>
            </svg>
            <span id="schedule-today-text">Hoy</span>
          </div>
          <div id="schedule-today-banner" style="display:none;margin-top:12px;">
            <div class="schedule-banner" id="schedule-banner-content"></div>
          </div>
          <div id="schedule-calendar" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;"></div>
        </div>
      </div>
    </div>

    <style>
      /* Row hover */
      .reserv-row.pending:hover { background: rgba(99,102,241,0.06); cursor: pointer; }

      /* Modal card */
      .modal-card { background: #fff; border-radius: 10px; box-shadow: 0 12px 48px rgba(2,6,23,0.12); }

      /* Grid */
      .rv-days { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; width: 100%; }

      /* Day tile base */
      .rv-day {
        min-width: 64px; padding: 10px 12px; border-radius: 10px; text-align: center;
        background: #f8fafc; border: 1px solid #eef2f7; font-size: 13px; color: #374151; position: relative;
        transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      }
      .rv-day .date { font-weight: 800; display: block; margin-bottom: 6px; }

      /* Past days (muted) */
      .rv-day.past { opacity: 0.55; filter: grayscale(.12); }
      .rv-day.past .day-dot { background: #94a3b8; }

      /* Waiting days (before reservation) */
      .rv-day.waiting { background: #fff7ed; border: 1px dashed #f59e0b; color: #92400e; }
      .rv-day.waiting .day-dot { background: #f59e0b; }

      /* In-range (reserved) */
      .rv-day.in-range { background: linear-gradient(90deg,#06b6d4,#6366f1); color: #fff; font-weight: 700; }
      .rv-day.in-range .day-dot { background: rgba(255,255,255,0.95); box-shadow: 0 0 0 2px rgba(99,102,241,0.12) inset; }

      /* Today (highest priority) */
      .rv-day.today {
        box-shadow: 0 12px 30px rgba(6,182,212,0.14); border-color: #06b6d4; background: linear-gradient(180deg,#71836e,#a7c0a3);
        transform: translateY(-2px);
      }
      .rv-day.today .day-dot { background: #30a364; box-shadow: 0 0 0 3px rgba(6,182,212,0.12) inset; }

      /* Combination: today inside range */
      .rv-day.in-range.today {
        background: linear-gradient(90deg,#71836e,#a7c0a3); color: #fff; border-color: #0369a1;
        box-shadow: 0 14px 34px rgba(59,130,246,0.12);
      }

      /* Day dot */
      .rv-day .day-dot { position: absolute; right: 8px; top: 8px; width: 10px; height: 10px; border-radius: 999px; display: inline-block; box-shadow: 0 2px 6px rgba(2,6,23,0.12); }

      .small, .small-muted { font-size: 0.9rem; color: #6b7280; }

      .rv-day.start { box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
      .rv-days.past-all .rv-day { opacity: 0.5; filter: grayscale(.18); }

      .passed-marker { display: flex; align-items: center; gap: 8px; padding-left: 8px; margin-top: 6px; color: #6b7280; }
      .passed-marker .dot { width: 10px; height: 10px; border-radius: 999px; background: #94a3b8; box-shadow: 0 2px 6px rgba(2,6,23,0.08); }

      .rv-waiting { display: flex; gap: 6px; align-items: center; margin-bottom: 8px; }
      .rv-waiting-label { font-size: 12px; color: #92400e; font-weight: 700; margin-bottom: 6px; }

      /* Banner (kept but muted) */
      .schedule-banner { background: linear-gradient(90deg,#06b6d4,#60a5fa); color: #052e2e; padding: 10px 12px; border-radius: 10px; font-weight: 800; display: flex; align-items: center; gap: 10px; font-size: 15px; }
      .schedule-banner svg { flex: 0 0 auto; }

      .muted { color: #6b7280; }
      .rv-summary { display: flex; gap: 8px; align-items: center; }
    </style>

    <script>
      (function(){
        function safeParseISO(d){ if(!d) return null; try { return new Date(d + 'T00:00:00'); } catch(e){return null;} }
        function openScheduleIfPossible(id,a,b){ if(!a || !b){ alert('Fechas no disponibles para esta reservación'); return; } openScheduleModal(id,a,b); }

        // click on row to open (and keep existing button behavior)
        document.querySelectorAll('.reserv-row.pending').forEach(tr=>{
          tr.addEventListener('click', function(e){
            const target = e.target;
            if (target.closest('a') || (target.tagName === 'BUTTON' && !target.classList.contains('open-schedule'))) return;
            const checkIn = tr.dataset.checkin;
            const checkOut = tr.dataset.checkout;
            const id = tr.dataset.id;
            console.debug('row click handler', {id, checkIn, checkOut});
            openScheduleIfPossible(id, checkIn, checkOut);
          });
          tr.addEventListener('mouseenter', ()=> tr.classList.add('hover'));
          tr.addEventListener('mouseleave', ()=> tr.classList.remove('hover'));
        });

        document.querySelectorAll('.open-schedule').forEach(btn=>{
          btn.addEventListener('click', function(e){ e.stopPropagation(); const tr = e.target.closest('tr'); console.debug('button click handler', {id: tr && tr.dataset.id}); openScheduleIfPossible(tr.dataset.id, tr.dataset.checkin, tr.dataset.checkout); });
        });

        // Delegate clicks as a fallback in case elements are re-rendered or listeners not attached
        document.addEventListener('click', function(e){
          const btn = e.target.closest && e.target.closest('.open-schedule');
          if (btn) {
            e.preventDefault(); e.stopPropagation(); const tr = btn.closest('tr'); console.debug('delegated .open-schedule click', tr && tr.dataset); if(tr) openScheduleIfPossible(tr.dataset.id, tr.dataset.checkin, tr.dataset.checkout);
            return;
          }
          const row = e.target.closest && e.target.closest('tr.reserv-row.pending');
          if (row && !e.target.closest('a') && !e.target.closest('button')) {
            console.debug('delegated row click', row.dataset);
            openScheduleIfPossible(row.dataset.id, row.dataset.checkin, row.dataset.checkout);
          }
        });

        document.querySelectorAll('#schedule-modal [data-close]').forEach(el=>el.addEventListener('click', hideScheduleModal));
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') hideScheduleModal(); });

        function hideScheduleModal(){ const m = document.getElementById('schedule-modal'); if(m){ m.style.display='none'; m.classList.remove('open'); } }

        function parseISO(d){ return safeParseISO(d); }
        function addDays(d,n){ const x=new Date(d); x.setDate(x.getDate()+n); return x; }
        function fmtDate(d){ return d.toISOString().slice(0,10); }
        function datesBetween(a,b){ const out=[]; let cur=parseISO(a); const end=parseISO(b); if(!cur || !end) return out; while(cur<=end){ out.push(fmtDate(cur)); cur = addDays(cur,1);} return out; }

        function openScheduleModal(id, a, b){
          const m = document.getElementById('schedule-modal');
          console.debug('openScheduleModal called', {id,a,b, modalExists: !!m});
          if(!m) return;
          m.style.display='flex'; m.style.alignItems='center'; m.style.justifyContent='center'; m.classList.add('open');
          document.getElementById('schedule-title').textContent = 'Reservación #' + id + ' — ' + a + ' → ' + b;
          // mostrar fecha actual en formato agradable
          try {
            const today = new Date();
            const txt = today.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
            const el = document.getElementById('schedule-today-text'); if(el) el.textContent = 'Hoy • ' + txt;
          } catch(e){}
          renderCalendarRange(a,b);
          setTimeout(()=>{ m.querySelector('.modal-panel') && m.querySelector('.modal-panel').offsetHeight; }, 10);
        }

        function renderCalendarRange(a,b){
          const start = parseISO(a); const end = parseISO(b);
          const container = document.getElementById('schedule-calendar'); container.innerHTML=''; const range = datesBetween(a,b);
          const days = range.slice(); const maxVisible = 25;
          if (days.length === 0) { container.innerHTML = '<div class="muted">Fechas no disponibles</div>'; return; }
            if (days.length <= maxVisible) {
              const todayISO = (new Date()).toISOString().slice(0,10);

              // compute start/end and determine if fully past
              const startISO = a; const endISO = b;
              const isPastAll = endISO < todayISO;

              // compute waiting range (days between tomorrow and day before start)
              let waitRange = [];
              const startDateObj = parseISO(startISO);
              const todayObj = parseISO(todayISO);
              if (startDateObj && todayObj && startISO > todayISO) {
                const waitStart = addDays(todayObj, 1);
                const waitEnd = addDays(startDateObj, -1);
                const waitStartISO = fmtDate(waitStart); const waitEndISO = fmtDate(waitEnd);
                waitRange = datesBetween(waitStartISO, waitEndISO);
                if (waitRange.length > 0) {
                  if (waitRange.length <= 10) {
                    const label = document.createElement('div'); label.className='rv-waiting-label'; label.textContent = 'Días de espera'; container.appendChild(label);
                    const waitGrid = document.createElement('div'); waitGrid.className='rv-waiting';
                    waitRange.forEach(isoW => {
                      const dW = new Date(isoW + 'T00:00:00');
                      const wEl = document.createElement('div'); wEl.className='rv-day waiting';
                      const dateElW = document.createElement('div'); dateElW.className='date'; dateElW.textContent = ('0'+dW.getDate()).slice(-2);
                      const mElW = document.createElement('div'); mElW.style.fontSize='11px'; mElW.style.color='#92400e'; mElW.textContent = dW.toLocaleString(undefined,{month:'short'});
                      const dotW = document.createElement('span'); dotW.className='day-dot'; wEl.appendChild(dotW);
                      // highlight waiting-day if it's today
                      if (isoW === todayISO) {
                        wEl.classList.add('today');
                      }
                      wEl.appendChild(dateElW); wEl.appendChild(mElW); waitGrid.appendChild(wEl);
                    }); container.appendChild(waitGrid);
                  } else {
                    const info = document.createElement('div'); info.className='small-muted'; info.textContent = 'Faltan ' + waitRange.length + ' días hasta ' + startDateObj.toLocaleDateString(); container.appendChild(info);
                  }
                }
              }

              const grid = document.createElement('div'); grid.className = 'rv-days' + (isPastAll ? ' past-all' : '');
              days.forEach(iso => {
                const d = new Date(iso + 'T00:00:00');
                const dayEl = document.createElement('div'); dayEl.className = 'rv-day';
                const dateEl = document.createElement('div'); dateEl.className = 'date'; dateEl.textContent = ('0'+d.getDate()).slice(-2);
                const mEl = document.createElement('div'); mEl.style.fontSize='11px'; mEl.style.color='#6b7280'; mEl.textContent = d.toLocaleString(undefined,{month:'short'});
                // status classes
                if (iso === a) dayEl.classList.add('start'); if (iso === b) dayEl.classList.add('end');
                if (iso < todayISO) dayEl.classList.add('past');
                if (iso === todayISO) dayEl.classList.add('today');
                if (range.includes(iso)) dayEl.classList.add('in-range');
                // dot indicator
                const dot = document.createElement('span'); dot.className = 'day-dot'; dayEl.appendChild(dot);
                dayEl.appendChild(dateEl); dayEl.appendChild(mEl); grid.appendChild(dayEl);
              }); container.appendChild(grid);

              // if reservation fully past, append a dot marker after the last day
              if (isPastAll) {
                const marker = document.createElement('div'); marker.className = 'passed-marker';
                const mdot = document.createElement('span'); mdot.className = 'dot'; marker.appendChild(mdot);
                const check_out = b;
                const txt = document.createElement('div'); txt.textContent = 'Reservación finalizada, finalizó el: ' + check_out;
                marker.appendChild(txt);
                container.appendChild(marker);
              }

              // (banner logic removed) - we highlight the specific tile(s) instead
            } else {
            const wrapper = document.createElement('div'); wrapper.className='rv-summary';
            const first = days[0]; const last = days[days.length-1];
            const firstDate = new Date(first + 'T00:00:00'); const lastDate = new Date(last + 'T00:00:00');
            const firstEl = document.createElement('div'); firstEl.className='rv-day'; firstEl.style.minWidth='140px'; firstEl.style.padding='10px'; firstEl.innerHTML = '<div style="font-weight:800">'+firstDate.toLocaleDateString()+'</div><div class="small">Check-in</div>';
            const dots = document.createElement('div'); dots.style.fontWeight='900'; dots.style.color='#6b7280'; dots.textContent = '…'; dots.style.padding='0 8px';
            const lastEl = document.createElement('div'); lastEl.className='rv-day'; lastEl.style.minWidth='140px'; lastEl.style.padding='10px'; lastEl.innerHTML = '<div style="font-weight:800">'+lastDate.toLocaleDateString()+'</div><div class="small">Check-out</div>';
            const info = document.createElement('div'); info.className='small-muted'; info.style.marginLeft='auto'; info.textContent = 'Total días: ' + days.length;
            wrapper.appendChild(firstEl); wrapper.appendChild(dots); wrapper.appendChild(lastEl); wrapper.appendChild(info); container.appendChild(wrapper);
          }
        }
      })();
    </script>

    <div id="qr-modal" class="modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:21000;">
      <div class="modal-backdrop" data-qr-close></div>
      <div class="modal-panel" style="max-width:520px;position:relative;">
        <button type="button" data-qr-close style="position:absolute;right:10px;top:10px;border:0;background:transparent;font-size:18px;">✕</button>
        <h3 style="margin-top:0;">Código QR (check-in)</h3>
        <div style="display:flex;justify-content:center;">
          <img id="qr-modal-img" src="" alt="QR grande" style="width:420px;height:420px;max-width:100%;max-height:70vh;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fff;">
        </div>
        <div id="qr-modal-code" style="margin-top:10px;text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;"></div>
      </div>
    </div>

    <script>
      (function(){
        const modal = document.getElementById('qr-modal');
        const img = document.getElementById('qr-modal-img');
        const code = document.getElementById('qr-modal-code');
        document.querySelectorAll('[data-qr-open]').forEach((btn) => {
          btn.addEventListener('click', function(){
            if(!modal || !img || !code) return;
            img.src = btn.getAttribute('data-qr-src') || '';
            code.textContent = btn.getAttribute('data-qr-code') || '';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
          });
        });
        document.querySelectorAll('[data-qr-close]').forEach((btn) => {
          btn.addEventListener('click', function(){ if(modal){ modal.style.display = 'none'; } });
        });
      })();
    </script>

@endsection
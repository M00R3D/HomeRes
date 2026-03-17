@php $user = $currentUser ?? auth()->user(); @endphp
<div class="notification-bell" style="position:relative;display:inline-block;">
  <button id="notif-bell-btn" aria-haspopup="true" aria-expanded="false" aria-label="Notificaciones" style="background:transparent;border:0;cursor:pointer;position:relative;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 17h5l-1.405-1.405C18.403 14.403 18 13.74 18 13V9c0-3.07-1.64-5.64-4.5-6.32V2a1.5 1.5 0 00-3 0v.68C7.64 3.36 6 5.92 6 9v4c0 .74-.403 1.403-1.595 2.595L3 17h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span id="notif-badge" role="status" aria-live="polite" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;padding:2px 6px;border-radius:999px;font-size:12px;display:none;">0</span>
  </button>

  <div id="notif-dropdown" role="menu" aria-label="Notificaciones" style="display:none;position:absolute;right:0;top:36px;width: min(560px, calc(100vw - 40px));border-radius:8px;z-index:1200;">
    <div style="padding:8px;display:flex;justify-content:space-between;align-items:center;">
      <strong>Notificaciones</strong>
      <div>
        <button id="notif-mark-all" class="btn-alt" style="padding:6px 8px;border-radius:6px">Marcar todas</button>
        <a href="{{ route('notifications.index') }}" style="margin-left:8px;">Ver todas</a>
      </div>
    </div>
    <div id="notif-list" style="max-height:480px;overflow:auto;overflow-x:hidden;padding:6px;">
      <div style="padding:12px;text-align:center;">Cargando…</div>
    </div>
    <div style="padding:8px;text-align:center;"><button id="notif-load-more" class="btn-alt" style="display:none;padding:8px 12px;border-radius:8px">Ver más</button></div>
  </div>
</div>

<!-- toast container -->
<div id="notif-toast-container" style="position:fixed;right:18px;top:80px;z-index:1400;pointer-events:none"></div>

@push('scripts')
<script>
// tooltip styles for notification items and small action links
const _notifTooltipStyle = document.createElement('style');
_notifTooltipStyle.textContent = `
.notif-item{position:relative}
.notif-tooltip { position:absolute; right:8px; top:50%; max-width:calc(100% - 24px); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:6px 8px; border-radius:8px; font-size:12px; opacity:0; transform:translateY(8px) translateY(-50%); transition:opacity .18s ease, transform .18s ease; pointer-events:none; backdrop-filter:blur(4px); z-index:2; }
.notif-tooltip.notif-tooltip-detail { right:256px; max-width:calc(100% - 112px); alpha:0.45;background:rgba(0,0,0,0.6); color:#fff; }
.notif-item:hover .notif-tooltip { opacity:1; transform:translateY(0) translateY(-50%); }
.action-hint { transition:opacity .18s ease, transform .18s ease; }
.action-hint.show { opacity:1; transform:translateY(0); }
.action-hint.inline{ position:static; display:inline-block; margin-left:8px; padding:4px 8px; border-radius:6px; font-size:12px; transform:none; opacity:1; pointer-events:auto }
`;
document.head.appendChild(_notifTooltipStyle);

document.addEventListener('DOMContentLoaded', function(){
  // Browser Notification support: request permission once and show native notifications
  let browserNotificationsEnabled = false;
  async function ensureNotificationPermission(){
    try{
      if(!('Notification' in window)) return false;
      if(Notification.permission === 'granted'){ browserNotificationsEnabled = true; return true; }
      if(Notification.permission === 'denied'){ browserNotificationsEnabled = false; return false; }
      const p = await Notification.requestPermission();
      browserNotificationsEnabled = (p === 'granted');
      return browserNotificationsEnabled;
    }catch(e){ return false; }
  }

  function showBrowserNotification(n){
    try{
      if(!('Notification' in window) || Notification.permission !== 'granted') return;
      const data = n.data || {};
      const title = data.title || 'Notificación';
      const body = data.body || '';
      const icon = data.icon || '/logos/logoHomeRes.png';
      const resource = n.link || data.link || data.url || '';
      const options = { body, icon, tag: n.id };
      const notif = new Notification(title, options);
      notif.onclick = function(ev){
        try{
          window.focus();
          if(resource){ window.open(resource, '_blank'); } else { window.location.href = '/notifications'; }
        }catch(e){}
        notif.close();
      };
      // auto-close after 8s
      setTimeout(()=>{ try{ notif.close(); }catch(e){} }, 8000);
    }catch(e){}
  }
  const btn = document.getElementById('notif-bell-btn');
  const badge = document.getElementById('notif-badge');
  const dropdown = document.getElementById('notif-dropdown');
  const list = document.getElementById('notif-list');
  const markAllBtn = document.getElementById('notif-mark-all');
  const loadMoreBtn = document.getElementById('notif-load-more');
  let loaded = 0;

  async function fetchCount(){
    try{
      const res = await fetch('/notifications/count', {headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(!res.ok) return;
      const json = await res.json();
      const n = json.unread_count || 0;
      if(n>0){ badge.style.display='inline-block'; badge.textContent = n; } else { badge.style.display='none'; }
    }catch(e){}
  }

  // Polling to detect new notifications (simple fallback if broadcasting not configured)
  let lastCount = 0;
  let userPrefInApp = true;
  let userPrefPush = true;
  async function pollForNew(){
    try{
      const res = await fetch('/notifications/count', {headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(!res.ok) return;
      const json = await res.json();
      const n = json.unread_count || 0;
      if(json.prefs){
        userPrefInApp = Boolean(json.prefs.channel_inapp);
        userPrefPush = Boolean(json.prefs.receive_push);
      }
      if(n > lastCount){
        // fetch newest notification payload and show toast
        try{
          const dd = await fetch('/notifications/dropdown?limit=1', {headers:{'X-Requested-With':'XMLHttpRequest'}});
          if(dd.ok){
            const j = await dd.json();
            const first = (j.notifications && j.notifications[0]) || null;
            if(first && userPrefInApp){ showToast(first); }
          }
        }catch(e){}
        // show browser push-style notification if permitted
        try{
          if(browserNotificationsEnabled && userPrefPush){
            const dd2 = await fetch('/notifications/dropdown?limit=1', {headers:{'X-Requested-With':'XMLHttpRequest'}});
            if(dd2.ok){ const j2 = await dd2.json(); const first2 = (j2.notifications && j2.notifications[0]) || null; if(first2) showBrowserNotification(first2); }
          }
        }catch(e){}
      }
      lastCount = n;
      if(n>0){ badge.style.display='inline-block'; badge.textContent = n; } else { badge.style.display='none'; }
    }catch(e){}
  }

  function showToast(n){
    const data = n.data || {};
    const el = document.createElement('div');
    el.className = 'notification-toast';
    el.style.pointerEvents='auto';
    el.style.borderRadius='8px';
    el.style.padding='10px 12px';
    el.style.boxShadow='0 12px 36px rgba(2,6,23,0.12)';
    el.style.display='flex';
    el.style.gap='10px';
    el.style.alignItems='flex-start';
    el.innerHTML = `
      <div style="width:44px;height:44px;border-radius:8px;flex:0 0 44px" class="toast-icon-placeholder"></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;margin-bottom:4px">${(data.title||'Notificación')}</div>
        <div class="toast-body-text" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${(data.body||'')}</div>
      </div>
      <button aria-label="Cerrar" style="border:0;cursor:pointer;margin-left:6px">✕</button>
    `;
    const container = document.getElementById('notif-toast-container');
    container.appendChild(el);
    const closeBtn = el.querySelector('button');
    const remove = () => { if(el.parentNode) el.parentNode.removeChild(el); };
    closeBtn.addEventListener('click', remove);
    // auto remove after 5s
    setTimeout(remove, 5000);
  }

  async function loadDropdown(reset=false){
    if(reset) loaded = 0;
    try{
      const limit = 10 + loaded;
      const res = await fetch(`/notifications/dropdown?limit=${limit}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(!res.ok){ list.innerHTML = '<div style="padding:12px;color:#b91c1c">Error cargando</div>'; return; }
      const json = await res.json();
      const items = json.notifications || [];
      // show only unread notifications in the bell dropdown
      const unread = items.filter(x => !x.read_at);
      if(unread.length===0){
        list.innerHTML = `<div style="padding:12px;text-align:center;">No tienes notificaciones sin leer. <a href="/notifications" style="margin-left:6px;">Ver todas las notificaciones</a></div>`;
        loadMoreBtn.style.display='none';
        return;
      }
      list.innerHTML = '';
      unread.forEach(n => {
        const read = n.read_at ? 'opacity:0.6' : 'font-weight:700';
        const data = n.data || {};
        const icon = data.icon ? `<img src="${data.icon}" style="width:28px;height:28px;border-radius:6px;margin-right:8px">` : `<div class="notif-icon-placeholder" style="width:28px;height:28px;border-radius:6px;margin-right:8px"></div>`;
          const el = document.createElement('div');
        el.setAttribute('role','menuitem');
          // keep resource link separate; clicking the item opens the notification detail
          el.dataset.resourceLink = n.link || data.link || data.url || '';
        el.dataset.id = n.id;
          el.className = 'notif-item';
          el.style.padding='10px';
          el.style.display='flex';
          el.style.alignItems='center';
          el.style.borderBottom='1px solid var(--input-border, #f3f4f6)';
          el.style.position = 'relative';
        el.innerHTML = `
              <div style="display:flex;align-items:center;flex:1;${read}">
                ${icon}
                <div style="flex:1;min-width:0">
                  <div style="white-space:normal;overflow-wrap:break-word;word-break:break-word">${data.title||''}</div>
                  <div class="notif-body-text" style="font-size:12px;white-space:normal;overflow-wrap:break-word;word-break:break-word">${(data.body||'')}</div>
                </div>
              </div>
              <div style="margin-left:8px">
                <button data-id="${n.id}" class="notif-mark-read" style="border:0;cursor:pointer">Marcar</button>
              </div>
                <span class="notif-tooltip notif-tooltip-detail">Clic para ver detalle de notificación</span>
            `;
        // click on item -> mark read then navigate or open detail
        el.addEventListener('click', async function(e){
          // prevent when clicking mark button
          if (e.target.closest('.notif-mark-read')) return;
          const id = el.dataset.id;
          try{
            await fetch(`/notifications/${id}/read`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'X-Requested-With':'XMLHttpRequest'}});
          }catch(err){}
          // open notification detail page (primary action)
          window.location.href = `/notifications/${id}`;
        });
        // add small action button to open resource link if present
        const resource = el.dataset.resourceLink;
        if(resource){
          function inferTypeFromUrl(url, data){
            try{
              const parsed = new URL(url, window.location.origin);
              const path = parsed.pathname.replace(/^\/+|\/+$/g,'');
              const seg = path.split('/').filter(Boolean);
              const first = (seg[0] || '').toLowerCase();
              const map = {reservaciones:'reservacion',pagos:'pago',propiedades:'propiedad',usuarios:'usuario',tarjetas_simuladas:'tarjeta',tarjetas:'tarjeta',cabanas:'cabana'};
              if(data && (data.tipo || data.type)) return (data.tipo || data.type).toString().toLowerCase();
              if(map[first]) return map[first];
              if(first.endsWith('es')) return first.slice(0,-2);
              return first.replace(/s$/,'') || 'recurso';
            }catch(e){
              return (data && (data.tipo || data.type)) ? (data.tipo || data.type).toString().toLowerCase() : 'recurso';
            }
          }

          const action = document.createElement('button');
          action.className = 'notif-open-resource action-hint inline';
          const inferred = inferTypeFromUrl(resource, data);
          action.textContent = 'ver ' + (inferred || 'recurso');
          action.style.marginLeft = '8px';
          action.addEventListener('click', function(ev){
            ev.stopPropagation();
            // open in new tab
            window.open(resource, '_blank');
          });
          // place inside right-side container
          const right = el.querySelector('div[style*="margin-left:8px"]');
          if(right) right.appendChild(action);
        }
        list.appendChild(el);
      });
      loadMoreBtn.style.display = items.length>10 ? 'block' : 'none';
      loaded = items.length;
    }catch(e){ list.innerHTML = '<div style="padding:12px;color:#b91c1c">Error</div>'; }
  }

  // Attach hover listeners to small action hints (e.g., payments 'Ver' link)
  function attachActionHints(){
    document.querySelectorAll('.action-hint').forEach(h => {
      const parent = h.parentElement;
      if(!parent) return;
      // avoid double-binding
      if(parent._hasActionHint) return;
      parent._hasActionHint = true;
      parent.addEventListener('mouseenter', ()=> h.classList.add('show'));
      parent.addEventListener('mouseleave', ()=> h.classList.remove('show'));
    });
  }

  btn.addEventListener('click', async function(e){
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    if(!expanded){
      dropdown.style.display='block'; btn.setAttribute('aria-expanded','true');
      list.innerHTML = '<div style="padding:12px;text-align:center;color:#6b7280">Cargando…</div>';
      await loadDropdown(true);
      // attach tooltips after loading dropdown items
      attachActionHints();
      await fetchCount();
    } else {
      dropdown.style.display='none'; btn.setAttribute('aria-expanded','false');
    }
  });

  document.addEventListener('click', function(e){
    if(!dropdown.contains(e.target) && !btn.contains(e.target)){
      dropdown.style.display='none'; btn.setAttribute('aria-expanded','false');
    }
  });

  list.addEventListener('click', async function(e){
    const markBtn = e.target.closest('.notif-mark-read');
    if(markBtn){
      const id = markBtn.dataset.id;
      try{
        const res = await fetch(`/notifications/${id}/read`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'X-Requested-With':'XMLHttpRequest'}});
        if(res.ok){ await loadDropdown(true); await fetchCount(); }
      }catch(err){}
    }
  });

  markAllBtn.addEventListener('click', async function(e){
    try{
      const res = await fetch('/notifications/mark-all-read', {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'X-Requested-With':'XMLHttpRequest'}});
      if(res.ok){ await loadDropdown(true); await fetchCount(); }
    }catch(err){}
  });

  // initial count
  fetchCount();
  // request permission politely when user first loads page (only if never asked)
  if('Notification' in window && Notification.permission === 'default'){
    // defer request slightly so it feels less abrupt
    setTimeout(() => ensureNotificationPermission(), 2000);
  } else if('Notification' in window && Notification.permission === 'granted'){
    browserNotificationsEnabled = true;
  }
  // start poll every 5s
  pollForNew();
  setInterval(pollForNew, 5000);
  // attach action hints on initial load (for pagos index buttons etc.)
  attachActionHints();
});
</script>
@endpush

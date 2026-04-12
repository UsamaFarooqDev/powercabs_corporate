(() => {
  const BC_NAME = 'powercab-corporate-rides';
  const LS_KEY = 'powercab_corporate_rides_refresh';

  const snapshotUrl = new URL('php/rides_snapshot.php', window.location.href).href;

  function statusClass(status) {
    if (status === 'In Progress') return 'text-warning';
    if (status === 'Completed') return 'text-success';
    if (status === 'Cancelled') return 'text-danger';
    return '';
  }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function formatPickupDateTime(raw) {
    if (!raw) return '';
    const d = new Date(String(raw).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(raw);
    const date = `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${String(d.getFullYear()).slice(-2)}`;
    let h = d.getHours();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const time = `${pad(h)}:${pad(d.getMinutes())} ${ampm}`;
    return `<div style="color:#111827;line-height:1.2;">${date}</div>`
         + `<div style="font-size:12px;color:#6b7280;line-height:1.2;margin-top:2px;">${time}</div>`;
  }

  function rideRowHtml(ride) {
    return `
      <tr style='border-bottom: 1px solid #e5e5e5;'>
        <td class='py-3' style='font-size: 14px;'>${ride.employee || ''}</td>
        <td class='py-3' style='font-size: 14px;'>${ride.pickup || ''}</td>
        <td class='py-3' style='font-size: 14px;'>${ride.destination || ''}</td>
        <td class='py-3' style='font-size: 14px; white-space: nowrap;'>${formatPickupDateTime(ride.pickupTime)}</td>
        <td class='py-3' style='font-size: 14px;'>${ride.vehicle_number || 'N/A'}</td>
        <td class='py-3' style='font-size: 14px;'>€${ride.fare || 0}</td>
        <td class='py-3' style='font-size: 14px;'><span class="${statusClass(ride.status || '')}">${ride.status || ''}</span></td>
      </tr>
    `;
  }

  function setStats(stats) {
    const totalNode = document.getElementById('total-rides');
    const pendingNode = document.getElementById('pending-rides');
    const expenseNode = document.getElementById('total-expense');
    if (totalNode && typeof stats.totalRides !== 'undefined') totalNode.textContent = stats.totalRides;
    if (pendingNode && typeof stats.pendingRides !== 'undefined') pendingNode.textContent = stats.pendingRides;
    if (expenseNode && typeof stats.expense !== 'undefined') expenseNode.textContent = stats.expense;
  }

  function renderRides(rides) {
    const bodyEl = document.getElementById('rides-body');
    if (!bodyEl) return;

    let savedPage = 0;
    let savedSearch = '';
    if (window.ridesDataTable) {
      try {
        const info = window.ridesDataTable.page.info();
        savedPage = info.page;
        savedSearch = window.ridesDataTable.search();
      } catch (_) {
        /* ignore */
      }
      try {
        window.ridesDataTable.destroy();
      } catch (_) {
        /* ignore */
      }
      window.ridesDataTable = null;
    }

    const rows = (rides || []).map(rideRowHtml);
    bodyEl.innerHTML = rows.join('');

    if (typeof window.initRidesDataTable === 'function') {
      window.initRidesDataTable();
    }

    if (window.ridesDataTable) {
      try {
        if (savedSearch) {
          window.ridesDataTable.search(savedSearch);
          const ridesSearch = document.getElementById('ridesSearch');
          if (ridesSearch) ridesSearch.value = savedSearch;
        }
        const info = window.ridesDataTable.page.info();
        const maxPage = Math.max(0, info.pages - 1);
        const targetPage = Math.min(savedPage, maxPage);
        window.ridesDataTable.page(targetPage).draw(false);
      } catch (_) {
        /* ignore */
      }
    }
  }

  let refreshDebounceTimer = null;
  function refreshRides() {
    if (refreshDebounceTimer) clearTimeout(refreshDebounceTimer);
    refreshDebounceTimer = setTimeout(() => {
      refreshDebounceTimer = null;
      fetch(snapshotUrl, { credentials: 'same-origin', cache: 'no-store' })
        .then((res) => {
          if (!res.ok) {
            console.warn('[RealtimeRides] snapshot HTTP', res.status, res.statusText);
            return null;
          }
          const ct = res.headers.get('Content-Type') || '';
          if (!ct.includes('application/json')) {
            console.warn('[RealtimeRides] snapshot not JSON, got', ct);
            return null;
          }
          return res.json();
        })
        .then((data) => {
          if (!data) return;
          if (!data.success) {
            console.warn('[RealtimeRides] snapshot rejected:', data.message || data);
            return;
          }
          try {
            renderRides(data.rides || []);
            setStats(data.stats || {});
          } catch (e) {
            console.error('[RealtimeRides] render error:', e);
          }
        })
        .catch((err) => {
          console.error('[RealtimeRides] refresh failed:', err);
        });
    }, 120);
  }

  window.refreshCorporateRidesDashboard = refreshRides;

  const hasRidesTable = !!document.getElementById('rides-body');
  const hasDashboardStats = !!document.getElementById('total-rides');
  if (hasRidesTable || hasDashboardStats) {
    const pollCfg = window.RIDES_REALTIME_CONFIG || {};
    const rawPoll = pollCfg.pollIntervalMs;
    let pollMs = 10000;
    if (rawPoll === 0 || rawPoll === false) {
      pollMs = 0;
    } else if (typeof rawPoll === 'number' && rawPoll >= 5000) {
      pollMs = rawPoll;
    }
    if (pollMs > 0) {
      setInterval(() => {
        if (document.visibilityState === 'hidden') return;
        refreshRides();
      }, pollMs);
    }

    try {
      const bc = new BroadcastChannel(BC_NAME);
      bc.onmessage = () => refreshRides();
    } catch (_) {}

    window.addEventListener('storage', (e) => {
      if (e.key === LS_KEY) refreshRides();
    });

    window.addEventListener('pageshow', (e) => {
      if (e.persisted) refreshRides();
    });
  }

  const cfg = window.RIDES_REALTIME_CONFIG || {};
  const supabaseNs = typeof window.supabase !== 'undefined' ? window.supabase : null;
  if (cfg.supabaseUrl && cfg.supabaseAnonKey && cfg.cid && supabaseNs && typeof supabaseNs.createClient === 'function') {
    const supabaseClient = supabaseNs.createClient(cfg.supabaseUrl, cfg.supabaseAnonKey);
    supabaseClient
      .channel(`corporate-rides-${cfg.cid}`)
      .on(
        'postgres_changes',
        {
          event: '*',
          schema: 'public',
          table: 'corporate_rides',
          filter: `cid=eq.${cfg.cid}`,
        },
        () => refreshRides()
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          refreshRides();
        }
      });
  }
})();

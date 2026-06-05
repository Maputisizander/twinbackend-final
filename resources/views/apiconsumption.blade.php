<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Consumption — TwinBackend</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f1117;color:#e2e8f0;min-height:100vh}
  .header{background:#161b27;border-bottom:1px solid #1e2535;padding:16px 28px;display:flex;align-items:center;justify-content:space-between}
  .header h1{font-size:15px;font-weight:700;color:#fff;letter-spacing:.02em}
  .header span{font-size:12px;color:#64748b}
  .badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:#0f2d1a;color:#22c55e;border:1px solid #166534}
  .badge.red{background:#2d0f0f;color:#f87171;border-color:#7f1d1d}
  .dot{width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse 1.5s infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .container{padding:24px 28px;max-width:1400px;margin:0 auto}

  /* Stat cards */
  .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
  .card{background:#161b27;border:1px solid #1e2535;border-radius:14px;padding:18px}
  .card-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.18em;color:#475569;margin-bottom:10px}
  .card-value{font-size:28px;font-weight:900;letter-spacing:-.02em;color:#f1f5f9}
  .card-sub{font-size:11px;color:#64748b;margin-top:4px}
  .card.green .card-value{color:#4ade80}
  .card.blue .card-value{color:#60a5fa}
  .card.amber .card-value{color:#fbbf24}
  .card.violet .card-value{color:#a78bfa}
  .card.rose .card-value{color:#fb7185}

  /* Services row */
  .services{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
  .svc{background:#161b27;border:1px solid #1e2535;border-radius:10px;padding:10px 16px;display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600}
  .svc-dot{width:8px;height:8px;border-radius:50%}
  .svc-dot.ok{background:#22c55e;box-shadow:0 0 6px #22c55e88}
  .svc-dot.err{background:#f87171;box-shadow:0 0 6px #f8717188}
  .svc-name{color:#94a3b8}
  .svc-val{color:#e2e8f0}

  /* Two-column layout */
  .cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
  @media(max-width:900px){.cols{grid-template-columns:1fr}}

  /* Panel */
  .panel{background:#161b27;border:1px solid #1e2535;border-radius:14px;overflow:hidden}
  .panel-head{padding:14px 18px;border-bottom:1px solid #1e2535;display:flex;align-items:center;justify-content:space-between}
  .panel-head h2{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.15em;color:#64748b}
  .panel-body{padding:16px 18px}

  /* Recent log */
  .log-row{display:grid;grid-template-columns:48px 60px 1fr 54px 54px 52px;gap:6px;align-items:center;padding:7px 0;border-bottom:1px solid #1e2535;font-size:11px}
  .log-row:last-child{border-bottom:none}
  .log-head{font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.1em;padding-bottom:6px;border-bottom:1px solid #1e2535;display:grid;grid-template-columns:48px 60px 1fr 54px 54px 52px;gap:6px}
  .method{font-weight:800;font-size:10px;padding:2px 6px;border-radius:4px;text-align:center}
  .method.GET{background:#0f2038;color:#60a5fa}
  .method.POST{background:#0f2d1a;color:#4ade80}
  .method.PUT,.method.PATCH{background:#2d1f0f;color:#fb923c}
  .method.DELETE{background:#2d0f0f;color:#f87171}
  .path{color:#cbd5e1;font-family:monospace;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .status-ok{color:#4ade80;font-weight:700}
  .status-err{color:#f87171;font-weight:700}
  .src-redis{background:#1e1040;color:#a78bfa;border:1px solid #4c1d95;font-weight:800;font-size:10px;padding:2px 7px;border-radius:5px}
  .src-db{background:#1c1505;color:#fbbf24;border:1px solid #78350f;font-weight:800;font-size:10px;padding:2px 7px;border-radius:5px}
  .ms{color:#64748b;font-family:monospace;font-size:10px;text-align:right}
  .ts{color:#475569;font-family:monospace;font-size:9px}

  /* Top routes */
  .route-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #1e2535;font-size:11px}
  .route-row:last-child{border-bottom:none}
  .route-path{flex:1;font-family:monospace;color:#cbd5e1;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .route-bar-wrap{width:90px;height:5px;background:#1e2535;border-radius:3px;overflow:hidden}
  .route-bar{height:100%;background:linear-gradient(90deg,#3b82f6,#60a5fa);border-radius:3px;transition:width .5s}
  .route-count{font-weight:800;color:#93c5fd;font-size:11px;min-width:30px;text-align:right}

  /* Bar chart */
  .chart{display:flex;align-items:flex-end;gap:3px;height:60px;padding-top:4px}
  .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px}
  .bar{width:100%;border-radius:2px 2px 0 0;background:linear-gradient(to top,#3b82f6,#818cf8);min-height:2px;transition:height .4s}
  .bar-label{font-size:8px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:28px;text-align:center}

  /* Buttons */
  .btn-reset{background:#2d0f0f;color:#f87171;border:1px solid #7f1d1d;border-radius:8px;padding:6px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.2s}
  .btn-reset:hover{background:#3d1515}
  .btn-refresh{background:#0f1e38;color:#60a5fa;border:1px solid #1e3a5f;border-radius:8px;padding:6px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.2s}
  .btn-refresh:hover{background:#162847}
  .btns{display:flex;gap:8px;align-items:center}

  /* Hit rate ring */
  .ring-wrap{display:flex;align-items:center;gap:16px}
  .ring{position:relative;width:64px;height:64px}
  .ring svg{transform:rotate(-90deg)}
  .ring-val{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;color:#a78bfa}
  .ring-label{font-size:11px;color:#64748b}
  .ring-label strong{display:block;font-size:13px;color:#e2e8f0;font-weight:700}

  .empty{color:#475569;font-size:12px;padding:20px 0;text-align:center}
  .refresh-badge{font-size:10px;color:#334155;margin-left:8px}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <h1>⚡ API Consumption</h1>
    <span id="status-badge" class="badge"><span class="dot"></span> Loading…</span>
    <span class="refresh-badge" id="last-refresh"></span>
  </div>
  <div class="btns">
    <button class="btn-refresh" onclick="load()">↻ Refresh</button>
    <button class="btn-reset" onclick="resetStats()">🗑 Reset Stats</button>
  </div>
</div>

<div class="container">

  <!-- Services -->
  <div class="services" id="services-row">
    <div class="svc"><span class="svc-dot" id="dot-db"></span><span class="svc-name">Database</span><span class="svc-val" id="val-db">…</span></div>
    <div class="svc"><span class="svc-dot" id="dot-redis"></span><span class="svc-name">Redis</span><span class="svc-val" id="val-redis">…</span></div>
    <div class="svc"><span class="svc-dot" id="dot-cache"></span><span class="svc-name">Cache Driver</span><span class="svc-val" id="val-cache">…</span></div>
    <div class="svc" id="svc-mem" style="display:none"><span class="svc-dot ok"></span><span class="svc-name">Redis Memory</span><span class="svc-val" id="val-mem">…</span></div>
  </div>

  <!-- Stat cards -->
  <div class="cards">
    <div class="card blue"><div class="card-label">Total Calls</div><div class="card-value" id="c-total">—</div><div class="card-sub">All time</div></div>
    <div class="card green"><div class="card-label">Today</div><div class="card-value" id="c-today">—</div><div class="card-sub">This date</div></div>
    <div class="card amber"><div class="card-label">This Hour</div><div class="card-value" id="c-hour">—</div><div class="card-sub" id="sub-hour">Current hour</div></div>
    <div class="card violet">
      <div class="card-label">Cache Hit Rate</div>
      <div class="card-value" id="c-hitrate">—</div>
      <div class="card-sub" id="sub-hits">Redis vs DB</div>
    </div>
    <div class="card green"><div class="card-label">DB Queries Saved</div><div class="card-value" id="c-saved">—</div><div class="card-sub">Served from Redis</div></div>
    <div class="card rose"><div class="card-label">Cache Misses</div><div class="card-value" id="c-misses">—</div><div class="card-sub">Hit the DB</div></div>
  </div>

  <!-- Charts row -->
  <div class="cols">
    <div class="panel">
      <div class="panel-head"><h2>Last 8 Days</h2></div>
      <div class="panel-body"><div class="chart" id="daily-chart"></div></div>
    </div>
    <div class="panel">
      <div class="panel-head"><h2>Last 24 Hours</h2></div>
      <div class="panel-body"><div class="chart" id="hourly-chart"></div></div>
    </div>
  </div>

  <!-- Top routes + Status codes -->
  <div class="cols">
    <div class="panel">
      <div class="panel-head"><h2>Top Routes</h2></div>
      <div class="panel-body" id="top-routes"><div class="empty">No data yet</div></div>
    </div>
    <div class="panel">
      <div class="panel-head"><h2>Status Codes</h2></div>
      <div class="panel-body" id="status-codes"><div class="empty">No data yet</div></div>
    </div>
  </div>

  <!-- Recent log -->
  <div class="panel" style="margin-top:16px">
    <div class="panel-head">
      <h2>Recent Calls <span style="color:#334155;font-weight:400;font-size:10px;text-transform:none;letter-spacing:0">(last 20)</span></h2>
      <span id="live-dot" class="badge" style="font-size:10px"><span class="dot"></span> Live</span>
    </div>
    <div class="panel-body" style="padding:0 18px">
      <div class="log-head">
        <span>Time</span><span>Method</span><span>Path</span><span>Status</span><span>Source</span><span style="text-align:right">ms</span>
      </div>
      <div id="recent-log"><div class="empty" style="padding:20px 0">No calls yet</div></div>
    </div>
  </div>

</div>

<script>
const BASE = '/api/v1';

// Read token from URL param or localStorage (passed from web dashboard)
const TOKEN = new URLSearchParams(location.search).get('token')
           || localStorage.getItem('auth_token')
           || '';

function authHeaders() {
  return TOKEN ? { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json' } : { 'Accept': 'application/json' };
}

function fmtTime(iso) {
  try { return new Date(iso).toLocaleTimeString('en-PH', {hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}); }
  catch { return iso?.substring(11,19) || '—'; }
}

function renderChart(containerId, data, labelKey, valueKey) {
  const el = document.getElementById(containerId);
  if (!data?.length) { el.innerHTML = '<div class="empty">No data</div>'; return; }
  const max = Math.max(...data.map(d => d[valueKey]), 1);
  el.innerHTML = data.map(d => {
    const h = Math.max(2, Math.round((d[valueKey] / max) * 56));
    return `<div class="bar-col">
      <div class="bar" style="height:${h}px" title="${d[valueKey]} calls"></div>
      <div class="bar-label">${d[labelKey]}</div>
    </div>`;
  }).join('');
}

async function load() {
  try {
    const [statusRes, consumeRes] = await Promise.all([
      fetch(`${BASE}/apistatus`, { headers: authHeaders() }),
      fetch(`${BASE}/apiconsumption`, { headers: authHeaders() })
    ]);
    const s = await statusRes.json();
    const c = await consumeRes.json();

    // Status badge
    const badge = document.getElementById('status-badge');
    badge.className = 'badge' + (s.status === 'ok' ? '' : ' red');
    badge.innerHTML = `<span class="dot"></span> ${s.status === 'ok' ? 'All Systems OK' : 'Degraded'}`;

    // Services
    const setDot = (id, ok) => { const d = document.getElementById(id); d.className = 'svc-dot ' + (ok?'ok':'err'); };
    const setVal = (id, v) => document.getElementById(id).textContent = v;
    setDot('dot-db',    s.services?.database === 'connected');
    setDot('dot-redis', s.services?.redis    === 'connected');
    setDot('dot-cache', s.services?.cache_driver === 'redis');
    setVal('val-db',    s.services?.database    || '—');
    setVal('val-redis', s.services?.redis       || '—');
    setVal('val-cache', s.services?.cache_driver|| '—');

    if (s.redis?.used_memory) {
      document.getElementById('svc-mem').style.display = 'flex';
      setVal('val-mem', s.redis.used_memory);
    }

    // Summary cards
    const sum = c.summary || {};
    document.getElementById('c-total').textContent  = (sum.total_calls   || 0).toLocaleString();
    document.getElementById('c-today').textContent  = (sum.calls_today   || 0).toLocaleString();
    document.getElementById('c-hour').textContent   = (sum.calls_this_hour || 0).toLocaleString();
    document.getElementById('c-hitrate').textContent = sum.hit_rate_percent != null ? sum.hit_rate_percent + '%' : '—';
    document.getElementById('c-saved').textContent  = (sum.saved_db_queries || 0).toLocaleString();
    document.getElementById('c-misses').textContent = (sum.cache_misses  || 0).toLocaleString();
    document.getElementById('sub-hits').textContent = `${sum.cache_hits||0} redis / ${sum.cache_misses||0} db / ${sum.uncacheable_calls||0} skip`;

    // Charts
    renderChart('daily-chart',  c.daily_chart,  'date', 'calls');
    renderChart('hourly-chart', c.hourly_chart, 'hour', 'calls');

    // Top routes
    const routeEl = document.getElementById('top-routes');
    if (c.top_routes?.length) {
      const max = c.top_routes[0].calls;
      routeEl.innerHTML = c.top_routes.map(r => `
        <div class="route-row">
          <div class="route-path" title="${r.route}">${r.route}</div>
          <div class="route-bar-wrap"><div class="route-bar" style="width:${Math.round((r.calls/max)*100)}%"></div></div>
          <div class="route-count">${r.calls.toLocaleString()}</div>
        </div>`).join('');
    } else {
      routeEl.innerHTML = '<div class="empty">No data yet</div>';
    }

    // Status codes
    const svcEl = document.getElementById('status-codes');
    if (c.status_codes?.length) {
      const colors = {2:'#4ade80', 3:'#60a5fa', 4:'#fbbf24', 5:'#f87171'};
      svcEl.innerHTML = c.status_codes.map(sc => {
        const color = colors[Math.floor(sc.status/100)] || '#94a3b8';
        return `<div class="route-row">
          <div style="font-size:14px;font-weight:900;color:${color};min-width:44px">${sc.status}</div>
          <div class="route-path" style="font-family:sans-serif">${statusLabel(sc.status)}</div>
          <div class="route-count" style="color:${color}">${sc.count.toLocaleString()}</div>
        </div>`;
      }).join('');
    } else {
      svcEl.innerHTML = '<div class="empty">No data yet</div>';
    }

    // Recent log
    const logEl = document.getElementById('recent-log');
    if (c.recent?.length) {
      logEl.innerHTML = c.recent.map(r => {
        const srcCls   = r.source === 'redis' ? 'src-redis' : 'src-db';
        const srcLabel = r.source === 'redis' ? '⚡ Redis'  : '🗄 DB';
        const stCls = r.status < 400 ? 'status-ok' : 'status-err';
        const mCls = `method ${r.method}`;
        return `<div class="log-row">
          <span class="ts">${fmtTime(r.ts)}</span>
          <span><span class="${mCls}">${r.method}</span></span>
          <span class="path" title="${r.path}">${r.path}</span>
          <span class="${stCls}">${r.status}</span>
          <span class="${srcCls}">${srcLabel}</span>
          <span class="ms">${r.ms}ms</span>
        </div>`;
      }).join('');
    } else {
      logEl.innerHTML = '<div class="empty" style="padding:20px 0">No calls yet — make some API requests</div>';
    }

    document.getElementById('last-refresh').textContent = 'Updated ' + new Date().toLocaleTimeString();

  } catch(e) {
    document.getElementById('status-badge').innerHTML = '<span class="dot"></span> Error';
    document.getElementById('status-badge').className = 'badge red';
    console.error(e);
  }
}

function statusLabel(code) {
  const m = {200:'OK',201:'Created',204:'No Content',301:'Redirect',304:'Not Modified',400:'Bad Request',401:'Unauthorized',403:'Forbidden',404:'Not Found',422:'Unprocessable',429:'Too Many Requests',500:'Server Error',503:'Unavailable'};
  return m[code] || '';
}

async function resetStats() {
  if (!confirm('Reset all API stats counters?')) return;
  try {
    const res = await fetch(`${BASE}/apiconsumption/reset`, { method: 'DELETE', headers: authHeaders() });
    const d = await res.json();
    alert(d.message || 'Reset done');
    load();
  } catch(e) { alert('Reset failed: ' + e.message); }
}

// Initial load + auto-refresh every 30s
load();
setInterval(load, 30000);
</script>
</body>
</html>

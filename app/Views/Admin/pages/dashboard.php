<?php
$pageTitle = 'Dashboard';
$active    = 'dashboard';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="users"></i></div><span class="stat-badge" id="kpi-active-badge">—</span></div><div class="stat-val" id="kpi-total-users">0</div><div class="stat-lbl">Total Users</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="activity"></i></div><span class="stat-badge live">Live</span></div><div class="stat-val" id="kpi-walking-now">0</div><div class="stat-lbl">Walking Now (5m)</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="footprints"></i></div><span class="stat-badge" id="kpi-km-today">0 km</span></div><div class="stat-val" id="kpi-steps-today">0</div><div class="stat-lbl">Steps Today</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon amber"><i data-lucide="coins"></i></div><span class="stat-badge" id="kpi-litties-total">0</span></div><div class="stat-val" id="kpi-litties-today">0</div><div class="stat-lbl">Litties Today</div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><h3>Steps Trend (7 days)</h3><div class="sub">Total steps per day</div></div></div>
    <div class="card-body"><canvas id="chart-steps" height="140"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>User Growth (30 days)</h3><div class="sub">Daily signups</div></div></div>
    <div class="card-body"><canvas id="chart-growth" height="140"></canvas></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><h3>Cause Distribution</h3><div class="sub">Steps by category (all-time)</div></div></div>
    <div class="card-body"><canvas id="chart-cause" height="180"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Transaction Trends (14 days)</h3><div class="sub">Litties earned vs spent</div></div></div>
    <div class="card-body"><canvas id="chart-tx" height="180"></canvas></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><h3>Peak Activity Hours</h3><div class="sub">Step events by hour (today)</div></div></div>
    <div class="card-body"><canvas id="chart-peak" height="140"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Top Walkers Today</h3><div class="sub">Ranked by steps</div></div></div>
    <div class="card-body" style="padding:0 20px 16px;">
      <div id="top-walkers">Loading...</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><h3>Users by Country</h3><div class="sub">From phone country codes</div></div></div>
    <div class="card-body" style="padding:0 20px 16px;">
      <div id="country-list">Loading...</div>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Live Activity Map</h3><div class="sub">Country markers + GPS clusters (last 7d)</div></div></div>
    <div class="card-body" style="padding:0;">
      <div id="country-map" style="height:360px;border-radius:0 0 14px 14px;"></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>Recent Signups</h3><div class="sub">Latest registered users</div></div></div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>User</th><th>Email</th><th>Joined</th></tr></thead>
      <tbody id="recent-users"><tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
var PALETTE = ['#003B4C','#22C55E','#F59E0B','#EF4444','#0E7490','#0D9488','#6C5CE7','#EC4899'];
function fmtNum(n){ n=Number(n)||0; return n.toLocaleString(); }
function fmtDate(s){ if(!s) return '—'; var d=new Date(s); if(isNaN(d)) return s; return d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0] + (p[1]? p[1][0] : '')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10)) % PALETTE.length]; }
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }

function lastNDates(n){
  var out=[]; var d=new Date();
  for(var i=n-1;i>=0;i--){ var x=new Date(d); x.setDate(d.getDate()-i); out.push(x.toISOString().slice(0,10)); }
  return out;
}
function seriesByDate(rows, days, key){
  var map={}; rows.forEach(function(r){ map[r.d]=Number(r[key])||0; });
  return days.map(function(d){ return map[d] || 0; });
}

fetch(window.API_BASE+'admin-dashboard-stats',{headers:{'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(res){
    if(!res || res.status!==true) throw new Error(res&&res.message||'Failed');
    renderAll(res.data);
  })
  .catch(function(e){ console.error(e); });

function renderAll(d){
  var k=d.kpi||{};
  document.getElementById('kpi-total-users').textContent = fmtNum(k.total_users);
  document.getElementById('kpi-active-badge').textContent = fmtNum(k.active_users)+' active';
  document.getElementById('kpi-walking-now').textContent = fmtNum(k.walking_now);
  document.getElementById('kpi-steps-today').textContent = fmtNum(k.steps_today);
  document.getElementById('kpi-km-today').textContent = (k.km_today||0)+' km';
  document.getElementById('kpi-litties-today').textContent = fmtNum(k.litties_today);
  document.getElementById('kpi-litties-total').textContent = fmtNum(k.litties_total)+' total';

  var d7 = lastNDates(7);
  new Chart(document.getElementById('chart-steps'), {
    type:'line',
    data:{ labels:d7.map(function(s){return s.slice(5);}), datasets:[{label:'Steps',data:seriesByDate(d.steps_trend||[],d7,'steps'),borderColor:'#003B4C',backgroundColor:'rgba(0,59,76,.08)',tension:.3,fill:true,pointRadius:3}] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  var d30 = lastNDates(30);
  new Chart(document.getElementById('chart-growth'), {
    type:'bar',
    data:{ labels:d30.map(function(s){return s.slice(5);}), datasets:[{label:'Signups',data:seriesByDate(d.user_growth||[],d30,'c'),backgroundColor:'#22C55E'}] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{ticks:{maxTicksLimit:10}}} }
  });

  var causes = d.cause_dist||[];
  new Chart(document.getElementById('chart-cause'), {
    type:'doughnut',
    data:{ labels:causes.map(function(c){return c.name;}), datasets:[{data:causes.map(function(c){return Number(c.steps)||0;}),backgroundColor:PALETTE.slice(0,causes.length)}] },
    options:{ responsive:true, maintainAspectRatio:false }
  });

  var d14 = lastNDates(14);
  new Chart(document.getElementById('chart-tx'), {
    type:'line',
    data:{ labels:d14.map(function(s){return s.slice(5);}), datasets:[
      {label:'Earned',data:seriesByDate(d.tx_trends||[],d14,'earned'),borderColor:'#22C55E',backgroundColor:'rgba(34,197,94,.08)',tension:.3,fill:true,pointRadius:2},
      {label:'Spent', data:seriesByDate(d.tx_trends||[],d14,'spent'), borderColor:'#EF4444',backgroundColor:'rgba(239,68,68,.08)',tension:.3,fill:true,pointRadius:2}
    ] },
    options:{ responsive:true, maintainAspectRatio:false }
  });

  var hours = []; for(var hh=0;hh<24;hh++) hours.push(hh);
  var hourMap={}; (d.peak_hours||[]).forEach(function(r){ hourMap[r.h]=Number(r.c)||0; });
  new Chart(document.getElementById('chart-peak'), {
    type:'bar',
    data:{ labels:hours.map(function(h){return h+'h';}), datasets:[{label:'Events',data:hours.map(function(h){return hourMap[h]||0;}),backgroundColor:'#0E7490'}] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  var walkers=d.top_walkers||[];
  var tw=document.getElementById('top-walkers');
  if(!walkers.length){ tw.innerHTML='<div style="padding:20px;color:var(--text-muted);text-align:center;">No walking activity today.</div>'; }
  else {
    tw.innerHTML = walkers.map(function(u,i){
      return '<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);">'
        + '<div style="font-weight:700;color:var(--text-muted);width:22px;">'+(i+1)+'</div>'
        + '<div class="user-ava" style="background:'+colorFor(u.id)+';">'+initials(u.full_name)+'</div>'
        + '<div style="flex:1;"><div class="user-name">'+esc(u.full_name||'—')+'</div></div>'
        + '<div style="font-weight:700;">'+fmtNum(u.steps)+' <span style="font-size:11px;color:var(--text-muted);font-weight:500;">steps</span></div>'
        + '</div>';
    }).join('');
  }

  var ru=d.recent_users||[];
  var tb=document.getElementById('recent-users');
  if(!ru.length){ tb.innerHTML='<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">No users yet.</td></tr>'; }
  else {
    tb.innerHTML = ru.map(function(u){
      return '<tr><td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(u.id)+';">'+initials(u.full_name)+'</div><div><div class="user-name">'+esc(u.full_name||'—')+'</div></div></div></td><td>'+esc(u.email||'—')+'</td><td>'+fmtDate(u.create_on)+'</td></tr>';
    }).join('');
  }

  // Countries list
  var countries = d.countries || [];
  var totalUsersAll = countries.reduce(function(s,c){ return s + (Number(c.users)||0); }, 0) || 1;
  var cl = document.getElementById('country-list');
  if (!countries.length) { cl.innerHTML='<div style="padding:20px;color:var(--text-muted);text-align:center;">No country data.</div>'; }
  else {
    cl.innerHTML = countries.map(function(c){
      var pct = Math.round((Number(c.users)||0) * 100 / totalUsersAll);
      return '<div style="padding:10px 0;border-bottom:1px solid var(--border);">'
        + '<div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">'
        + '<div style="font-size:20px;line-height:1;">'+c.flag+'</div>'
        + '<div style="flex:1;"><div style="font-weight:600;font-size:13px;">'+esc(c.name)+' <span style="color:var(--text-muted);font-weight:400;font-size:11px;">('+esc(c.phone_code)+')</span></div></div>'
        + '<div style="text-align:right;"><div style="font-weight:700;">'+fmtNum(c.users)+' <span style="font-size:11px;color:var(--text-muted);font-weight:500;">users</span></div><div style="font-size:10.5px;color:var(--text-muted);">'+fmtNum(c.steps)+' steps</div></div>'
        + '</div>'
        + '<div style="height:4px;background:var(--bg);border-radius:2px;overflow:hidden;"><div style="height:100%;width:'+pct+'%;background:'+colorFor(c.iso.charCodeAt(0))+';"></div></div>'
        + '</div>';
    }).join('');
  }

  // Map
  var mapEl = document.getElementById('country-map');
  if (mapEl && window.L) {
    mapEl.innerHTML = '';
    var map = L.map('country-map', { worldCopyJump:true }).setView([20, 30], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap', maxZoom:18 }).addTo(map);
    var maxUsers = Math.max(1, ...countries.map(function(c){return Number(c.users)||0;}));
    countries.forEach(function(c){
      if (!c.lat && !c.lng) return;
      var r = 8 + Math.round((Number(c.users)||0) * 18 / maxUsers);
      L.circleMarker([c.lat, c.lng], { radius:r, color:'#003B4C', weight:2, fillColor:'#003B4C', fillOpacity:.35 })
        .bindPopup('<strong>'+c.flag+' '+c.name+'</strong><br>'+fmtNum(c.users)+' users<br>'+fmtNum(c.steps)+' steps')
        .addTo(map);
    });
    (d.gps_clusters||[]).forEach(function(g){
      L.circleMarker([Number(g.lat), Number(g.lng)], { radius:5, color:'#22C55E', weight:2, fillColor:'#22C55E', fillOpacity:.7 })
        .bindPopup('<strong>GPS cluster</strong><br>'+fmtNum(g.walkers)+' walkers<br>'+fmtNum(g.events)+' events<br>'+fmtNum(g.steps)+' steps')
        .addTo(map);
    });
  }

  if(window.lucide) lucide.createIcons();
}
</script>

<style>
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
@media (max-width: 960px) { .grid-2 { grid-template-columns:1fr; } }
.stat-badge.live { background:rgba(34,197,94,.1); color:#22C55E; }
</style>

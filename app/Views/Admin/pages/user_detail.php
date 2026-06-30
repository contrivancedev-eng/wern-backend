<?php
$pageTitle = 'User Detail';
$active    = 'users';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:960px){ .g2 { grid-template-columns:1fr; } }
  .profile-hero { display:flex; align-items:center; gap:18px; padding:20px; }
  .profile-ava { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:26px; font-weight:700; flex-shrink:0; }
  .profile-meta { font-size:12px; color:var(--text-muted); display:flex; flex-wrap:wrap; gap:14px; align-items:center; }
  .profile-meta span { display:inline-flex; align-items:center; gap:4px; }
  .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; }
  .detail-item label { font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px; }
  .detail-item span { font-size:13px; color:var(--text); }
</style>

<a href="<?= base_url('admin/users') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:12px;"><i data-lucide="arrow-left" style="width:13px;height:13px;"></i> Back to Users</a>

<div id="err" style="display:none;padding:16px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;color:var(--red);margin-bottom:14px;"></div>

<div class="card" style="margin-bottom:14px;">
  <div class="profile-hero">
    <div class="profile-ava" id="hero-ava">?</div>
    <div style="flex:1;">
      <h2 id="hero-name" style="margin:0 0 4px;font-size:20px;">Loading...</h2>
      <div class="profile-meta">
        <span><i data-lucide="mail" style="width:13px;height:13px;"></i> <span id="hero-email">—</span></span>
        <span><i data-lucide="phone" style="width:13px;height:13px;"></i> <span id="hero-phone">—</span></span>
        <span><i data-lucide="hash" style="width:13px;height:13px;"></i> <span id="hero-code" class="mono">—</span></span>
        <span><i data-lucide="calendar" style="width:13px;height:13px;"></i> Joined <span id="hero-joined">—</span></span>
        <span id="hero-status"></span>
      </div>
    </div>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon amber"><i data-lucide="coins"></i></div></div><div class="stat-val" id="k-balance">0</div><div class="stat-lbl">Litties Balance</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="footprints"></i></div></div><div class="stat-val" id="k-steps">0</div><div class="stat-lbl">Total Steps</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="route"></i></div></div><div class="stat-val" id="k-km">0</div><div class="stat-lbl">Total km</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon red"><i data-lucide="flame"></i></div></div><div class="stat-val" id="k-kcal">0</div><div class="stat-lbl">Calories</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="share-2"></i></div></div><div class="stat-val" id="k-refs">0</div><div class="stat-lbl">Referrals Made</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon blue"><i data-lucide="calendar-check"></i></div></div><div class="stat-val" id="k-streak">0</div><div class="stat-lbl">Claim Streak (d)</div></div>
</div>

<div class="g2">
  <div class="card"><div class="card-head"><div><h3>Steps Trend</h3><div class="sub">Last 30 days</div></div></div><div class="card-body"><canvas id="stepsChart" height="140"></canvas></div></div>
  <div class="card"><div class="card-head"><div><h3>Litties Trend</h3><div class="sub">Earned vs spent (30d)</div></div></div><div class="card-body"><canvas id="littiesChart" height="140"></canvas></div></div>
</div>

<div class="g2">
  <div class="card"><div class="card-head"><div><h3>Cause Contribution</h3><div class="sub">Steps per cause</div></div></div><div class="card-body"><canvas id="causeChart" height="160"></canvas></div></div>
  <div class="card"><div class="card-head"><div><h3>Peak Activity Hours</h3><div class="sub">When this user walks</div></div></div><div class="card-body"><canvas id="peakChart" height="160"></canvas></div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Profile & Goals</h3></div></div>
    <div class="card-body"><div class="detail-grid" id="profile-details">—</div></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Referred Users</h3><div class="sub">People brought in via this user</div></div></div>
    <div style="overflow-x:auto;">
      <table class="tbl">
        <thead><tr><th>User</th><th>Email</th><th>Joined</th><th>Status</th></tr></thead>
        <tbody id="referred-body"><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:14px;">
  <div class="card-head"><div><h3>Recent Transactions</h3><div class="sub">Latest 20 Litties movements</div></div></div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>ID</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
      <tbody id="tx-body"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>Walking Locations</h3><div class="sub">Last 30 days GPS clusters</div></div></div>
  <div id="user-map" style="height:320px;border-radius:0 0 14px 14px;"></div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
var PALETTE=['#6C5CE7','#14B8A6','#22C55E','#EC4899','#F59E0B','#0EA5E9','#EF4444','#A855F7'];
var DETAIL_URL = '<?= base_url('admin/user-detail') ?>';
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function fmtNum(n){ return (Number(n)||0).toLocaleString(); }
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10))%PALETTE.length]; }
function fmtDate(s){ if(!s) return '—'; var d=new Date(String(s).replace(' ','T')); if(isNaN(d)) return s; return d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }
function fmtDateTime(s){ if(!s) return '—'; var d=new Date(String(s).replace(' ','T')); if(isNaN(d)) return s; return d.toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); }
function lastNDates(n){ var out=[]; var d=new Date(); for(var i=n-1;i>=0;i--){ var x=new Date(d); x.setDate(d.getDate()-i); out.push(x.toISOString().slice(0,10)); } return out; }
function seriesByDate(rows, days, key){ var m={}; (rows||[]).forEach(function(r){ m[r.d]=Number(r[key])||0; }); return days.map(function(d){ return m[d]||0; }); }

var params = new URLSearchParams(location.search);
var userId = params.get('id');

if (!userId) { showErr('Missing user id. Open this page with ?id=<user_id>.'); }
else {
  fetch(window.API_BASE+'admin-user-detail?id='+encodeURIComponent(userId), {headers:{'Accept':'application/json'}})
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res || res.status !== true) { showErr(res && res.message || 'Failed to load'); return; }
      render(res.data);
    })
    .catch(function(){ showErr('Network error'); });
}

function showErr(m){ var el=document.getElementById('err'); el.textContent=m; el.style.display='block'; }

function render(d){
  var p=d.profile||{}, k=d.kpi||{}, g=d.goals||{};
  document.title = 'WERN Admin - '+(p.full_name||'User');

  document.getElementById('hero-ava').textContent      = initials(p.full_name);
  document.getElementById('hero-ava').style.background = colorFor(p.id);
  document.getElementById('hero-name').textContent     = p.full_name || '—';
  document.getElementById('hero-email').textContent    = p.email || '—';
  document.getElementById('hero-phone').textContent    = ((p.country_code||'')+' '+(p.phone_number||'')).trim() || '—';
  document.getElementById('hero-code').textContent     = p.referal_code || '—';
  document.getElementById('hero-joined').textContent   = fmtDate(p.create_on);
  document.getElementById('hero-status').innerHTML     = Number(p.status)===1 ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>';

  document.getElementById('k-balance').textContent = fmtNum(k.balance);
  document.getElementById('k-steps').textContent   = fmtNum(k.total_steps);
  document.getElementById('k-km').textContent      = (k.total_km||0);
  document.getElementById('k-kcal').textContent    = fmtNum(Math.round(k.total_kcal||0));
  document.getElementById('k-refs').textContent    = fmtNum(k.referrals_made);
  document.getElementById('k-streak').textContent  = fmtNum(k.streak_day);

  var d30 = lastNDates(30);
  new Chart(document.getElementById('stepsChart'), {
    type:'line',
    data:{ labels:d30.map(function(s){return s.slice(5);}), datasets:[{label:'Steps',data:seriesByDate(d.steps_trend,d30,'steps'),borderColor:'#003B4C',backgroundColor:'rgba(0,59,76,.08)',tension:.3,fill:true,pointRadius:2}]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{ticks:{maxTicksLimit:10}}} }
  });
  new Chart(document.getElementById('littiesChart'), {
    type:'line',
    data:{ labels:d30.map(function(s){return s.slice(5);}), datasets:[
      {label:'Earned',data:seriesByDate(d.litties_trend,d30,'earned'),borderColor:'#22C55E',backgroundColor:'rgba(34,197,94,.08)',tension:.3,fill:true,pointRadius:2},
      {label:'Spent', data:seriesByDate(d.litties_trend,d30,'spent'), borderColor:'#EF4444',backgroundColor:'rgba(239,68,68,.08)',tension:.3,fill:true,pointRadius:2}
    ]},
    options:{ responsive:true, maintainAspectRatio:false, scales:{x:{ticks:{maxTicksLimit:10}}} }
  });

  var cb = d.cause_breakdown||[];
  new Chart(document.getElementById('causeChart'), {
    type:'doughnut',
    data:{ labels:cb.map(function(c){return c.name;}), datasets:[{data:cb.map(function(c){return Number(c.steps)||0;}),backgroundColor:cb.map(function(_,i){return PALETTE[i%PALETTE.length];}),borderWidth:0,spacing:2}]},
    options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}}} }
  });

  var hours=[]; for(var h=0;h<24;h++) hours.push(h);
  var hMap={}; (d.peak_hours||[]).forEach(function(r){ hMap[r.h]=Number(r.c)||0; });
  new Chart(document.getElementById('peakChart'), {
    type:'bar',
    data:{ labels:hours.map(function(h){return h+'h';}), datasets:[{label:'Events',data:hours.map(function(h){return hMap[h]||0;}),backgroundColor:'#0E7490'}]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  var refInfo = d.referrer ? (esc(d.referrer.full_name)+' <span class="mono" style="font-size:11px;color:var(--text-muted);">'+esc(d.referrer.referal_code||'')+'</span>') : '<span class="text-muted">—</span>';
  document.getElementById('profile-details').innerHTML =
      fieldHTML('Card Number', p.membership_card_number || '—')
    + fieldHTML('Card Points', fmtNum(p.card_points))
    + fieldHTML('Nickname', p.nickname || '—')
    + fieldHTML('Referred By', refInfo, true)
    + fieldHTML('Daily Goal', g.daily_step_goal ? fmtNum(g.daily_step_goal)+' steps' : '—')
    + fieldHTML('Weekly Goal', g.weekly_goal   ? fmtNum(g.weekly_goal)+' steps'   : '—')
    + fieldHTML('Activity Level', g.activity_level || '—')
    + fieldHTML('Events Logged', fmtNum(k.total_events))
    + fieldHTML('Total Earned', fmtNum(k.earned)+' Litties')
    + fieldHTML('Total Spent', fmtNum(k.spent)+' Litties')
    + fieldHTML('Daily Claims', fmtNum(k.claim_count)+(k.last_claim?(' · last '+fmtDate(k.last_claim)):''))
    + fieldHTML('User ID', '#'+p.id);

  var ru = d.referred_users||[];
  var rb = document.getElementById('referred-body');
  if (!ru.length) rb.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">No referrals yet.</td></tr>';
  else rb.innerHTML = ru.map(function(r){
    var st = Number(r.status)===1 ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>';
    return '<tr><td><a href="'+DETAIL_URL+'?id='+r.id+'" style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none;"><div class="user-ava" style="background:'+colorFor(r.id)+';">'+initials(r.full_name)+'</div><span>'+esc(r.full_name||'—')+'</span></a></td><td>'+esc(r.email||'')+'</td><td>'+fmtDate(r.create_on)+'</td><td>'+st+'</td></tr>';
  }).join('');

  var tx = d.recent_tx||[];
  var tb = document.getElementById('tx-body');
  if (!tx.length) tb.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">No transactions.</td></tr>';
  else tb.innerHTML = tx.map(function(t){
    var isEarn = String(t.type)==='1';
    var amt = (isEarn?'+':'-') + Number(t.points||0);
    var cls = isEarn ? 'text-green' : 'text-red';
    return '<tr><td class="text-muted mono fs-sm">#'+t.id+'</td><td>'+esc(t.category_name||'—')+'</td><td class="text-muted" style="max-width:280px;">'+esc(t.description||'—')+'</td><td class="'+cls+' fw-600">'+amt+'</td><td>'+fmtDateTime(t.date)+'</td></tr>';
  }).join('');

  var mapEl=document.getElementById('user-map');
  if (mapEl && window.L) {
    mapEl.innerHTML='';
    var clusters = d.gps_clusters||[];
    var center = clusters.length ? [Number(clusters[0].lat), Number(clusters[0].lng)] : [20, 30];
    var map = L.map('user-map').setView(center, clusters.length ? 10 : 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap', maxZoom:18 }).addTo(map);
    var maxE = Math.max(1, ...clusters.map(function(c){return Number(c.events)||0;}));
    clusters.forEach(function(g){
      var r = 5 + Math.round((Number(g.events)||0)*12/maxE);
      L.circleMarker([Number(g.lat), Number(g.lng)], { radius:r, color:'#22C55E', weight:2, fillColor:'#22C55E', fillOpacity:.5 })
        .bindPopup('<strong>'+fmtNum(g.events)+' events</strong><br>'+fmtNum(g.steps)+' steps')
        .addTo(map);
    });
    if (!clusters.length) {
      L.popup().setLatLng([20,30]).setContent('No location data for this user.').openOn(map);
    }
  }

  if (window.lucide) lucide.createIcons();
}

function fieldHTML(label, val, html){
  return '<div class="detail-item"><label>'+esc(label)+'</label><span>'+(html ? val : esc(val))+'</span></div>';
}
</script>

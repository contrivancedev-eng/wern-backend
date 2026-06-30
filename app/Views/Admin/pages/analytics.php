<?php
$pageTitle = 'Analytics';
$active    = 'analytics';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .cohort-row { display: flex; gap: 3px; margin-bottom: 3px; align-items: center; }
  .cohort-label { width: 90px; font-size: 10px; font-weight: 600; color: var(--text-muted); text-align: right; padding-right: 6px; }
  .cohort-cell { flex: 1; height: 26px; border-radius: 3px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 600; color: #fff; }
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width: 960px) { .g2 { grid-template-columns:1fr; } }
</style>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="users"></i></div></div><div class="stat-val" id="k-users">0</div><div class="stat-lbl">Users</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="activity"></i></div></div><div class="stat-val" id="k-dau">0%</div><div class="stat-lbl">DAU Rate</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="footprints"></i></div></div><div class="stat-val" id="k-avg-steps">0</div><div class="stat-lbl">Avg Steps/User</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon amber"><i data-lucide="trending-up"></i></div></div><div class="stat-val" id="k-total-steps">0</div><div class="stat-lbl">Total Steps</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon blue"><i data-lucide="repeat"></i></div></div><div class="stat-val" id="k-ret7">0%</div><div class="stat-lbl">Retention (7d)</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon red"><i data-lucide="target"></i></div></div><div class="stat-val" id="k-goal">0%</div><div class="stat-lbl">Goal Hit Rate</div></div>
</div>

<div class="g2">
  <div class="card"><div class="card-head"><div><h3>Daily Active Users</h3><div class="sub">Last 30 days (distinct walkers)</div></div></div><div class="card-body"><canvas id="dauChart" height="160"></canvas></div></div>
  <div class="card"><div class="card-head"><div><h3>Step Distribution</h3><div class="sub">Users by total steps (today)</div></div></div><div class="card-body"><canvas id="distChart" height="160"></canvas></div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Cohort Retention</h3><div class="sub">Weekly signup cohorts → % active in following weeks</div></div></div>
    <div class="card-body" style="padding:14px 18px;">
      <div id="cohort-grid">Loading...</div>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Activity Level Split</h3><div class="sub">From user goals</div></div></div>
    <div class="card-body"><canvas id="levelChart" height="200"></canvas></div>
  </div>
</div>

<div class="card" style="margin-bottom:14px;">
  <div class="card-head"><div><h3>User Growth</h3><div class="sub">Monthly signups (organic vs referral)</div></div></div>
  <div class="card-body"><canvas id="growthChart" height="120"></canvas></div>
</div>

<div class="card" style="margin-bottom:14px;">
  <div class="card-head">
    <div><h3>Top 10 Walkers</h3><div class="sub" id="tw-range-label">Ranked by total steps (all-time)</div></div>
  </div>
  <div style="padding:12px 20px 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <label style="font-size:12px;color:var(--text-muted);">From</label>
    <input id="tw-from" type="date" class="fi" style="width:150px;">
    <label style="font-size:12px;color:var(--text-muted);">To</label>
    <input id="tw-to" type="date" class="fi" style="width:150px;">
    <button class="btn btn-pri btn-sm" onclick="applyTwDate()"><i data-lucide="search" style="width:13px;height:13px;"></i> Apply</button>
    <button class="btn btn-sec btn-sm" onclick="resetTwDate()"><i data-lucide="x" style="width:13px;height:13px;"></i> Reset</button>
    <button class="btn btn-ghost btn-sm" onclick="setTwQuick('today')">Today</button>
    <button class="btn btn-ghost btn-sm" onclick="setTwQuick('7d')">Last 7d</button>
    <button class="btn btn-ghost btn-sm" onclick="setTwQuick('30d')">Last 30d</button>
  </div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th style="width:50px;">#</th><th>User</th><th>Steps</th><th>Distance</th><th>Calories</th><th>Events</th></tr></thead>
      <tbody id="top-walkers-body"><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>Key Metrics</h3><div class="sub">Platform-wide performance</div></div></div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>Metric</th><th>Today</th><th>This Week</th><th>This Month</th><th>All Time</th></tr></thead>
      <tbody id="metrics-body"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
function fmtNum(n){ n=Number(n)||0; return n.toLocaleString(); }
function lastNDates(n){ var out=[]; var d=new Date(); for(var i=n-1;i>=0;i--){ var x=new Date(d); x.setDate(d.getDate()-i); out.push(x.toISOString().slice(0,10)); } return out; }
function seriesByDate(rows, days, key){ var map={}; rows.forEach(function(r){ map[r.d]=Number(r[key])||0; }); return days.map(function(d){ return map[d] || 0; }); }
function lastNMonths(n){ var out=[]; var d=new Date(); d.setDate(1); for(var i=n-1;i>=0;i--){ var x=new Date(d); x.setMonth(d.getMonth()-i); out.push(x.toISOString().slice(0,7)); } return out; }

fetch(window.API_BASE+'admin-analytics-stats',{headers:{'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(res){ if(!res || res.status!==true) throw new Error('load failed'); renderAll(res.data); })
  .catch(function(e){ console.error(e); });

function renderAll(d){
  var k=d.kpi||{};
  document.getElementById('k-users').textContent       = fmtNum(k.total_users);
  document.getElementById('k-dau').textContent         = (k.dau_rate||0)+'%';
  document.getElementById('k-avg-steps').textContent   = fmtNum(k.avg_steps_user);
  document.getElementById('k-total-steps').textContent = fmtNum(k.total_steps);
  document.getElementById('k-ret7').textContent        = (k.retention_7d||0)+'%';
  document.getElementById('k-goal').textContent        = (k.goal_hit_rate||0)+'%';

  // DAU (30 days)
  var d30 = lastNDates(30);
  new Chart(document.getElementById('dauChart'), {
    type:'line',
    data:{ labels:d30.map(function(s){return s.slice(5);}), datasets:[{label:'DAU',data:seriesByDate(d.dau_30d||[],d30,'c'),borderColor:'#003B4C',backgroundColor:'rgba(0,59,76,.08)',tension:.3,fill:true,pointRadius:2}] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{ticks:{maxTicksLimit:10}}} }
  });

  // Step distribution
  var order = ['0-2K','2-5K','5-10K','10-20K','20-30K','30K+'];
  var dMap = {}; (d.step_dist||[]).forEach(function(r){ dMap[r.bucket]=Number(r.c)||0; });
  new Chart(document.getElementById('distChart'), {
    type:'bar',
    data:{ labels:order, datasets:[{label:'Users',data:order.map(function(b){return dMap[b]||0;}),backgroundColor:['rgba(0,59,76,.15)','rgba(0,59,76,.25)','rgba(0,59,76,.45)','rgba(0,59,76,.65)','rgba(0,59,76,.8)','#003B4C'],borderRadius:4,borderSkipped:false}] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  // Activity level
  var lvls = d.activity_lvl||[];
  new Chart(document.getElementById('levelChart'), {
    type:'doughnut',
    data:{ labels:lvls.map(function(x){return x.lvl;}), datasets:[{data:lvls.map(function(x){return Number(x.c)||0;}),backgroundColor:['#0E7490','#003B4C','#22C55E'],borderWidth:0,spacing:2}]},
    options:{ responsive:true, maintainAspectRatio:false, cutout:'68%', plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}}} }
  });

  // User growth (6 months)
  var months = lastNMonths(6);
  var gMap = {}; (d.user_growth||[]).forEach(function(r){ gMap[r.ym]={organic:Number(r.organic)||0,referral:Number(r.referral)||0}; });
  new Chart(document.getElementById('growthChart'), {
    type:'bar',
    data:{ labels:months, datasets:[
      {label:'Organic', data:months.map(function(m){return (gMap[m]&&gMap[m].organic)||0;}), backgroundColor:'#003B4C', borderRadius:4, borderSkipped:false},
      {label:'Referral',data:months.map(function(m){return (gMap[m]&&gMap[m].referral)||0;}), backgroundColor:'#22C55E', borderRadius:4, borderSkipped:false}
    ]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}}}, scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}} }
  });

  // Cohort grid
  renderCohort(d.cohort||[]);

  // Key metrics table
  renderMetrics(d.metrics||{});

  // Top 10 walkers — initial load (all-time)
  loadTopWalkers();
}

function loadTopWalkers(){
  var from = document.getElementById('tw-from').value;
  var to   = document.getElementById('tw-to').value;
  var qs   = [];
  if (from) qs.push('from='+encodeURIComponent(from));
  if (to)   qs.push('to='+encodeURIComponent(to));
  var url  = window.API_BASE+'admin-analytics-top-walkers' + (qs.length ? '?'+qs.join('&') : '');
  document.getElementById('top-walkers-body').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr>';
  fetch(url, {headers:{'Accept':'application/json'}})
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res || res.status !== true) throw 0;
      var d = res.data || {};
      document.getElementById('tw-range-label').textContent = 'Ranked by total steps · ' + (d.range && d.range.label || 'All time');
      renderTopWalkers(d.top_walkers || []);
    })
    .catch(function(){
      document.getElementById('top-walkers-body').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--red);">Failed to load</td></tr>';
    });
}

function applyTwDate(){ loadTopWalkers(); }
function resetTwDate(){ document.getElementById('tw-from').value=''; document.getElementById('tw-to').value=''; loadTopWalkers(); }
function setTwQuick(range){
  var today = new Date();
  var iso = function(d){ return d.toISOString().slice(0,10); };
  var from, to = iso(today);
  if (range === 'today') { from = to; }
  else if (range === '7d') { var d = new Date(today); d.setDate(d.getDate()-6); from = iso(d); }
  else if (range === '30d'){ var d = new Date(today); d.setDate(d.getDate()-29); from = iso(d); }
  document.getElementById('tw-from').value = from;
  document.getElementById('tw-to').value   = to;
  loadTopWalkers();
}

var PALETTE=['#6C5CE7','#14B8A6','#22C55E','#EC4899','#F59E0B','#0EA5E9','#EF4444','#A855F7'];
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10))%PALETTE.length]; }
function medalColor(i){ return i===0?'var(--amber)':i===1?'var(--text-muted)':i===2?'#CD7F32':'var(--text-muted)'; }

function renderTopWalkers(rows){
  var tb=document.getElementById('top-walkers-body');
  if(!rows.length){ tb.innerHTML='<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">No walking activity yet.</td></tr>'; return; }
  tb.innerHTML = rows.map(function(w,i){
    var km   = (Number(w.km)||0).toFixed(1);
    var kcal = Math.round(Number(w.kcal)||0);
    return '<tr>'
      + '<td><div style="display:flex;align-items:center;gap:6px;font-weight:800;color:'+medalColor(i)+';"><i data-lucide="'+(i<3?'medal':'hash')+'" style="width:14px;height:14px;"></i>'+(i+1)+'</div></td>'
      + '<td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(w.id)+';">'+initials(w.full_name)+'</div><div><div class="user-name">'+esc(w.full_name||'—')+'</div><div class="user-email">'+esc(w.email||'')+'</div></div></div></td>'
      + '<td><strong>'+fmtNum(w.steps)+'</strong></td>'
      + '<td>'+km+' km</td>'
      + '<td>'+fmtNum(kcal)+' kcal</td>'
      + '<td>'+fmtNum(w.events)+'</td>'
      + '</tr>';
  }).join('');
  if(window.lucide) lucide.createIcons();
}

function renderCohort(rows){
  var box=document.getElementById('cohort-grid');
  if(!rows.length){ box.innerHTML='<div style="color:var(--text-muted);padding:12px;text-align:center;">Not enough cohort data yet.</div>'; return; }
  var html = '<div style="display:flex;gap:3px;margin-bottom:6px;align-items:center;"><div style="width:90px;"></div>';
  for(var w=0;w<=6;w++) html += '<div style="flex:1;text-align:center;font-size:9px;font-weight:600;color:var(--text-muted);">W'+w+'</div>';
  html += '</div>';
  rows.forEach(function(r){
    var size = Number(r.cohort_size)||0;
    html += '<div class="cohort-row"><div class="cohort-label">'+esc(r.cohort_start)+' ('+size+')</div>';
    for(var w=0;w<=6;w++){
      var n = Number(r['w'+w])||0;
      var pct = size ? Math.round(n*100/size) : 0;
      var alpha = w===0 ? 1 : Math.max(0.15, pct/100);
      var txt = pct>0 ? pct+'%' : '';
      html += '<div class="cohort-cell" style="background:rgba(0,59,76,'+alpha.toFixed(2)+');'+(pct<15?'color:var(--text-sec);':'')+'">'+txt+'</div>';
    }
    html += '</div>';
  });
  box.innerHTML = html;
}

function renderMetrics(m){
  var labels = {
    total_steps:'Total Steps', distance_km:'Distance (km)', calories:'Calories',
    litties:'Litties Distributed', daily_claims:'Daily Claims',
    signups:'New Signups', referral_signups:'Referral Signups'
  };
  var tb=document.getElementById('metrics-body');
  var rows = Object.keys(labels).map(function(key){
    var x = m[key] || {today:0,week:0,month:0,all:0};
    return '<tr><td><strong>'+labels[key]+'</strong></td><td>'+fmtNum(x.today)+'</td><td>'+fmtNum(x.week)+'</td><td>'+fmtNum(x.month)+'</td><td>'+fmtNum(x.all)+'</td></tr>';
  });
  tb.innerHTML = rows.join('');
}

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
</script>

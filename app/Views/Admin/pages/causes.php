<?php
$pageTitle = 'Causes';
$active    = 'causes';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:960px) { .g2 { grid-template-columns:1fr; } }
  .cause-card { padding:16px; border-radius:var(--radius); background:var(--surface); border:1px solid var(--border); }
  .progress { height:6px; background:var(--bg); border-radius:3px; overflow:hidden; margin-top:4px; }
  .progress-fill { height:100%; border-radius:3px; transition:width .3s; }
  .progress-meta { display:flex; justify-content:space-between; font-size:11px; color:var(--text-muted); margin-top:6px; }
</style>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="heart"></i></div></div><div class="stat-val" id="k-active">0</div><div class="stat-lbl">Active Causes</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="footprints"></i></div></div><div class="stat-val" id="k-steps">0</div><div class="stat-lbl">Total Cause Steps</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="users"></i></div></div><div class="stat-val" id="k-contrib">0</div><div class="stat-lbl">Contributors</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon blue"><i data-lucide="droplets"></i></div></div><div class="stat-val" id="k-litres">0</div><div class="stat-lbl">Litres (est.)</div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Distribution</h3><div class="sub">Steps per cause</div></div></div>
    <div class="card-body"><canvas id="causesChart" height="220"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Actions</h3></div></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:8px;">
        <button class="btn btn-sec" style="justify-content:flex-start;" onclick="openAddCause()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add New Cause</button>
        <button class="btn btn-sec" style="justify-content:flex-start;" onclick="loadCauses()"><i data-lucide="refresh-cw" style="width:15px;height:15px;"></i> Refresh</button>
        <button class="btn btn-sec" style="justify-content:flex-start;" onclick="exportCauses()"><i data-lucide="download" style="width:15px;height:15px;"></i> Export Report</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>All Causes</h3></div>
  <div class="card-body">
    <div id="causes-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
      <div style="color:var(--text-muted);padding:20px;">Loading...</div>
    </div>
  </div>
</div>

<div class="modal-bg" id="cause-modal"><div class="modal">
  <div class="modal-head"><h3 id="cause-modal-title">Add New Cause</h3><button class="modal-close" onclick="closeCauseModal()"><i data-lucide="x" style="width:16px;height:16px;"></i></button></div>
  <div class="modal-body">
    <div class="fg"><label class="fl">Cause Name</label><input id="cause-name" type="text" class="fi" placeholder="e.g., Ocean Cleanup"></div>
    <div id="cause-form-msg" style="font-size:12px;margin-top:6px;"></div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-sec btn-sm" onclick="closeCauseModal()">Cancel</button>
    <button class="btn btn-pri btn-sm" id="cause-save-btn" onclick="saveCause()">Save</button>
  </div>
</div></div>

<?php echo view('Admin/includes/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
var PALETTE=['#22C55E','#0EA5E9','#F59E0B','#EC4899','#6C5CE7','#EF4444','#0D9488','#003B4C'];
var ICONS =['tree-pine','droplets','wheat','sparkles','baby','heart','sun','leaf'];
var state = { causes:[], editingId:0, chart:null };

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function fmtNum(n){ return (Number(n)||0).toLocaleString(); }

function loadCauses(){
  fetch(window.API_BASE+'admin-causes-stats',{headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(res){ if(!res||res.status!==true) throw 0; renderAll(res.data); })
    .catch(function(){ document.getElementById('causes-grid').innerHTML='<div style="color:var(--red);padding:20px;">Failed to load</div>'; });
}

function renderAll(d){
  var k=d.kpi||{};
  document.getElementById('k-active').textContent = fmtNum(k.active_causes);
  document.getElementById('k-steps').textContent  = fmtNum(k.total_steps);
  document.getElementById('k-contrib').textContent= fmtNum(k.contributors);
  document.getElementById('k-litres').textContent = fmtNum(k.litres_water);

  var causes = d.causes||[];
  state.causes = causes;

  // Chart
  if (state.chart) { state.chart.destroy(); }
  state.chart = new Chart(document.getElementById('causesChart'), {
    type:'doughnut',
    data:{ labels:causes.map(function(c){return c.name;}), datasets:[{data:causes.map(function(c){return Number(c.steps)||0;}),backgroundColor:causes.map(function(_,i){return PALETTE[i%PALETTE.length];}),borderWidth:0,spacing:2}]},
    options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}}} }
  });

  // Cards grid
  var maxSteps = 1;
  causes.forEach(function(c){ var n=Number(c.steps)||0; if(n>maxSteps) maxSteps=n; });
  var goal = Math.max(1000, Math.ceil(maxSteps * 1.25 / 1000) * 1000); // round goal up

  var grid=document.getElementById('causes-grid');
  if(!causes.length){ grid.innerHTML='<div style="color:var(--text-muted);padding:20px;text-align:center;grid-column:1/-1;">No causes defined yet.</div>'; return; }
  grid.innerHTML = causes.map(function(c,i){
    var color = PALETTE[i%PALETTE.length];
    var icon  = ICONS[i%ICONS.length];
    var steps = Number(c.steps)||0;
    var pct   = Math.min(100, Math.round(steps*100/goal));
    return '<div class="cause-card" style="border-left:3px solid '+color+';">'
      + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">'
      + '<div style="display:flex;align-items:center;gap:10px;"><div class="stat-icon" style="width:34px;height:34px;background:'+color+'20;color:'+color+';"><i data-lucide="'+icon+'" style="width:16px;height:16px;"></i></div><strong>'+esc(c.name)+'</strong></div>'
      + '<div style="display:flex;gap:4px;">'
      + '<button class="btn btn-ghost btn-icon btn-sm" title="Edit" onclick="openEditCause('+c.id+')"><i data-lucide="pencil" style="width:14px;height:14px;"></i></button>'
      + '<button class="btn btn-ghost btn-icon btn-sm" title="Delete" onclick="deleteCause('+c.id+')"><i data-lucide="trash-2" style="width:14px;height:14px;color:var(--red);"></i></button>'
      + '</div>'
      + '</div>'
      + '<div class="progress"><div class="progress-fill" style="width:'+pct+'%;background:'+color+';"></div></div>'
      + '<div class="progress-meta"><span>'+fmtNum(steps)+' / '+fmtNum(goal)+'</span><span>'+pct+'%</span></div>'
      + '<div style="display:flex;gap:14px;margin-top:10px;font-size:11.5px;color:var(--text-muted);">'
      + '<span><strong style="color:var(--text);">'+fmtNum(c.walkers)+'</strong> walkers</span>'
      + '<span><strong style="color:var(--text);">'+fmtNum(c.events)+'</strong> events</span>'
      + '</div>'
      + '</div>';
  }).join('');
  if(window.lucide) lucide.createIcons();
}

function openAddCause(){ state.editingId=0; document.getElementById('cause-modal-title').textContent='Add New Cause'; document.getElementById('cause-name').value=''; document.getElementById('cause-form-msg').textContent=''; document.getElementById('cause-modal').classList.add('show'); }
function openEditCause(id){ var c=state.causes.find(function(x){return String(x.id)===String(id);}); if(!c) return; state.editingId=id; document.getElementById('cause-modal-title').textContent='Edit Cause'; document.getElementById('cause-name').value=c.name||''; document.getElementById('cause-form-msg').textContent=''; document.getElementById('cause-modal').classList.add('show'); }
function closeCauseModal(){ document.getElementById('cause-modal').classList.remove('show'); }
function saveCause(){
  var name=document.getElementById('cause-name').value.trim();
  var msg=document.getElementById('cause-form-msg');
  if(!name){ msg.style.color='var(--red)'; msg.textContent='Name is required.'; return; }
  var btn=document.getElementById('cause-save-btn'); btn.disabled=true;
  fetch(window.API_BASE+'admin-causes-save',{
    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify({id:state.editingId, name:name})
  }).then(function(r){return r.json();}).then(function(res){
    btn.disabled=false;
    if(res && res.status===true){ closeCauseModal(); loadCauses(); }
    else { msg.style.color='var(--red)'; msg.textContent=(res&&res.message)||'Save failed'; }
  }).catch(function(){ btn.disabled=false; msg.style.color='var(--red)'; msg.textContent='Network error'; });
}
function deleteCause(id){
  if(!confirm('Delete this cause? Step events referencing it will remain.')) return;
  fetch(window.API_BASE+'admin-causes-delete',{
    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
    body:JSON.stringify({id:id})
  }).then(function(r){return r.json();}).then(function(res){
    if(res && res.status===true) loadCauses();
    else alert((res&&res.message)||'Delete failed');
  });
}

function exportCauses(){
  var rows=[['id','name','steps','walkers','events']];
  state.causes.forEach(function(c){ rows.push([c.id,c.name,c.steps,c.walkers,c.events]); });
  var csv=rows.map(function(r){return r.map(function(x){return '"'+String(x==null?'':x).replace(/"/g,'""')+'"';}).join(',');}).join('\n');
  var a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download='causes.csv'; a.click();
}

loadCauses();
</script>

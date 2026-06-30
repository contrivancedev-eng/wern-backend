<?php
$pageTitle = 'Transactions';
$active    = 'transactions';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon amber"><i data-lucide="coins"></i></div><span class="stat-badge" id="k-net">0</span></div><div class="stat-val" id="k-earned">0</div><div class="stat-lbl">Total Litties Earned</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="footprints"></i></div></div><div class="stat-val" id="k-steps">0</div><div class="stat-lbl">Step Rewards</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="gift"></i></div></div><div class="stat-val" id="k-claim">0</div><div class="stat-lbl">Daily Claims</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="user-plus"></i></div></div><div class="stat-val" id="k-signup">0</div><div class="stat-lbl">Signup Rewards</div></div>
</div>

<div class="card" style="margin-bottom:14px;">
  <div class="card-head"><div><h3>Reward Distribution</h3><div class="sub">Litties earned by category</div></div></div>
  <div class="card-body"><canvas id="txChart" height="140"></canvas></div>
</div>

<div class="card">
  <div class="card-head">
    <div><h3>All Transactions</h3><div class="sub">Latest 200</div></div>
    <button class="btn btn-sec btn-sm" onclick="exportTx()"><i data-lucide="download" style="width:13px;height:13px;"></i> Export</button>
  </div>
  <div style="padding:14px 20px 0;">
    <div class="toolbar">
      <div class="toolbar-l"><div class="filter-grp" id="tx-filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="3">Steps</button>
        <button class="filter-btn" data-filter="2">Claim</button>
        <button class="filter-btn" data-filter="1">Signup</button>
        <button class="filter-btn" data-filter="deduct">Deduct</button>
      </div></div>
      <div class="toolbar-r"><div class="search-box"><i data-lucide="search" style="width:13px;height:13px;color:var(--text-muted);"></i><input id="tx-search" type="text" class="tbl-search" placeholder="Search..."></div></div>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>ID</th><th>User</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
      <tbody id="tx-body"><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
  <div class="paging">
    <div class="paging-info" id="tx-info">—</div>
    <div class="paging-btns" id="tx-pager"></div>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
var PALETTE=['#003B4C','#22C55E','#F59E0B','#EF4444','#0E7490','#6C5CE7'];
var state = { list: [], filter:'all', search:'', page:1, pageSize:15 };

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function fmtNum(n){ return (Number(n)||0).toLocaleString(); }
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10))%PALETTE.length]; }
function fmtDate(s){ if(!s) return '—'; var d=new Date(s.replace(' ','T')); if(isNaN(d)) return s; return d.toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
function badgeFor(catId, type){
  if(String(type)==='2') return '<span class="badge banned">Deduct</span>';
  var map = {'1':['Signup','var(--primary-soft)','var(--primary)'],'2':['Claim','var(--amber-soft)','var(--amber)'],'3':['Steps','var(--teal-soft)','var(--teal)']};
  var x = map[String(catId)] || ['Other','var(--bg)','var(--text-sec)'];
  return '<span class="badge" style="background:'+x[1]+';color:'+x[2]+';">'+x[0]+'</span>';
}

fetch(window.API_BASE+'admin-transactions-stats',{headers:{'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(res){ if(!res||res.status!==true) throw 0; renderAll(res.data); })
  .catch(function(){ document.getElementById('tx-body').innerHTML='<tr><td colspan="6" style="text-align:center;color:var(--red);padding:20px;">Failed to load</td></tr>'; });

function renderAll(d){
  var k=d.kpi||{};
  var earned = Number(k.total_earned)||0;
  var spent  = Number(k.total_spent)||0;
  document.getElementById('k-earned').textContent = fmtNum(earned);
  document.getElementById('k-net').textContent    = fmtNum(k.net_circ)+' net';

  var byCat = {}; (k.by_category||[]).forEach(function(r){ byCat[String(r.id)] = Number(r.total)||0; });
  document.getElementById('k-steps').textContent  = fmtNum(byCat['3']||0);
  document.getElementById('k-claim').textContent  = fmtNum(byCat['2']||0);
  document.getElementById('k-signup').textContent = fmtNum(byCat['1']||0);

  var cats = k.by_category || [];
  new Chart(document.getElementById('txChart'), {
    type:'bar',
    data:{ labels:cats.map(function(c){return c.name;}), datasets:[{label:'Litties',data:cats.map(function(c){return Number(c.total)||0;}),backgroundColor:cats.map(function(_,i){return PALETTE[i%PALETTE.length];}),borderRadius:4,borderSkipped:false}]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  state.list = d.list || [];
  renderTable();
}

function filteredRows(){
  return state.list.filter(function(r){
    if(state.filter==='deduct'){ if(String(r.type)!=='2') return false; }
    else if(state.filter!=='all'){
      if(String(r.earn_category_id)!==state.filter || String(r.type)!=='1') return false;
    }
    if(state.search){
      var q=state.search.toLowerCase();
      return (r.full_name||'').toLowerCase().indexOf(q)>-1
          || (r.email||'').toLowerCase().indexOf(q)>-1
          || (r.description||'').toLowerCase().indexOf(q)>-1;
    }
    return true;
  });
}

function renderTable(){
  var all = filteredRows();
  var total = all.length;
  var pages = Math.max(1, Math.ceil(total/state.pageSize));
  if(state.page>pages) state.page=pages;
  if(state.page<1) state.page=1;
  var start=(state.page-1)*state.pageSize;
  var rows = all.slice(start, start+state.pageSize);

  var tbody=document.getElementById('tx-body');
  if(!rows.length){
    tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No transactions</td></tr>';
    document.getElementById('tx-info').textContent='0';
    document.getElementById('tx-pager').innerHTML='';
    return;
  }

  tbody.innerHTML = rows.map(function(t){
    var isEarn = String(t.type)==='1';
    var amount = (isEarn?'+':'-') + Number(t.points||0);
    var cls = isEarn ? 'text-green' : 'text-red';
    return '<tr>'
      + '<td class="text-muted mono fs-sm">#'+t.id+'</td>'
      + '<td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(t.user_id)+';width:28px;height:28px;font-size:9px;">'+initials(t.full_name)+'</div><div><div class="user-name">'+esc(t.full_name||'—')+'</div><div class="user-email">'+esc(t.email||'')+'</div></div></div></td>'
      + '<td>'+badgeFor(t.earn_category_id, t.type)+'</td>'
      + '<td class="text-muted" style="max-width:280px;">'+esc(t.description||'—')+'</td>'
      + '<td class="'+cls+' fw-600">'+amount+'</td>'
      + '<td>'+fmtDate(t.date)+'</td>'
      + '</tr>';
  }).join('');

  document.getElementById('tx-info').textContent='Showing '+(start+1)+'-'+(start+rows.length)+' of '+total;
  renderPager(pages);
  if(window.lucide) lucide.createIcons();
}

function renderPager(pages){
  var box=document.getElementById('tx-pager');
  var p=state.page;
  var parts=['<button class="pg-btn" '+(p===1?'disabled':'')+' onclick="txGoto('+(p-1)+')"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></button>'];
  var nums=[]; if(pages<=7){ for(var i=1;i<=pages;i++) nums.push(i); }
  else { nums.push(1); if(p>3) nums.push('...'); for(var j=Math.max(2,p-1); j<=Math.min(pages-1,p+1); j++) nums.push(j); if(p<pages-2) nums.push('...'); nums.push(pages); }
  nums.forEach(function(n){ if(n==='...') parts.push('<button class="pg-btn" disabled>...</button>'); else parts.push('<button class="pg-btn '+(n===p?'active':'')+'" onclick="txGoto('+n+')">'+n+'</button>'); });
  parts.push('<button class="pg-btn" '+(p===pages?'disabled':'')+' onclick="txGoto('+(p+1)+')"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></button>');
  box.innerHTML=parts.join('');
}
function txGoto(n){ state.page=n; renderTable(); }

document.querySelectorAll('#tx-filters .filter-btn').forEach(function(b){
  b.addEventListener('click', function(){
    document.querySelectorAll('#tx-filters .filter-btn').forEach(function(x){x.classList.remove('active');});
    b.classList.add('active');
    state.filter=b.dataset.filter; state.page=1; renderTable();
  });
});
document.getElementById('tx-search').addEventListener('input', function(e){ state.search=e.target.value; state.page=1; renderTable(); });

function exportTx(){
  var rows=[['id','user','email','category','type','description','amount','date']];
  state.list.forEach(function(t){ rows.push([t.id,t.full_name,t.email,t.category_name,t.type==='1'?'earn':'deduct',t.description,t.points,t.date]); });
  var csv=rows.map(function(r){ return r.map(function(c){return '"'+String(c==null?'':c).replace(/"/g,'""')+'"';}).join(','); }).join('\n');
  var a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download='transactions.csv'; a.click();
}
</script>

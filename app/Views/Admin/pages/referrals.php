<?php
$pageTitle = 'Referrals';
$active    = 'referrals';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:960px) { .g2 { grid-template-columns:1fr; } }
  .feed { list-style:none; margin:0; padding:0; }
  .feed-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
  .feed-item:last-child { border-bottom:none; }
</style>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon blue"><i data-lucide="link"></i></div><span class="stat-badge" id="k-new">0 new</span></div><div class="stat-val" id="k-total">0</div><div class="stat-lbl">Total Referrals</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="user-check"></i></div></div><div class="stat-val" id="k-conv">0%</div><div class="stat-lbl">Conversion Rate</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="award"></i></div></div><div class="stat-val" id="k-litties">0</div><div class="stat-lbl">Litties Earned</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="users"></i></div></div><div class="stat-val" id="k-active">0</div><div class="stat-lbl">Active Referrers</div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Top Referrers</h3><div class="sub">Ranked by signups brought in</div></div></div>
    <div class="card-body" style="padding:12px 20px;">
      <ul class="feed" id="top-list"><li style="color:var(--text-muted);padding:16px;text-align:center;">Loading...</li></ul>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Recent Referrals</h3><div class="sub">Latest signups via referral</div></div></div>
    <div style="overflow-x:auto;">
      <table class="tbl">
        <thead><tr><th>User</th><th>Referrer</th><th>Joined</th></tr></thead>
        <tbody id="recent-body"><tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div><h3>All Referral Codes</h3><div class="sub">Per-user referral code with usage stats</div></div>
    <button class="btn btn-sec btn-sm" onclick="exportCodes()"><i data-lucide="download" style="width:13px;height:13px;"></i> Export</button>
  </div>
  <div style="padding:14px 20px 0;">
    <div class="toolbar">
      <div class="toolbar-l"><div class="filter-grp" id="code-filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="used">Used</button>
        <button class="filter-btn" data-filter="unused">Unused</button>
      </div></div>
      <div class="toolbar-r"><div class="search-box"><i data-lucide="search" style="width:13px;height:13px;color:var(--text-muted);"></i><input id="code-search" type="text" class="tbl-search" placeholder="Search user or code..."></div></div>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>User</th><th>Code</th><th>Referrals</th><th>Litties Earned</th><th>Status</th></tr></thead>
      <tbody id="codes-body"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
    </table>
  </div>
  <div class="paging">
    <div class="paging-info" id="code-info">—</div>
    <div class="paging-btns" id="code-pager"></div>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var PALETTE=['#6C5CE7','#14B8A6','#22C55E','#EC4899','#F59E0B','#0EA5E9','#EF4444','#A855F7'];
var state = { codes:[], filter:'all', search:'', page:1, pageSize:15 };

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function fmtNum(n){ return (Number(n)||0).toLocaleString(); }
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10))%PALETTE.length]; }
function fmtDate(s){ if(!s) return '—'; var d=new Date(s.replace(' ','T')); if(isNaN(d)) return s; return d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }

fetch(window.API_BASE+'admin-referrals-stats',{headers:{'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(res){ if(!res||res.status!==true) throw 0; renderAll(res.data); })
  .catch(function(){ document.getElementById('codes-body').innerHTML='<tr><td colspan="5" style="text-align:center;color:var(--red);padding:20px;">Failed to load</td></tr>'; });

function renderAll(d){
  var k=d.kpi||{};
  document.getElementById('k-total').textContent   = fmtNum(k.total_referrals);
  document.getElementById('k-new').textContent     = fmtNum(k.new_this_month)+' this month';
  document.getElementById('k-conv').textContent    = (k.conversion_rate||0)+'%';
  document.getElementById('k-litties').textContent = fmtNum(k.litties_earned);
  document.getElementById('k-active').textContent  = fmtNum(k.active_referrers);

  // Top referrers
  var top=d.top||[];
  var topBox=document.getElementById('top-list');
  if(!top.length){ topBox.innerHTML='<li style="color:var(--text-muted);padding:16px;text-align:center;">No referrers yet.</li>'; }
  else {
    topBox.innerHTML = top.map(function(u,i){
      var medal = i===0 ? 'color:var(--amber);' : i===1 ? 'color:var(--text-muted);' : i===2 ? 'color:#CD7F32;' : 'color:var(--text-muted);';
      return '<li class="feed-item">'
        + '<div style="width:22px;text-align:center;font-weight:800;'+medal+'">'+(i+1)+'</div>'
        + '<div class="user-ava" style="background:'+colorFor(u.id)+';width:34px;height:34px;font-size:11px;">'+initials(u.full_name)+'</div>'
        + '<div class="flex-1"><div class="feed-text"><strong>'+esc(u.full_name||'—')+'</strong></div><div class="feed-time mono" style="font-size:11px;color:var(--text-muted);">'+esc(u.referal_code||'')+'</div></div>'
        + '<div style="text-align:right;"><div class="fw-700">'+fmtNum(u.referral_count)+'</div><div class="text-muted fs-sm" style="font-size:10px;">'+fmtNum(u.litties_earned)+' Litties</div></div>'
        + '</li>';
    }).join('');
  }

  // Recent referrals
  var rec=d.recent||[];
  var recBox=document.getElementById('recent-body');
  if(!rec.length){ recBox.innerHTML='<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">None yet.</td></tr>'; }
  else {
    recBox.innerHTML = rec.map(function(r){
      return '<tr>'
        + '<td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(r.id)+';">'+initials(r.full_name)+'</div><div><div class="user-name">'+esc(r.full_name||'—')+'</div><div class="user-email">'+esc(r.email||'')+'</div></div></div></td>'
        + '<td><div class="user-name">'+esc(r.referrer_name||'—')+'</div><div class="mono" style="font-size:11px;color:var(--text-muted);">'+esc(r.referrer_code||'')+'</div></td>'
        + '<td>'+fmtDate(r.create_on)+'</td>'
        + '</tr>';
    }).join('');
  }

  state.codes = d.codes||[];
  renderCodes();
}

function filteredCodes(){
  return state.codes.filter(function(c){
    var uses = Number(c.uses)||0;
    if(state.filter==='used' && uses===0) return false;
    if(state.filter==='unused' && uses>0) return false;
    if(state.search){
      var q=state.search.toLowerCase();
      return (c.full_name||'').toLowerCase().indexOf(q)>-1
          || (c.email||'').toLowerCase().indexOf(q)>-1
          || (c.referal_code||'').toLowerCase().indexOf(q)>-1;
    }
    return true;
  });
}

function renderCodes(){
  var all=filteredCodes();
  var total=all.length;
  var pages=Math.max(1, Math.ceil(total/state.pageSize));
  if(state.page>pages) state.page=pages; if(state.page<1) state.page=1;
  var start=(state.page-1)*state.pageSize;
  var rows=all.slice(start,start+state.pageSize);

  var tb=document.getElementById('codes-body');
  if(!rows.length){
    tb.innerHTML='<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">No codes match</td></tr>';
    document.getElementById('code-info').textContent='0';
    document.getElementById('code-pager').innerHTML='';
    return;
  }
  tb.innerHTML = rows.map(function(c){
    var statusBadge = String(c.status)==='1' ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>';
    return '<tr>'
      + '<td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(c.id)+';">'+initials(c.full_name)+'</div><div><div class="user-name">'+esc(c.full_name||'—')+'</div><div class="user-email">'+esc(c.email||'')+'</div></div></div></td>'
      + '<td><span class="mono" style="font-size:12px;">'+esc(c.referal_code||'')+'</span></td>'
      + '<td><strong>'+fmtNum(c.uses)+'</strong></td>'
      + '<td>'+fmtNum(c.litties_earned)+'</td>'
      + '<td>'+statusBadge+'</td>'
      + '</tr>';
  }).join('');
  document.getElementById('code-info').textContent='Showing '+(start+1)+'-'+(start+rows.length)+' of '+total;
  renderPager(pages);
  if(window.lucide) lucide.createIcons();
}

function renderPager(pages){
  var box=document.getElementById('code-pager');
  var p=state.page;
  var parts=['<button class="pg-btn" '+(p===1?'disabled':'')+' onclick="codeGoto('+(p-1)+')"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></button>'];
  var nums=[]; if(pages<=7){ for(var i=1;i<=pages;i++) nums.push(i); }
  else { nums.push(1); if(p>3) nums.push('...'); for(var j=Math.max(2,p-1); j<=Math.min(pages-1,p+1); j++) nums.push(j); if(p<pages-2) nums.push('...'); nums.push(pages); }
  nums.forEach(function(n){ if(n==='...') parts.push('<button class="pg-btn" disabled>...</button>'); else parts.push('<button class="pg-btn '+(n===p?'active':'')+'" onclick="codeGoto('+n+')">'+n+'</button>'); });
  parts.push('<button class="pg-btn" '+(p===pages?'disabled':'')+' onclick="codeGoto('+(p+1)+')"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></button>');
  box.innerHTML=parts.join('');
}
function codeGoto(n){ state.page=n; renderCodes(); }

document.querySelectorAll('#code-filters .filter-btn').forEach(function(b){
  b.addEventListener('click', function(){
    document.querySelectorAll('#code-filters .filter-btn').forEach(function(x){x.classList.remove('active');});
    b.classList.add('active');
    state.filter=b.dataset.filter; state.page=1; renderCodes();
  });
});
document.getElementById('code-search').addEventListener('input', function(e){ state.search=e.target.value; state.page=1; renderCodes(); });

function exportCodes(){
  var rows=[['user','email','code','uses','litties_earned','status']];
  state.codes.forEach(function(c){ rows.push([c.full_name,c.email,c.referal_code,c.uses,c.litties_earned,c.status]); });
  var csv=rows.map(function(r){return r.map(function(x){return '"'+String(x==null?'':x).replace(/"/g,'""')+'"';}).join(',');}).join('\n');
  var a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download='referral-codes.csv'; a.click();
}
</script>

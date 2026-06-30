<?php
$pageTitle = 'Users';
$active    = 'users';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>

<div class="stats" id="user-stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="users"></i></div></div><div class="stat-val" id="stat-total">0</div><div class="stat-lbl">Total Users</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon green"><i data-lucide="user-check"></i></div></div><div class="stat-val" id="stat-active">0</div><div class="stat-lbl">Active Users</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="user-plus"></i></div></div><div class="stat-val" id="stat-new">0</div><div class="stat-lbl">New Today</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon red"><i data-lucide="user-x"></i></div></div><div class="stat-val" id="stat-inactive">0</div><div class="stat-lbl">Inactive</div></div>
</div>

<div class="card">
  <div class="card-head">
    <div><h3>All Users</h3><div class="sub">Manage registered users</div></div>
    <button class="btn btn-sec btn-sm" onclick="exportUsers()"><i data-lucide="download" style="width:13px;height:13px;"></i> Export</button>
  </div>
  <div style="padding:14px 20px 0;">
    <div class="toolbar">
      <div class="toolbar-l">
        <div class="filter-grp">
          <button class="filter-btn active" data-filter="all">All</button>
          <button class="filter-btn" data-filter="1">Active</button>
          <button class="filter-btn" data-filter="0">Inactive</button>
        </div>
      </div>
      <div class="toolbar-r"><div class="search-box"><i data-lucide="search" style="width:13px;height:13px;color:var(--text-muted);"></i><input id="user-search" type="text" class="tbl-search" placeholder="Search users..."></div></div>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="tbl">
      <thead><tr><th>User</th><th>Phone</th><th>Email</th><th>Card #</th><th>Steps</th><th>Litties</th><th>Referrals</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody id="user-tbody">
        <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">Loading...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="paging">
    <div class="paging-info" id="paging-info">—</div>
    <div class="paging-btns" id="paging-btns"></div>
  </div>
</div>

<div class="modal-bg" id="user-view-modal"><div class="modal">
  <div class="modal-head"><h3>User Details</h3><button class="modal-close" onclick="closeModal()"><i data-lucide="x" style="width:16px;height:16px;"></i></button></div>
  <div class="modal-body" id="user-view-body">—</div>
  <div class="modal-foot"><button class="btn btn-sec btn-sm" onclick="closeModal()">Close</button></div>
</div></div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var PALETTE = ['#6C5CE7','#14B8A6','#22C55E','#EC4899','#F59E0B','#0EA5E9','#EF4444','#A855F7'];
var state = { users: [], filter: 'all', search: '', page: 1, pageSize: 10 };

function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0] + (p[1]? p[1][0] : '')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10)) % PALETTE.length]; }
function fmtDate(s){ if(!s) return '—'; var d=new Date(s); if(isNaN(d)) return s; return d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }

function loadUsers(){
  var tbody=document.getElementById('user-tbody');
  tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">Loading...</td></tr>';
  fetch(window.API_BASE+'admin-users-list',{headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(res){
      if(!res || res.status!==true){ tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--red);">'+(res&&res.message||'Failed to load')+'</td></tr>'; return; }
      state.users = res.data || [];
      renderStats();
      renderTable();
    }).catch(function(){ tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--red);">Network error</td></tr>'; });
}

function renderStats(){
  var users=state.users;
  var total=users.length;
  var active=users.filter(function(u){return String(u.status)==='1';}).length;
  var inactive=total-active;
  var today=new Date().toISOString().slice(0,10);
  var newToday=users.filter(function(u){ return (u.create_on||'').slice(0,10)===today; }).length;
  document.getElementById('stat-total').textContent=total.toLocaleString();
  document.getElementById('stat-active').textContent=active.toLocaleString();
  document.getElementById('stat-new').textContent=newToday.toLocaleString();
  document.getElementById('stat-inactive').textContent=inactive.toLocaleString();
}

function filteredRows(){
  return state.users.filter(function(u){
    if(state.filter!=='all' && String(u.status)!==state.filter) return false;
    if(state.search){
      var q=state.search.toLowerCase();
      return (u.full_name||'').toLowerCase().indexOf(q)>-1
          || (u.email||'').toLowerCase().indexOf(q)>-1
          || (u.phone_number||'').toLowerCase().indexOf(q)>-1;
    }
    return true;
  });
}

function renderTable(){
  var tbody=document.getElementById('user-tbody');
  var all = filteredRows();
  var total = all.length;
  var pages = Math.max(1, Math.ceil(total / state.pageSize));
  if(state.page > pages) state.page = pages;
  if(state.page < 1) state.page = 1;
  var start = (state.page - 1) * state.pageSize;
  var rows = all.slice(start, start + state.pageSize);

  if(!rows.length){
    tbody.innerHTML='<tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">No users found</td></tr>';
    document.getElementById('paging-info').textContent='0 users';
    document.getElementById('paging-btns').innerHTML='';
    return;
  }
  tbody.innerHTML = rows.map(function(u){
    var statusBadge = String(u.status)==='1' ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>';
    var phone = ((u.country_code||'') + ' ' + (u.phone_number||'')).trim() || '—';
    return '<tr>'
      + '<td><div class="user-cell"><div class="user-ava" style="background:'+colorFor(u.id)+';">'+initials(u.full_name)+'</div><div><div class="user-name">'+esc(u.full_name||'—')+'</div><div class="user-email">'+esc(u.nickname||'')+'</div></div></div></td>'
      + '<td>'+esc(phone)+'</td>'
      + '<td>'+esc(u.email||'—')+'</td>'
      + '<td>'+esc(u.membership_card_number||'—')+'</td>'
      + '<td>'+Number(u.total_steps||0).toLocaleString()+'</td>'
      + '<td><strong>'+Number(u.points_balance||0).toLocaleString()+'</strong><div style="font-size:10px;color:var(--text-muted);">'+Number(u.points_earned||0).toLocaleString()+' earned</div></td>'
      + '<td>'+Number(u.referrals_count||0)+' <span style="font-size:10px;color:var(--text-muted);">'+esc(u.referal_code||'')+'</span></td>'
      + '<td>'+statusBadge+'</td>'
      + '<td>'+fmtDate(u.create_on)+'</td>'
      + '<td><div class="flex gap-4"><a class="btn btn-ghost btn-icon btn-sm" href="<?= base_url('admin/user-detail') ?>?id='+u.id+'" title="View details"><i data-lucide="eye" style="width:14px;height:14px;"></i></a><button class="btn btn-ghost btn-icon btn-sm" onclick="viewUser('+u.id+')" title="Quick view"><i data-lucide="user" style="width:14px;height:14px;"></i></button></div></td>'
      + '</tr>';
  }).join('');
  document.getElementById('paging-info').textContent='Showing '+(start+1)+'-'+(start+rows.length)+' of '+total;
  renderPager(pages);
  if(window.lucide) lucide.createIcons();
}

function renderPager(pages){
  var box=document.getElementById('paging-btns');
  var p=state.page;
  var parts=[];
  parts.push('<button class="pg-btn" '+(p===1?'disabled':'')+' onclick="gotoPage('+(p-1)+')"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></button>');
  var nums=[];
  if(pages<=7){ for(var i=1;i<=pages;i++) nums.push(i); }
  else{
    nums.push(1);
    if(p>3) nums.push('...');
    for(var j=Math.max(2,p-1); j<=Math.min(pages-1,p+1); j++) nums.push(j);
    if(p<pages-2) nums.push('...');
    nums.push(pages);
  }
  nums.forEach(function(n){
    if(n==='...') parts.push('<button class="pg-btn" disabled>...</button>');
    else parts.push('<button class="pg-btn '+(n===p?'active':'')+'" onclick="gotoPage('+n+')">'+n+'</button>');
  });
  parts.push('<button class="pg-btn" '+(p===pages?'disabled':'')+' onclick="gotoPage('+(p+1)+')"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></button>');
  box.innerHTML=parts.join('');
}

function gotoPage(n){ state.page=n; renderTable(); }

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }

function viewUser(id){
  var u = state.users.find(function(x){return String(x.id)===String(id);});
  if(!u) return;
  var body=document.getElementById('user-view-body');
  body.innerHTML =
    '<div style="text-align:center;margin-bottom:20px;"><div class="user-ava" style="width:52px;height:52px;font-size:20px;margin:0 auto 10px;background:'+colorFor(u.id)+';">'+initials(u.full_name)+'</div><h4 style="font-size:15px;">'+esc(u.full_name||'—')+'</h4><p class="text-muted fs-sm">Member since '+fmtDate(u.create_on)+'</p></div>'
    + '<div class="detail-grid">'
    + '<div class="detail-item"><label>Email</label><span>'+esc(u.email||'—')+'</span></div>'
    + '<div class="detail-item"><label>Phone</label><span>'+esc(((u.country_code||'')+' '+(u.phone_number||'')).trim()||'—')+'</span></div>'
    + '<div class="detail-item"><label>Nickname</label><span>'+esc(u.nickname||'—')+'</span></div>'
    + '<div class="detail-item"><label>Card #</label><span>'+esc(u.membership_card_number||'—')+'</span></div>'
    + '<div class="detail-item"><label>Litties Balance</label><span>'+Number(u.points_balance||0).toLocaleString()+'</span></div>'
    + '<div class="detail-item"><label>Total Earned</label><span>'+Number(u.points_earned||0).toLocaleString()+'</span></div>'
    + '<div class="detail-item"><label>Total Steps</label><span>'+Number(u.total_steps||0).toLocaleString()+'</span></div>'
    + '<div class="detail-item"><label>Referrals</label><span>'+Number(u.referrals_count||0)+'</span></div>'
    + '<div class="detail-item"><label>Referral Code</label><span class="mono">'+esc(u.referal_code||'—')+'</span></div>'
    + '<div class="detail-item"><label>Card Points</label><span>'+(u.card_points||0)+'</span></div>'
    + '<div class="detail-item"><label>Status</label><span>'+(String(u.status)==='1'?'Active':'Inactive')+'</span></div>'
    + '</div>';
  document.getElementById('user-view-modal').classList.add('show');
}
function closeModal(){ document.getElementById('user-view-modal').classList.remove('show'); }

function exportUsers(){
  var rows=[['id','full_name','email','phone','card_number','points','status','created']];
  state.users.forEach(function(u){ rows.push([u.id,u.full_name,u.email,(u.country_code||'')+' '+(u.phone_number||''),u.membership_card_number,u.card_points,u.status,u.create_on]); });
  var csv=rows.map(function(r){return r.map(function(c){return '"'+String(c==null?'':c).replace(/"/g,'""')+'"';}).join(',');}).join('\n');
  var a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download='users.csv'; a.click();
}

document.querySelectorAll('.filter-btn').forEach(function(b){ b.addEventListener('click', function(){
  document.querySelectorAll('.filter-btn').forEach(function(x){x.classList.remove('active');});
  b.classList.add('active');
  state.filter = b.dataset.filter;
  state.page = 1;
  renderTable();
});});
document.getElementById('user-search').addEventListener('input', function(e){ state.search=e.target.value; state.page=1; renderTable(); });

loadUsers();
</script>

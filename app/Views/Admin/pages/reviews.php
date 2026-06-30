<?php
$pageTitle = 'Reviews';
$active    = 'reviews';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
$adminBase = base_url('admin');
?>
<style>
  .inline-stats { display:flex; gap:24px; padding:14px 20px; background:var(--surface); border-radius:var(--radius); border:1px solid var(--border); margin-bottom:20px; box-shadow:var(--shadow); flex-wrap:wrap; }
  .inline-stat { display:flex; align-items:center; gap:10px; }
  .inline-stat-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .inline-stat-icon svg { width:15px; height:15px; }
  .inline-stat-val { font-size:16px; font-weight:750; }
  .inline-stat-lbl { font-size:11px; color:var(--text-muted); }

  .dist-row { display:flex; align-items:center; gap:10px; padding:6px 0; }
  .dist-label { width:50px; font-size:12px; font-weight:600; color:var(--text-sec); display:flex; align-items:center; gap:4px; }
  .dist-track { flex:1; height:8px; background:var(--bg); border-radius:4px; overflow:hidden; }
  .dist-fill  { height:100%; background:#F59E0B; border-radius:4px; transition:width .4s ease; }
  .dist-count { width:50px; text-align:right; font-size:12px; font-weight:700; }

  .filter-bar { display:flex; gap:8px; flex-wrap:wrap; }
  .filter-btn { padding:6px 12px; border:1px solid var(--border); background:var(--surface); border-radius:var(--radius-xs); font-size:12px; font-weight:600; color:var(--text-sec); cursor:pointer; display:inline-flex; align-items:center; gap:4px; }
  .filter-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }
  .filter-btn svg { width:12px; height:12px; }

  .rev-item { display:flex; align-items:flex-start; gap:14px; padding:14px 0; border-bottom:1px solid var(--border); cursor:pointer; text-decoration:none; color:inherit; transition:background .15s; }
  .rev-item:hover { background:var(--bg); }
  .rev-item:last-child { border-bottom:none; }
  .rev-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; overflow:hidden; }
  .rev-avatar img { width:100%; height:100%; object-fit:cover; }
  .rev-info { flex:1; min-width:0; }
  .rev-head { display:flex; align-items:center; gap:10px; margin-bottom:4px; flex-wrap:wrap; }
  .rev-name { font-size:13.5px; font-weight:650; }
  .rev-email { font-size:11.5px; color:var(--text-muted); }
  .rev-userid { display:inline-block; padding:1px 8px; background:var(--bg); border:1px solid var(--border); border-radius:10px; font-size:10.5px; font-weight:600; color:var(--text-sec); }
  .rev-stars { display:inline-flex; gap:2px; }
  .rev-stars svg { width:14px; height:14px; }
  .rev-stars .on { color:#F59E0B; fill:#F59E0B; }
  .rev-stars .off { color:#E5E7EB; }
  .rev-body { font-size:12.5px; color:var(--text-sec); line-height:1.55; }
  .rev-body.empty { font-style:italic; color:var(--text-muted); }
  .rev-meta { font-size:11px; color:var(--text-muted); margin-top:4px; }
  .empty-state { text-align:center; padding:40px 20px; color:var(--text-muted); }
  .empty-state svg { width:36px; height:36px; margin-bottom:8px; opacity:.5; }
</style>

<div class="inline-stats">
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--amber-soft);color:var(--amber);"><i data-lucide="star"></i></div><div><div class="inline-stat-val" id="k-total">0</div><div class="inline-stat-lbl">Total reviews</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--green-soft);color:var(--green);"><i data-lucide="trending-up"></i></div><div><div class="inline-stat-val" id="k-avg">0.00</div><div class="inline-stat-lbl">Average rating</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--blue-soft);color:var(--blue);"><i data-lucide="message-square"></i></div><div><div class="inline-stat-val" id="k-text">0</div><div class="inline-stat-lbl">With response text</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--primary-soft);color:var(--primary);"><i data-lucide="calendar"></i></div><div><div class="inline-stat-val" id="k-today">0</div><div class="inline-stat-lbl">Today</div></div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Star Distribution</h3><div class="sub">Breakdown by rating</div></div></div>
    <div class="card-body"><div id="dist-wrap"></div></div>
  </div>

  <div class="card">
    <div class="card-head"><div><h3>Filter</h3><div class="sub">Narrow down the list</div></div></div>
    <div class="card-body">
      <div class="filter-bar" id="filter-bar">
        <button class="filter-btn active" data-star="all">All</button>
        <button class="filter-btn" data-star="5">5 <i data-lucide="star"></i></button>
        <button class="filter-btn" data-star="4">4 <i data-lucide="star"></i></button>
        <button class="filter-btn" data-star="3">3 <i data-lucide="star"></i></button>
        <button class="filter-btn" data-star="2">2 <i data-lucide="star"></i></button>
        <button class="filter-btn" data-star="1">1 <i data-lucide="star"></i></button>
        <button class="filter-btn" data-star="text">With text</button>
      </div>
      <div style="margin-top:12px;">
        <input type="text" id="rev-search" class="fi" placeholder="Search name / email / text...">
      </div>
    </div>
  </div>
</div>

<div class="card mt-20">
  <div class="card-head"><div><h3>All Reviews</h3><div class="sub">Sorted by newest, user_id wise</div></div></div>
  <div class="card-body" id="rev-list" style="padding:4px 20px 12px;">
    <div class="empty-state"><i data-lucide="loader-2"></i><div>Loading reviews...</div></div>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var ADMIN_BASE = window.ADMIN_BASE || '<?= $adminBase ?>';
var state = { reviews: [], filterStar: 'all', search: '' };

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function initials(n){ if(!n) return '?'; var p = String(n).trim().split(/\s+/); return (p[0][0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function starSvg(on){ return '<svg class="'+(on?'on':'off')+'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'; }
function renderStars(n){ var o=''; for(var i=1;i<=5;i++) o += starSvg(i<=n); return '<span class="rev-stars">'+o+'</span>'; }

function renderKpi(k){
  document.getElementById('k-total').textContent = k.total_reviews || 0;
  document.getElementById('k-avg').textContent   = (Number(k.average_rating)||0).toFixed(2);
  document.getElementById('k-text').textContent  = k.with_response || 0;
  document.getElementById('k-today').textContent = k.today || 0;
}

function renderDist(dist, total){
  var wrap = document.getElementById('dist-wrap');
  wrap.innerHTML = '';
  [5,4,3,2,1].forEach(function(s){
    var c = dist[s] || 0;
    var pct = total > 0 ? (c*100/total) : 0;
    var row = document.createElement('div');
    row.className = 'dist-row';
    row.innerHTML = '<div class="dist-label">'+s+' <i data-lucide="star" style="width:11px;height:11px;"></i></div>'
                  + '<div class="dist-track"><div class="dist-fill" style="width:'+pct.toFixed(1)+'%"></div></div>'
                  + '<div class="dist-count">'+c+'</div>';
    wrap.appendChild(row);
  });
  if (window.lucide) lucide.createIcons();
}

function renderList(){
  var list = document.getElementById('rev-list');
  var q = (state.search||'').toLowerCase().trim();

  var filtered = state.reviews.filter(function(r){
    if (state.filterStar === 'all') {}
    else if (state.filterStar === 'text') { if (!r.response || !String(r.response).trim()) return false; }
    else { if (String(r.star) !== String(state.filterStar)) return false; }
    if (q){
      var hay = ((r.full_name||'')+' '+(r.email||'')+' '+(r.response||'')).toLowerCase();
      if (hay.indexOf(q) === -1) return false;
    }
    return true;
  });

  if (!filtered.length){
    list.innerHTML = '<div class="empty-state"><i data-lucide="inbox"></i><div>No reviews match.</div></div>';
    if (window.lucide) lucide.createIcons();
    return;
  }

  list.innerHTML = filtered.map(function(r){
    var avatar = r.user_image ? '<img src="'+esc(r.user_image)+'" alt="">' : esc(initials(r.full_name));
    var body = r.response && String(r.response).trim()
      ? '<div class="rev-body">'+esc(r.response)+'</div>'
      : '<div class="rev-body empty">No written response</div>';
    var href = ADMIN_BASE + '/review-view?user_id=' + encodeURIComponent(r.user_id);
    return '<a class="rev-item" href="'+href+'">'
         + '<div class="rev-avatar">'+avatar+'</div>'
         + '<div class="rev-info">'
         +   '<div class="rev-head">'
         +     '<span class="rev-name">'+esc(r.full_name||'Unknown user')+'</span>'
         +     '<span class="rev-userid">#'+esc(r.user_id)+'</span>'
         +     '<span class="rev-email">'+esc(r.email||'')+'</span>'
         +     renderStars(parseInt(r.star,10)||0)
         +   '</div>'
         +   body
         +   '<div class="rev-meta">'+esc(r.created_at||'')+'</div>'
         + '</div>'
         + '</a>';
  }).join('');
  if (window.lucide) lucide.createIcons();
}

function loadReviews(){
  fetch(window.API_BASE+'admin-reviews-stats',{headers:{'Accept':'application/json'}})
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res || res.status !== true){
        document.getElementById('rev-list').innerHTML =
          '<div class="empty-state"><i data-lucide="alert-triangle"></i><div>'+esc((res && res.message) || 'Failed to load.')+'</div></div>';
        if (window.lucide) lucide.createIcons();
        return;
      }
      var d = res.data || {};
      state.reviews = d.reviews || [];
      renderKpi(d.kpi || {});
      renderDist(d.distribution || {}, (d.kpi && d.kpi.total_reviews) || 0);
      renderList();
    })
    .catch(function(e){
      document.getElementById('rev-list').innerHTML =
        '<div class="empty-state"><i data-lucide="alert-triangle"></i><div>Network error: '+esc(e.message)+'</div></div>';
      if (window.lucide) lucide.createIcons();
    });
}

document.getElementById('filter-bar').addEventListener('click', function(e){
  var b = e.target.closest('.filter-btn');
  if (!b) return;
  document.querySelectorAll('#filter-bar .filter-btn').forEach(function(x){ x.classList.remove('active'); });
  b.classList.add('active');
  state.filterStar = b.getAttribute('data-star');
  renderList();
});

document.getElementById('rev-search').addEventListener('input', function(e){
  state.search = e.target.value || '';
  renderList();
});

loadReviews();
</script>

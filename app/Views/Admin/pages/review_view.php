<?php
$pageTitle = 'Review Detail';
$active    = 'reviews';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
$adminBase = base_url('admin');
?>
<style>
  .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--text-sec); font-size:12.5px; font-weight:600; text-decoration:none; padding:6px 10px; border:1px solid var(--border); border-radius:var(--radius-xs); margin-bottom:14px; }
  .back-link:hover { background:var(--bg); color:var(--text); }
  .back-link svg { width:14px; height:14px; }

  .user-card { display:flex; align-items:center; gap:18px; padding:22px; background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid var(--border); margin-bottom:18px; flex-wrap:wrap; }
  .user-avatar { width:68px; height:68px; border-radius:50%; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:24px; flex-shrink:0; overflow:hidden; }
  .user-avatar img { width:100%; height:100%; object-fit:cover; }
  .user-meta h2 { font-size:20px; font-weight:700; margin-bottom:4px; }
  .user-meta .email { font-size:13px; color:var(--text-sec); margin-bottom:6px; }
  .user-meta .tags { display:flex; flex-wrap:wrap; gap:6px; }
  .user-tag { font-size:11px; padding:2px 9px; border-radius:10px; background:var(--bg); border:1px solid var(--border); color:var(--text-sec); font-weight:600; }
  .user-summary { margin-left:auto; display:flex; gap:18px; flex-shrink:0; flex-wrap:wrap; }
  .usum { text-align:center; }
  .usum-val { font-size:20px; font-weight:750; }
  .usum-lbl { font-size:11px; color:var(--text-muted); }
  .usum-val .big-star { color:#F59E0B; }

  .dist-row { display:flex; align-items:center; gap:10px; padding:6px 0; }
  .dist-label { width:50px; font-size:12px; font-weight:600; color:var(--text-sec); display:flex; align-items:center; gap:4px; }
  .dist-track { flex:1; height:8px; background:var(--bg); border-radius:4px; overflow:hidden; }
  .dist-fill  { height:100%; background:#F59E0B; border-radius:4px; transition:width .4s ease; }
  .dist-count { width:50px; text-align:right; font-size:12px; font-weight:700; }

  .rev-row { display:flex; gap:14px; padding:14px 0; border-bottom:1px solid var(--border); }
  .rev-row:last-child { border-bottom:none; }
  .rev-num { width:32px; height:32px; border-radius:8px; background:var(--bg); color:var(--text-sec); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0; }
  .rev-head2 { display:flex; align-items:center; gap:10px; margin-bottom:4px; flex-wrap:wrap; }
  .rev-stars { display:inline-flex; gap:2px; }
  .rev-stars svg { width:14px; height:14px; }
  .rev-stars .on { color:#F59E0B; fill:#F59E0B; }
  .rev-stars .off { color:#E5E7EB; }
  .rev-when { font-size:11px; color:var(--text-muted); }
  .rev-text { font-size:12.5px; line-height:1.55; }
  .rev-text.empty { font-style:italic; color:var(--text-muted); }

  .info-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); font-size:12.5px; }
  .info-row:last-child { border-bottom:none; }
  .info-key { color:var(--text-muted); }
  .info-val { font-weight:600; }

  .empty-state { text-align:center; padding:40px 20px; color:var(--text-muted); }
  .empty-state svg { width:36px; height:36px; margin-bottom:8px; opacity:.5; }
</style>

<a href="<?= $adminBase ?>/reviews" class="back-link"><i data-lucide="arrow-left"></i> Back to all reviews</a>

<div id="user-card-wrap">
  <div class="empty-state"><i data-lucide="loader-2"></i><div>Loading user...</div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Rating Distribution</h3><div class="sub">This user's ratings</div></div></div>
    <div class="card-body"><div id="dist-wrap"></div></div>
  </div>

  <div class="card">
    <div class="card-head"><div><h3>User Info</h3><div class="sub">Account snapshot</div></div></div>
    <div class="card-body" id="info-wrap"><div class="empty-state">Loading...</div></div>
  </div>
</div>

<div class="card mt-20">
  <div class="card-head"><div><h3>All Reviews</h3><div class="sub">Newest first</div></div></div>
  <div class="card-body" id="rev-list" style="padding:4px 20px 12px;">
    <div class="empty-state"><i data-lucide="loader-2"></i><div>Loading reviews...</div></div>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script>
function qs(n){ return new URLSearchParams(location.search).get(n); }
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function initials(n){ if(!n) return '?'; var p = String(n).trim().split(/\s+/); return (p[0][0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function starSvg(on){ return '<svg class="'+(on?'on':'off')+'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'; }
function renderStars(n){ var o=''; for(var i=1;i<=5;i++) o += starSvg(i<=n); return '<span class="rev-stars">'+o+'</span>'; }

function renderUserCard(u, kpi){
  var avatar = u.user_image ? '<img src="'+esc(u.user_image)+'" alt="">' : esc(initials(u.full_name));
  var tags = [];
  if (u.membership_card_number) tags.push('<span class="user-tag">'+esc(u.membership_card_number)+'</span>');
  if (u.referal_code)           tags.push('<span class="user-tag">Ref: '+esc(u.referal_code)+'</span>');
  if (u.phone_number)           tags.push('<span class="user-tag">'+esc((u.country_code||'')+u.phone_number)+'</span>');
  tags.push('<span class="user-tag">#'+esc(u.id)+'</span>');

  document.getElementById('user-card-wrap').innerHTML = '<div class="user-card">'
    + '<div class="user-avatar">'+avatar+'</div>'
    + '<div class="user-meta">'
    +   '<h2>'+esc(u.full_name||'Unknown user')+'</h2>'
    +   '<div class="email">'+esc(u.email||'')+'</div>'
    +   '<div class="tags">'+tags.join('')+'</div>'
    + '</div>'
    + '<div class="user-summary">'
    +   '<div class="usum"><div class="usum-val">'+(kpi.total_reviews||0)+'</div><div class="usum-lbl">Reviews</div></div>'
    +   '<div class="usum"><div class="usum-val"><span class="big-star">&#9733;</span> '+(Number(kpi.average_rating)||0).toFixed(2)+'</div><div class="usum-lbl">Average</div></div>'
    +   '<div class="usum"><div class="usum-val">'+(kpi.with_response||0)+'</div><div class="usum-lbl">With text</div></div>'
    + '</div>'
  + '</div>';
}

function renderInfo(u){
  var rows = [
    ['Full name',  u.full_name],
    ['Email',      u.email],
    ['Phone',      (u.country_code||'')+(u.phone_number||'')],
    ['Member ID',  u.membership_card_number],
    ['Referral',   u.referal_code],
    ['Joined',     u.create_on]
  ];
  document.getElementById('info-wrap').innerHTML = rows.map(function(r){
    return '<div class="info-row"><span class="info-key">'+esc(r[0])+'</span><span class="info-val">'+esc(r[1] || '—')+'</span></div>';
  }).join('');
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

function renderReviews(reviews){
  var list = document.getElementById('rev-list');
  if (!reviews.length){
    list.innerHTML = '<div class="empty-state"><i data-lucide="inbox"></i><div>No reviews yet.</div></div>';
    if (window.lucide) lucide.createIcons();
    return;
  }
  list.innerHTML = reviews.map(function(r, idx){
    var body = r.response && String(r.response).trim()
      ? '<div class="rev-text">'+esc(r.response)+'</div>'
      : '<div class="rev-text empty">No written response</div>';
    return '<div class="rev-row">'
         + '<div class="rev-num">'+(idx+1)+'</div>'
         + '<div style="flex:1;min-width:0;">'
         +   '<div class="rev-head2">'+renderStars(parseInt(r.star,10)||0)+'<span class="rev-when">'+esc(r.created_at||'')+'</span></div>'
         +   body
         + '</div>'
       + '</div>';
  }).join('');
}

function loadUser(){
  var uid = qs('user_id');
  if (!uid){
    document.getElementById('user-card-wrap').innerHTML =
      '<div class="empty-state"><i data-lucide="alert-triangle"></i><div>Missing user_id in URL.</div></div>';
    document.getElementById('rev-list').innerHTML = '';
    document.getElementById('info-wrap').innerHTML = '';
    if (window.lucide) lucide.createIcons();
    return;
  }

  fetch(window.API_BASE+'admin-reviews-by-user?user_id='+encodeURIComponent(uid),{headers:{'Accept':'application/json'}})
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res || res.status !== true){
        document.getElementById('user-card-wrap').innerHTML =
          '<div class="empty-state"><i data-lucide="alert-triangle"></i><div>'+esc((res && res.message) || 'Failed to load.')+'</div></div>';
        if (window.lucide) lucide.createIcons();
        return;
      }
      var d = res.data || {};
      renderUserCard(d.user || {}, d.kpi || {});
      renderInfo(d.user || {});
      renderDist(d.distribution || {}, (d.kpi && d.kpi.total_reviews) || 0);
      renderReviews(d.reviews || []);
    })
    .catch(function(e){
      document.getElementById('user-card-wrap').innerHTML =
        '<div class="empty-state"><i data-lucide="alert-triangle"></i><div>Network error: '+esc(e.message)+'</div></div>';
      if (window.lucide) lucide.createIcons();
    });
}

loadUser();
</script>

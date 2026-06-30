<?php
$pageTitle = 'Notifications';
$active    = 'notifications';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .compose-layout { display: grid; grid-template-columns: 1fr 300px; gap: 0; margin-bottom: 20px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
  .compose-main { padding: 24px 28px; }
  .compose-preview { background: #0F172A; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:28px 20px; }
  .compose-section { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
  .compose-section:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
  .section-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
  .section-num { width:24px; height:24px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
  .section-title { font-size:13px; font-weight:650; }
  .audience-chip { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border:1.5px solid var(--border); border-radius:22px; font-size:12px; font-weight:500; color:var(--text-sec); cursor:pointer; background:var(--surface); margin:0 6px 6px 0; }
  .audience-chip.selected { background:var(--primary); color:#fff; border-color:var(--primary); }
  .audience-chip .chip-count { font-size:10px; opacity:.75; }
  .phone-frame { width:240px; background:#1E293B; border-radius:28px; padding:10px; }
  .phone-screen { background:#0F172A; border-radius:20px; padding:16px 14px; min-height:220px; }
  .phone-notch { width:80px; height:4px; background:#1E293B; border-radius:2px; margin:0 auto 12px; }
  .phone-notif { background:rgba(255,255,255,.07); border-radius:14px; padding:14px; border:1px solid rgba(255,255,255,.05); }
  .phone-notif-head { display:flex; align-items:center; gap:7px; margin-bottom:8px; }
  .phone-notif-icon { width:22px; height:22px; border-radius:6px; background:linear-gradient(135deg,#003B4C,#0D9488); }
  .phone-notif-app { font-size:9px; color:rgba(255,255,255,.4); text-transform:uppercase; font-weight:600; }
  .phone-notif-time { font-size:9px; color:rgba(255,255,255,.3); margin-left:auto; }
  .phone-notif-title { font-size:13px; font-weight:600; color:#fff; margin-bottom:3px; min-height:18px; }
  .phone-notif-body { font-size:11.5px; color:rgba(255,255,255,.5); line-height:1.55; min-height:36px; }
  .preview-label { color:rgba(255,255,255,.3); font-size:10px; letter-spacing:1px; font-weight:600; margin-bottom:14px; }
  .inline-stats { display:flex; gap:24px; padding:14px 20px; background:var(--surface); border-radius:var(--radius); border:1px solid var(--border); margin-bottom:20px; box-shadow:var(--shadow); flex-wrap:wrap; }
  .inline-stat { display:flex; align-items:center; gap:10px; }
  .inline-stat-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .inline-stat-val { font-size:16px; font-weight:750; }
  .inline-stat-lbl { font-size:11px; color:var(--text-muted); }
  .hist-item { display:flex; align-items:center; gap:14px; padding:13px 0; border-bottom:1px solid var(--border); }
  .hist-item:last-child { border-bottom:none; }
  .hist-icon { width:34px; height:34px; border-radius:8px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; }
  .hist-info { flex:1; min-width:0; }
  .hist-title { font-size:13px; font-weight:600; }
  .hist-meta { font-size:11px; color:var(--text-muted); }
  .hist-stats { display:flex; gap:16px; }
  .hist-stat-val { font-size:13px; font-weight:700; text-align:center; }
  .hist-stat-lbl { font-size:9px; color:var(--text-muted); text-transform:uppercase; }
  @media (max-width:1024px) { .compose-layout { grid-template-columns:1fr; } }
</style>

<div class="inline-stats">
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--primary-soft);color:var(--primary);"><i data-lucide="send"></i></div><div><div class="inline-stat-val" id="k-month">0</div><div class="inline-stat-lbl">Sent this month</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--green-soft);color:var(--green);"><i data-lucide="check-check"></i></div><div><div class="inline-stat-val" id="k-read">0%</div><div class="inline-stat-lbl">Read rate</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--amber-soft);color:var(--amber);"><i data-lucide="bell-ring"></i></div><div><div class="inline-stat-val" id="k-today">0</div><div class="inline-stat-lbl">Sent today</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--teal-soft);color:var(--teal);"><i data-lucide="users"></i></div><div><div class="inline-stat-val" id="k-optin">0</div><div class="inline-stat-lbl">Opt-in users</div></div></div>
  <div class="inline-stat"><div class="inline-stat-icon" style="background:var(--blue-soft);color:var(--blue);"><i data-lucide="database"></i></div><div><div class="inline-stat-val" id="k-total">0</div><div class="inline-stat-lbl">All time</div></div></div>
</div>

<div class="compose-layout">
  <div class="compose-main">
    <div class="compose-section">
      <div class="section-head"><div class="section-num">1</div><div class="section-title">Audience</div></div>
      <div id="audience-chips">
        <span class="audience-chip selected" data-aud="all">All Users <span class="chip-count" data-count="all">0</span></span>
        <span class="audience-chip" data-aud="active">Active 7d <span class="chip-count" data-count="active">0</span></span>
        <span class="audience-chip" data-aud="inactive">Inactive <span class="chip-count" data-count="inactive">0</span></span>
        <span class="audience-chip" data-aud="walking">Walking Now <span class="chip-count" data-count="walking">0</span></span>
        <span class="audience-chip" data-aud="daily_goal">Daily Goal Opt-in <span class="chip-count" data-count="daily_goal">0</span></span>
        <span class="audience-chip" data-aud="achievement">Achievements <span class="chip-count" data-count="achievement">0</span></span>
        <span class="audience-chip" data-aud="weekly">Weekly Report <span class="chip-count" data-count="weekly">0</span></span>
        <span class="audience-chip" data-aud="tier">Tier Updates <span class="chip-count" data-count="tier">0</span></span>
        <span class="audience-chip" data-aud="challenges">Challenges <span class="chip-count" data-count="challenges">0</span></span>
      </div>
    </div>

    <div class="compose-section">
      <div class="section-head"><div class="section-num">2</div><div class="section-title">Message</div></div>
      <div class="fg"><label class="fl">Title</label><input id="notif-title" type="text" class="fi" maxlength="80" placeholder="Time to walk!" oninput="updatePreview()"></div>
      <div class="fg"><label class="fl">Body</label><textarea id="notif-body" class="fi" rows="3" maxlength="300" placeholder="You&#39;re almost at your daily goal — keep going!" oninput="updatePreview()"></textarea></div>
      <div class="fg"><label class="fl">Type (tag)</label><input id="notif-type" type="text" class="fi" placeholder="admin" value="admin"></div>
    </div>

    <div class="compose-section">
      <div class="section-head"><div class="section-num">3</div><div class="section-title">Send</div></div>
      <div class="send-bar" style="display:flex;align-items:center;gap:10px;justify-content:space-between;flex-wrap:wrap;">
        <div style="font-size:12px;color:var(--text-muted);">Will be delivered to <strong id="recipients">0</strong> users.</div>
        <div>
          <button class="btn btn-pri btn-sm" id="send-btn" onclick="sendNotification()"><i data-lucide="send" style="width:13px;height:13px;"></i> Send now</button>
        </div>
      </div>
      <div id="send-msg" style="margin-top:10px;font-size:12px;"></div>
    </div>
  </div>

  <div class="compose-preview">
    <div class="preview-label">PREVIEW</div>
    <div class="phone-frame">
      <div class="phone-screen">
        <div class="phone-notch"></div>
        <div class="phone-notif">
          <div class="phone-notif-head"><div class="phone-notif-icon"></div><div class="phone-notif-app">WERN</div><div class="phone-notif-time">now</div></div>
          <div class="phone-notif-title" id="pv-title">Title preview</div>
          <div class="phone-notif-body" id="pv-body">Your message preview appears here.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>Recent Notifications</h3><div class="sub">Latest admin-sent campaigns</div></div></div>
  <div style="padding:8px 20px 20px;">
    <div id="history">Loading...</div>
  </div>
</div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var selectedAudience = 'all';
var cachedCounts = {};

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function fmtNum(n){ return (Number(n)||0).toLocaleString(); }
function fmtDate(s){ if(!s) return '—'; var d=new Date(s.replace(' ','T')); if(isNaN(d)) return s; return d.toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); }

function loadStats(){
  fetch(window.API_BASE+'admin-notifications-stats',{headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(res){ if(!res||res.status!==true) return; renderStats(res.data); })
    .catch(function(){});
}

function renderStats(d){
  var k=d.kpi||{};
  document.getElementById('k-month').textContent = fmtNum(k.sent_month);
  document.getElementById('k-today').textContent = fmtNum(k.sent_today);
  document.getElementById('k-total').textContent = fmtNum(k.sent_total);
  document.getElementById('k-read').textContent  = (k.read_rate||0)+'%';
  document.getElementById('k-optin').textContent = fmtNum(k.opt_in_users);

  cachedCounts = d.audiences||{};
  document.querySelectorAll('[data-count]').forEach(function(el){
    el.textContent = fmtNum(cachedCounts[el.dataset.count] || 0);
  });
  document.getElementById('recipients').textContent = fmtNum(cachedCounts[selectedAudience] || 0);

  var box=document.getElementById('history');
  var rows=d.recent||[];
  if(!rows.length){ box.innerHTML='<div style="padding:20px;color:var(--text-muted);text-align:center;">No notifications sent yet.</div>'; return; }
  box.innerHTML = rows.map(function(r){
    var sent=Number(r.sent)||0, read=Number(r.read_count)||0;
    var rate = sent ? Math.round(read*100/sent) : 0;
    return '<div class="hist-item">'
      + '<div class="hist-icon"><i data-lucide="bell"></i></div>'
      + '<div class="hist-info"><div class="hist-title">'+esc(r.title||'—')+'</div><div class="hist-meta">'+esc(r.body||'')+' · <em>'+esc(r.type||'')+'</em> · '+fmtDate(r.last_at)+'</div></div>'
      + '<div class="hist-stats"><div><div class="hist-stat-val">'+fmtNum(sent)+'</div><div class="hist-stat-lbl">Sent</div></div><div><div class="hist-stat-val">'+rate+'%</div><div class="hist-stat-lbl">Read</div></div></div>'
      + '</div>';
  }).join('');
  if(window.lucide) lucide.createIcons();
}

function updatePreview(){
  var t=document.getElementById('notif-title').value;
  var b=document.getElementById('notif-body').value;
  document.getElementById('pv-title').textContent = t || 'Title preview';
  document.getElementById('pv-body').textContent  = b || 'Your message preview appears here.';
}

document.querySelectorAll('.audience-chip').forEach(function(chip){
  chip.addEventListener('click', function(){
    document.querySelectorAll('.audience-chip').forEach(function(c){c.classList.remove('selected');});
    chip.classList.add('selected');
    selectedAudience = chip.dataset.aud;
    document.getElementById('recipients').textContent = fmtNum(cachedCounts[selectedAudience]||0);
  });
});

function sendNotification(){
  var t=document.getElementById('notif-title').value.trim();
  var b=document.getElementById('notif-body').value.trim();
  var type=document.getElementById('notif-type').value.trim() || 'admin';
  var msg=document.getElementById('send-msg');
  var btn=document.getElementById('send-btn');
  msg.textContent=''; msg.style.color='';
  if(!t || !b){ msg.textContent='Title and body are required.'; msg.style.color='var(--red)'; return; }
  btn.disabled=true; btn.textContent='Sending...';
  fetch(window.API_BASE+'admin-notifications-send',{
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json'},
    body:JSON.stringify({audience:selectedAudience,title:t,body:b,type:type})
  }).then(function(r){return r.json();}).then(function(res){
    btn.disabled=false; btn.innerHTML='<i data-lucide="send" style="width:13px;height:13px;"></i> Send now';
    if(window.lucide) lucide.createIcons();
    if(res && res.status===true){
      msg.style.color='var(--green)';
      msg.textContent='✓ Sent to '+ (res.data && res.data.recipients || 0) +' users.';
      document.getElementById('notif-title').value='';
      document.getElementById('notif-body').value='';
      updatePreview();
      loadStats();
    } else {
      msg.style.color='var(--red)';
      msg.textContent = (res && res.message) || 'Failed to send.';
    }
  }).catch(function(){
    btn.disabled=false; btn.textContent='Send now';
    msg.style.color='var(--red)'; msg.textContent='Network error.';
  });
}

loadStats();
updatePreview();
</script>

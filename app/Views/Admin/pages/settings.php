<?php
$pageTitle = 'Settings';
$active    = 'settings';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:960px){ .g2 { grid-template-columns:1fr; } }
  .admin-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
  .admin-row:last-child { border-bottom:none; }
  .admin-ava { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:13px; font-weight:700; }
  .toast { position:fixed; bottom:24px; right:24px; padding:10px 14px; background:var(--primary); color:#fff; border-radius:8px; font-size:12px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,.15); display:none; z-index:9999; }
  .toast.show { display:block; }
  .toast.err { background:var(--red); }
</style>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Admin Profile</h3><div class="sub">Your logged-in account</div></div></div>
    <div class="card-body">
      <div class="fg"><label class="fl">Name</label><input id="p-name" type="text" class="fi"></div>
      <div class="fg"><label class="fl">Email</label><input id="p-email" type="email" class="fi"></div>
      <div style="text-align:right;"><button class="btn btn-pri btn-sm" onclick="saveProfile()"><i data-lucide="save" style="width:13px;height:13px;"></i> Update Profile</button></div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><div><h3>Change Password</h3><div class="sub">MD5 hash stored in <code>admin</code> table</div></div></div>
    <div class="card-body">
      <div class="fg"><label class="fl">Current Password</label><input id="pw-current" type="password" class="fi" autocomplete="current-password"></div>
      <div class="fg"><label class="fl">New Password</label><input id="pw-new" type="password" class="fi" autocomplete="new-password"><div class="fhint">Minimum 6 characters</div></div>
      <div class="fg"><label class="fl">Confirm Password</label><input id="pw-confirm" type="password" class="fi" autocomplete="new-password"></div>
      <div style="text-align:right;"><button class="btn btn-pri btn-sm" onclick="changePassword()"><i data-lucide="key" style="width:13px;height:13px;"></i> Change Password</button></div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:14px;">
  <div class="card-head"><div><h3>Twilio SMS</h3><div class="sub">Used by OTP flows (<code>settings</code> table)</div></div></div>
  <div class="card-body">
    <div class="frow">
      <div class="fg"><label class="fl">Account SID</label><input id="t-sid" type="text" class="fi"></div>
      <div class="fg"><label class="fl">Phone Number</label><input id="t-phone" type="text" class="fi" placeholder="+1..."></div>
    </div>
    <div class="fg"><label class="fl">Auth Token</label><input id="t-token" type="password" class="fi" autocomplete="off"><div class="fhint">Stored in plain text in <code>settings.twilio_token</code></div></div>
    <div style="text-align:right;"><button class="btn btn-pri btn-sm" onclick="saveTwilio()"><i data-lucide="save" style="width:13px;height:13px;"></i> Save Twilio</button></div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>All Admins</h3><div class="sub">Accounts in the <code>admin</code> table</div></div></div>
  <div class="card-body" style="padding:8px 20px 16px;">
    <div id="admins-list"><div style="color:var(--text-muted);padding:10px;">Loading...</div></div>
  </div>
</div>

<div id="toast" class="toast"></div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var PALETTE=['#6C5CE7','#14B8A6','#22C55E','#EC4899','#F59E0B','#0EA5E9','#EF4444','#A855F7'];
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }
function initials(name){ if(!name) return '?'; var p=name.trim().split(/\s+/); return ((p[0]||'?')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
function colorFor(id){ return PALETTE[(parseInt(id||0,10))%PALETTE.length]; }
function toast(msg, isErr){ var t=document.getElementById('toast'); t.textContent=msg; t.className='toast show'+(isErr?' err':''); setTimeout(function(){ t.classList.remove('show'); }, 2500); }

function currentAdminId(){
  try {
    var a = JSON.parse(localStorage.getItem('wern_admin_auth') || '{}');
    return (a.admin && a.admin.id) ? a.admin.id : 1;
  } catch(e){ return 1; }
}

function load(){
  fetch(window.API_BASE+'admin-settings-get?admin_id='+currentAdminId(), {headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(res){ if(!res||res.status!==true) throw 0; render(res.data); })
    .catch(function(){ toast('Failed to load settings', true); });
}

function render(d){
  var a = d.admin || {};
  document.getElementById('p-name').value  = a.name  || '';
  document.getElementById('p-email').value = a.email || '';

  var t = d.twilio || {};
  document.getElementById('t-sid').value   = t.twilio_sid   || '';
  document.getElementById('t-token').value = t.twilio_token || '';
  document.getElementById('t-phone').value = t.twilio_phone || '';

  var list = d.admins || [];
  var box = document.getElementById('admins-list');
  if (!list.length) { box.innerHTML='<div style="color:var(--text-muted);padding:10px;">No admin accounts.</div>'; }
  else {
    box.innerHTML = list.map(function(x){
      var st = Number(x.status)===1 ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>';
      return '<div class="admin-row">'
        + '<div class="admin-ava" style="background:'+colorFor(x.id)+';">'+initials(x.name)+'</div>'
        + '<div style="flex:1;"><div style="font-weight:600;font-size:13px;">'+esc(x.name)+'</div><div style="font-size:11px;color:var(--text-muted);">'+esc(x.email)+' · joined '+(x.created_at||'').slice(0,10)+'</div></div>'
        + st
        + '</div>';
    }).join('');
  }
}

function saveProfile(){
  var payload = {
    id:    currentAdminId(),
    name:  document.getElementById('p-name').value.trim(),
    email: document.getElementById('p-email').value.trim(),
  };
  post('admin-settings-update-profile', payload, function(){
    // Keep the display name (used for the UI greeting) in sync.
    try { localStorage.setItem('wern_admin_name', payload.name); } catch(e){}
    load();
  });
}

function changePassword(){
  var payload = {
    id: currentAdminId(),
    current_password: document.getElementById('pw-current').value,
    new_password:     document.getElementById('pw-new').value,
    confirm_password: document.getElementById('pw-confirm').value,
  };
  post('admin-settings-change-password', payload, function(){
    document.getElementById('pw-current').value='';
    document.getElementById('pw-new').value='';
    document.getElementById('pw-confirm').value='';
  });
}

function saveTwilio(){
  var payload = {
    twilio_sid:   document.getElementById('t-sid').value.trim(),
    twilio_token: document.getElementById('t-token').value.trim(),
    twilio_phone: document.getElementById('t-phone').value.trim(),
  };
  post('admin-settings-update-twilio', payload);
}

function post(endpoint, body, onSuccess){
  fetch(window.API_BASE+endpoint, {
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify(body)
  }).then(function(r){return r.json();}).then(function(res){
    if (res && res.status===true) { toast(res.message || 'Saved'); if (onSuccess) onSuccess(); }
    else { toast((res && res.message) || 'Save failed', true); }
  }).catch(function(){ toast('Network error', true); });
}

load();
</script>

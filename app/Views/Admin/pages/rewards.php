<?php
$pageTitle = 'Rewards';
$active    = 'rewards';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>
<style>
  .g2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:960px){ .g2 { grid-template-columns:1fr; } }
  .set-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border); }
  .set-row:last-child { border-bottom:none; }
  .set-info h5 { font-size:12.5px; font-weight:600; margin:0; }
  .set-info p { font-size:11px; color:var(--text-muted); margin:2px 0 0; }
  .toast { position:fixed; bottom:24px; right:24px; padding:10px 14px; background:var(--primary); color:#fff; border-radius:8px; font-size:12px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,.15); display:none; z-index:9999; }
  .toast.show { display:block; }
  .toast.err { background:var(--red); }
</style>

<div class="stats">
  <div class="stat"><div class="stat-top"><div class="stat-icon amber"><i data-lucide="gift"></i></div></div><div class="stat-val" id="k-claims">0</div><div class="stat-lbl">Claims Today</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon red"><i data-lucide="flame"></i></div></div><div class="stat-val" id="k-streak">0</div><div class="stat-lbl">Avg Streak (days)</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon primary"><i data-lucide="coins"></i></div></div><div class="stat-val" id="k-litties">0</div><div class="stat-lbl">Litties Today</div></div>
  <div class="stat"><div class="stat-top"><div class="stat-icon teal"><i data-lucide="percent"></i></div></div><div class="stat-val" id="k-rate">0%</div><div class="stat-lbl">Claim Rate</div></div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><div><h3>Daily Check-in Bonus</h3><div class="sub">Litties by streak day (<code>sys_daily_bonus</code>)</div></div></div>
    <div style="overflow-x:auto;">
      <table class="tbl">
        <thead><tr><th>Day</th><th>Reward</th></tr></thead>
        <tbody id="daily-body"><tr><td colspan="2" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
      </table>
    </div>
    <div style="padding:14px 20px;text-align:right;"><button class="btn btn-pri btn-sm" onclick="saveDaily()">Save Daily</button></div>
  </div>

  <div class="card">
    <div class="card-head"><div><h3>Signup Rewards</h3><div class="sub">Bonuses for new account creation</div></div></div>
    <div class="card-body" style="padding:0 20px;">
      <div class="set-row"><div class="set-info"><h5>Sign-up Points</h5><p>Base points on account creation</p></div><input id="s-sign_up_points" type="number" class="fi" style="width:100px;text-align:center;"></div>
      <div class="set-row"><div class="set-info"><h5>New User Bonus (with referral)</h5><p>Extra when joining via a code</p></div><input id="s-sign_up_bonus_new_user" type="number" class="fi" style="width:100px;text-align:center;"></div>
      <div class="set-row"><div class="set-info"><h5>Existing User Bonus</h5><p>Paid to the referrer</p></div><input id="s-sign_up_bonus_existing_user" type="number" class="fi" style="width:100px;text-align:center;"></div>
      <div class="set-row"><div class="set-info"><h5>No-Referral New User Bonus</h5><p>Extra even without a code</p></div><input id="s-without_refferal_bonus_new_user" type="number" class="fi" style="width:100px;text-align:center;"></div>
      <div class="set-row"><div class="set-info"><h5>Sign-in Bonus</h5><p>Login bonus (<code>sys_settings.sign_in_bonus</code>)</p></div><input id="s-sign_in_bonus" type="number" class="fi" style="width:100px;text-align:center;"></div>
    </div>
    <div style="padding:14px 20px;text-align:right;"><button class="btn btn-pri btn-sm" onclick="saveSignup()">Save Signup</button></div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><h3>Referral Rewards</h3><div class="sub">Points on successful referrals (<code>sys_settings</code>)</div></div></div>
  <div class="card-body" style="padding:0 20px;">
    <div class="set-row"><div class="set-info"><h5>Referrer Bonus</h5><p>Awarded to the inviter (<code>refferal_bonus</code>)</p></div><input id="r-refferal_bonus" type="number" class="fi" style="width:100px;text-align:center;"></div>
    <div class="set-row"><div class="set-info"><h5>Referee Bonus</h5><p>Awarded to the new user (<code>with_rrefferal_bonus</code>)</p></div><input id="r-with_rrefferal_bonus" type="number" class="fi" style="width:100px;text-align:center;"></div>
  </div>
  <div style="padding:14px 20px;text-align:right;"><button class="btn btn-pri btn-sm" onclick="saveReferral()">Save Referral</button></div>
</div>

<div id="toast" class="toast"></div>

<?php echo view('Admin/includes/footer'); ?>

<script>
var state = { daily:null, sys:null, settings:null };
var DAY_KEYS = ['first_day','second_day','third_day','fourth_day','fifth_day','sixth_day','seventh_day'];

function fmtNum(n){ return (Number(n)||0).toLocaleString(); }
function toast(msg, isErr){ var t=document.getElementById('toast'); t.textContent=msg; t.className='toast show'+(isErr?' err':''); setTimeout(function(){ t.classList.remove('show'); }, 2500); }

function load(){
  fetch(window.API_BASE+'admin-rewards-stats',{headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(res){ if(!res||res.status!==true) throw 0; render(res.data); })
    .catch(function(){ toast('Failed to load rewards', true); });
}

function render(d){
  var k=d.kpi||{};
  document.getElementById('k-claims').textContent = fmtNum(k.claims_today);
  document.getElementById('k-litties').textContent= fmtNum(k.litties_today);
  document.getElementById('k-rate').textContent   = (k.claim_rate||0)+'%';
  document.getElementById('k-streak').textContent = (k.avg_streak||0);

  state.daily    = d.daily_bonus || {};
  state.sys      = d.sys_settings|| {};
  state.settings = d.settings    || {};

  // Daily table
  var tb=document.getElementById('daily-body');
  tb.innerHTML = DAY_KEYS.map(function(k,i){
    var day = i+1;
    var val = Number(state.daily[k])||0;
    var highlight = day===7 ? 'style="color:var(--primary);font-weight:700;"' : '';
    return '<tr><td><strong>Day '+day+'</strong></td><td '+highlight+'><input type="number" class="fi day-val" data-k="'+k+'" value="'+val+'" style="width:90px;text-align:center;"></td></tr>';
  }).join('');

  // Signup inputs
  ['sign_up_points','sign_up_bonus_new_user','sign_up_bonus_existing_user','without_refferal_bonus_new_user'].forEach(function(k){
    var el=document.getElementById('s-'+k); if(el) el.value = Number(state.settings[k])||0;
  });
  document.getElementById('s-sign_in_bonus').value = Number(state.sys.sign_in_bonus)||0;

  // Referral inputs
  document.getElementById('r-refferal_bonus').value       = Number(state.sys.refferal_bonus)||0;
  document.getElementById('r-with_rrefferal_bonus').value  = Number(state.sys.with_rrefferal_bonus)||0;
}

function saveDaily(){
  var payload={};
  document.querySelectorAll('.day-val').forEach(function(inp){ payload[inp.dataset.k] = parseInt(inp.value,10)||0; });
  post('admin-rewards-save-daily', payload);
}
function saveSignup(){
  var payload = {};
  ['sign_up_points','sign_up_bonus_new_user','sign_up_bonus_existing_user','without_refferal_bonus_new_user','sign_in_bonus'].forEach(function(k){
    payload[k] = parseInt(document.getElementById('s-'+k).value,10)||0;
  });
  post('admin-rewards-save-signup', payload);
}
function saveReferral(){
  var payload = {
    refferal_bonus:       parseInt(document.getElementById('r-refferal_bonus').value,10)||0,
    with_rrefferal_bonus: parseInt(document.getElementById('r-with_rrefferal_bonus').value,10)||0,
  };
  post('admin-rewards-save-referral', payload);
}

function post(endpoint, body){
  fetch(window.API_BASE+endpoint,{
    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify(body)
  }).then(function(r){return r.json();}).then(function(res){
    if(res && res.status===true){ toast(res.message||'Saved'); load(); }
    else { toast((res&&res.message)||'Save failed', true); }
  }).catch(function(){ toast('Network error', true); });
}

load();
</script>

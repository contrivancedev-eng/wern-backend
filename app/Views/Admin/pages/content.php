<?php
$pageTitle = 'Content';
$active    = 'content';
echo view('Admin/includes/header',  ['pageTitle'=>$pageTitle,'active'=>$active]);
echo view('Admin/includes/sidebar', ['pageTitle'=>$pageTitle,'active'=>$active]);
?>

      <div class="card">
        <div class="tabs">
          <button class="tab active" data-tab="tab-banners">Banners</button>
          <button class="tab" data-tab="tab-products">Products</button>
          <button class="tab" data-tab="tab-onboarding">Onboarding</button>
        </div>

        <div class="tab-pane active" id="tab-banners">
          <div class="card-head" style="border-bottom:none;"><div><h3>Banner Carousel</h3><div class="sub">Home screen banners (max 5)</div></div><button class="btn btn-pri btn-sm" data-modal="banner-modal"><i data-lucide="plus" style="width:13px;height:13px;"></i> Add</button></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
              <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
                <div style="height:110px;background:linear-gradient(135deg,#6C5CE7,#A29BFE);display:flex;align-items:center;justify-content:center;color:#fff;"><div style="text-align:center;"><i data-lucide="footprints" style="width:24px;height:24px;margin-bottom:4px;"></i><div style="font-weight:700;font-size:14px;">Walk & Earn</div></div></div>
                <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;"><div><div class="fw-600 fs-sm">Banner 1</div><div class="text-muted" style="font-size:10.5px;">Position 1</div></div><div class="flex gap-4"><button class="btn btn-ghost btn-icon btn-sm" onclick="editBanner(1)"><i data-lucide="pencil" style="width:13px;height:13px;"></i></button><button class="btn btn-ghost btn-icon btn-sm" onclick="deleteBanner(1)"><i data-lucide="trash-2" style="width:13px;height:13px;color:var(--red);"></i></button></div></div>
              </div>
              <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
                <div style="height:110px;background:linear-gradient(135deg,#22C55E,#86EFAC);display:flex;align-items:center;justify-content:center;color:#fff;"><div style="text-align:center;"><i data-lucide="tree-pine" style="width:24px;height:24px;margin-bottom:4px;"></i><div style="font-weight:700;font-size:14px;">Plant Trees</div></div></div>
                <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;"><div><div class="fw-600 fs-sm">Banner 2</div><div class="text-muted" style="font-size:10.5px;">Position 2</div></div><div class="flex gap-4"><button class="btn btn-ghost btn-icon btn-sm" onclick="editBanner(2)"><i data-lucide="pencil" style="width:13px;height:13px;"></i></button><button class="btn btn-ghost btn-icon btn-sm" onclick="deleteBanner(2)"><i data-lucide="trash-2" style="width:13px;height:13px;color:var(--red);"></i></button></div></div>
              </div>
              <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
                <div style="height:110px;background:linear-gradient(135deg,#EC4899,#F9A8D4);display:flex;align-items:center;justify-content:center;color:#fff;"><div style="text-align:center;"><i data-lucide="gift" style="width:24px;height:24px;margin-bottom:4px;"></i><div style="font-weight:700;font-size:14px;">Daily Rewards</div></div></div>
                <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;"><div><div class="fw-600 fs-sm">Banner 3</div><div class="text-muted" style="font-size:10.5px;">Position 3</div></div><div class="flex gap-4"><button class="btn btn-ghost btn-icon btn-sm" onclick="editBanner(3)"><i data-lucide="pencil" style="width:13px;height:13px;"></i></button><button class="btn btn-ghost btn-icon btn-sm" onclick="deleteBanner(3)"><i data-lucide="trash-2" style="width:13px;height:13px;color:var(--red);"></i></button></div></div>
              </div>
              <div style="border:2px dashed var(--border);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;min-height:152px;cursor:pointer;" data-modal="banner-modal"><div style="text-align:center;color:var(--text-muted);"><i data-lucide="plus-circle" style="width:24px;height:24px;margin-bottom:4px;"></i><div class="fs-sm">Add Banner</div></div></div>
            </div>
          </div>
        </div>

        <div class="tab-pane" id="tab-products">
          <div class="empty">
            <div class="empty-icon"><i data-lucide="package"></i></div>
            <h4>No Products Yet</h4>
            <p>Add redeemable products for your users' Litties.</p>
            <button class="btn btn-pri" data-modal="product-modal"><i data-lucide="plus" style="width:13px;height:13px;"></i> Add Product</button>
          </div>
        </div>

        <div class="tab-pane" id="tab-onboarding">
          <div class="empty">
            <div class="empty-icon"><i data-lucide="smartphone"></i></div>
            <h4>Onboarding Screens</h4>
            <p>Configured in app code. Contact development team for changes.</p>
          </div>
        </div>
      </div>

<div class="modal-bg" id="banner-modal"><div class="modal">
  <div class="modal-head"><h3>Add Banner</h3><button class="modal-close"><i data-lucide="x" style="width:16px;height:16px;"></i></button></div>
  <div class="modal-body">
    <div class="fg"><label class="fl">Title</label><input type="text" class="fi" placeholder="e.g., Walk & Earn"></div>
    <div class="fg"><label class="fl">Subtitle</label><input type="text" class="fi" placeholder="e.g., Start your journey"></div>
    <div class="fg"><label class="fl">Image</label><div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:24px;text-align:center;cursor:pointer;"><i data-lucide="upload" style="width:20px;height:20px;color:var(--text-muted);margin-bottom:4px;"></i><div class="text-muted fs-sm">Click to upload (1080x540)</div></div></div>
    <div class="frow"><div class="fg"><label class="fl">Position</label><select class="fsel"><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select></div><div class="fg"><label class="fl">Link (optional)</label><input type="url" class="fi" placeholder="https://..."></div></div>
  </div>
  <div class="modal-foot"><button class="btn btn-sec btn-sm" data-dismiss>Cancel</button><button class="btn btn-pri btn-sm" onclick="showToast('Banner added','success')">Save</button></div>
</div></div>

<div class="modal-bg" id="product-modal"><div class="modal">
  <div class="modal-head"><h3>Add Product</h3><button class="modal-close"><i data-lucide="x" style="width:16px;height:16px;"></i></button></div>
  <div class="modal-body">
    <div class="fg"><label class="fl">Name</label><input type="text" class="fi" placeholder="e.g., WERN T-Shirt"></div>
    <div class="fg"><label class="fl">Description</label><textarea class="fta" placeholder="Describe..."></textarea></div>
    <div class="frow"><div class="fg"><label class="fl">Price (Litties)</label><input type="number" class="fi" placeholder="5000"></div><div class="fg"><label class="fl">Stock</label><input type="number" class="fi" placeholder="100"></div></div>
    <div class="fg"><label class="fl">Image</label><div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:24px;text-align:center;cursor:pointer;"><i data-lucide="upload" style="width:20px;height:20px;color:var(--text-muted);margin-bottom:4px;"></i><div class="text-muted fs-sm">Click to upload</div></div></div>
  </div>
  <div class="modal-foot"><button class="btn btn-sec btn-sm" data-dismiss>Cancel</button><button class="btn btn-pri btn-sm" onclick="showToast('Product added','success')">Add</button></div>
</div></div>

<?php echo view('Admin/includes/footer'); ?>

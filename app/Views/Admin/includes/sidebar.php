<?php
$active    = $active ?? '';
$assets    = base_url('public/admin/assets');
$adminBase = base_url('admin');
$nav = [
    ['grp' => 'Overview',   'items' => [
        ['key' => 'dashboard',     'url' => $adminBase . '/dashboard',     'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        ['key' => 'analytics',     'url' => $adminBase . '/analytics',     'icon' => 'bar-chart-3',      'label' => 'Analytics'],
    ]],
    ['grp' => 'Management', 'items' => [
        ['key' => 'users',         'url' => $adminBase . '/users',         'icon' => 'users',            'label' => 'Users'],
        ['key' => 'notifications', 'url' => $adminBase . '/notifications', 'icon' => 'bell',             'label' => 'Notifications'],
        ['key' => 'reviews',       'url' => $adminBase . '/reviews',       'icon' => 'star',             'label' => 'Reviews'],
        ['key' => 'transactions',  'url' => $adminBase . '/transactions',  'icon' => 'wallet',           'label' => 'Transactions'],
        ['key' => 'referrals',     'url' => $adminBase . '/referrals',     'icon' => 'share-2',          'label' => 'Referrals'],
    ]],
    ['grp' => 'Content',    'items' => [
        ['key' => 'causes',        'url' => $adminBase . '/causes',        'icon' => 'heart',            'label' => 'Causes'],
        ['key' => 'rewards',       'url' => $adminBase . '/rewards',       'icon' => 'gift',             'label' => 'Rewards'],
        ['key' => 'content',       'url' => $adminBase . '/content',       'icon' => 'image',            'label' => 'Content'],
    ]],
    ['grp' => 'System',     'items' => [
        ['key' => 'settings',      'url' => $adminBase . '/settings',      'icon' => 'settings',         'label' => 'Settings'],
    ]],
];
?>
<aside class="sidebar">
  <div class="sidebar-brand"><img src="<?= $assets ?>/img/logo.png" alt="WERN"><h2>WERN <span>Admin</span></h2></div>
  <nav class="sidebar-nav">
    <?php foreach ($nav as $group): ?>
      <div class="nav-group">
        <div class="nav-label"><?= esc($group['grp']) ?></div>
        <?php foreach ($group['items'] as $it): ?>
          <a href="<?= $it['url'] ?>" class="nav-item<?= $active === $it['key'] ? ' active' : '' ?>"><i data-lucide="<?= $it['icon'] ?>"></i> <?= esc($it['label']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer"><a href="#" onclick="logout();return false;" class="nav-item"><i data-lucide="log-out"></i> Logout</a></div>
</aside>
<div class="main">
<header class="header">
  <div class="header-left">
    <button class="menu-toggle"><i data-lucide="menu" style="width:18px;height:18px;"></i></button>
    <h1 class="page-title"><?= esc($pageTitle ?? '') ?></h1>
  </div>
  <div class="header-right">
    <div class="header-search"><i data-lucide="search"></i><input type="text" placeholder="Search..."></div>
    <button class="icon-btn"><i data-lucide="bell" style="width:18px;height:18px;"></i><span class="dot"></span></button>
    <div class="avatar-wrap">
      <div class="avatar" style="cursor:pointer;">A</div>
      <div class="avatar-menu">
        <a href="<?= $adminBase ?>/settings"><i data-lucide="user"></i> Admin Profile</a>
        <a href="<?= $adminBase ?>/settings"><i data-lucide="settings"></i> Settings</a>
        <div class="divider"></div>
        <button class="logout-item" onclick="logout()"><i data-lucide="log-out"></i> Logout</button>
      </div>
    </div>
  </div>
</header>
<div class="content">

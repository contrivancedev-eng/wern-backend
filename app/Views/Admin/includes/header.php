<?php
$assets    = base_url('public/admin/assets');
$adminBase = base_url('admin');
$pageTitle = $pageTitle ?? 'Dashboard';
$active    = $active ?? '';
// Path portions only — the JS below prefixes location.origin so every API/admin
// call stays on the SAME origin the admin is actually browsing (www/apex,
// http/https). That keeps the session cookie flowing and avoids CORS entirely.
$apiPath   = rtrim(parse_url(base_url('api'),   PHP_URL_PATH) ?: '/api',   '/');
$adminPath = rtrim(parse_url(base_url('admin'), PHP_URL_PATH) ?: '/admin', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WERN Admin - <?= esc($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $assets ?>/css/styles.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    // Authentication is enforced server-side: the login_check filter on the
    // admin routes redirects here to /admin/login when the session is missing
    // or expired, so no client-side guard is needed.
    window.ADMIN_BASE = location.origin + '<?= $adminPath ?>';
    window.API_BASE   = location.origin + '<?= $apiPath ?>/';
    window.ASSETS     = '<?= $assets ?>';
    function logout(){ try{localStorage.removeItem('wern_admin_name');}catch(e){} location.href = window.ADMIN_BASE + '/logout'; }
  </script>
</head>
<body>
<div class="app">

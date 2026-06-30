<?php
$webAssets = base_url('public/web/assets');
$webBase   = base_url();
$pageTitle = $pageTitle ?? 'WERN';
$extraCss  = $extraCss  ?? [];
?>
<!doctype html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($pageTitle) ?></title>
    <link rel="icon" type="image/icon" href="<?= $webAssets ?>/img/favicon.png">

    <link rel="stylesheet" href="<?= $webAssets ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://kit-pro.fontawesome.com/releases/v7.2.0/css/pro.min.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/animate.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/swiper.min.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/odometer.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/mousecursor.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/nice-select.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/custom-fonts.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/magnific-popup.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/jquery-ui.css">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/main.css?v=1.7">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/theme.css?v=1.1">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/responsive.css?v=1.1">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/wern-form.css?v=1.1">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/3d-slider.css?v=1.1">
    <link rel="stylesheet" href="<?= $webAssets ?>/css/slideder.css?v=1.1">
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= $webAssets ?>/css/<?= esc($css) ?>">
    <?php endforeach; ?>
</head>
<body class="ai-agency">

    <div class="xb-backtotop">
        <a href="#" class="scroll"><i class="far fa-arrow-up"></i></a>
    </div>

    <div id="preloader" class="preloader">
        <div class="loader-circle"></div>
    </div>

    <div class="body_wrap o-clip">

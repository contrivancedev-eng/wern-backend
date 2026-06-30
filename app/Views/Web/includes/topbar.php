<?php
$webAssets = base_url('public/web/assets');
$webBase   = base_url();
?>
<header id="xb-header-area" class="header-area header-style--two header-transparent is-sticky">
    <div class="xb-header stricky">
        <div class="container">
            <div class="header__wrap xb-border ul_li_between pe-lg-4">
                <div class="xb-header-logo">
                    <a href="<?= $webBase ?>" class="logo1">
                        <img src="<?= $webAssets ?>/img/logo/logo.png" alt="WERN" style="width: 177px;">
                    </a>
                </div>
                <div class="main-menu__wrap navbar navbar-expand-lg p-0">
                    <nav class="main-menu collapse navbar-collapse">
                        <ul>
                            <li class="scrollspy-btn active"><a href="<?= $webBase ?>#home"><span>Home</span></a></li>
                            <li><a class="scrollspy-btn" href="<?= $webBase ?>#major-features"><span>Major Features</span></a></li>
                            <li><a class="scrollspy-btn" href="<?= $webBase ?>#features"><span>All Features</span></a></li>
                            <li><a class="scrollspy-btn" href="<?= $webBase ?>#process"><span>Process</span></a></li>
                            <li><a class="scrollspy-btn" href="<?= $webBase ?>#coming-soon"><span>Upcoming</span></a></li>
                            <li><a class="scrollspy-btn" href="<?= $webBase ?>#stats"><span>Statistics</span></a></li>
                        </ul>
                    </nav>
                </div>

                <div class="store-badges">
                    <a href="https://apps.apple.com/in/app/wern-walk-track-empower/id6761259828#information" target="_blank" rel="noopener" aria-label="Download on the App Store">
                        <img src="<?= $webAssets ?>/img/pricing/appstore.png" alt="App Store">
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.wern.app&hl=en_IN" target="_blank" rel="noopener" aria-label="Get it on Google Play">
                        <img src="<?= $webAssets ?>/img/pricing/playstore.png" alt="Google Play">
                    </a>
                </div>

                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                    <i class="far fa-sun-alt"></i>
                    <i class="far fa-moon"></i>
                </button>

                <div class="header-bar-mobile side-menu d-lg-none">
                    <a class="xb-nav-mobile" href="javascript:void(0);"><i class="far fa-bars"></i></a>
                </div>
            </div>
            <div class="xb-header-wrap">
                <div class="xb-header-menu">
                    <div class="xb-header-menu-scroll">
                        <div class="xb-menu-close xb-hide-xl xb-close"></div>
                        <div class="xb-logo-mobile xb-hide-xl">
                            <a href="<?= $webBase ?>" rel="home">
                                <img src="<?= $webAssets ?>/img/logo/logo.png" alt="WERN" style="height: 45px;">
                            </a>
                            <button id="theme-toggle-mobile" class="theme-toggle" aria-label="Toggle theme" onclick="toggleTheme()" style="margin-left: auto;">
                                <i class="far fa-sun-alt"></i>
                                <i class="far fa-moon"></i>
                            </button>
                        </div>
                        <nav class="xb-header-nav">
                            <ul class="xb-menu-primary clearfix">
                                <li class="scrollspy-btn"><a href="<?= $webBase ?>#home"><span>Home</span></a></li>
                                <li><a class="scrollspy-btn" href="<?= $webBase ?>#major-features"><span>Major Features</span></a></li>
                                <li><a class="scrollspy-btn" href="<?= $webBase ?>#features"><span>All Features</span></a></li>
                                <li><a class="scrollspy-btn" href="<?= $webBase ?>#process"><span>Process</span></a></li>
                                <li><a class="scrollspy-btn" href="<?= $webBase ?>#coming-soon"><span>Upcoming</span></a></li>
                                <li><a class="scrollspy-btn" href="<?= $webBase ?>#stats"><span>Statistics</span></a></li>
                            </ul>
                        </nav>
                        <div class="store-badges store-badges--mobile">
                            <a href="https://apps.apple.com/in/app/wern-walk-track-empower/id6761259828#information" target="_blank" rel="noopener" aria-label="Download on the App Store">
                                <img src="<?= $webAssets ?>/img/pricing/appstore.png" alt="App Store">
                            </a>
                            <a href="https://play.google.com/store/apps/details?id=com.wern.app&hl=en_IN" target="_blank" rel="noopener" aria-label="Get it on Google Play">
                                <img src="<?= $webAssets ?>/img/pricing/playstore.png" alt="Google Play">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="xb-header-menu-backdrop"></div>
            </div>
        </div>
    </div>
</header>

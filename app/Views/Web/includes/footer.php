<?php
$webAssets = base_url('public/web/assets');
$webBase   = base_url();
?>
<footer class="footer pos-rel z-1 bg_img">
    <div class="ac-footer-wrap mxw-1650 m-0 m-auto wow fadeInUp" data-wow-duration="600ms">
        <div class="container">
            <div class="pos-rel z-1">
                <div class="xb-copyright ul_li_between pt-0">
                    <div><p>Copyright © <?= date('Y') ?> <a href="<?= $webBase ?>">WERN</a>, All rights reserved.</p></div>
                    <ul class="list-unstyled d-flex gap-3" style="font-size: 14px;">
                        <li><a href="<?= base_url('terms-of-service') ?>" style="color: inherit; text-decoration: none;">Terms of Services</a></li>
                        <li>|</li>
                        <li><a href="<?= base_url('privacy-and-data-protection-policy') ?>" style="color: inherit; text-decoration: none;">Privacy &amp; Data Protection Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

    </div><!-- /body_wrap -->

    <script src="<?= $webAssets ?>/js/jquery-3.7.1.min.js"></script>
    <script src="<?= $webAssets ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $webAssets ?>/js/theme-switcher.js"></script>
    <script src="<?= $webAssets ?>/js/imagesloaded.pkgd.min.js"></script>
    <script src="<?= $webAssets ?>/js/isotope.pkgd.min.js"></script>
    <script src="<?= $webAssets ?>/js/easing.min.js"></script>
    <script src="<?= $webAssets ?>/js/scrollspy.js"></script>
    <script src="<?= $webAssets ?>/js/wow.min.js"></script>
    <script src="<?= $webAssets ?>/js/appear.js"></script>
    <script src="<?= $webAssets ?>/js/parallaxie.js"></script>
    <script src="<?= $webAssets ?>/js/parallax-scroll.js"></script>
    <script src="<?= $webAssets ?>/js/imageRevealHover.js"></script>
    <script src="<?= $webAssets ?>/js/jquery.marquee.min.js"></script>
    <script src="<?= $webAssets ?>/js/jquery.magnific-popup.min.js"></script>
    <script src="<?= $webAssets ?>/js/jquery.nice-select.min.js"></script>
    <script src="<?= $webAssets ?>/js/odometer.min.js"></script>
    <script src="<?= $webAssets ?>/js/swiper.min.js"></script>
    <script src="<?= $webAssets ?>/js/plugin.js"></script>
    <script src="<?= $webAssets ?>/js/lenis.js"></script>
    <script src="<?= $webAssets ?>/js/magiccursor.js"></script>
    <script src="<?= $webAssets ?>/js/main.js?v=1.1"></script>
    <script src="<?= $webAssets ?>/js/login.js?v=1.1"></script>
    <?php if (!empty($extraJs)) foreach ($extraJs as $js): ?>
    <script src="<?= $webAssets ?>/js/<?= esc($js) ?>"></script>
    <?php endforeach; ?>
    <?php if (!empty($pageScripts)) foreach ($pageScripts as $s) echo $s; ?>
</body>
</html>

<?php
$pageTitle = 'Step-based Rewards';
echo view('Web/includes/header', ['pageTitle' => $pageTitle]);
echo view('Web/includes/topbar');
?>

<style>
    /* Breadcrumb background for light mode */
    body.light-mode .breadcrumb.bg_img {
        background-image: url('<?= base_url('public/web/assets/img/bg/bootcamp-bg-light.png') ?>') !important;
    }
</style>

<div class="body-overlay"></div>

<!-- main area start -->
<main>
    <!-- hero start -->
    <section class="breadcrumb bg_img" data-background="<?= base_url('public/web/assets/img/bg/bootcamp-bg.png') ?>">
        <div class="container">
            <div class="breadcrumb__content">
                <ul class="breadcrumb__list clearfix list-unstyled">
                    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">home</a></li>
                    <li class="breadcrumb-item dotIcon1 position-relative"><a href="<?= base_url('/') ?>">Features</a>
                    </li>
                    <li class="breadcrumb-item dotIcon2 position-relative"> Step-based Rewards </li>
                </ul>
                <h2 class="breadcrumb__title">Step-based Rewards</h2>
            </div>
        </div>
    </section>
    <!-- hero end -->

    <!-- blog content start  -->
    <section class="blog_details_section pt-70">
        <div class="container">
            <!-- <div class="item_details_content mb-80 pb-70 border-bottom">
                <h2 class="details-content-title mb-15">User Onboarding</h2>
                <p class="mb-35">
                    Frictionless sign-up and orientation with animated walkthroughs, referral-aware access, and
                    user-type selection tailored to your journey. Our onboarding process is designed to give
                    every new user a smooth start, helping them understand key features and navigation within
                    minutes.
                    Interactive tooltips and guided tours ensure that users quickly learn how to make the most
                    of the platform. Personalized dashboards, contextual hints, and goal-based prompts create a
                    learning flow that adapts to each user's needs. Whether you’re an individual or part of a
                    team, our onboarding ensures you feel confident and productive from day one. The process is
                    intuitive, fast, and built to convert first-time visitors into engaged, long-term users.
                </p>
            </div> -->
            <div class="row mt-none-30 featuresDtls g-0 align-items-start">
                <div class="col-lg-8 col-xl-7 mt-30">
                    <div class="blog_details_content">

                        <h3 class="item_details_info_heading mb-25">
                            What it does
                        </h3>
                        <p>
                            WERN converts your movement into Littles: 1,000 steps = 1 Little. We combine sensor fusion, GPS drift guards, and speed anomaly checks to ensure fairness. Earned Littles can be converted to impact tokens or redeemed with partners.
                        </p>

                        <div class="highlights-list mt-45">
                            <h3 class="item_details_info_heading2 mb-15">
                                Highlights
                            </h3>
                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Real-time Tracking</h4>
                                    <p class="highlight-description">Accelerometer + GPS with drift and spoof protection.</p>
                                </div>
                            </div>

                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Anti-Fraud</h4>
                                    <p class="highlight-description">Device attestation and improbable speed detection.</p>
                                </div>
                            </div>

                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="7" y1="2" x2="7" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="17" y1="2" x2="17" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="2" y1="7" x2="7" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="2" y1="17" x2="7" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="17" y1="17" x2="22" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="17" y1="7" x2="22" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Convert or Redeem</h4>
                                    <p class="highlight-description">Turn Littles into impact tokens or partner rewards.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-4 col-xl-5">
                    <div class="sidebar">
                        <!-- Countdown Widget -->
                        <div class="sidebar_widget xb-border at-glance-widget mb-0">
                            <div class="at-glance-wrapper text-center">
                                <h3 class="sidebar_widget_title at-glance-title mb-30">At a glance</h3>
                                <!-- Stats Grid -->
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-number">1,000</div>
                                        <div class="stat-label">Steps → 1 Little</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number stat-positive">+5%</div>
                                        <div class="stat-label">Proximity Bonus Chance</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ul_li_between d-block xb-border try-it-widget mt-30">
                            <div class="xb-item--holder text-start">
                                <h3 class="xb-item--title mb-2">Try it</h3>
                                <p class="mb-25 try-it-description fs-6">
                                    Walk with your device and watch steps convert to Littles in real time.
                                </p>
                                <div class="pricing-btn mb-0 mt-15">
                                    <a class="thm-btn chatbot-btn py-3 px-5" href="<?= base_url('/') ?>">
                                        <span class="text">Back to Features</span>
                                        <span class="btn-bg">
                                            <svg width="750" height="60" viewBox="0 0 750 60" fill="none"
                                                preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="750" height="60"
                                                    fill="url(#paint0_radial_2224_3380)"></rect>
                                                <defs>
                                                    <radialGradient id="paint0_radial_2224_3380" cx="0" cy="0"
                                                        r="1"
                                                        gradientTransform="matrix(-667.5 -25 0.582116 -49.7476 497 39)"
                                                        gradientUnits="userSpaceOnUse">
                                                        <stop offset="0" stop-color="#00FF97"></stop>
                                                        <stop offset="1" stop-color="#00020F" stop-opacity="0">
                                                        </stop>
                                                    </radialGradient>
                                                </defs>
                                            </svg>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- How it looks Section -->
            <div class="how-it-looks-wrapper mt-45">
                <div class="how-it-looks-container">
                    <div class="how-it-looks-content">
                        <h3 class="how-it-looks-title">How it looks</h3>
                        <p class="how-it-looks-description">
                            Clear conversion visuals and streak encouragement, with fraud shield indicators inline.
                        </p>
                    </div>
                    <div class="how-it-looks-preview p-0">
                        <div class="d-flex countLogic align-items-center gap-3">
                            <div class="leftSideStep text-center">
                                <h3>1000</h3>
                                <small>Steps Takend</small>
                            </div>
                            <div class="middle">
                                <h4>=</h4>
                            </div>
                            <div class="rightSideStep text-center">
                                <h3>1</h3>
                                <small>Littie Earned</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- blog content end -->

</main>
<!-- main area end -->

<?php echo view('Web/includes/footer'); ?>

<!-- Countdown Timer Script -->
<script>
    // Set countdown time (24 hours from now)
    function startCountdown() {
        // Set the date we're counting down to (24 hours from now)
        const countDownDate = new Date().getTime() + (24 * 60 * 60 * 1000);

        // Update the countdown every 1 second
        const countdownInterval = setInterval(function () {
            // Get current time
            const now = new Date().getTime();

            // Find the distance between now and the countdown date
            const distance = countDownDate - now;

            // Time calculations for hours, minutes and seconds
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display the result with leading zeros
            document.getElementById("hours").innerHTML = String(hours).padStart(2, '0');
            document.getElementById("minutes").innerHTML = String(minutes).padStart(2, '0');
            document.getElementById("seconds").innerHTML = String(seconds).padStart(2, '0');

            // If the countdown is finished, reset it
            if (distance < 0) {
                clearInterval(countdownInterval);
                // Restart countdown
                startCountdown();
            }
        }, 1000);
    }

    // Start countdown when page loads
    window.addEventListener('load', function () {
        startCountdown();
    });
</script>

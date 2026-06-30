<?php
$pageTitle = 'Daily Social Map';
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
                    <li class="breadcrumb-item dotIcon2 position-relative"> Daily Social Map </li>
                </ul>
                <h2 class="breadcrumb__title">Daily Social Map</h2>
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
                            The Daily Social Map updates continuously to show nearby walkers, community hotspots, and verified safe routes. Switch between Nearby Flags (markers) and Nearby Trails (paths) for different exploration modes.
                        </p>

                        <div class="highlights-list mt-45">
                            <h3 class="item_details_info_heading2 mb-15">
                                Highlights
                            </h3>
                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Activity Hotspots</h4>
                                    <p class="highlight-description">Heat-like overlays reveal popular areas.</p>
                                </div>
                            </div>

                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 12v-1.5c0-4.14-3.36-7.5-7.5-7.5S5 6.36 5 10.5V12m-2 0h18a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 15v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Safety Zones</h4>
                                    <p class="highlight-description">Community-validated safe routes and alerts.</p>
                                </div>
                            </div>

                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="highlight-content">
                                    <h4 class="highlight-title">Flags/Trails Toggle</h4>
                                    <p class="highlight-description">Switch views instantly to match your goal.</p>
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
                                <div class="stats-grid stats-grid-three">
                                    <div class="stat-card">
                                        <div class="stat-number">24</div>
                                        <div class="stat-label">Active Now</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">156</div>
                                        <div class="stat-label">Safe Routes</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">89K</div>
                                        <div class="stat-label">Steps Today</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ul_li_between d-block xb-border try-it-widget mt-30">
                            <div class="xb-item--holder text-start">
                                <h3 class="xb-item--title mb-2">Try it</h3>
                                <p class="mb-25 try-it-description fs-6">
                                    Toggle Flags vs Trails to change how the map guides your walk.
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
                            A crisp map widget with grid overlay and animated markers, optimized for clarity day or night.
                        </p>
                    </div>
                    <div class="how-it-looks-preview p-0">
                        <div class="proximityImg">
                            <img src="<?= base_url('public/web/assets/img/proximity.png') ?>" alt="">
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

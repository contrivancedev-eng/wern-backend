<?php
$pageTitle = 'Privacy & Data Protection Policy | WERN';
echo view('Web/includes/header', ['pageTitle' => $pageTitle]);
echo view('Web/includes/topbar');
?>

<style>
    /* ========================================
       TERMS OF SERVICE PAGE STYLES
       ======================================== */

    /* Terms Content Section - Dark Mode (Default) */
    .terms-content {
        padding: 80px 0;
        background-color: #00020f;
    }

    /* Sidebar */
    .terms-sidebar {
        position: sticky;
        top: 100px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 30px;
        backdrop-filter: blur(10px);
    }

    .terms-sidebar h4 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #fff;
        padding-bottom: 15px;
        border-bottom: 2px solid #00ff97;
    }

    .terms-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 60vh;
        overflow-y: auto;
    }

    .terms-nav::-webkit-scrollbar {
        width: 4px;
    }

    .terms-nav::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 4px;
    }

    .terms-nav::-webkit-scrollbar-thumb {
        background: #00ff97;
        border-radius: 4px;
    }

    .terms-nav li {
        margin-bottom: 6px;
    }

    .terms-nav a {
        display: block;
        padding: 10px 15px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .terms-nav a:hover {
        background: rgba(0, 255, 151, 0.1);
        color: #00ff97;
        border-color: rgba(0, 255, 151, 0.3);
    }

    .terms-nav a.active {
        background: #00ff97;
        color: #00020f;
        font-weight: 600;
    }

    /* Main Content */
    .terms-main {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 50px;
        backdrop-filter: blur(10px);
    }

    /* Introduction Box */
    .terms-intro {
        background: linear-gradient(135deg, rgba(0, 255, 151, 0.1) 0%, rgba(0, 255, 151, 0.03) 100%);
        border-left: 4px solid #00ff97;
        padding: 25px 30px;
        border-radius: 0 12px 12px 0;
        margin-bottom: 40px;
    }

    .terms-intro p {
        margin: 0;
        font-size: 16px;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.85);
    }

    .terms-intro p strong {
        color: #00ff97;
    }

    /* Sections */
    .terms-section {
        margin-bottom: 50px;
        padding-bottom: 40px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .terms-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .terms-section h2 {
        font-size: 26px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .terms-section h2 .section-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #00ff97 0%, #008257 100%);
        color: #00020f;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 700;
    }

    .terms-section h4 {
        font-size: 18px;
        font-weight: 600;
        color: #00ff97;
        margin-top: 25px;
        margin-bottom: 15px;
    }

    .terms-section p {
        font-size: 16px;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.75);
        margin-bottom: 15px;
    }

    .terms-section p strong {
        color: #fff;
    }

    .terms-section ul {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }

    .terms-section ul li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 12px;
        font-size: 15px;
        line-height: 1.7;
        color: rgba(255, 255, 255, 0.75);
    }

    .terms-section ul li::before {
        content: '\f00c';
        font-family: 'Font Awesome 6 Pro', 'Font Awesome 5 Pro', 'FontAwesome';
        font-weight: 900;
        position: absolute;
        left: 0;
        top: 2px;
        color: #00ff97;
        font-size: 14px;
    }

    /* Highlight Box */
    .terms-section .highlight-box {
        background: rgba(0, 255, 151, 0.08);
        border: 1px solid rgba(0, 255, 151, 0.2);
        border-radius: 12px;
        padding: 20px 25px;
        margin: 20px 0;
    }

    .terms-section .highlight-box p {
        margin: 0;
        color: rgba(255, 255, 255, 0.85);
    }

    /* Warning Box */
    .terms-section .warning-box {
        background: rgba(252, 211, 50, 0.08);
        border-left: 4px solid #fcd332;
        border-radius: 0 12px 12px 0;
        padding: 20px 25px;
        margin: 20px 0;
    }

    .terms-section .warning-box p {
        margin: 0;
        color: rgba(255, 255, 255, 0.85);
    }

    .terms-section .warning-box p strong {
        color: #fcd332;
    }

    /* Contact Card */
    .contact-card {
        background: linear-gradient(135deg, #008257 0%, #183e49 100%);
        border-radius: 16px;
        padding: 40px;
        color: #fff;
        margin-top: 30px;
    }

    .contact-card h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #fff;
    }

    .contact-card p {
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 12px;
        font-size: 15px;
    }

    .contact-card p i {
        color: #00ff97;
        margin-right: 10px;
        width: 20px;
    }

    .contact-card a {
        color: #00ff97;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .contact-card a:hover {
        text-decoration: underline;
    }

    /* ========================================
       LIGHT MODE STYLES
       ======================================== */

    body.light-mode .terms-content {
        background-color: #f8f9fa;
    }

    body.light-mode .terms-sidebar {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        backdrop-filter: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    body.light-mode .terms-sidebar h4 {
        color: #1f2937;
        border-bottom-color: #008257;
    }

    body.light-mode .terms-nav a:hover {
        background: linear-gradient(135deg, #008257 0%, #183e49 100%);
        color: #008257;
        border-color: rgba(0, 130, 87, 0.3);
    }

    body.light-mode .terms-nav a.active {
        background: linear-gradient(135deg, #008257 0%, #183e49 100%);
        color: #fff !important;
    }

    body.light-mode .terms-nav::-webkit-scrollbar-thumb {
        background: #008257;
    }

    body.light-mode .terms-main {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        backdrop-filter: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    body.light-mode .terms-intro {
        background: linear-gradient(135deg, rgba(0, 130, 87, 0.1) 0%, rgba(0, 130, 87, 0.03) 100%);
        border-left-color: #008257;
    }

    body.light-mode .terms-intro p {
        color: #374151;
    }

    body.light-mode .terms-intro p strong {
        color: #008257;
    }

    body.light-mode .terms-section {
        border-bottom-color: #e5e7eb;
    }

    body.light-mode .terms-section h2 {
        color: #1f2937;
    }

    body.light-mode .terms-section h2 .section-number {
        background: linear-gradient(135deg, #008257 0%, #183e49 100%);
        color: #fff;
    }

    body.light-mode .terms-section h4 {
        color: #008257;
    }


    body.light-mode .terms-section ul li {
        color: #4b5563;
    }

    body.light-mode .terms-section ul li::before {
        color: #008257;
    }

    body.light-mode .terms-section .highlight-box {
        background: rgba(0, 130, 87, 0.08);
        border-color: rgba(0, 130, 87, 0.2);
    }

    body.light-mode .terms-section .highlight-box p {
        color: #374151;
    }

    body.light-mode .terms-section .warning-box {
        background: rgba(252, 211, 50, 0.15);
    }

    body.light-mode .terms-section .warning-box p {
        color: #374151;
    }

    body.light-mode .terms-section .warning-box p strong {
        color: #b45309;
    }

    body.light-mode .contact-card {
        background: linear-gradient(135deg, #008257 0%, #183e49 100%);
    }

    /* Breadcrumb background for light mode */
    body.light-mode .breadcrumb.bg_img {
        background-image: url('<?= base_url('public/web/assets/img/bg/bootcamp-bg-light.png') ?>') !important;
    }

    /* ========================================
       RESPONSIVE STYLES
       ======================================== */

    @media (max-width: 991px) {
        .terms-content{
            padding-top: 0;
            padding-bottom: 40px;
        }
        .terms-sidebar {
            position: relative;
            top: 0;
            margin-bottom: 30px;
        }

        .terms-nav {
            max-height: 250px;
        }

        .terms-main {
            padding: 30px;
        }
    }

    @media (max-width: 576px) {
        .terms-section h2 {
            font-size: 20px;
        }

        .terms-section h2 .section-number {
            min-width: 38px;
            height: 38px;
            font-size: 15px;
        }

        .terms-intro {
            padding: 20px;
        }

        .contact-card {
            padding: 25px;
        }
    }
</style>

<div class="body-overlay"></div>

<!-- main area start -->
<main>
    <!-- Breadcrumb / Hero Section -->
    <section class="breadcrumb bg_img" data-background="<?= base_url('public/web/assets/img/bg/bootcamp-bg.png') ?>">
        <div class="container">
            <div class="breadcrumb__content">
                <ul class="breadcrumb__list clearfix list-unstyled">
                    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
                    <li class="breadcrumb-item dotIcon1 position-relative"><a href="#">Legal</a></li>
                    <li class="breadcrumb-item dotIcon2 position-relative">Privacy & Data Protection Policy</li>
                </ul>
                <h2 class="breadcrumb__title">Enterprise Privacy & Data Protection Policy</h2>
            </div>
        </div>
    </section>
    <!-- Breadcrumb End -->

    <!-- Terms Content -->
    <section class="terms-content">
        <div class="container">
            <div class="row">
                <!-- Sidebar Navigation -->
                <div class="col-lg-4 d-lg-block d-none">
                    <div class="terms-sidebar wow fadeInLeft" data-wow-duration="600ms" style="visibility: visible; animation-duration: 600ms; animation-name: fadeInLeft;">
                        <h4>Quick Navigation</h4>
                        <ul class="terms-nav">
                            <li><a href="#information-collect" class="">1. Information We Collect</a></li>
                            <li><a href="#how-used" class="">2. How Your Information Is Used</a></li>
                            <li><a href="#information-sharing" class="">3. Information Sharing</a></li>
                            <li><a href="#security">4. Enterprise-Grade Security Measures</a></li>
                            <li><a href="#data-retention">5. Data Retention</a></li>
                            <li><a href="#childrens-privacy">6. Children's Privacy</a></li>
                            <li><a href="#user-rights">7. User &amp; Corporate Rights</a></li>
                            <li><a href="#international-data">8. International Data Transfer</a></li>
                            <li><a href="#cookies">9. Cookies &amp; Tracking</a></li>
                            <li><a href="#data-breach">10. Data Breach Protocol</a></li>
                            <li><a href="#policy-updates">11. Policy Updates</a></li>
                            <li><a href="#governing-law">12. Governing Law</a></li>
                            <li><a href="#contact">13. Contact Information</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="terms-main wow fadeInRight" data-wow-duration="600ms" style="visibility: visible; animation-duration: 600ms; animation-name: fadeInRight;">
                        <!-- Introduction -->
                        <div class="terms-intro">
                            <p><strong>Effective Date:</strong> 04 / 02 / 2026</p>
                            <p style="margin-top: 15px;">
                                WERN is committed to providing a safe, transparent, and secure environment for all users, corporates, and partners. This Privacy &amp; Data Protection Policy explains how we collect, process, store, and protect information within the app.
                            </p>
                        </div>

                        <!-- Section 1: Information We Collect -->
                        <div class="terms-section" id="information-collect">
                            <h2><span class="section-number">1</span> Information We Collect</h2>

                            <h4>Voluntarily Provided Data</h4>
                            <ul>
                                <li>Full name, email, phone number, and profile details</li>
                                <li>Corporate or brand account information</li>
                                <li>Activity tracking for steps, challenges, and engagement</li>
                            </ul>

                            <h4>Automatically Collected Data</h4>
                            <ul>
                                <li>Device and connection information (IP address, operating system, browser, device ID)</li>
                                <li>In-app activity patterns for performance monitoring and feature improvement</li>
                                <li>Location data for app features (opt-in only)</li>
                                <li>Cookies and analytics to optimize app functionality and performance</li>
                            </ul>
                        </div>

                        <!-- Section 2: How Your Information Is Used -->
                        <div class="terms-section" id="how-used">
                            <h2><span class="section-number">2</span> How Your Information Is Used</h2>
                            <p>Data is used solely for the purposes of:</p>
                            <ul>
                                <li>Account creation and verification</li>
                                <li>Accurate activity tracking and milestone recognition</li>
                                <li>Personalized insights, notifications, and engagement guidance</li>
                                <li>Analytics and reporting for app improvement</li>
                                <li>Facilitating communication for challenges and app features</li>
                                <li>Enhancing platform features and long-term product development</li>
                            </ul>
                            <div class="highlight-box">
                                <p>We follow a <strong>strict, purpose-limited data usage model</strong>, ensuring information is never used beyond what is necessary to operate and improve WERN.</p>
                            </div>
                        </div>

                        <!-- Section 3: Information Sharing -->
                        <div class="terms-section" id="information-sharing">
                            <h2><span class="section-number">3</span> Information Sharing</h2>
                            <p>WERN shares information only when necessary and in a controlled manner:</p>

                            <h4>Legal &amp; Regulatory Compliance</h4>
                            <p>Data may be disclosed to comply with applicable UAE and international laws, or to protect the rights, safety, and integrity of WERN and its community.</p>

                            <h4>Trusted Service Providers</h4>
                            <p>Third-party partners supporting:</p>
                            <ul>
                                <li>Cloud hosting</li>
                                <li>Analytics</li>
                                <li>Security</li>
                                <li>Email and communication</li>
                            </ul>
                            <p>All partners are contractually bound to strict confidentiality and data protection standards.</p>

                            <h4>Business Transfers</h4>
                            <p>In mergers, acquisitions, or reorganizations, data may be transferred under secure, compliant protocols.</p>

                            <h4>Public &amp; Community Information</h4>
                            <p>Visible to users only where intended:</p>
                            <ul>
                                <li>Leaderboards</li>
                                <li>Challenges</li>
                                <li>Achievement badges</li>
                                <li>Public profile elements</li>
                            </ul>
                        </div>

                        <!-- Section 4: Enterprise-Grade Security Measures -->
                        <div class="terms-section" id="security">
                            <h2><span class="section-number">4</span> Enterprise-Grade Security Measures</h2>
                            <p>WERN employs multi-layered security, including:</p>
                            <ul>
                                <li>Secure access controls and identity verification</li>
                                <li>Continuous monitoring and threat detection</li>
                            </ul>
                        </div>

                        <!-- Section 5: Data Retention -->
                        <div class="terms-section" id="data-retention">
                            <h2><span class="section-number">5</span> Data Retention</h2>
                            <p>Information is retained only as long as necessary to provide services and meet legal obligations.</p>
                            <p>Aggregated, anonymized data may be used for analysis and platform improvement.</p>
                        </div>

                        <!-- Section 6: Children's Privacy -->
                        <div class="terms-section" id="childrens-privacy">
                            <h2><span class="section-number">6</span> Children's Privacy</h2>
                            <p>WERN is not intended for minors. It may be used under parental supervision.</p>
                            <p>We do not knowingly collect data from minors.</p>
                            <p>If such data is identified, it will be removed immediately.</p>
                        </div>

                        <!-- Section 7: User & Corporate Rights -->
                        <div class="terms-section" id="user-rights">
                            <h2><span class="section-number">7</span> User &amp; Corporate Rights</h2>
                            <p>Users have the following rights:</p>
                            <ul>
                                <li>Access your information</li>
                                <li>Request updates or corrections</li>
                                <li>Request account and data deletion</li>
                                <li>Manage notification and communication settings</li>
                                <li>Disable location services at any time</li>
                                <li>Request data portability</li>
                                <li>Object to certain processing activities</li>
                            </ul>
                            <p>Requests may be made through account settings or via email.</p>
                        </div>

                        <!-- Section 8: International Data Transfer -->
                        <div class="terms-section" id="international-data">
                            <h2><span class="section-number">8</span> International Data Transfer</h2>
                            <p>WERN may process and store information in jurisdictions including the United Arab Emirates.</p>
                            <p>All transfers comply with recognized safeguarding standards to ensure secure and lawful processing.</p>
                        </div>

                        <!-- Section 9: Cookies & Tracking -->
                        <div class="terms-section" id="cookies">
                            <h2><span class="section-number">9</span> Cookies &amp; Tracking</h2>
                            <p>Used to:</p>
                            <ul>
                                <li>Improve user experience</li>
                                <li>Enable app functionality</li>
                                <li>Analyze engagement and performance</li>
                            </ul>
                            <p>Users may manage their cookie preferences; disabling cookies may limit functionality.</p>
                        </div>

                        <!-- Section 10: Data Breach Protocol -->
                        <div class="terms-section" id="data-breach">
                            <h2><span class="section-number">10</span> Data Breach Protocol</h2>
                            <p>In the event of a data breach, WERN will promptly notify affected users and relevant authorities in accordance with applicable laws.</p>
                        </div>

                        <!-- Section 11: Policy Updates -->
                        <div class="terms-section" id="policy-updates">
                            <h2><span class="section-number">11</span> Policy Updates</h2>
                            <p>Material changes will be reflected in the "Last Updated" date.</p>
                            <p>Updates will be communicated via email or in-app notifications.</p>
                        </div>

                        <!-- Section 12: Governing Law -->
                        <div class="terms-section" id="governing-law">
                            <h2><span class="section-number">12</span> Governing Law</h2>
                            <p>This policy is governed by the laws of the UAE.</p>
                            <p>Any disputes fall under the jurisdiction of UAE courts.</p>
                        </div>

                        <!-- Section 13: Contact Information -->
                        <div class="terms-section" id="contact">
                            <h2><span class="section-number">13</span> Contact Information</h2>
                            <div class="contact-card">
                                <h3>WERN Privacy Team</h3>
                                <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Address: EREC 20, Floor 1, Al Danah, Abu Dhabi, UAE</p>
                                <p><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email: <a href="mailto:technical@projectliberte.io">technical@projectliberte.io</a></p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<!-- main area end -->

<?php echo view('Web/includes/footer'); ?>

<!-- Smooth scroll for sidebar navigation -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navLinks = document.querySelectorAll('.terms-nav a');
        const termsNav = document.querySelector('.terms-nav');

        // Smooth scroll for sidebar links
        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    const headerOffset = 100;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Update active state
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Scroll sidebar to show active link
                    scrollSidebarToActive(this);
                }
            });
        });

        // Function to scroll sidebar to keep active item visible
        function scrollSidebarToActive(activeLink) {
            if (!termsNav || !activeLink) return;

            const navRect = termsNav.getBoundingClientRect();
            const linkRect = activeLink.getBoundingClientRect();
            const linkOffsetTop = activeLink.offsetTop;
            const navScrollTop = termsNav.scrollTop;
            const navHeight = termsNav.clientHeight;
            const linkHeight = activeLink.offsetHeight;

            // Calculate if link is outside visible area
            const linkTopRelative = linkOffsetTop - navScrollTop;
            const linkBottomRelative = linkTopRelative + linkHeight;

            // If link is above visible area, scroll up
            if (linkTopRelative < 0) {
                termsNav.scrollTo({
                    top: linkOffsetTop - 10,
                    behavior: 'smooth'
                });
            }
            // If link is below visible area, scroll down
            else if (linkBottomRelative > navHeight) {
                termsNav.scrollTo({
                    top: linkOffsetTop - navHeight + linkHeight + 10,
                    behavior: 'smooth'
                });
            }
        }

        // Update active link on scroll
        window.addEventListener('scroll', function () {
            const sections = document.querySelectorAll('.terms-section');
            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                    // Auto-scroll sidebar to keep active item visible
                    scrollSidebarToActive(link);
                }
            });
        });
    });
</script>

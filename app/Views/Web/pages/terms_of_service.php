<?php
$pageTitle = 'Terms of Service | WERN';
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
                    <li class="breadcrumb-item dotIcon2 position-relative">Terms of Service</li>
                </ul>
                <h2 class="breadcrumb__title">Terms of Service</h2>
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
                            <li><a href="#eligibility" class="">1. Eligibility</a></li>
                            <li><a href="#user-accounts" class="">2. User Accounts</a></li>
                            <li><a href="#permitted-use" class="">3. Permitted &amp; Prohibited Use</a></li>
                            <li><a href="#health-safety" class="">4. Health &amp; Safety Disclaimer</a></li>
                            <li><a href="#recognition" class="">5. Recognition &amp; Achievements</a></li>
                            <li><a href="#brand-partners" class="">6. Brand &amp; Partner Features</a></li>
                            <li><a href="#ugc" class="">7. User-Generated Content (UGC)</a></li>
                            <li><a href="#privacy" class="">8. Data Collection &amp; Privacy</a></li>
                            <li><a href="#location" class="">9. Location Features</a></li>
                            <li><a href="#referral" class="">10. Referral Program</a></li>
                            <li><a href="#ip" class="">11. Intellectual Property</a></li>
                            <li><a href="#disclaimers" class="">12. Disclaimers</a></li>
                            <li><a href="#liability" class="">13. Limitation of Liability</a></li>
                            <li><a href="#indemnification" class="">14. Indemnification</a></li>
                            <li><a href="#termination" class="">15. Account Suspension &amp; Termination</a></li>
                            <li><a href="#disputes" class="">16. Dispute Resolution</a></li>
                            <li><a href="#force-majeure" class="">17. Force Majeure</a></li>
                            <li><a href="#governing-law" class="">18. Governing Law</a></li>
                            <li><a href="#severability" class="">19. Severability</a></li>
                            <li><a href="#entire-agreement" class="">20. Entire Agreement</a></li>
                            <li><a href="#amendments" class="">21. Amendments</a></li>
                            <li><a href="#contact" class="">22. Contact Information</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="terms-main wow fadeInRight" data-wow-duration="600ms" style="visibility: visible; animation-duration: 600ms; animation-name: fadeInRight;">
                        <!-- Introduction -->
                        <div class="terms-intro">
                            <p><strong>WERN Terms of Service</strong></p>
                            <p style="margin-top: 12px;"><strong>Effective Date:</strong> 04 / 02 / 2026</p>
                            <p style="margin-top: 16px;">
                                Welcome to WERN, a platform that encourages daily movement, healthy habits, and engagement in social and community activities. By accessing or using the WERN app, website, or related services (collectively, the &ldquo;Service&rdquo;), you agree to these Terms of Service, our Privacy &amp; Data Protection Policy, and applicable laws. If you do not agree, please discontinue use immediately.
                            </p>
                        </div>

                        <!-- Section 1: Eligibility -->
                        <div class="terms-section" id="eligibility">
                            <h2><span class="section-number">1</span> Eligibility</h2>
                            <ul>
                                <li>You must be 18 years or older to use WERN.</li>
                                <li>Users under 18 may only use the Service with parental consent.</li>
                                <li>You confirm that you have the legal capacity to enter this agreement.</li>
                                <li>WERN may refuse service at its discretion, subject to applicable laws.</li>
                            </ul>
                        </div>

                        <!-- Section 2: User Accounts -->
                        <div class="terms-section" id="user-accounts">
                            <h2><span class="section-number">2</span> User Accounts</h2>
                            <p>By creating an account, you agree to:</p>
                            <ul>
                                <li>Provide accurate and complete information</li>
                                <li>Maintain confidentiality of login credentials</li>
                                <li>Be responsible for all account activity</li>
                                <li>Immediately notify WERN of unauthorized access</li>
                                <li>Not sell, trade, or transfer your account</li>
                                <li>Comply with UAE and applicable international data and cybersecurity laws</li>
                            </ul>
                        </div>

                        <!-- Section 3: Permitted & Prohibited Use -->
                        <div class="terms-section" id="permitted-use">
                            <h2><span class="section-number">3</span> Permitted &amp; Prohibited Use</h2>
                            <p>You agree to use WERN only for lawful purposes. Prohibited actions include:</p>
                            <ul>
                                <li>Interfering with platform integrity, servers, or networks</li>
                                <li>Attempting to hack, reverse engineer, or bypass system protections</li>
                                <li>Creating fake accounts, fake reviews, or artificial traffic</li>
                                <li>Uploading violent, harmful, sexual, hateful, or fraudulent content</li>
                                <li>Harassing, threatening, or endangering other users</li>
                                <li>Violating UAE, GCC, or international law</li>
                            </ul>
                            <p>WERN may investigate and take technical or legal action for violations, including account suspension or termination.</p>
                        </div>

                        <!-- Section 4: Health & Safety Disclaimer -->
                        <div class="terms-section" id="health-safety">
                            <h2><span class="section-number">4</span> Health &amp; Safety Disclaimer</h2>
                            <h4>Not a Medical Provider</h4>
                            <p>WERN is not a medical provider. The Service is for informational and motivational purposes only.</p>
                            <h4>Consult a Physician</h4>
                            <p>Always consult a physician before starting a new physical activity or fitness program.</p>
                            <h4>Assumption of Risk</h4>
                            <p>You agree that your participation in any movement, walk, or challenge is voluntary and at your own risk. WERN is not liable for any injuries sustained while using the Service.</p>
                        </div>

                        <!-- Section 5: Recognition & Achievements -->
                        <div class="terms-section" id="recognition">
                            <h2><span class="section-number">5</span> Recognition &amp; Achievements</h2>
                            <ul>
                                <li>The app may track steps, activity, or challenge participation.</li>
                                <li>Recognition is symbolic only and does not represent financial value or ownership.</li>
                                <li>WERN may adjust, modify, or remove recognition for operational reasons or violations of the Terms.</li>
                                <li>WERN is not liable for loss of recognition due to device issues, account problems, or user error.</li>
                            </ul>
                        </div>

                        <!-- Section 6: Brand & Partner Features -->
                        <div class="terms-section" id="brand-partners">
                            <h2><span class="section-number">6</span> Brand &amp; Partner Features</h2>
                            <p>Any promotions, challenges, or campaigns offered by brands or partners are the responsibility of the respective entity.</p>
                            <p>WERN acts only as a facilitator and is not liable for:</p>
                            <ul>
                                <li>Incorrect offers</li>
                                <li>Out-of-stock items</li>
                                <li>Brand misrepresentation</li>
                                <li>Financial loss or disputes between users and brands</li>
                            </ul>
                        </div>

                        <!-- Section 7: User-Generated Content (UGC) -->
                        <div class="terms-section" id="ugc">
                            <h2><span class="section-number">7</span> User-Generated Content (UGC)</h2>
                            <p>By posting content:</p>
                            <ul>
                                <li>You grant WERN a worldwide, royalty-free, perpetual license to use, reproduce, modify, distribute, and display content.</li>
                                <li>You confirm ownership or rights to the content.</li>
                                <li>WERN may remove content that violates laws or Terms.</li>
                                <li>Misuse may result in legal action under UAE Cybercrime and Anti-Defamation laws.</li>
                            </ul>
                        </div>

                        <!-- Section 8: Data Collection & Privacy -->
                        <div class="terms-section" id="privacy">
                            <h2><span class="section-number">8</span> Data Collection &amp; Privacy</h2>
                            <p>WERN processes personal data in line with:</p>
                            <ul>
                                <li>UAE Federal Data Protection Law (2021)</li>
                                <li>GDPR (EU) and global standards</li>
                            </ul>
                            <h4>Collected data may include</h4>
                            <ul>
                                <li>Device activity, app interactions, and engagement patterns</li>
                                <li>Location data for features (opt-in only)</li>
                                <li>Analytics for platform performance</li>
                            </ul>
                            <h4>Usage</h4>
                            <ul>
                                <li>Improving app features</li>
                                <li>Monitoring activity and engagement</li>
                                <li>Facilitating communication within the app</li>
                            </ul>
                            <h4>Security</h4>
                            <p>Encryption, access controls, secure cloud hosting, and monitoring</p>
                            <p>WERN does not sell personal data</p>
                        </div>

                        <!-- Section 9: Location Features -->
                        <div class="terms-section" id="location">
                            <h2><span class="section-number">9</span> Location Features</h2>
                            <p>Location-enabled features include walk tracking, proximity alerts, and safety notifications.</p>
                            <p>WERN is not responsible for incorrect readings caused by device limitations or network issues.</p>
                            <p>Users assume responsibility for sharing location.</p>
                        </div>

                        <!-- Section 10: Referral Program -->
                        <div class="terms-section" id="referral">
                            <h2><span class="section-number">10</span> Referral Program</h2>
                            <ul>
                                <li>Participation is voluntary and subject to verification.</li>
                                <li>Fake or duplicate referrals may result in account penalties.</li>
                                <li>WERN may modify or terminate the program at any time.</li>
                            </ul>
                        </div>

                        <!-- Section 11: Intellectual Property -->
                        <div class="terms-section" id="ip">
                            <h2><span class="section-number">11</span> Intellectual Property</h2>
                            <p>All WERN content, code, logos, and assets are the property of WERN.</p>
                            <p>Unauthorized use, copying, or reverse engineering is prohibited.</p>
                            <p>Violations may be pursued under UAE Copyright, GCC Trademark Law, and international treaties.</p>
                        </div>

                        <!-- Section 12: Disclaimers -->
                        <div class="terms-section" id="disclaimers">
                            <h2><span class="section-number">12</span> Disclaimers</h2>
                            <p>WERN is provided &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo;.</p>
                            <p>WERN does not guarantee continuous access, accuracy of partner content, or device compatibility.</p>
                            <p>Engagement with third-party content or campaigns is at your own risk.</p>
                        </div>

                        <!-- Section 13: Limitation of Liability -->
                        <div class="terms-section" id="liability">
                            <h2><span class="section-number">13</span> Limitation of Liability</h2>
                            <p>To the fullest extent allowed by law, WERN is not liable for indirect or consequential damages.</p>
                            <p>Total liability shall not exceed any amounts paid by the user, or AED 0 for free users.</p>
                        </div>

                        <!-- Section 14: Indemnification -->
                        <div class="terms-section" id="indemnification">
                            <h2><span class="section-number">14</span> Indemnification</h2>
                            <p>You agree to indemnify WERN from claims, damages, or liabilities arising from:</p>
                            <ul>
                                <li>Misuse of the app</li>
                                <li>Violation of Terms or laws</li>
                                <li>Third-party disputes</li>
                            </ul>
                        </div>

                        <!-- Section 15: Account Suspension & Termination -->
                        <div class="terms-section" id="termination">
                            <h2><span class="section-number">15</span> Account Suspension &amp; Termination</h2>
                            <p>WERN may suspend or terminate accounts for:</p>
                            <ul>
                                <li>Misuse or manipulation</li>
                                <li>Violating laws or platform rules</li>
                                <li>Damaging platform integrity</li>
                            </ul>
                            <p>Termination may result in loss of recognition or account access.</p>
                        </div>

                        <!-- Section 16: Dispute Resolution -->
                        <div class="terms-section" id="disputes">
                            <h2><span class="section-number">16</span> Dispute Resolution</h2>
                            <ul>
                                <li>First, internal resolution via WERN Support.</li>
                                <li>Mediation if agreed.</li>
                                <li>UAE courts have jurisdiction if necessary.</li>
                            </ul>
                        </div>

                        <!-- Section 17: Force Majeure -->
                        <div class="terms-section" id="force-majeure">
                            <h2><span class="section-number">17</span> Force Majeure</h2>
                            <p>WERN is not liable for failures caused by events beyond reasonable control:</p>
                            <ul>
                                <li>Natural disasters, war, riots</li>
                                <li>Cyberattacks, government restrictions</li>
                                <li>Network outages</li>
                            </ul>
                        </div>

                        <!-- Section 18: Governing Law -->
                        <div class="terms-section" id="governing-law">
                            <h2><span class="section-number">18</span> Governing Law</h2>
                            <p>UAE Federal and Abu Dhabi local laws apply.</p>
                            <p>International commercial contracting principles also govern, where applicable.</p>
                        </div>

                        <!-- Section 19: Severability -->
                        <div class="terms-section" id="severability">
                            <h2><span class="section-number">19</span> Severability</h2>
                            <p>Invalid clauses do not affect remaining provisions.</p>
                        </div>

                        <!-- Section 20: Entire Agreement -->
                        <div class="terms-section" id="entire-agreement">
                            <h2><span class="section-number">20</span> Entire Agreement</h2>
                            <p>These Terms and the Privacy Policy constitute the complete agreement.</p>
                        </div>

                        <!-- Section 21: Amendments -->
                        <div class="terms-section" id="amendments">
                            <h2><span class="section-number">21</span> Amendments</h2>
                            <p>WERN may update Terms.</p>
                            <p>Material changes will be communicated via email or in-app notifications.</p>
                            <div class="highlight-box">
                                <p>Continued use signifies acceptance.</p>
                            </div>
                        </div>

                        <!-- Section 22: Contact Information -->
                        <div class="terms-section" id="contact">
                            <h2><span class="section-number">22</span> Contact Information</h2>
                            <div class="contact-card">
                                <h3>WERN Legal Team</h3>
                                <p><i class="fas fa-location-dot"></i> EREC 20, Floor 1, Al Danah, Abu Dhabi, UAE</p>
                                <p><i class="fas fa-envelope"></i> Email: <a href="mailto:technical@projectliberte.io">technical@projectliberte.io</a></p>
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

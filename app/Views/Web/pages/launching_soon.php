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
    <section class="breadcrumb bg_img" data-background="<?= base_url('public/web/assets/img/bg/bootcamp-bg.png') ?>" style="height: 100vh;">
        <div class="container">
            <div class="breadcrumb__content">
                <ul class="breadcrumb__list clearfix list-unstyled">
                    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
                    <!-- <li class="breadcrumb-item dotIcon1 position-relative"><a href="#">Legal</a></li> -->
                    <li class="breadcrumb-item dotIcon2 position-relative">Launching Soon</li>
                </ul>
                <h2 class="breadcrumb__title">Launching Soon</h2>
            </div>
        </div>
    </section>
    <!-- Breadcrumb End -->


</main>
<!-- main area end -->

<?php echo view('Web/includes/footer'); ?>

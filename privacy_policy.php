<?php
require_once 'includes/security.php';

// ---- TODO before your defense: fill these in with the real details from your school ----
$dpo_name      = 'Data Protection Officer';
$dpo_email     = 'privacy@neu.edu.ph';        // TODO: the real Data Privacy Office email
$dpo_phone     = '';                           // TODO: the real phone number (leave empty to hide it)
$retention     = 'for as long as you are enrolled, and afterward only as long as the university\'s records policy requires'; // TODO: confirm with the school
$last_updated  = 'November 19, 2026';
// -----------------------------------------------------------------------------------------

$logged_in  = isset($_SESSION['role']);
$is_student = $logged_in && $_SESSION['role'] === 'Student';
$back_href  = $logged_in ? 'profile.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Privacy Policy | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
    <style>
        .policy h2 { font-size: 1.05rem; font-weight: 800; margin: 0; }
        .policy p, .policy li { font-size: .88rem; color: var(--muted); line-height: 1.6; }
        .policy ul { padding-left: 18px; margin: 10px 0 0; }
        .policy li { margin-bottom: 6px; }
        .policy strong { color: var(--text); }
        .policy .head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .policy .head .n-bubble { width: 40px; height: 40px; border-radius: 13px; font-size: .95rem; }
    </style>
</head>
<body class="app<?= $is_student ? '' : ' no-dock' ?>">

    <header class="n-sticky">
        <div class="n-topbar">
            <a href="<?= h($back_href) ?>" class="n-iconbtn" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
            <h1 class="title">Privacy policy</h1>
        </div>
    </header>

    <main class="n-page pt-4 policy">

        <section class="n-hero-card mb-3 rise">
            <h2 style="color:#fff; font-size: 1.25rem;">The short version</h2>
            <ul style="color: rgba(255,255,255,.92); padding-left: 18px; margin: 10px 0 0;">
                <li style="color: inherit;">We collect only what's needed to confirm your medical clearance for enrollment.</li>
                <li style="color: inherit;">Your documents are seen only by the clinic staff who review them.</li>
                <li style="color: inherit;">We never sell your data.</li>
                <li style="color: inherit;">You can ask us to see or correct your information any time.</li>
            </ul>
            <p class="small mt-3 mb-0" style="color: rgba(255,255,255,.75);">Last updated <?= h($last_updated) ?></p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:1">
            <div class="head"><span class="n-bubble ok"><i class="fas fa-hand-wave"></i></span><h2>Welcome</h2></div>
            <p class="mb-0">NEUMed helps New Era University students submit medical requirements and track their clearance. This page explains what information we collect, how we use it, and how we keep it safe. We follow the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:2">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-clipboard-list"></i></span><h2>What we collect</h2></div>
            <ul>
                <li><strong>Account details:</strong> your name, student number, NEU email, program, year level, and your password (stored scrambled, so nobody can read it).</li>
                <li><strong>Medical documents you upload:</strong> your Chest X-Ray and Physical Examination, plus the clinic's decision and any notes.</li>
                <li><strong>Activity records:</strong> when you sign in, and when clinic staff open a student's document.</li>
                <li><strong>On your device:</strong> a sign-in session cookie and your light or dark theme choice.</li>
            </ul>
            <p class="mt-3 mb-0">Health information is <strong>sensitive personal information</strong> under the law, so we ask for your consent when you create your account and handle it with extra care.</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:3">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-bullseye"></i></span><h2>Why we use it</h2></div>
            <ul>
                <li>To check your medical requirements and confirm whether you are cleared to enroll.</li>
                <li>To send you updates and reminders about your documents.</li>
                <li>To keep accounts secure and keep a record of who opened medical files.</li>
                <li>To meet the university's academic and legal requirements.</li>
            </ul>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:4">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-users"></i></span><h2>Who can see it</h2></div>
            <ul>
                <li><strong>You:</strong> all of your own information.</li>
                <li><strong>University Clinic staff and system administrators:</strong> can open your medical documents, only to review them or in a medical emergency. Each time is recorded.</li>
                <li><strong>Enrollment officers:</strong> see whether you are cleared, not your documents.</li>
                <li><strong>Professors or other people you show your Medical ID to:</strong> see your name, student number, program, year level, and cleared status. They do not see your documents.</li>
                <li><strong>Others:</strong> only when the law requires us to share it. We do not sell your data.</li>
            </ul>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:5">
            <div class="head"><span class="n-bubble ok"><i class="fas fa-shield-halved"></i></span><h2>How we keep it safe</h2></div>
            <p class="mb-0">Passwords are stored scrambled. Documents can only be opened through pages that check who you are, and they are not available by direct link. Access to your records is limited by role, and staff access is logged. No system is perfectly secure, so please use a strong password and sign out on shared devices.</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:6">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-clock-rotate-left"></i></span><h2>How long we keep it</h2></div>
            <p class="mb-0">We keep your information <?= h($retention) ?>.</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:7">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-scale-balanced"></i></span><h2>Your rights</h2></div>
            <p class="mb-0">Under the Data Privacy Act you may:</p>
            <ul>
                <li>be informed about how your data is used,</li>
                <li>ask to see the data we hold about you,</li>
                <li>ask us to correct anything that is wrong,</li>
                <li>object to certain uses, or withdraw your consent,</li>
                <li>ask us to block or delete data where the law and school records rules allow it,</li>
                <li>ask for a copy of your data in a usable format, and</li>
                <li>file a complaint with the National Privacy Commission if you feel your data was mishandled.</li>
            </ul>
            <p class="mt-3 mb-0">You can fix your name, program, and year in <strong>Profile</strong> any time. For anything else, contact us below.</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:8">
            <div class="head"><span class="n-bubble idle"><i class="fas fa-file-pen"></i></span><h2>Changes to this policy</h2></div>
            <p class="mb-0">If we change this policy, the new version will appear on this page with a new "last updated" date.</p>
        </section>

        <section class="n-card p-4 mb-3 rise" style="--i:9">
            <div class="head"><span class="n-bubble ok"><i class="fas fa-envelope"></i></span><h2>Contact us</h2></div>
            <p class="mb-2">Questions or requests about your data? Reach our <?= h($dpo_name) ?>:</p>
            <p class="mb-0">
                <a href="mailto:<?= h($dpo_email) ?>" class="n-link"><i class="fas fa-envelope me-2"></i><?= h($dpo_email) ?></a>
                <?php if ($dpo_phone !== ''): ?>
                    <br><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $dpo_phone)) ?>" class="n-link"><i class="fas fa-phone me-2"></i><?= h($dpo_phone) ?></a>
                <?php endif; ?>
            </p>
        </section>

    </main>

    <?php if ($is_student) include('includes/navbar.php'); ?>
</body>
</html>
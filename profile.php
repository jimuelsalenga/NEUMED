<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT fullname, student_number, course, year FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit();
}

$fullname = $user['fullname'];
$_SESSION['fullname'] = $fullname;

// Subtitle under the name: real info instead of a placeholder
if ($role === 'Student') {
    $subtitle = trim(($user['course'] ?: '') . ($user['course'] && $user['year'] ? ' • ' : '') . ($user['year'] ?: ''));
    $subtitle = $subtitle ?: 'Student';
} else {
    $depts = ['Medical Staff' => 'University Clinic', 'Enrollment Officer' => 'Enrollment Office', 'Admin' => 'Administration'];
    $subtitle = $role . ' • ' . ($depts[$role] ?? 'NEUMed');
}

// Bottom nav for staff roles (each role gets its own pages; students use includes/navbar.php)
$staff_tabs = [
    'Medical Staff' => [
        ['staff_dashboard.php',    'fa-clipboard-list', 'Requests'],
        ['batch_approval.php',     'fa-layer-group',    'Batch'],
        ['emergency_override.php', 'fa-shield-halved',  'Emergency'],
        ['profile.php',            'fa-user',           'Profile'],
    ],
    'Enrollment Officer' => [
        ['officer_dashboard.php',  'fa-chart-pie',      'Heatmap'],
        ['student_list.php',       'fa-users',          'Students'],
        ['profile.php',            'fa-user',           'Profile'],
    ],
    'Admin' => [
        ['admin_dashboard.php',    'fa-shield-halved',  'Dashboard'],
        ['manage_users.php',       'fa-users',          'Users'],
        ['profile.php',            'fa-user',           'Profile'],
    ],
];
$tabs = $staff_tabs[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Profile | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app">

    <header class="n-profile-hero">
        <div class="n-avatar"><?= h(mb_strtoupper(mb_substr($fullname, 0, 1))) ?></div>
        <h1 class="fw-bold m-0" style="font-size: 1.5rem; letter-spacing: -.4px;"><?= h($fullname) ?></h1>
        <p class="small mb-0 mt-1" style="opacity: .85;"><?= h($subtitle) ?></p>
        <?php if ($role === 'Student' && $user['student_number']): ?>
            <span class="n-chip-w"><i class="fas fa-id-card me-1"></i> <?= h($user['student_number']) ?></span>
        <?php endif; ?>
    </header>

    <main class="n-page">

        <h2 class="n-section-title">Account</h2>
        <nav class="n-card n-menu rise" aria-label="Account">
            <?php if ($role === 'Student'): ?>
                <a href="medical_id.php">
                    <span class="n-bubble ok"><i class="fas fa-id-card"></i></span> Medical ID <i class="fas fa-chevron-right chev"></i>
                </a>
            <?php endif; ?>
            <a href="edit_profile.php">
                <span class="n-bubble idle"><i class="fas fa-user-pen"></i></span> Edit profile <i class="fas fa-chevron-right chev"></i>
            </a>
            <a href="change_password.php">
                <span class="n-bubble idle"><i class="fas fa-key"></i></span> Change password <i class="fas fa-chevron-right chev"></i>
            </a>
            <?php if ($role === 'Medical Staff'): ?>
                <a href="update_signature.php">
                    <span class="n-bubble ok"><i class="fas fa-signature"></i></span> Update digital signature <i class="fas fa-chevron-right chev"></i>
                </a>
            <?php endif; ?>
        </nav>

        <h2 class="n-section-title">Appearance</h2>
        <div class="n-card p-3 rise" style="--i:1">
            <div class="n-seg" role="group" aria-label="Theme">
                <button type="button" data-mode="light"><i class="fas fa-sun"></i> Light</button>
                <button type="button" data-mode="dark"><i class="fas fa-moon"></i> Dark</button>
                <button type="button" data-mode="auto"><i class="fas fa-mobile-screen"></i> Auto</button>
            </div>
            <p class="muted small mt-2 mb-0 px-1">Auto follows your phone's setting.</p>
        </div>

        <h2 class="n-section-title">About</h2>
        <nav class="n-card n-menu rise" style="--i:2" aria-label="About">
            <a href="privacy_policy.php">
                <span class="n-bubble idle"><i class="fas fa-shield-halved"></i></span> Privacy policy <i class="fas fa-chevron-right chev"></i>
            </a>
        </nav>

        <nav class="n-card n-menu danger mt-3 rise" style="--i:3" aria-label="Log out">
            <a href="logout.php">
                <span class="n-bubble bad"><i class="fas fa-right-from-bracket"></i></span> Log out
            </a>
        </nav>

    </main>

    <?php if ($role === 'Student'): ?>
        <?php include('includes/navbar.php'); ?>
    <?php elseif ($tabs): ?>
        <nav class="dock" aria-label="Main navigation"
             style="grid-template-columns: repeat(<?= count($tabs) ?>, 1fr); max-width: <?= count($tabs) > 3 ? 460 : 360 ?>px;">
            <?php foreach ($tabs as [$href, $icon, $label]): $active = ($href === 'profile.php'); ?>
                <a href="<?= $href ?>" class="<?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                    <span class="ico"><i class="fas <?= $icon ?>"></i></span>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <script>
    (function () {
        var buttons = document.querySelectorAll('.n-seg button');
        function mark() {
            var m = NeuTheme.mode();
            buttons.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-mode') === m); });
        }
        buttons.forEach(function (b) {
            b.addEventListener('click', function () { NeuTheme.setMode(this.getAttribute('data-mode')); mark(); });
        });
        mark();
    })();
    </script>
</body>
</html>

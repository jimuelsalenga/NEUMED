<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/status.php';
require_once 'includes/medical_id.php';
require_login(['Student']);

header('Cache-Control: no-store');

$student_id = (int)$_SESSION['user_id'];
$info = medical_id_info($conn, $student_id);

if (!$info) {
    header("Location: logout.php");
    exit();
}

$cleared = $info['cleared'];
$checklist = [];

if (!$cleared) {
    // Show what is still missing
    $stmt = $conn->prepare(
        "SELECT r.req_name, s.status
           FROM requirements r
           LEFT JOIN submissions s ON s.document_type = r.req_name AND s.student_id = ?
          WHERE r.is_active = 1
          ORDER BY r.id ASC"
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $checklist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Status color (from status.php) -> pill/bubble style
$style_of = ['success' => 'ok', 'warning' => 'wait', 'danger' => 'bad', 'secondary' => 'idle'];
$initial  = mb_strtoupper(mb_substr(trim($info['fullname']), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Medical ID | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app no-dock">

    <header class="n-sticky">
        <div class="n-topbar">
            <a href="student_dashboard.php" class="n-iconbtn" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
            <h1 class="title">Medical ID</h1>
        </div>
    </header>

    <main class="n-page pt-4">

    <?php if ($cleared): ?>

        <div class="n-id rise">
            <div class="n-id-top">
                <div class="d-flex align-items-center gap-3">
                    <img class="n-id-seal" src="img/logo.png" alt="New Era University seal">
                    <div>
                        <div class="fw-bold" style="font-size: 1.1rem; letter-spacing: -.3px;">NEU<span style="color: var(--sun);">Med</span></div>
                        <div class="small" style="opacity: .85;">NEU Medical Clearance ID</div>
                    </div>
                </div>
            </div>

            <div class="n-id-avatar" aria-hidden="true"><?= h($initial) ?></div>

            <div class="n-id-body">
                <div class="n-id-name"><?= h($info['fullname']) ?></div>
                <div class="n-id-status"><i class="fas fa-circle-check"></i> MEDICALLY CLEARED</div>

                <div class="n-id-rows">
                    <div><span>Student no.</span><strong><?= h($info['student_number'] ?: 'Not on file') ?></strong></div>
                    <div><span>Program</span><strong><?= h($info['course']) ?></strong></div>
                    <div><span>Year level</span><strong><?= h($info['year']) ?></strong></div>
                    <?php if ($info['cleared_on']): ?>
                        <div><span>Cleared on</span><strong><?= h(date('F j, Y', strtotime($info['cleared_on']))) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="n-id-stripe"></div>
            <div class="n-id-foot">
                <span class="n-live">LIVE</span> &nbsp;<span id="clock"></span><br>
                Status is checked live from the NEUMed server each time this page opens.
            </div>
        </div>

        <p class="text-center muted small mt-4 mb-0 rise" style="--i:2">
            <i class="fas fa-sun me-1" style="color: var(--sun);"></i> Raise your screen brightness, then show this to your professor.
        </p>

        <script>
        (function () {
            var clock = document.getElementById('clock');
            function tick() {
                clock.textContent = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' });
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>

    <?php else: ?>

        <div class="n-card p-4 text-center rise">
            <div class="n-bubble idle mx-auto mb-3" style="width: 76px; height: 76px; border-radius: 50%; font-size: 1.9rem;"><i class="fas fa-lock"></i></div>
            <h2 class="fw-bold" style="font-size: 1.3rem;">Medical ID locked</h2>
            <p class="muted small mb-4">It unlocks once the University Clinic approves all your requirements.</p>

            <div class="text-start">
                <?php foreach ($checklist as $c):
                    $style = $style_of[status_color($c['status'])] ?? 'idle';
                ?>
                    <div class="d-flex justify-content-between align-items-center py-3" style="border-top: 1px solid var(--border);">
                        <span class="fw-bold small"><?= h($c['req_name']) ?></span>
                        <span class="n-pill <?= $style ?>"><?= h(status_label($c['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="student_dashboard.php" class="n-btn n-btn-primary n-btn-block mt-3">Go to my requirements</a>
        </div>

    <?php endif; ?>

    </main>
</body>
</html>

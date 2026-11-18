<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/status.php';
require_login(['Student']);

$student_id = (int)$_SESSION['user_id'];
$first_name = explode(' ', trim($_SESSION['fullname']))[0];

// This student's submissions, keyed by requirement name
$subs = [];
$stmt = $conn->prepare("SELECT id, document_type, status, remarks FROM submissions WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $subs[$row['document_type']] = $row;
}
$stmt->close();

// Active requirements
$requirements = [];
$res = $conn->query("SELECT req_name, description FROM requirements WHERE is_active = 1 ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $requirements[] = $row;
}

// Progress numbers + the next thing the student should do
$total = count($requirements);
$approved = $pending = $resubmit = 0;
$next = null;
foreach ($requirements as $req) {
    $st = $subs[$req['req_name']]['status'] ?? null;
    if ($st === 'Approved')      $approved++;
    elseif (is_resubmit($st))    $resubmit++;
    elseif ($st)                 $pending++;

    if ($next === null && (!$st || is_resubmit($st))) {
        $next = ['name' => $req['req_name'], 'resubmit' => is_resubmit($st)];
    }
}
$progress = $total ? (int)round($approved / $total * 100) : 0;
$cleared  = $total > 0 && $approved === $total;
$ring_off = round(264 * (1 - $progress / 100), 1);

if ($cleared) {
    $hero_title = "You're medically cleared";
    $hero_sub   = 'All requirements are approved. Show your Medical ID when you need it.';
} elseif ($resubmit > 0) {
    $hero_title = 'Action needed';
    $hero_sub   = ($resubmit === 1 ? '1 document needs' : "$resubmit documents need") . ' to be resubmitted.';
} elseif ($total > 0 && $pending === $total - $approved && $pending > 0) {
    $hero_title = 'Sit tight';
    $hero_sub   = 'The clinic is reviewing your documents.';
} else {
    $hero_title = 'Almost there';
    $hero_sub   = "$approved of $total requirements approved" . ($pending > 0 ? ", $pending in review." : '.');
}

// Unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$unread = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Status color (from status.php) -> pill/bubble style
$style_of = ['success' => 'ok', 'warning' => 'wait', 'danger' => 'bad', 'secondary' => 'idle'];
$icon_of  = ['ok' => 'fa-check', 'wait' => 'fa-hourglass-half', 'bad' => 'fa-rotate-right', 'idle' => 'fa-file-arrow-up'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Home | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app">

    <div id="sync-status" class="d-none text-center py-2 small fw-bold" style="background: var(--text); color: var(--surface);" role="status">
        <i class="fas fa-triangle-exclamation me-1"></i> You're offline. Uploads won't work until you reconnect.
    </div>

    <header class="n-appbar">
        <div class="brand">
            <img src="img/logo.png" alt="">
            <span class="wordmark">NEU<em>Med</em></span>
        </div>
        <a href="notifications.php" class="n-iconbtn" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($unread > 0): ?><span class="count"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </a>
    </header>

    <main class="n-page">

        <section class="pt-4 pb-3 rise">
            <p class="muted small fw-bold mb-1">Welcome back</p>
            <h1 class="fw-bold m-0" style="font-size: 1.9rem;">Hi, <?= h($first_name) ?> 👋</h1>
        </section>

        <?php if ($total > 0): ?>
        <!-- Progress hero -->
        <section class="n-hero-card <?= ($resubmit > 0 && !$cleared) ? 'bad' : '' ?> rise" style="--i:1">
            <div class="d-flex align-items-center gap-3">
                <div class="n-ring <?= $cleared ? 'done' : '' ?>" style="--off: <?= $ring_off ?>">
                    <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">
                        <circle class="track" cx="48" cy="48" r="42" fill="none" stroke-width="8"/>
                        <circle class="bar" cx="48" cy="48" r="42" fill="none" stroke-width="8"/>
                    </svg>
                    <div class="num" aria-label="<?= $approved ?> of <?= $total ?> requirements approved">
                        <span><?= $approved ?>/<?= $total ?></span><small>approved</small>
                    </div>
                </div>
                <div>
                    <div class="fw-bold" style="font-size: 1.15rem; line-height: 1.2;"><?= h($hero_title) ?></div>
                    <div class="small mt-1" style="opacity: .88;"><?= h($hero_sub) ?></div>
                </div>
            </div>

            <?php if ($cleared): ?>
                <a href="medical_id.php" class="n-btn n-btn-light n-btn-block mt-4"><i class="fas fa-id-card"></i> Show my Medical ID</a>
            <?php elseif ($next): ?>
                <a href="upload.php?type=<?= urlencode($next['name']) ?>" class="n-btn n-btn-light n-btn-block mt-4">
                    <i class="fas <?= $next['resubmit'] ? 'fa-rotate-right' : 'fa-cloud-arrow-up' ?>"></i>
                    <?= $next['resubmit'] ? 'Resubmit' : 'Upload' ?> <?= h($next['name']) ?>
                </a>
            <?php endif; ?>
        </section>

        <?php if (!$cleared): ?>
        <a href="medical_id.php" class="n-card d-flex align-items-center gap-3 p-3 mt-3 rise" style="--i:2">
            <div class="n-bubble idle"><i class="fas fa-lock"></i></div>
            <div class="flex-fill">
                <div class="fw-bold small">Medical ID</div>
                <div class="muted" style="font-size: .75rem;">Unlocks when all requirements are approved.</div>
            </div>
            <i class="fas fa-chevron-right muted small"></i>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <h2 class="n-section-title">Your requirements</h2>

        <?php if ($total > 0): ?>
            <?php foreach ($requirements as $i => $req):
                $name  = $req['req_name'];
                $sub   = $subs[$name] ?? null;
                $st    = $sub['status'] ?? null;
                $style = $style_of[status_color($st)] ?? 'idle';
            ?>
                <div class="n-card mb-3 rise" style="--i: <?= $i + 3 ?>">
                    <div class="n-item">
                        <div class="n-bubble <?= $style ?>"><i class="fas <?= $icon_of[$style] ?>"></i></div>
                        <div class="flex-fill">
                            <div class="title"><?= h($name) ?></div>
                            <?php if (!empty($req['description'])): ?><div class="sub"><?= h($req['description']) ?></div><?php endif; ?>
                            <span class="n-pill <?= $style ?>"><?= h(status_label($st)) ?></span>
                        </div>

                        <?php if ($st === 'Approved'): ?>
                            <a href="view_file.php?id=<?= (int)$sub['id'] ?>" target="_blank" class="n-btn n-btn-ghost n-btn-sm">View</a>
                        <?php else: ?>
                            <a href="upload.php?type=<?= urlencode($name) ?>"
                               class="n-btn <?= is_resubmit($st) ? 'n-btn-danger' : 'n-btn-primary' ?> n-btn-sm"
                               aria-label="<?= is_resubmit($st) ? 'Resubmit' : 'Upload' ?> <?= h($name) ?>">
                                <?= is_resubmit($st) ? 'Fix' : ($st ? 'Replace' : 'Upload') ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (is_resubmit($st) && !empty($sub['remarks'])): ?>
                        <div class="n-note"><strong>Reviewer's note:</strong> <?= h($sub['remarks']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="n-card text-center p-4 muted small">No requirements have been set up yet.</div>
        <?php endif; ?>

    </main>

    <?php if (isset($_GET['success'])): ?>
        <span data-toast="Submitted! The clinic will review it soon." data-toast-icon="fa-circle-check" hidden></span>
    <?php endif; ?>

    <?php include('includes/navbar.php'); ?>

    <script src="js/app.js"></script>
    <script>
        var bar = document.getElementById('sync-status');
        function sync() { bar.classList.toggle('d-none', navigator.onLine); }
        window.addEventListener('offline', sync);
        window.addEventListener('online', sync);
        sync();
    </script>
</body>
</html>

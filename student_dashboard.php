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

// Progress numbers
$total = count($requirements);
$approved = $pending = $resubmit = 0;
foreach ($requirements as $req) {
    $st = $subs[$req['req_name']]['status'] ?? null;
    if ($st === 'Approved')      $approved++;
    elseif (is_resubmit($st))    $resubmit++;
    elseif ($st)                 $pending++;
}
$progress = $total ? (int)round($approved / $total * 100) : 0;
$cleared  = $total > 0 && $approved === $total;

if ($cleared) {
    $banner = ['success', 'fa-circle-check', 'Medically cleared', 'All requirements have been approved.'];
} elseif ($resubmit > 0) {
    $banner = ['danger', 'fa-circle-exclamation', 'Action needed',
               ($resubmit === 1 ? '1 document needs' : "$resubmit documents need") . ' to be resubmitted.'];
} else {
    $note = "$approved of $total requirements approved";
    if ($pending > 0) $note .= ", $pending waiting for review";
    $banner = ['primary', 'fa-clipboard-check', 'Clearance in progress', $note . '.'];
}

// Unread notifications
$unread = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$unread = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .app-bar { background: #fff; padding: 15px; border-bottom: 1px solid #eee; }
        .welcome-section { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; padding: 25px 20px; border-radius: 0 0 25px 25px; }
        .stat-card { border: none; border-radius: 15px; transition: 0.3s; margin-bottom: 12px; }
        .stat-card:active { transform: scale(0.98); }
        .offline-indicator { font-size: 11px; font-weight: bold; }
        .bell { position: relative; color: #374151; font-size: 1.15rem; text-decoration: none; }
        .bell .count { position: absolute; top: -6px; right: -9px; font-size: 10px; }
    </style>
</head>
<body>

    <div id="sync-status" class="bg-dark text-white text-center py-1 offline-indicator d-none" role="status">
        <i class="fas fa-triangle-exclamation"></i> You're offline. Uploads won't work until you reconnect.
    </div>

    <div class="app-bar d-flex justify-content-between align-items-center shadow-sm sticky-top">
        <span class="fw-bold text-primary"><i class="fas fa-notes-medical me-2"></i>MediTrack</span>
        <a href="notifications.php" class="bell" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($unread > 0): ?>
                <span class="badge rounded-pill bg-danger count"><?= $unread > 9 ? '9+' : $unread ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="welcome-section mb-4 shadow">
        <h4 class="m-0 fw-bold">Hi, <?= h($first_name) ?>!</h4>
        <p class="small opacity-75 mb-0">University Medical Clearance Tracker</p>
    </div>

    <div class="container">

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success border-0 shadow-sm small py-2" style="border-radius:12px;" role="status">
                <i class="fas fa-check-circle me-1"></i> Document submitted. It's now waiting for review.
            </div>
        <?php endif; ?>

        <?php if ($total > 0): ?>
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas <?= $banner[1] ?> text-<?= $banner[0] ?> fs-3 me-3"></i>
                    <div>
                        <div class="fw-bold"><?= h($banner[2]) ?></div>
                        <small class="text-muted"><?= h($banner[3]) ?></small>
                    </div>
                </div>
                <div class="progress" style="height: 8px;" role="progressbar"
                     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-<?= $banner[0] ?>" style="width: <?= $progress ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <h6 class="fw-bold mb-3 small text-muted">Requirement checklist</h6>

        <?php if ($total > 0): ?>
            <?php foreach ($requirements as $req):
                $name = $req['req_name'];
                $sub  = $subs[$name] ?? null;
                $st   = $sub['status'] ?? null;
            ?>
                <div class="card stat-card shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($st === 'Approved'): ?>
                                        <i class="fas fa-check-circle text-success fs-4"></i>
                                    <?php elseif (is_resubmit($st)): ?>
                                        <i class="fas fa-circle-exclamation text-danger fs-4"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-alt text-light fs-4 border rounded p-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold small"><?= h($name) ?></div>
                                    <?= status_badge($st) ?>
                                </div>
                            </div>

                            <?php if ($st === 'Approved'): ?>
                                <a href="view_file.php?id=<?= (int)$sub['id'] ?>" target="_blank"
                                   class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">View</a>
                            <?php else: ?>
                                <a href="upload.php?type=<?= urlencode($name) ?>"
                                   class="btn btn-light btn-sm rounded-circle shadow-sm"
                                   aria-label="<?= is_resubmit($st) ? 'Resubmit' : 'Upload' ?> <?= h($name) ?>">
                                    <i class="fas fa-chevron-right text-primary" style="font-size: 12px;"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (is_resubmit($st) && !empty($sub['remarks'])): ?>
                            <div class="mt-2 p-2 bg-light rounded small text-danger">
                                <strong>Reviewer's note:</strong> <?= h($sub['remarks']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted small">No requirements have been set up yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/navbar.php'); ?>

    <script>
        window.addEventListener('offline', function () {
            document.getElementById('sync-status').classList.remove('d-none');
        });
        window.addEventListener('online', function () {
            document.getElementById('sync-status').classList.add('d-none');
        });
        if (!navigator.onLine) document.getElementById('sync-status').classList.remove('d-none');
    </script>
</body>
</html>

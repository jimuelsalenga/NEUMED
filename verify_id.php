<?php
// Public page: opens when someone scans a student's Medical ID QR code. No login needed.
// It shows identity + cleared/not cleared only. Never any medical details.
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/medical_id.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$check = verify_id_token((string)($_GET['t'] ?? ''));
$state = 'invalid';   // cleared | not_cleared | expired | invalid
$info  = null;

if ($check['result'] === 'ok') {
    $info = medical_id_info($conn, $check['student_id']);
    if ($info) {
        $state = $info['cleared'] ? 'cleared' : 'not_cleared';
        audit_log($conn, 'verify_id', "student #{$check['student_id']}: $state");
    }
} elseif ($check['result'] === 'expired') {
    $state = 'expired'; // no personal details are shown for expired codes
}

$styles = [
    'cleared'     => ['#198754', 'fa-circle-check',        'MEDICALLY CLEARED'],
    'not_cleared' => ['#d97706', 'fa-triangle-exclamation', 'NOT YET CLEARED'],
    'expired'     => ['#6b7280', 'fa-clock',               'QR CODE EXPIRED'],
    'invalid'     => ['#dc3545', 'fa-circle-xmark',        'INVALID QR CODE'],
];
[$color, $icon, $title] = $styles[$state];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Verify Medical ID | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 20px 0; }
        .v-card { width: 92%; max-width: 380px; background: #fff; border-radius: 28px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.10); }
        .v-head { background: <?= $color ?>; color: #fff; text-align: center; padding: 30px 20px; }
        .v-head i { font-size: 3rem; }
        .v-body { padding: 22px 20px; text-align: center; }
        .v-foot { background: #f3f4f6; padding: 10px 16px; font-size: 11px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="v-card">
        <div class="v-head">
            <i class="fas <?= $icon ?> mb-2"></i>
            <div class="fw-bold" style="letter-spacing:1px;"><?= h($title) ?></div>
        </div>

        <div class="v-body">
            <?php if ($info): ?>
                <div class="fs-4 fw-bold"><?= h($info['fullname']) ?></div>
                <div class="text-muted small"><?= h($info['student_number'] ?: 'No student number on file') ?></div>
                <div class="text-muted small"><?= h($info['course']) ?> &middot; <?= h($info['year']) ?></div>
                <?php if ($state === 'cleared' && $info['cleared_on']): ?>
                    <div class="small mt-2"><span class="text-muted">Cleared on</span> <strong><?= h(date('F j, Y', strtotime($info['cleared_on']))) ?></strong></div>
                <?php elseif ($state === 'not_cleared'): ?>
                    <div class="small text-muted mt-3">This student still has requirements to complete.</div>
                <?php endif; ?>
            <?php elseif ($state === 'expired'): ?>
                <p class="mb-1">This code is too old to trust.</p>
                <p class="small text-muted mb-0">Ask the student to open <strong>Medical ID</strong> in MediTrack again and scan the new code.</p>
            <?php else: ?>
                <p class="mb-1">This code isn't valid.</p>
                <p class="small text-muted mb-0">Ask the student to open <strong>Medical ID</strong> in MediTrack and scan it again.</p>
            <?php endif; ?>
        </div>

        <div class="v-foot">
            Checked live on <?= h(date('M j, Y g:i:s A')) ?><br>
            MediTrack &middot; NEU Student Medical Record System
        </div>
    </div>
</body>
</html>

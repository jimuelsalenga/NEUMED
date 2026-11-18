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
$qr_url = '';
$checklist = [];

if ($cleared) {
    $qr_url = id_verify_url(make_id_token($student_id));
} else {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical ID | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 40px; font-family: 'Segoe UI', sans-serif; }
        .id-card { background: #fff; border-radius: 28px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.10); max-width: 380px; margin: 0 auto; }
        .id-top { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; padding: 18px 20px; display: flex; align-items: center; }
        .id-top img { width: 42px; height: 42px; background: #fff; border-radius: 50%; padding: 2px; margin-right: 12px; }
        .id-status { background: #198754; color: #fff; text-align: center; padding: 12px; font-weight: 800; letter-spacing: 1px; }
        .id-body { padding: 22px 20px; text-align: center; }
        .id-name { font-size: 1.35rem; font-weight: 800; margin-bottom: 2px; }
        .qr-box { display: inline-block; background: #fff; padding: 12px; border: 1px solid #e5e7eb; border-radius: 16px; margin-top: 16px; }
        .id-foot { background: #f3f4f6; padding: 10px 16px; font-size: 11px; color: #6b7280; text-align: center; }
        .locked-icon { width: 72px; height: 72px; border-radius: 50%; background: #eef1f5; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #6b7280; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white d-flex align-items-center mb-4">
        <a href="student_dashboard.php" class="text-dark me-3 text-decoration-none" aria-label="Back"><i class="fas fa-chevron-left"></i></a>
        <h6 class="m-0 fw-bold">Medical ID</h6>
    </div>

    <div class="container">

    <?php if ($cleared): ?>

        <div class="id-card">
            <div class="id-top">
                <img src="img/logo.png" alt="New Era University seal">
                <div>
                    <div class="fw-bold">MediTrack</div>
                    <small style="opacity:.85;">NEU Medical Clearance ID</small>
                </div>
            </div>

            <div class="id-status"><i class="fas fa-circle-check me-2"></i>MEDICALLY CLEARED</div>

            <div class="id-body">
                <div class="id-name"><?= h($info['fullname']) ?></div>
                <div class="text-muted small"><?= h($info['student_number'] ?: 'No student number on file') ?></div>
                <div class="text-muted small"><?= h($info['course']) ?> &middot; <?= h($info['year']) ?></div>
                <?php if ($info['cleared_on']): ?>
                    <div class="small mt-2"><span class="text-muted">Cleared on</span> <strong><?= h(date('F j, Y', strtotime($info['cleared_on']))) ?></strong></div>
                <?php endif; ?>

                <div class="qr-box"><div id="qr" aria-label="QR code to verify this Medical ID"></div></div>
                <div class="small text-muted mt-2">Your professor can scan this to verify you.</div>
                <div class="small mt-1">QR refreshes in <strong id="countdown">15:00</strong></div>
            </div>

            <div class="id-foot">
                <span id="clock"></span><br>
                A screenshot won't work: the code expires and is checked live online.
            </div>
        </div>
        <p class="text-center text-muted small mt-3">Tip: raise your screen brightness before showing this.</p>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        (function () {
            new QRCode(document.getElementById('qr'), {
                text: <?= json_encode($qr_url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
                width: 200, height: 200,
                colorDark: '#111111', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });

            var expires = Date.now() + <?= ID_TOKEN_TTL ?> * 1000;
            var countdown = document.getElementById('countdown');
            var clock = document.getElementById('clock');

            function tick() {
                var left = Math.max(0, Math.round((expires - Date.now()) / 1000));
                var m = Math.floor(left / 60), s = left % 60;
                countdown.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                clock.textContent = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' });
                if (left === 0) location.reload(); // get a fresh code
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>

    <?php else: ?>

        <div class="id-card">
            <div class="id-body">
                <div class="locked-icon mb-3"><i class="fas fa-lock"></i></div>
                <h5 class="fw-bold">Medical ID locked</h5>
                <p class="text-muted small">Your ID unlocks once all requirements are approved by the University Clinic.</p>

                <div class="text-start mt-4">
                    <?php foreach ($checklist as $c): ?>
                        <div class="d-flex justify-content-between align-items-center border-top py-2">
                            <span class="small fw-bold"><?= h($c['req_name']) ?></span>
                            <?= status_badge($c['status']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="student_dashboard.php" class="btn btn-primary rounded-pill px-4 mt-4">Go to my requirements</a>
            </div>
        </div>

    <?php endif; ?>

    </div>
</body>
</html>

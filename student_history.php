<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/status.php';
require_login(['Medical Staff', 'Enrollment Officer', 'Admin']);

$back = home_for_role($_SESSION['role']);
$student_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT fullname, student_number, email, course, year FROM users WHERE id = ? AND role = 'Student'"
);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: $back");
    exit();
}

$stmt = $conn->prepare(
    "SELECT document_type, status, remarks, uploaded_at
       FROM submissions WHERE student_id = ? ORDER BY uploaded_at DESC"
);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="p-3 bg-white border-bottom d-flex align-items-center">
        <a href="<?= h($back) ?>" class="text-dark me-3 text-decoration-none" aria-label="Back">
            <i class="fas fa-chevron-left"></i>
        </a>
        <div>
            <h6 class="m-0 fw-bold"><?= h($student['fullname']) ?></h6>
            <small class="text-muted">
                <?= h($student['student_number'] ?: 'No student number') ?> &middot;
                <?= h($student['course']) ?> &middot; <?= h($student['year']) ?>
            </small>
        </div>
    </div>

    <div class="container mt-3">
        <?php if ($history): ?>
        <ul class="list-group border-0 shadow-sm rounded-4 overflow-hidden">
            <?php foreach ($history as $row): ?>
                <li class="list-group-item border-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><?= h($row['document_type']) ?></div>
                            <small class="text-muted"><?= h(date('M d, Y g:i A', strtotime($row['uploaded_at']))) ?></small>
                        </div>
                        <?= status_badge($row['status']) ?>
                    </div>
                    <?php if (!empty($row['remarks'])): ?>
                        <div class="mt-2 small text-danger"><strong>Note:</strong> <?= h($row['remarks']) ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p class="text-center text-muted mt-5 small">This student hasn't submitted any documents yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>

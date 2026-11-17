<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/status.php';
require_login(['Student']);

$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, document_type, status, remarks, uploaded_at, reviewed_at
       FROM submissions WHERE student_id = ? ORDER BY uploaded_at DESC"
);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .record-card { border: none; border-radius: 15px; margin-bottom: 12px; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white shadow-sm">
        <h6 class="m-0 fw-bold text-center">My Medical Records</h6>
    </div>

    <div class="container mt-3">
        <?php if ($records): ?>
            <?php foreach ($records as $row): $st = $row['status']; ?>
                <div class="card record-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?= h($row['document_type']) ?></h6>
                                <small class="text-muted d-block mb-2">
                                    Uploaded: <?= h(date('M d, Y g:i A', strtotime($row['uploaded_at']))) ?>
                                </small>
                                <small>
                                    <span class="status-dot bg-<?= status_color($st) ?>"></span><?= h(status_label($st)) ?>
                                </small>
                                <?php if ($st === 'Approved' && $row['reviewed_at']): ?>
                                    <small class="text-muted d-block mt-1">
                                        Approved on <?= h(date('M d, Y', strtotime($row['reviewed_at']))) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <a href="view_file.php?id=<?= (int)$row['id'] ?>" target="_blank"
                               class="btn btn-light btn-sm rounded-pill px-3">View</a>
                        </div>

                        <?php if (!empty($row['remarks'])): ?>
                            <div class="mt-2 p-2 bg-light rounded small text-danger">
                                <strong>Reviewer's note:</strong> <?= h($row['remarks']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (is_resubmit($st)): ?>
                            <a href="upload.php?type=<?= urlencode($row['document_type']) ?>"
                               class="btn btn-danger btn-sm rounded-pill w-100 mt-3">
                                <i class="fas fa-rotate me-1"></i> Resubmit document
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center mt-5 text-muted">
                <i class="fas fa-folder-open fa-3x mb-3"></i>
                <p class="mb-3">You haven't submitted any documents yet.</p>
                <a href="upload.php" class="btn btn-primary rounded-pill px-4">Upload a document</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/navbar.php'); ?>
</body>
</html>

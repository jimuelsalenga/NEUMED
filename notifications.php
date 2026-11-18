<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$back = home_for_role($_SESSION['role']);

$stmt = $conn->prepare(
    "SELECT id, message, is_read, created_at FROM notifications
      WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Opening this page marks everything as read (after we've loaded the list, so new ones still show highlighted)
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; }
        .note { border: none; border-radius: 15px; margin-bottom: 10px; }
        .note.unread { border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="<?= h($back) ?>" class="text-dark me-3 text-decoration-none" aria-label="Back">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Notifications</h6>
    </div>

    <div class="container mt-3">
        <?php if ($items): ?>
            <?php foreach ($items as $n): ?>
                <div class="card note shadow-sm <?= $n['is_read'] ? '' : 'unread' ?>">
                    <div class="card-body py-3">
                        <div class="small <?= $n['is_read'] ? '' : 'fw-bold' ?>"><?= nl2br(h($n['message'])) ?></div>
                        <small class="text-muted"><?= h(date('M d, Y g:i A', strtotime($n['created_at']))) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center mt-5 text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <p>No notifications yet. You'll see updates here when a document is reviewed.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

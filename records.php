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

// ---- Filter tabs --------------------------------------------------------
$tabs = [
    'all'      => 'All',
    'pending'  => 'Pending Review',
    'approved' => 'Approved',
    'resubmit' => 'Needs Resubmission',
];

$empty_text = [
    'all'      => "You haven't submitted any documents yet.",
    'pending'  => 'Nothing is waiting for review right now.',
    'approved' => 'No approved documents yet.',
    'resubmit' => 'Nothing needs resubmission. Nice!',
];

function tab_key($status): ?string {
    if ($status === 'Pending')  return 'pending';
    if ($status === 'Approved') return 'approved';
    if (is_resubmit($status))   return 'resubmit';
    return null; // unknown status: only shows under "All"
}

$counts = ['all' => count($records), 'pending' => 0, 'approved' => 0, 'resubmit' => 0];
foreach ($records as $r) {
    $k = tab_key($r['status']);
    if ($k) $counts[$k]++;
}

$current = $_GET['status'] ?? 'all';
if (!isset($tabs[$current])) $current = 'all';

$visible = array_values(array_filter($records, function ($r) use ($current) {
    return $current === 'all' || tab_key($r['status']) === $current;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .record-card { border: none; border-radius: 15px; margin-bottom: 12px; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }

        /* Status tabs: scroll sideways on small phones */
        .tabs-bar { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.06); position: sticky; top: 0; z-index: 10; }
        .status-tabs { display: flex; overflow-x: auto; scrollbar-width: none; }
        .status-tabs::-webkit-scrollbar { display: none; }
        .status-tab { flex: 0 0 auto; padding: 14px 18px; color: #374151; text-decoration: none; font-size: 0.9rem; white-space: nowrap; border-bottom: 3px solid transparent; }
        .status-tab.active { color: #007bff; border-bottom-color: #007bff; font-weight: 600; }
        .tab-count { color: #007bff; }
        .status-tab[data-key="resubmit"] .tab-count { color: #dc3545; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white">
        <h6 class="m-0 fw-bold text-center">My Medical Records</h6>
    </div>

    <div class="tabs-bar">
        <nav class="status-tabs" aria-label="Filter records by status">
            <?php foreach ($tabs as $key => $label): $active = ($key === $current); ?>
                <a href="records.php?status=<?= $key ?>"
                   class="status-tab<?= $active ? ' active' : '' ?>"
                   data-key="<?= $key ?>"
                   <?= $active ? 'aria-current="true"' : '' ?>>
                    <?= h($label) ?><?php if ($key !== 'all' && $counts[$key] > 0): ?> <span class="tab-count">(<?= $counts[$key] ?>)</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="container mt-3">
        <?php if ($visible): ?>
            <?php foreach ($visible as $row): $st = $row['status']; ?>
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
                <p class="mb-3"><?= h($empty_text[$current]) ?></p>
                <?php if ($current === 'all'): ?>
                    <a href="upload.php" class="btn btn-primary rounded-pill px-4">Upload a document</a>
                <?php else: ?>
                    <a href="records.php" class="btn btn-outline-primary rounded-pill px-4">Show all records</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/navbar.php'); ?>

    <script>
        // Keep the selected tab visible when the tab bar scrolls sideways
        var active = document.querySelector('.status-tab.active');
        if (active) active.scrollIntoView({ inline: 'center', block: 'nearest' });
    </script>
</body>
</html>

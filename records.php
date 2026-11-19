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

// Status color (from status.php) -> pill/bubble style
$style_of = ['success' => 'ok', 'warning' => 'wait', 'danger' => 'bad', 'secondary' => 'idle'];
$icon_of  = ['ok' => 'fa-check', 'wait' => 'fa-hourglass-half', 'bad' => 'fa-rotate-right', 'idle' => 'fa-file-lines'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Records | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app">

    <header class="n-sticky">
        <div class="n-topbar"><h1 class="title">My records</h1></div>
        <nav class="n-tabs" aria-label="Filter records by status">
            <?php foreach ($tabs as $key => $label): $active = ($key === $current); ?>
                <a href="records.php?status=<?= $key ?>"
                   class="n-tab<?= $active ? ' active' : '' ?>"
                   data-key="<?= $key ?>"
                   <?= $active ? 'aria-current="true"' : '' ?>>
                    <?= h($label) ?><?php if ($key !== 'all' && $counts[$key] > 0): ?> <span class="tc">(<?= $counts[$key] ?>)</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>

    <main class="n-page pt-4">
        <?php if ($visible): ?>
            <?php foreach ($visible as $i => $row):
                $st = $row['status'];
                $style = $style_of[status_color($st)] ?? 'idle';
            ?>
                <div class="n-card mb-3 rise" style="--i: <?= min($i, 6) ?>">
                    <div class="n-item">
                        <div class="n-bubble <?= $style ?>"><i class="fas <?= $icon_of[$style] ?>"></i></div>
                        <div class="flex-fill">
                            <div class="title"><?= h($row['document_type']) ?></div>
                            <div class="sub">
                                Uploaded <?= h(date('M d, Y g:i A', strtotime($row['uploaded_at']))) ?>
                                <?php if ($st === 'Approved' && $row['reviewed_at']): ?>
                                    <br>Approved <?= h(date('M d, Y', strtotime($row['reviewed_at']))) ?>
                                <?php endif; ?>
                            </div>
                            <span class="n-pill <?= $style ?>"><?= h(status_label($st)) ?></span>
                        </div>
                        <a href="view_file.php?id=<?= (int)$row['id'] ?>" target="_blank" class="n-btn n-btn-ghost n-btn-sm">View</a>
                    </div>

                    <?php if (!empty($row['remarks'])): ?>
                        <div class="n-note"><strong>Reviewer's note:</strong> <?= h($row['remarks']) ?></div>
                    <?php endif; ?>

                    <?php if (is_resubmit($st)): ?>
                        <div class="px-3 pb-3">
                            <a href="upload.php?type=<?= urlencode($row['document_type']) ?>" class="n-btn n-btn-danger n-btn-sm n-btn-block">
                                <i class="fas fa-rotate-right"></i> Resubmit document
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="n-empty">
                <div class="bubble"><i class="fas fa-folder-open"></i></div>
                <h2><?= h($empty_text[$current]) ?></h2>
                <p class="small mb-4">Your submitted documents will show up here.</p>
                <?php if ($current === 'all'): ?>
                    <a href="upload.php" class="n-btn n-btn-primary">Upload a document</a>
                <?php else: ?>
                    <a href="records.php" class="n-btn n-btn-ghost">Show all records</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include('includes/navbar.php'); ?>

    <script>
        // Keep the selected tab visible when the tab bar scrolls sideways
        var active = document.querySelector('.n-tab.active');
        if (active) active.scrollIntoView({ inline: 'center', block: 'nearest' });
    </script>
</body>
</html>

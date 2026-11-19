<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$back = home_for_role($_SESSION['role']);

$stmt = $conn->prepare(
    "SELECT id, message, type, is_read, created_at FROM notifications
      WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Opening this page marks everything as read (after we've loaded the list, so new ones still show as New)
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

$new_items     = array_values(array_filter($items, function ($n) { return !$n['is_read']; }));
$earlier_items = array_values(array_filter($items, function ($n) { return  $n['is_read']; }));

/** "5 min ago", "2 days ago", or a date for older ones */
function time_ago(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) { $d = (int)floor($diff / 86400); return $d . ($d === 1 ? ' day' : ' days') . ' ago'; }
    return date('M j, Y', strtotime($ts));
}

/** Pick an icon and color from what the message says */
function notif_style(string $msg, ?string $type): array {
    $m = strtolower($msg);
    if (strpos($m, 'approved') !== false) return ['ok',   'fa-circle-check'];
    if (strpos($m, 'resubmit') !== false) return ['bad',  'fa-rotate-right'];
    if ($type === 'Reminder')             return ['wait', 'fa-bell'];
    return ['idle', 'fa-circle-info'];
}

function render_notif(array $n, int $i): void {
    [$style, $icon] = notif_style($n['message'], $n['type'] ?? null);
    $unread = !$n['is_read'];
    ?>
    <div class="n-card n-notif mb-3 rise <?= $unread ? 'unread' : '' ?>" style="--i: <?= min($i, 6) ?>">
        <div class="n-bubble <?= $style ?>"><i class="fas <?= $icon ?>"></i></div>
        <div class="flex-fill">
            <div class="msg"><?= nl2br(h($n['message'])) ?></div>
            <div class="time"><?= h(time_ago($n['created_at'])) ?></div>
        </div>
        <?php if ($unread): ?><span class="dot" aria-label="New"></span><?php endif; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Notifications | NEUMed</title>
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
            <a href="<?= h($back) ?>" class="n-iconbtn" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
            <h1 class="title">Notifications</h1>
        </div>
    </header>

    <main class="n-page pt-3">
        <?php if ($items): ?>

            <?php if ($new_items): ?>
                <h2 class="n-section-title">New</h2>
                <?php foreach ($new_items as $i => $n) render_notif($n, $i); ?>
            <?php endif; ?>

            <?php if ($earlier_items): ?>
                <h2 class="n-section-title"><?= $new_items ? 'Earlier' : 'All caught up' ?></h2>
                <?php foreach ($earlier_items as $i => $n) render_notif($n, $i + count($new_items)); ?>
            <?php endif; ?>

        <?php else: ?>
            <div class="n-empty">
                <div class="bubble"><i class="fas fa-bell-slash"></i></div>
                <h2>No notifications yet</h2>
                <p class="small mb-0">You'll see an update here when a document is reviewed.</p>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>

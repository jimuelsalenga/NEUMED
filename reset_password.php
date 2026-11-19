<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;     // the matching, still-valid reset request
$errors = [];
$done = false;

// Find a reset request that is unused and not expired
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT id, user_id FROM password_resets
          WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1"
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    }
    if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        $errors[] = "Password must be at least 8 characters and include a letter and a number.";
    }
    if ($new !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (!$errors) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $uid = (int)$reset['user_id'];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $newHash, $uid);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            // Every reset link for this account stops working
            $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['fails'], $_SESSION['lock_until']);
            $done = true;
        } else {
            error_log("Password reset failed for user #$uid: " . $conn->error);
            $errors[] = "We couldn't update your password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="auth">

<header class="auth-hero sm">
    <img class="auth-logo" src="img/logo.png" alt="New Era University seal">
    <div class="auth-brand" style="font-size:1.7rem;">NEU<span>Med</span></div>
</header>

<main class="auth-sheet">

<?php if ($done): ?>

    <div class="n-success">
        <div class="big"><i class="fas fa-check"></i></div>
        <h1>Password updated 🎉</h1>
        <p class="muted mb-4">You can now sign in with your new password.</p>
        <a href="index.php" class="n-btn n-btn-primary n-btn-block">Go to sign in</a>
    </div>

<?php elseif (!$reset): ?>

    <div class="n-success">
        <div class="big" style="background: linear-gradient(135deg, #d62839, #9d1320); box-shadow: 0 0 0 8px var(--red-soft);"><i class="fas fa-link-slash"></i></div>
        <h1>This link doesn't work</h1>
        <p class="muted mb-4">It may have expired or already been used. Reset links last 30 minutes and work once.</p>
        <a href="forgot_password.php" class="n-btn n-btn-primary n-btn-block">Get a new link</a>
    </div>

<?php else: ?>

    <h1>Choose a new password</h1>
    <p class="muted small mb-4">Pick something strong that you don't use anywhere else.</p>

    <?php if ($errors): ?>
        <div class="n-notice bad" role="alert">
            <i class="fas fa-circle-exclamation mt-1"></i>
            <div><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
        </div>
    <?php endif; ?>

    <form action="reset_password.php" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= h($token) ?>">

        <div class="mb-3">
            <label class="n-label" for="new_password">New password</label>
            <div class="n-input has-icon">
                <i class="fas fa-lock lead"></i>
                <input type="password" id="new_password" name="new_password" class="form-control n-control" autocomplete="new-password" required>
                <button type="button" class="n-toggle" data-target="new_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
            <ul class="n-checks" id="pwChecks">
                <li data-rule="len">8+ characters</li>
                <li data-rule="letter">A letter</li>
                <li data-rule="num">A number</li>
                <li data-rule="match">Passwords match</li>
            </ul>
        </div>

        <div class="mb-4">
            <label class="n-label" for="confirm_password">Confirm password</label>
            <div class="n-input has-icon">
                <i class="fas fa-lock lead"></i>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control n-control" autocomplete="new-password" required>
                <button type="button" class="n-toggle" data-target="confirm_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button type="submit" name="reset" class="n-btn n-btn-primary n-btn-block"><i class="fas fa-key"></i> Update password</button>
    </form>

    <script>
    (function () {
        document.querySelectorAll('.n-toggle').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = document.getElementById(this.getAttribute('data-target'));
                var show = i.type === 'password';
                i.type = show ? 'text' : 'password';
                this.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        });
        var pw = document.getElementById('new_password'), cf = document.getElementById('confirm_password');
        function checks() {
            var v = pw.value;
            var r = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), num: /\d/.test(v), match: v !== '' && v === cf.value };
            document.querySelectorAll('#pwChecks li').forEach(function (li) { li.classList.toggle('ok', !!r[li.getAttribute('data-rule')]); });
        }
        pw.addEventListener('input', checks);
        cf.addEventListener('input', checks);
    })();
    </script>

<?php endif; ?>

</main>
</body>
</html>
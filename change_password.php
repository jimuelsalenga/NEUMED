<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_matches($row['password'], $current)) {
            $errors[] = "Your current password is incorrect.";
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors[] = "New password must be at least 8 characters and include a letter and a number.";
        }
        if ($new !== $confirm) {
            $errors[] = "New passwords do not match.";
        }
        if ($new !== '' && $new === $current) {
            $errors[] = "Choose a password different from your current one.";
        }

        if (!$errors) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $user_id);
            if ($stmt->execute()) {
                session_regenerate_id(true);
                $success = true;
            } else {
                $errors[] = "We couldn't update your password. Please try again.";
            }
            $stmt->close();
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
    <title>Change Password | NEUMed</title>
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
            <a href="profile.php" class="n-iconbtn" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
            <h1 class="title">Change password</h1>
        </div>
    </header>

    <main class="n-page pt-4">

    <?php if ($success): ?>

        <div class="n-card n-success p-4 rise">
            <div class="big"><i class="fas fa-check"></i></div>
            <h2 class="fw-bold" style="font-size: 1.3rem;">Password updated</h2>
            <p class="muted small mb-4">Use your new password the next time you sign in.</p>
            <a href="profile.php" class="n-btn n-btn-primary n-btn-block">Back to profile</a>
        </div>

    <?php else: ?>

        <?php if ($errors): ?>
            <div class="n-notice bad" role="alert">
                <i class="fas fa-circle-exclamation mt-1"></i>
                <div><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" class="n-card p-4 rise">
            <?= csrf_field() ?>

            <?php foreach ([
                ['current_password', 'Current password', 'current-password'],
                ['new_password', 'New password', 'new-password'],
                ['confirm_password', 'Confirm new password', 'new-password'],
            ] as [$name, $label, $auto]): ?>
                <div class="mb-3">
                    <label class="n-label" for="<?= $name ?>"><?= $label ?></label>
                    <div class="n-input has-icon">
                        <i class="fas fa-lock lead"></i>
                        <input type="password" id="<?= $name ?>" name="<?= $name ?>" class="form-control n-control"
                               autocomplete="<?= $auto ?>" required>
                        <button type="button" class="n-toggle" data-target="<?= $name ?>" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <?php if ($name === 'new_password'): ?>
                        <ul class="n-checks" id="pwChecks">
                            <li data-rule="len">8+ characters</li>
                            <li data-rule="letter">A letter</li>
                            <li data-rule="num">A number</li>
                            <li data-rule="match">Passwords match</li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="change" class="n-btn n-btn-primary n-btn-block mt-4">
                <i class="fas fa-key"></i> Update password
            </button>
        </form>

    <?php endif; ?>

    </main>

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
        if (!pw) return;
        function checks() {
            var v = pw.value;
            var r = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), num: /\d/.test(v), match: v !== '' && v === cf.value };
            document.querySelectorAll('#pwChecks li').forEach(function (li) { li.classList.toggle('ok', !!r[li.getAttribute('data-rule')]); });
        }
        pw.addEventListener('input', checks);
        cf.addEventListener('input', checks);
    })();
    </script>
</body>
</html>

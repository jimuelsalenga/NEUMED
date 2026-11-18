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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-custom { background: #fff; border-radius: 25px; padding: 25px; border: none; }
        .form-control { border-radius: 15px; padding: 12px 15px; border: 1px solid #eee; font-size: 0.95rem; }
        .btn-save { background: #0d6efd; border-radius: 15px; padding: 14px; font-weight: 700; border: none; }
        label.field { font-size: 0.8rem; font-weight: 700; color: #6c757d; margin: 0 0 6px 4px; }
        .pw-wrap { position: relative; }
        .toggle-pw { position: absolute; right: 14px; top: 11px; background: none; border: 0; color: #6b7280; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="profile.php" class="text-dark me-3 text-decoration-none" aria-label="Back"><i class="fas fa-chevron-left"></i></a>
        <h6 class="m-0 fw-bold">Change Password</h6>
    </div>

    <div class="container mt-4">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm small" style="border-radius:12px;" role="status">
                <i class="fas fa-check-circle me-1"></i> Password updated.
            </div>
            <a href="profile.php" class="btn btn-outline-primary w-100 rounded-4">Back to Profile</a>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger border-0 shadow-sm small" style="border-radius:12px;" role="alert">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card-custom shadow-sm">
                <form method="POST" action="change_password.php">
                    <?= csrf_field() ?>
                    <?php foreach ([
                        ['current_password', 'Current password', 'current-password'],
                        ['new_password', 'New password', 'new-password'],
                        ['confirm_password', 'Confirm new password', 'new-password'],
                    ] as [$name, $label, $auto]): ?>
                        <div class="mb-3">
                            <label class="field d-block" for="<?= $name ?>"><?= $label ?></label>
                            <div class="pw-wrap">
                                <input type="password" id="<?= $name ?>" name="<?= $name ?>" class="form-control"
                                       autocomplete="<?= $auto ?>" required>
                                <button type="button" class="toggle-pw" data-target="<?= $name ?>">Show</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-text mb-3">At least 8 characters, with a letter and a number.</div>
                    <button type="submit" name="change" class="btn btn-primary w-100 btn-save shadow">
                        <i class="fas fa-key me-2"></i> Update password
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.querySelectorAll('.toggle-pw').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = document.getElementById(this.dataset.target);
            var show = i.type === 'password';
            i.type = show ? 'text' : 'password';
            this.textContent = show ? 'Hide' : 'Show';
        });
    });
    </script>
</body>
</html>

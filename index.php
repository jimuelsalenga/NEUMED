<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';

if (isset($_SESSION['role'])) {
    header("Location: " . home_for_role($_SESSION['role']));
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = "Too many failed attempts. Please wait a few minutes and try again.";
    } elseif (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, fullname, role, password, course FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ok = false;
        if ($user) {
            $stored = $user['password'];
            if (password_verify($password, $stored)) {
                $ok = true;
            } elseif (empty(password_get_info($stored)['algo']) && hash_equals($stored, $password)) {
                // Legacy plain-text password: accept once, then upgrade it to a hash below.
                $ok = true;
            }

            if ($ok && (empty(password_get_info($stored)['algo']) || password_needs_rehash($stored, PASSWORD_DEFAULT))) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up->bind_param('si', $newHash, $user['id']);
                $up->execute();
                $up->close();
            }
        }

        if ($ok) {
            session_regenerate_id(true);
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['course']   = $user['course'];
            header("Location: " . home_for_role($user['role']));
            exit();
        }

        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) {
            $_SESSION['lock_until'] = time() + 300;
            $_SESSION['fails'] = 0;
        }
        $error = "Incorrect email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Sign In | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="auth">

<header class="auth-hero">
    <img class="auth-logo" src="img/logo.png" alt="New Era University seal">
    <div class="auth-brand">NEU<span>Med</span></div>
    <p class="auth-tag">Your medical clearance, sorted.</p>
</header>

<main class="auth-sheet">
    <h1>Welcome back</h1>
    <p class="muted small mb-4">Sign in to track your requirements.</p>

    <?php if ($error): ?>
        <div class="n-notice bad" role="alert"><i class="fas fa-circle-exclamation mt-1"></i><span><?= h($error) ?></span></div>
    <?php endif; ?>

    <form action="index.php" method="POST" autocomplete="on">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="n-label" for="email">Institutional email</label>
            <div class="n-input has-icon">
                <i class="fas fa-envelope lead"></i>
                <input type="email" id="email" name="email" class="form-control n-control"
                       placeholder="name@neu.edu.ph" autocomplete="username" autocapitalize="none" inputmode="email"
                       value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-2">
            <label class="n-label" for="password">Password</label>
            <div class="n-input has-icon">
                <i class="fas fa-lock lead"></i>
                <input type="password" id="password" name="password" class="form-control n-control"
                       placeholder="Enter your password" autocomplete="current-password" required>
                <button type="button" class="n-toggle" id="togglePw" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="forgot_password.php" class="n-link small">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="n-btn n-btn-primary n-btn-block">Sign in</button>
    </form>

    <div class="d-flex align-items-center gap-3 my-4 muted small">
        <hr class="flex-fill m-0"><span>New to NEUMed?</span><hr class="flex-fill m-0">
    </div>

    <a href="register.php" class="n-btn n-btn-ghost n-btn-block"><i class="fas fa-user-plus"></i> Create an account</a>

    <p class="text-center mt-4 mb-0 small">
        <a href="staff_login.php" class="n-link muted"><i class="fas fa-shield-halved me-1"></i> Health Office Portal</a>
    </p>
</main>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    var pw = document.getElementById('password');
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    this.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>
</body>
</html>

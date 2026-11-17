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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-blue: #2563eb; --bg-gray: #f9fafb; }
        body { background: var(--bg-gray); min-height: 100vh; display: flex; flex-direction: column; justify-content: center; font-family: 'Segoe UI', Roboto, sans-serif; }
        .login-container { width: 100%; max-width: 400px; margin: auto; padding: 20px; }
        .app-card { background: #fff; padding: 30px 25px; border-radius: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
        .logo-box img { width: 65px; margin-bottom: 10px; }
        .app-title { font-size: 1.6rem; font-weight: 800; color: #111; margin-bottom: 2px; }
        .app-subtitle { font-size: 0.75rem; color: #6b7280; margin-bottom: 25px; }
        .input-group-custom { margin-bottom: 12px; text-align: left; position: relative; }
        .input-group-custom label { font-size: 0.75rem; font-weight: 700; color: #374151; margin-left: 5px; margin-bottom: 4px; display: block; }
        .form-control { border-radius: 12px; padding: 10px 15px; background: #f3f4f6; border: 1.5px solid transparent; font-size: 0.9rem; height: 48px; }
        .form-control:focus { background: #fff; border-color: var(--primary-blue); box-shadow: none; }
        .toggle-pw { position: absolute; right: 14px; top: 31px; background: none; border: 0; color: #6b7280; padding: 4px; }
        .btn-login { background: var(--primary-blue); border: none; border-radius: 12px; padding: 12px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .helper-links { font-size: 0.8rem; margin-top: 20px; color: #6b7280; }
        .helper-links a, .forgot-link { color: var(--primary-blue); text-decoration: none; font-weight: 600; }
        .forgot-link { font-size: 0.75rem; display: block; text-align: right; margin-top: 6px; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="app-card text-center">
        <div class="logo-box">
            <img src="img/logo.png" alt="New Era University seal">
        </div>
        <div class="app-title">MediTrack</div>
        <p class="app-subtitle">NEU Student Medical Record System</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small border-0 mb-3" style="border-radius:10px;" role="alert">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" autocomplete="on">
            <?= csrf_field() ?>

            <div class="input-group-custom">
                <label for="email">Institutional Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="name@neu.edu.ph" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group-custom">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password"><i class="fas fa-eye"></i></button>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 btn-login text-white mt-3">Sign In</button>
        </form>

        <div class="helper-links">
            <p class="mb-1"><i class="fas fa-user-shield me-1 opacity-50"></i> <a href="staff_login.php">Health Office Portal</a></p>
            <p class="mb-0">New student? <a href="register.php">Create Account</a></p>
        </div>
    </div>
</div>

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

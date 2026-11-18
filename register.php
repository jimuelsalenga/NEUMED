<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/courses.php';

// Set to false while testing with non-school emails (e.g. gmail). Keep true in production.
const REQUIRE_NEU_EMAIL = true;

$errors  = [];
$success = false;
$old = ['fullname' => '', 'student_number' => '', 'email' => '', 'course' => '', 'year' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    }
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) {
        $errors[] = "Enter your full name.";
    }
    if (!preg_match('/^[A-Za-z0-9\-]{5,20}$/', $old['student_number'])) {
        $errors[] = "Enter a valid student number (letters, numbers, and dashes only).";
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } elseif (REQUIRE_NEU_EMAIL && !preg_match('/@neu\.edu\.ph$/i', $old['email'])) {
        $errors[] = "Use your NEU email (name@neu.edu.ph).";
    }
    if (!in_array($old['course'], $courses, true)) {
        $errors[] = "Select your program.";
    }
    if (!in_array($old['year'], $year_levels, true)) {
        $errors[] = "Select your year level.";
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = "Password must be at least 8 characters and include a letter and a number.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($_POST['consent'])) {
        $errors[] = "You must agree to the Privacy Policy to create an account.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR student_number = ? LIMIT 1");
        $stmt->bind_param('ss', $old['email'], $old['student_number']);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $errors[] = "An account with this email or student number already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "Student";
            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, student_number, email, password, role, course, year, consent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('sssssss',
                $old['fullname'], $old['student_number'], $old['email'],
                $hash, $role, $old['course'], $old['year']
            );
            if ($stmt->execute()) {
                $success = true;
            } else {
                error_log("Register failed: " . $stmt->error);
                $errors[] = "We couldn't create your account. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<link rel="stylesheet" href="css/campus-bg.css">
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-blue: #2563eb; --bg-gray: #f9fafb; }
        body { background: var(--bg-gray); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Roboto, sans-serif; padding: 20px 0; }
        .register-card { width: 95%; max-width: 420px; padding: 30px 25px; border-radius: 30px; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
        .logo-box img { width: 55px; margin-bottom: 8px; }
        .app-title { font-size: 1.6rem; font-weight: 800; color: #111; margin-bottom: 2px; }
        .btn-register { background: var(--primary-blue); border: none; border-radius: 12px; padding: 12px; font-weight: 700; color: #fff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .form-control, .form-select { border-radius: 12px; padding: 10px 15px; background: #f3f4f6; border: 1.5px solid transparent; font-size: 0.9rem; min-height: 48px; }
        .form-control:focus, .form-select:focus { background: #fff; border-color: var(--primary-blue); box-shadow: none; }
        label.field { font-size: 0.75rem; font-weight: 700; color: #374151; margin: 0 0 4px 5px; display: block; text-align: left; }
        a { color: var(--primary-blue); text-decoration: none; font-weight: 600; }
        .pw-wrap { position: relative; }
        .toggle-pw { position: absolute; right: 14px; top: 11px; background: none; border: 0; color: #6b7280; padding: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="register-card text-center">
    <div class="logo-box"><img src="img/logo.png" alt="New Era University seal"></div>
    <div class="app-title">Create Account</div>
    <p class="text-muted mb-4 small">NeuMed student registration</p>

    <?php if ($success): ?>
        <div class="alert alert-success py-3 small border-0" style="border-radius:12px;" role="status">
            Account created. <a href="index.php">Sign in</a> to start your medical clearance.
        </div>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger py-2 small border-0 text-start" style="border-radius:12px;" role="alert">
                <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="text-start" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="field" for="fullname">Full name</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="<?= h($old['fullname']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="field" for="student_number">Student number</label>
                <input type="text" id="student_number" name="student_number" class="form-control" value="<?= h($old['student_number']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="field" for="email">Institutional email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@neu.edu.ph" value="<?= h($old['email']) ?>" required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="field" for="course">Program</label>
                    <select id="course" name="course" class="form-select" required>
                        <option value="" disabled <?= $old['course'] === '' ? 'selected' : '' ?>>Select program</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= h($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5">
                    <label class="field" for="year">Year level</label>
                    <select id="year" name="year" class="form-select" required>
                        <?php foreach ($year_levels as $y): ?>
                            <option value="<?= h($y) ?>" <?= $old['year'] === $y ? 'selected' : '' ?>><?= h($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="field" for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button type="button" class="toggle-pw" data-target="password">Show</button>
                </div>
                <div class="form-text ms-1">At least 8 characters, with a letter and a number.</div>
            </div>

            <div class="mb-3">
                <label class="field" for="confirm_password">Confirm password</label>
                <div class="pw-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <button type="button" class="toggle-pw" data-target="confirm_password">Show</button>
                </div>
            </div>

            <div class="form-check mb-4 small">
                <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                <label class="form-check-label" for="consent">
                    I agree to the <a href="privacy_policy.php" target="_blank">Privacy Policy</a> and consent to
                    NeuMed collecting and processing my medical documents for university clearance.
                </label>
            </div>

            <button type="submit" name="register" class="btn w-100 btn-register">Create account</button>
        </form>

        <div class="mt-4 text-center">
            <small class="text-muted">Already have an account? <a href="index.php">Sign in</a></small>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(this.dataset.target);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
    });
});
</script>
</body>
</html>

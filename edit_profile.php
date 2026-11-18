<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/courses.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$errors = [];
$saved = false;

$stmt = $conn->prepare("SELECT fullname, email, role, student_number, course, year FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit();
}

$isStudent = ($user['role'] === 'Student');

// Old accounts may still have "3rd" instead of "3rd Year"
if ($user['year'] && !in_array($user['year'], $year_levels, true) && in_array($user['year'] . ' Year', $year_levels, true)) {
    $user['year'] .= ' Year';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $course = $user['course'];
    $year = $user['year'];

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    }
    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors[] = "Enter your full name.";
    }
    if ($isStudent) {
        $course = $_POST['course'] ?? '';
        $year = $_POST['year'] ?? '';
        if (!in_array($course, $courses, true)) $errors[] = "Select your program.";
        if (!in_array($year, $year_levels, true)) $errors[] = "Select your year level.";
    }

    if (!$errors) {
        if ($isStudent) {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, course = ?, year = ? WHERE id = ?");
            $stmt->bind_param('sssi', $fullname, $course, $year, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
            $stmt->bind_param('si', $fullname, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            if ($isStudent) $_SESSION['course'] = $course;
            $user['fullname'] = $fullname;
            $user['course'] = $course;
            $user['year'] = $year;
            $saved = true;
        } else {
            error_log("Profile update failed: " . $stmt->error);
            $errors[] = "We couldn't save your changes. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-custom { background: #fff; border-radius: 25px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .form-control, .form-select { border-radius: 15px; padding: 12px 15px; border: 1px solid #eee; background: #fff; font-size: 0.95rem; }
        .readonly-field { background: #f1f3f5; color: #6c757d; cursor: not-allowed; }
        .btn-save { background: #0d6efd; border-radius: 15px; padding: 14px; font-weight: 700; border: none; }
        label.field { font-size: 0.8rem; font-weight: 700; color: #6c757d; margin: 0 0 6px 4px; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="profile.php" class="text-dark me-3 text-decoration-none" aria-label="Back"><i class="fas fa-chevron-left"></i></a>
        <h6 class="m-0 fw-bold">Edit Profile</h6>
    </div>

    <div class="container mt-4 mb-5">
        <?php if ($saved): ?>
            <div class="alert alert-success py-2 small border-0 shadow-sm mb-3" style="border-radius:12px;" role="status">
                <i class="fas fa-check-circle me-1"></i> Profile updated.
            </div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger py-2 small border-0 shadow-sm mb-3" style="border-radius:12px;" role="alert">
                <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card-custom shadow-sm">
            <form action="edit_profile.php" method="POST">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="field d-block" for="fullname">Full name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" value="<?= h($user['fullname']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="field d-block" for="email">Email address</label>
                    <input type="email" id="email" class="form-control readonly-field" value="<?= h($user['email']) ?>" readonly>
                    <small class="text-muted ms-1" style="font-size: 0.75rem;">Your email is your login and can't be changed here.</small>
                </div>

                <?php if ($isStudent): ?>
                    <div class="mb-3">
                        <label class="field d-block" for="student_number">Student number</label>
                        <input type="text" id="student_number" class="form-control readonly-field"
                               value="<?= h($user['student_number'] ?: 'Not set') ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="field d-block" for="course">Program</label>
                        <select id="course" name="course" class="form-select" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= h($c) ?>" <?= $user['course'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="field d-block" for="year">Year level</label>
                        <select id="year" name="year" class="form-select" required>
                            <?php foreach ($year_levels as $y): ?>
                                <option value="<?= h($y) ?>" <?= $user['year'] === $y ? 'selected' : '' ?>><?= h($y) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" name="update_profile" class="btn btn-primary w-100 btn-save shadow">
                    <i class="fas fa-save me-2"></i> Save changes
                </button>
            </form>
        </div>
    </div>
</body>
</html>

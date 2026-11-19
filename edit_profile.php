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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Edit Profile | NEUMed</title>
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
            <h1 class="title">Edit profile</h1>
        </div>
    </header>

    <main class="n-page pt-4">

        <?php if ($errors): ?>
            <div class="n-notice bad" role="alert">
                <i class="fas fa-circle-exclamation mt-1"></i>
                <div><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST" class="n-card p-4 rise">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="n-label" for="fullname">Full name</label>
                <div class="n-input has-icon">
                    <i class="fas fa-user lead"></i>
                    <input type="text" id="fullname" name="fullname" class="form-control n-control" autocomplete="name"
                           value="<?= h($user['fullname']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="n-label" for="email">Email address</label>
                <div class="n-input has-icon">
                    <i class="fas fa-envelope lead"></i>
                    <input type="email" id="email" class="form-control n-control" value="<?= h($user['email']) ?>" readonly>
                </div>
                <div class="form-text ms-1">Your email is your login, so it can't be changed here.</div>
            </div>

            <?php if ($isStudent): ?>
                <div class="mb-3">
                    <label class="n-label" for="student_number">Student number</label>
                    <div class="n-input has-icon">
                        <i class="fas fa-id-card lead"></i>
                        <input type="text" id="student_number" class="form-control n-control"
                               value="<?= h($user['student_number'] ?: 'Not set') ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="n-label" for="course">Program</label>
                    <select id="course" name="course" class="form-select n-control" required>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= h($c) ?>" <?= $user['course'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="n-label" for="year">Year level</label>
                    <select id="year" name="year" class="form-select n-control" required>
                        <?php foreach ($year_levels as $y): ?>
                            <option value="<?= h($y) ?>" <?= $user['year'] === $y ? 'selected' : '' ?>><?= h($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" name="update_profile" class="n-btn n-btn-primary n-btn-block">
                <i class="fas fa-floppy-disk"></i> Save changes
            </button>
        </form>
    </main>

    <?php if ($saved): ?>
        <span data-toast="Profile updated!" data-toast-icon="fa-circle-check" hidden></span>
    <?php endif; ?>

    <script src="js/app.js"></script>
</body>
</html>

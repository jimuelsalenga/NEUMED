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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Create Account | NEUMed</title>
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

<?php if ($success): ?>

    <div class="n-success">
        <div class="big"><i class="fas fa-check"></i></div>
        <h1>You're in! 🎉</h1>
        <p class="muted mb-4">Your account is ready. Sign in to start your medical clearance.</p>
        <a href="index.php" class="n-btn n-btn-primary n-btn-block">Go to sign in</a>
    </div>

<?php else: ?>

    <div class="d-flex align-items-center justify-content-between">
        <a href="index.php" class="n-back"><i class="fas fa-arrow-left me-1"></i> Sign in</a>
        <span class="n-stepcount" id="stepCount">Step 1 of 3</span>
    </div>
    <div class="n-progress"><div id="stepBar"></div></div>

    <h1 id="stepTitle">About you</h1>
    <p class="muted small mb-4" id="stepSub">Let's set up your student profile.</p>

    <?php if ($errors): ?>
        <div class="n-notice bad" role="alert">
            <i class="fas fa-circle-exclamation mt-1"></i>
            <div><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" id="regForm" novalidate>
        <?= csrf_field() ?>

        <!-- Step 1 -->
        <section class="n-step active" data-title="About you" data-sub="Let's set up your student profile.">
            <div class="mb-3">
                <label class="n-label" for="fullname">Full name</label>
                <div class="n-input has-icon">
                    <i class="fas fa-user lead"></i>
                    <input type="text" id="fullname" name="fullname" class="form-control n-control" autocomplete="name"
                           value="<?= h($old['fullname']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="n-label" for="student_number">Student number</label>
                <div class="n-input has-icon">
                    <i class="fas fa-id-card lead"></i>
                    <input type="text" id="student_number" name="student_number" class="form-control n-control"
                           pattern="[A-Za-z0-9\-]{5,20}" title="5 to 20 letters, numbers or dashes"
                           value="<?= h($old['student_number']) ?>" required>
                </div>
                <div class="form-text ms-1">You can find it on your school ID.</div>
            </div>
            <div class="mb-1">
                <label class="n-label" for="email">Institutional email</label>
                <div class="n-input has-icon">
                    <i class="fas fa-envelope lead"></i>
                    <input type="email" id="email" name="email" class="form-control n-control" placeholder="name@neu.edu.ph"
                           autocapitalize="none" inputmode="email"
                           <?= REQUIRE_NEU_EMAIL ? 'pattern=".+@[nN][eE][uU]\.edu\.ph" title="Use your NEU email (name@neu.edu.ph)"' : '' ?>
                           value="<?= h($old['email']) ?>" required>
                </div>
            </div>
        </section>

        <!-- Step 2 -->
        <section class="n-step" data-title="Your program" data-sub="So the clinic knows where you belong.">
            <div class="mb-3">
                <label class="n-label" for="course">Program</label>
                <select id="course" name="course" class="form-select n-control" required>
                    <option value="" disabled <?= $old['course'] === '' ? 'selected' : '' ?>>Select your program</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= h($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-1">
                <label class="n-label" for="year">Year level</label>
                <select id="year" name="year" class="form-select n-control" required>
                    <?php foreach ($year_levels as $y): ?>
                        <option value="<?= h($y) ?>" <?= $old['year'] === $y ? 'selected' : '' ?>><?= h($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- Step 3 -->
        <section class="n-step" data-title="Secure your account" data-sub="Pick a strong password. Almost done!">
            <div class="mb-3">
                <label class="n-label" for="password">Password</label>
                <div class="n-input has-icon">
                    <i class="fas fa-lock lead"></i>
                    <input type="password" id="password" name="password" class="form-control n-control" autocomplete="new-password" required>
                    <button type="button" class="n-toggle" data-target="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                </div>
                <ul class="n-checks" id="pwChecks">
                    <li data-rule="len">8+ characters</li>
                    <li data-rule="letter">A letter</li>
                    <li data-rule="num">A number</li>
                    <li data-rule="match">Passwords match</li>
                </ul>
            </div>
            <div class="mb-3">
                <label class="n-label" for="confirm_password">Confirm password</label>
                <div class="n-input has-icon">
                    <i class="fas fa-lock lead"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control n-control" autocomplete="new-password" required>
                    <button type="button" class="n-toggle" data-target="confirm_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="form-check small">
                <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                <label class="form-check-label" for="consent">
                    I agree to the <a href="privacy_policy.php" target="_blank" class="n-link">Privacy Policy</a> and consent to
                    NEUMed collecting and processing my medical documents for university clearance.
                </label>
            </div>
        </section>

        <div class="n-actions">
            <button type="button" class="n-btn n-btn-ghost" id="btnBack" hidden>Back</button>
            <button type="button" class="n-btn n-btn-primary" id="btnNext">Next</button>
            <button type="submit" name="register" class="n-btn n-btn-primary" id="btnSubmit" hidden>Create account</button>
        </div>
    </form>

<?php endif; ?>

</main>

<script>
(function () {
    var form = document.getElementById('regForm');
    if (!form) return;

    var steps = Array.prototype.slice.call(form.querySelectorAll('.n-step'));
    var cur = 0;
    var bar = document.getElementById('stepBar'), count = document.getElementById('stepCount'),
        title = document.getElementById('stepTitle'), sub = document.getElementById('stepSub'),
        back = document.getElementById('btnBack'), next = document.getElementById('btnNext'),
        submit = document.getElementById('btnSubmit');

    function show(i) {
        cur = i;
        steps.forEach(function (s, k) { s.classList.toggle('active', k === i); });
        bar.style.width = ((i + 1) / steps.length * 100) + '%';
        count.textContent = 'Step ' + (i + 1) + ' of ' + steps.length;
        title.textContent = steps[i].getAttribute('data-title');
        sub.textContent = steps[i].getAttribute('data-sub');
        back.hidden = (i === 0);
        next.hidden = (i === steps.length - 1);
        submit.hidden = (i !== steps.length - 1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Check the visible step's fields before moving on
    function stepValid(i) {
        var fields = steps[i].querySelectorAll('input, select');
        for (var k = 0; k < fields.length; k++) {
            if (!fields[k].checkValidity()) { fields[k].reportValidity(); return false; }
        }
        return true;
    }

    next.addEventListener('click', function () { if (stepValid(cur)) show(cur + 1); });
    back.addEventListener('click', function () { show(cur - 1); });

    // Pressing Enter should go to the next step, not submit early
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && cur < steps.length - 1) {
            e.preventDefault();
            next.click();
        }
    });

    form.addEventListener('submit', function (e) {
        for (var i = 0; i < steps.length; i++) {
            if (!stepValid(i)) { e.preventDefault(); show(i); steps[i].querySelector(':invalid') && steps[i].querySelector(':invalid').reportValidity(); return; }
        }
        submit.disabled = true; // second tap can't send it twice
        submit.textContent = 'Creating...';
    });

    // Live password checklist
    var pw = document.getElementById('password'), cf = document.getElementById('confirm_password');
    function checks() {
        var v = pw.value;
        var r = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), num: /\d/.test(v), match: v !== '' && v === cf.value };
        document.querySelectorAll('#pwChecks li').forEach(function (li) { li.classList.toggle('ok', !!r[li.getAttribute('data-rule')]); });
    }
    pw.addEventListener('input', checks);
    cf.addEventListener('input', checks);

    // Show / hide password buttons
    document.querySelectorAll('.n-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-target'));
            var showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            this.innerHTML = showing ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    });

    show(0);
})();
</script>
</body>
</html>

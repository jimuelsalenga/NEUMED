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
    <style>
        /* ---- Welcome screen + swipe-up sign-in sheet ---- */
        html { scroll-snap-type: y proximity; }
        body.intro-mode { display: block; min-height: 0; }

        /* The welcome screen stays put while the sheet slides up over it */
        .intro {
            position: fixed; inset: 0; z-index: 0;
            display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
            padding: 24px 24px calc(72px + env(safe-area-inset-bottom));
        }
        .intro-inner {
            transform: translateY(calc(var(--p, 0) * -9vh)) scale(calc(1 - var(--p, 0) * .18));
            opacity: calc(1 - var(--p, 0) * .55);
            will-change: transform, opacity;
        }
        .intro .auth-logo { width: 124px; height: 124px; padding: 6px; animation: introPop .8s cubic-bezier(.2, 1.3, .4, 1) both, float 4s ease-in-out .8s infinite; }
        .intro .auth-brand { font-size: 3rem; margin-top: 20px; animation: introUp .7s .25s cubic-bezier(.2, .8, .2, 1) both; }
        .intro .auth-tag { font-size: 1rem; animation: introUp .7s .4s cubic-bezier(.2, .8, .2, 1) both; }
        @keyframes introPop { from { transform: scale(.4); opacity: 0; } }
        @keyframes introUp  { from { transform: translateY(18px); opacity: 0; } }

        /* Empty space the page scrolls through (leaves only the sheet's top handle visible at first) */
        .spacer { height: calc(100vh - 72px); height: calc(100dvh - 72px - env(safe-area-inset-bottom)); scroll-snap-align: start; }

        body.intro-mode .sheet {
            position: relative; z-index: 2; margin: 0 auto; animation: none; scroll-snap-align: end;
            border-radius: 32px 32px 0 0; padding: 0 22px calc(28px + env(safe-area-inset-bottom));
        }
        .peek { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; height: 72px; margin: 0 -22px 6px; text-decoration: none; color: var(--muted); font-size: .8rem; font-weight: 700; }
        .peek .grab { width: 44px; height: 5px; border-radius: 99px; background: var(--border); }
        .peek .hint { transition: opacity .25s; }
        .peek .hint i { margin-right: 6px; animation: nudge 1.4s ease-in-out infinite; }
        body.opened .peek .hint { opacity: 0; }
        @keyframes nudge { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-snap-type: none; }
            .intro-inner { transform: none !important; opacity: 1 !important; }
        }
    </style>
</head>
<body class="auth intro-mode" data-open="<?= ($error !== '' || $_SERVER['REQUEST_METHOD'] === 'POST') ? '1' : '0' ?>">

<!-- Screen 1: welcome -->
<section class="intro" id="intro" aria-label="Welcome to NEUMed">
    <div class="intro-inner">
        <img class="auth-logo" src="img/logo.png" alt="New Era University seal">
        <div class="auth-brand">NEU<span>Med</span></div>
        <p class="auth-tag">Your medical clearance, sorted.</p>
    </div>
</section>

<div class="spacer" id="spacer"></div>

<!-- Screen 2: sign-in sheet (slides up over the welcome screen) -->
<main class="auth-sheet sheet" id="signin">
    <a href="#signin" class="peek" id="peek" aria-controls="signin">
        <span class="grab"></span>
        <span class="hint"><i class="fas fa-chevron-up"></i>Swipe up to sign in</span>
    </a>

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
(function () {
    var intro = document.getElementById('intro'),
        spacer = document.getElementById('spacer'),
        peek = document.getElementById('peek'),
        reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function toBottom(smooth) { window.scrollTo({ top: document.documentElement.scrollHeight, behavior: smooth && !reduce ? 'smooth' : 'auto' }); }
    function toTop(smooth)    { window.scrollTo({ top: 0, behavior: smooth && !reduce ? 'smooth' : 'auto' }); }

    // The welcome screen shrinks and fades as the sheet rises
    var ticking = false;
    function update() {
        var p = Math.min(1, Math.max(0, window.scrollY / (spacer.offsetHeight || 1)));
        intro.style.setProperty('--p', p.toFixed(3));
        document.body.classList.toggle('opened', p > 0.5);
        ticking = false;
    }
    window.addEventListener('scroll', function () {
        if (!ticking) { ticking = true; requestAnimationFrame(update); }
    }, { passive: true });

    // Tap the handle: open the sheet, or close it again
    peek.addEventListener('click', function (e) {
        e.preventDefault();
        if (document.body.classList.contains('opened')) toTop(true); else toBottom(true);
    });

    // Skip the welcome screen when it would just be in the way:
    // after a failed sign-in, when the address ends in #signin, or when coming from another page of the app
    var cameFromApp = document.referrer && document.referrer.indexOf(location.origin) === 0;
    var openNow = document.body.getAttribute('data-open') === '1' || location.hash === '#signin' || cameFromApp;
    window.addEventListener('load', function () {
        if (openNow) { toBottom(false); }
        update();
    });

    // Show / hide password
    document.getElementById('togglePw').addEventListener('click', function () {
        var pw = document.getElementById('password');
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        this.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
</script>
</body>
</html>
<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/mailer.php';

if (isset($_SESSION['role'])) {
    header("Location: " . home_for_role($_SESSION['role']));
    exit();
}

const RESET_LINK_MINUTES = 30;   // how long the emailed link works
const RESET_MAX_PER_15_MIN = 3;  // stops someone spamming an inbox

$error = "";
$sent = false;

function reset_email_html(string $name, string $url): string {
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $url  = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $mins = RESET_LINK_MINUTES;
    return "
    <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto; padding: 28px; border: 1px solid #e1eae4; border-radius: 20px;'>
        <h2 style='color: #137a3a; margin: 0 0 4px;'>NEU<span style='color:#d4a600;'>Med</span></h2>
        <p style='color:#5b6b62; margin: 0 0 20px; font-size: 13px;'>Password reset</p>
        <p>Hi <b>$name</b>,</p>
        <p>We got a request to reset your NEUMed password. Tap the button to choose a new one. This link works once and expires in $mins minutes.</p>
        <p style='text-align:center; margin: 26px 0;'>
            <a href='$url' style='background:#137a3a; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:14px; font-weight:bold; display:inline-block;'>Reset my password</a>
        </p>
        <p style='font-size:12px; color:#5b6b62;'>If the button doesn't work, copy this link into your browser:<br><span style='word-break:break-all;'>$url</span></p>
        <hr style='border:0; border-top:1px solid #eee; margin: 20px 0;'>
        <p style='font-size:12px; color:#5b6b62;'>Didn't ask for this? You can ignore this email. Your password stays the same.</p>
    </div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
    $email = trim($_POST['email'] ?? '');

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Limit how many links one account can request
            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS c FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)"
            );
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $recent = (int)$stmt->get_result()->fetch_assoc()['c'];
            $stmt->close();

            if ($recent < RESET_MAX_PER_15_MIN) {
                $token = bin2hex(random_bytes(32));          // the secret in the emailed link
                $hash  = hash('sha256', $token);              // only the hash is saved

                $stmt = $conn->prepare(
                    "INSERT INTO password_resets (user_id, token_hash, expires_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . RESET_LINK_MINUTES . " MINUTE))"
                );
                $stmt->bind_param('is', $user['id'], $hash);
                $saved = $stmt->execute();
                $stmt->close();

                if ($saved) {
                    $url = app_url('reset_password.php?token=' . $token);
                    if (!send_mail($email, $user['fullname'], 'Reset your NEUMed password', reset_email_html($user['fullname'], $url))) {
                        error_log("Reset email could not be sent to user #" . $user['id']);
                    }
                }
            }
        }

        // Always the same answer, so nobody can use this page to find out which emails are registered
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Forgot Password | NEUMed</title>
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

<?php if ($sent): ?>

    <div class="n-success">
        <div class="big"><i class="fas fa-envelope-circle-check"></i></div>
        <h1>Check your email</h1>
        <p class="muted mb-2">If that address is registered, we've sent a link to reset your password.</p>
        <p class="muted small mb-4">It works for <?= RESET_LINK_MINUTES ?> minutes. Check your spam folder if you don't see it.</p>
        <a href="index.php" class="n-btn n-btn-primary n-btn-block">Back to sign in</a>
    </div>

<?php else: ?>

    <a href="index.php" class="n-back"><i class="fas fa-arrow-left me-1"></i> Sign in</a>

    <h1 class="mt-3">Forgot your password?</h1>
    <p class="muted small mb-4">No stress. Enter your email and we'll send you a link to set a new one.</p>

    <?php if ($error): ?>
        <div class="n-notice bad" role="alert"><i class="fas fa-circle-exclamation mt-1"></i><span><?= h($error) ?></span></div>
    <?php endif; ?>

    <form action="forgot_password.php" method="POST">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="n-label" for="email">Institutional email</label>
            <div class="n-input has-icon">
                <i class="fas fa-envelope lead"></i>
                <input type="email" id="email" name="email" class="form-control n-control" placeholder="name@neu.edu.ph"
                       autocapitalize="none" inputmode="email" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
        </div>
        <button type="submit" name="send_reset" class="n-btn n-btn-primary n-btn-block">
            <i class="fas fa-paper-plane"></i> Send reset link
        </button>
    </form>

<?php endif; ?>

</main>
</body>
</html>
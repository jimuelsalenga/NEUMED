<?php
// Include this FIRST on any page (before db_conn.php). It starts the session safely.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/** Escape text for safe output in HTML. Use: <?= h($value) ?> */
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** CSRF protection: put <?= csrf_field() ?> inside every POST form. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/** Single place that decides where each role lands after login. */
function home_for_role(string $role): string {
    switch ($role) {
        case 'Student':            return 'student_dashboard.php';
        case 'Medical Staff':      return 'staff_dashboard.php';
        case 'Enrollment Officer': return 'officer_dashboard.php';
        default:                   return 'admin_dashboard.php';
    }
}

/**
 * Block pages from people who aren't logged in (or have the wrong role).
 * Usage: require_login();                  // any logged-in user
 *        require_login(['Student']);       // students only
 */
function require_login(array $roles = []): void {
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($roles && !in_array($_SESSION['role'], $roles, true)) {
        header("Location: " . home_for_role($_SESSION['role']));
        exit();
    }
}

/** True if $input matches the stored password (hashed, or legacy plain text). */
function password_matches(string $stored, string $input): bool {
    if (password_verify($input, $stored)) {
        return true;
    }
    return empty(password_get_info($stored)['algo']) && hash_equals($stored, $input);
}
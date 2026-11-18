<?php
// Helpers for the Medical ID (QR code that proves a student is cleared).
// Include AFTER security.php and db_conn.php.
require_once __DIR__ . '/helpers.php';

const ID_TOKEN_TTL = 900; // a QR code stays valid for 15 minutes

/**
 * Secret used to sign QR codes. It is created automatically the first time it's needed
 * and saved in includes/id_secret.php. Never share or upload that file.
 */
function id_secret(): string {
    static $secret = null;
    if ($secret !== null) return $secret;

    $file = __DIR__ . '/id_secret.php';
    if (!is_file($file)) {
        @file_put_contents($file, "<?php\nreturn '" . bin2hex(random_bytes(32)) . "';\n", LOCK_EX);
    }
    $value = is_file($file) ? include $file : '';
    if (!is_string($value) || strlen($value) < 32) {
        http_response_code(500);
        exit('Medical ID is not set up: the server could not create includes/id_secret.php (check folder permissions).');
    }
    return $secret = $value;
}

/** Signed token: studentId.expiry.signature (digits and hex only, safe in a URL). */
function make_id_token(int $student_id): string {
    $exp = time() + ID_TOKEN_TTL;
    $sig = substr(hash_hmac('sha256', $student_id . '.' . $exp, id_secret()), 0, 32);
    return $student_id . '.' . $exp . '.' . $sig;
}

/** Returns ['result' => 'ok' | 'expired' | 'invalid', 'student_id' => int|null] */
function verify_id_token(string $token): array {
    $parts = explode('.', $token);
    if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !ctype_xdigit($parts[2])) {
        return ['result' => 'invalid', 'student_id' => null];
    }
    [$sid, $exp, $sig] = $parts;
    $expected = substr(hash_hmac('sha256', $sid . '.' . $exp, id_secret()), 0, 32);
    if (!hash_equals($expected, strtolower($sig))) {
        return ['result' => 'invalid', 'student_id' => null];
    }
    if ((int)$exp < time()) {
        return ['result' => 'expired', 'student_id' => (int)$sid];
    }
    return ['result' => 'ok', 'student_id' => (int)$sid];
}

/** Full web address the QR code points to. */
function id_verify_url(string $token): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $host . $dir . '/verify_id.php?t=' . $token;
}

/**
 * Live, up-to-date ID details for a student (recalculates clearance first).
 * Only identity + clearance status. Never any health information.
 */
function medical_id_info(mysqli $conn, int $student_id): ?array {
    sync_clearance($conn, $student_id);

    $stmt = $conn->prepare(
        "SELECT fullname, student_number, course, year, clearance_status
           FROM users WHERE id = ? AND role = 'Student'"
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$info) return null;

    // Date the last required document was approved
    $stmt = $conn->prepare(
        "SELECT MAX(COALESCE(s.reviewed_at, s.uploaded_at)) AS cleared_on
           FROM submissions s
           JOIN requirements r ON r.req_name = s.document_type AND r.is_active = 1
          WHERE s.student_id = ? AND s.status = 'Approved'"
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $info['cleared_on'] = $stmt->get_result()->fetch_assoc()['cleared_on'] ?? null;
    $stmt->close();

    $info['cleared'] = ($info['clearance_status'] === 'Cleared');
    return $info;
}

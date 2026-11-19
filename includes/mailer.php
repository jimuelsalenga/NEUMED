<?php
// Sends emails with PHPMailer. Settings come from includes/mail_config.php (kept private).
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

function mail_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $loaded = @include __DIR__ . '/mail_config.php';
        $cfg = is_array($loaded) ? $loaded : [];
    }
    return $cfg;
}

/**
 * Full web address for a page, used inside emails.
 * Uses 'base_url' from mail_config.php so nobody can fake the host name to poison the link.
 */
function app_url(string $path): string {
    $base = rtrim((string)(mail_config()['base_url'] ?? ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

/** Returns true if the email was handed to the mail server. Problems are written to the PHP error log. */
function send_mail(string $to, string $toName, string $subject, string $html, string $text = ''): bool {
    $cfg = mail_config();
    if (empty($cfg['username']) || empty($cfg['password'])) {
        error_log('NEUMed mail is not set up: fill in includes/mail_config.php');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = str_replace(' ', '', (string)$cfg['password']);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)($cfg['port'] ?? 465);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from_email'] ?: $cfg['username'], $cfg['from_name'] ?? 'NEUMed Support');
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text !== '' ? $text : trim(strip_tags($html));

        return $mail->send();
    } catch (Exception $e) {
        error_log('NEUMed mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
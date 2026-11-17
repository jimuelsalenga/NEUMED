<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT student_id, file_path FROM submissions WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sub) {
    http_response_code(404);
    exit('File not found.');
}

// Who may open a medical document: the student who owns it, Medical Staff, or Admin.
$role    = $_SESSION['role'];
$isOwner = ($role === 'Student' && (int)$sub['student_id'] === (int)$_SESSION['user_id']);
$isStaff = in_array($role, ['Medical Staff', 'Admin'], true);

if (!$isOwner && !$isStaff) {
    http_response_code(403);
    exit('You do not have permission to view this file.');
}

// Make sure the path really points inside /uploads
$uploadsDir = realpath(__DIR__ . '/uploads');
$full       = realpath(__DIR__ . '/' . $sub['file_path']);

if (!$uploadsDir || !$full || strpos($full, $uploadsDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) {
    http_response_code(404);
    exit('File not found.');
}

$types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
$mime  = (new finfo(FILEINFO_MIME_TYPE))->file($full);
if (!isset($types[$mime])) {
    http_response_code(415);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: inline; filename="document.' . $types[$mime] . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($full);
exit;

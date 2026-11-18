<?php
// Shared logic used by the staff, officer, admin and Medical ID pages.
// Include AFTER security.php and db_conn.php.

/**
 * Recalculate users.clearance_status from the real data.
 * A student is "Cleared" only when EVERY active requirement has an Approved submission.
 * Pass a student id to update one student, or nothing to update everyone.
 */
function sync_clearance(mysqli $conn, ?int $student_id = null): void {
    $sql = "UPDATE users u
               SET u.clearance_status = CASE
                   WHEN (SELECT COUNT(*) FROM requirements r WHERE r.is_active = 1) > 0
                    AND (SELECT COUNT(*) FROM requirements r
                          WHERE r.is_active = 1
                            AND EXISTS (SELECT 1 FROM submissions s
                                         WHERE s.student_id = u.id
                                           AND s.document_type = r.req_name
                                           AND s.status = 'Approved'))
                      = (SELECT COUNT(*) FROM requirements r WHERE r.is_active = 1)
                   THEN 'Cleared' ELSE 'Not Cleared' END
             WHERE u.role = 'Student'";

    if ($student_id === null) {
        $conn->query($sql);
        return;
    }
    $stmt = $conn->prepare($sql . " AND u.id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->close();
}

/** Cleared % per program + year. Returns rows: label, total, cleared, pct */
function compliance_heatmap(mysqli $conn): array {
    $res = $conn->query(
        "SELECT COALESCE(NULLIF(u.course, ''), 'No program') AS program,
                COALESCE(NULLIF(u.year, ''), '') AS year_level,
                COUNT(*) AS total,
                SUM(u.clearance_status = 'Cleared') AS cleared
           FROM users u
          WHERE u.role = 'Student'
          GROUP BY u.course, u.year
          ORDER BY program, year_level"
    );
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $total   = (int)$r['total'];
        $cleared = (int)$r['cleared'];
        $rows[] = [
            'label'   => $r['program'] . ($r['year_level'] !== '' ? ' - ' . $r['year_level'] : ''),
            'total'   => $total,
            'cleared' => $cleared,
            'pct'     => $total > 0 ? (int)round($cleared / $total * 100) : 0,
        ];
    }
    return $rows;
}

/** Add one in-app notification for a user. */
function notify_user(mysqli $conn, int $user_id, string $message, string $type = 'Info'): bool {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param('iss', $user_id, $message, $type);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Record who did what (needed because this app handles medical records). */
function audit_log(mysqli $conn, string $action, string $details = ''): void {
    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = @$conn->prepare("INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if (!$stmt) return; // audit_log table not created yet (run migration_02.sql)
    $details = mb_substr($details, 0, 255);
    $stmt->bind_param('isss', $uid, $action, $details, $ip);
    @$stmt->execute();
    $stmt->close();
}
<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

$auto_clear = "UPDATE users u 
               SET u.clearance_status = 'Cleared' 
               WHERE u.role = 'Student' 
               AND u.clearance_status != 'Cleared'
               AND (SELECT COUNT(*) FROM submissions s WHERE s.student_id = u.id AND s.status = 'Approved' AND s.document_type = 'X-Ray') > 0
               AND (SELECT COUNT(*) FROM submissions s WHERE s.student_id = u.id AND s.status = 'Approved' AND s.document_type = 'Physical Exam') > 0";
$conn->query($auto_clear);

$pending_count_query = "SELECT COUNT(*) as total FROM submissions WHERE status = 'Pending'";
$p_result = $conn->query($pending_count_query);
$p_data = $p_result->fetch_assoc();

$query = "SELECT s.*, u.fullname, CONCAT(u.course, ' - ', u.year) as course_year 
          FROM submissions s 
          JOIN users u ON s.student_id = u.id 
          WHERE s.status = 'Pending' 
          ORDER BY s.uploaded_at ASC";
$result = $conn->query($query);

$heatmap_query = "SELECT CONCAT(u.course, ' - ', u.year) as course_year, 
                         COUNT(*) as total_students,
                         SUM(CASE WHEN u.clearance_status = 'Cleared' THEN 1 ELSE 0 END) as cleared_count
                  FROM users u 
                  WHERE u.role = 'Student' 
                  GROUP BY u.course, u.year";
$heatmap_result = $conn->query($heatmap_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Dashboard | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; padding-bottom: 100px; font-family: 'Segoe UI', Roboto, sans-serif; }
        .staff-header { background: #198754; color: white; padding: 20px; border-radius: 0 0 20px 20px; }
        .request-card { border: none; border-radius: 15px; transition: 0.3s; margin-bottom: 10px; }
        .indicator { width: 8px; height: 40px; border-radius: 4px; margin-right: 12px; }
        .heatmap-card { border: none; border-radius: 15px; }
        .progress { height: 10px; border-radius: 5px; }
        .section-title { font-size: 0.85rem; font-weight: 700; color: #475569; letter-spacing: 0.5px; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="staff-header shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="m-0 fw-bold">Staff Portal</h5>
                <small class="opacity-75">Medical Review & Tracking</small>
            </div>
            <div class="badge bg-white text-success p-2 rounded-circle shadow-sm">
                <i class="fas fa-user-md fa-lg"></i>
            </div>
        </div>
    </div>

    <div class="container">
        
        <div class="row g-2 mb-4 text-center">
            <div class="col-6">
                <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                    <h3 class="fw-bold text-success m-0"><?php echo $p_data['total']; ?></h3>
                    <small class="text-muted fw-semibold">Pending Requests</small>
                </div>
            </div>
            <div class="col-6">
                <a href="emergency_override.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm p-3 rounded-4 bg-danger text-white h-100 d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-bolt mb-1 fa-lg"></i>
                        <small class="fw-bold">Emergency Mode</small>
                    </div>
                </a>
            </div>
        </div>

        <div class="d-flex align-items-center mb-3">
            <h6 class="section-title m-0"><i class="fas fa-fire text-danger me-2"></i>Compliance Heatmap</h6>
        </div>
        
        <div class="card heatmap-card shadow-sm p-3 mb-4 bg-white">
            <?php if ($heatmap_result && $heatmap_result->num_rows > 0): ?>
                <?php while($h_row = $heatmap_result->fetch_assoc()): 
                    $percentage = ($h_row['total_students'] > 0) ? ($h_row['cleared_count'] / $h_row['total_students']) * 100 : 0;
                    $bar_color = ($percentage > 80) ? 'bg-success' : (($percentage > 40) ? 'bg-warning' : 'bg-danger');
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold text-dark" style="font-size: 0.8rem;"><?php echo htmlspecialchars($h_row['course_year']); ?></small>
                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo round($percentage); ?>% Cleared</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted text-center small my-2">No student records trackable at the moment.</p>
            <?php endif; ?>
        </div>

        <h6 class="section-title mb-3"><i class="fas fa-tasks text-success me-2"></i>Compliance Management</h6>
        <div class="row g-2 mb-4">
            <div class="col-6">
                <a href="bulk_notifications.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm p-3 text-center rounded-4 bg-white h-100">
                        <i class="fas fa-bell text-primary mb-2 fs-4"></i>
                        <small class="d-block fw-bold text-dark">Send Reminders</small>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="escalation_workflow.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm p-3 text-center rounded-4 bg-white h-100">
                        <i class="fas fa-exclamation-circle text-warning mb-2 fs-4"></i>
                        <small class="d-block fw-bold text-dark">Escalations</small>
                    </div>
                </a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title m-0"><i class="fas fa-file-medical text-success me-2"></i>Recent Submissions</h6>
            <a href="batch_approval.php" class="small text-decoration-none fw-bold text-success">Batch Mode</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card request-card shadow-sm bg-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="indicator bg-warning"></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['fullname']); ?></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($row['document_type']); ?> • <?php echo htmlspecialchars($row['course_year']); ?>
                            </small>
                        </div>
                        <a href="review_document.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                            Review
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="fas fa-check-circle fa-3x text-success opacity-50 mb-3"></i>
                <p class="text-muted m-0 small fw-semibold">All caught up! No pending records.</p>
            </div>
        <?php endif; ?>
    </div>

    <nav class="fixed-bottom bg-white border-top shadow-sm">
        <div class="row g-0 text-center py-2">
            <a href="staff_dashboard.php" class="col text-decoration-none text-success">
                <i class="fas fa-clipboard-list d-block mb-1"></i>
                <small style="font-size: 10px; font-weight: 600;">Requests</small>
            </a>
            <a href="batch_approval.php" class="col text-decoration-none text-muted">
                <i class="fas fa-layer-group d-block mb-1"></i>
                <small style="font-size: 10px;">Batch</small>
            </a>
            <a href="emergency_override.php" class="col text-decoration-none text-muted">
                <i class="fas fa-shield-alt d-block mb-1"></i>
                <small style="font-size: 10px;">Emergency</small>
            </a>
            <a href="profile.php" class="col text-decoration-none text-muted">
                <i class="fas fa-user-circle d-block mb-1"></i>
                <small style="font-size: 10px;">Profile</small>
            </a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
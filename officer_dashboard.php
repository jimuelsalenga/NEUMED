<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Enrollment Officer') {
    header("Location: index.php");
    exit();
}

$auto_clear = "UPDATE users u 
               SET u.clearance_status = 'Cleared' 
               WHERE u.role = 'Student' 
               AND (SELECT COUNT(*) FROM submissions s WHERE s.student_id = u.id AND s.status = 'Approved') >= 2";
$conn->query($auto_clear);

$heatmap_query = "SELECT course_year, 
                  COUNT(*) as total_students,
                  SUM(CASE WHEN clearance_status = 'Cleared' THEN 1 ELSE 0 END) as cleared_count
                  FROM users WHERE role = 'Student' GROUP BY course_year";
$heatmap_result = $conn->query($heatmap_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding-bottom: 80px; }
        .officer-header { background: #6f42c1; color: white; padding: 25px 20px; border-radius: 0 0 25px 25px; }
        .heatmap-card { border: none; border-radius: 15px; }
        .progress { height: 10px; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="officer-header shadow mb-4">
        <h5 class="fw-bold m-0">Enrollment Officer</h5>
        <small class="opacity-75">University Compliance Tracking</small>
    </div>

    <div class="container">
        <h6 class="fw-bold mb-3"><i class="fas fa-fire text-danger me-2"></i>Compliance Heatmap</h6>
        
        <div class="card heatmap-card shadow-sm p-3 mb-4">
            <?php while($row = $heatmap_result->fetch_assoc()): 
                $percentage = ($row['total_students'] > 0) ? ($row['cleared_count'] / $row['total_students']) * 100 : 0;
                $bar_color = ($percentage > 80) ? 'bg-success' : (($percentage > 40) ? 'bg-warning' : 'bg-danger');
            ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-bold"><?php echo $row['course_year']; ?></small>
                        <small class="text-muted"><?php echo round($percentage); ?>% Cleared</small>
                    </div>
                    <div class="progress">
                        <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <h6 class="fw-bold mb-3">Management</h6>
        <div class="row g-2">
            <div class="col-6">
                <a href="bulk_notifications.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm p-3 text-center rounded-4">
                        <i class="fas fa-bell text-primary mb-2 fs-4"></i>
                        <small class="d-block fw-bold text-dark">Send Reminders</small>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="escalation_workflow.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm p-3 text-center rounded-4">
                        <i class="fas fa-exclamation-circle text-warning mb-2 fs-4"></i>
                        <small class="d-block fw-bold text-dark">Escalations</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <nav class="fixed-bottom bg-white border-top shadow-sm">
        <div class="row g-0 text-center py-2">
            <a href="officer_dashboard.php" class="col text-decoration-none text-primary">
                <i class="fas fa-chart-pie d-block mb-1"></i>
                <small style="font-size: 10px;">Heatmap</small>
            </a>
            <a href="student_list.php" class="col text-decoration-none text-muted">
                <i class="fas fa-users d-block mb-1"></i>
                <small style="font-size: 10px;">Students</small>
            </a>
            <a href="profile.php" class="col text-decoration-none text-muted">
                <i class="fas fa-user-circle d-block mb-1"></i>
                <small style="font-size: 10px;">Profile</small>
            </a>
        </div>
    </nav>

</body>
</html>
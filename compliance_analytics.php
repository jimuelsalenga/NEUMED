<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$total_students_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Student'");
$total_students = $total_students_query->fetch_assoc()['total'] ?? 0;

$cleared_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Student' AND clearance_status = 'Cleared'");
$cleared_count = $cleared_query->fetch_assoc()['total'] ?? 0;

$cleared_percent = ($total_students > 0) ? round(($cleared_count / $total_students) * 100) : 0;

$course_stats = $conn->query("SELECT course, year, 
                             COUNT(*) as total,
                             SUM(CASE WHEN clearance_status = 'Cleared' THEN 1 ELSE 0 END) as cleared
                             FROM users 
                             WHERE role = 'Student' 
                             AND course IS NOT NULL
                             GROUP BY course, year 
                             ORDER BY cleared DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Compliance Analytics | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8fafc; padding-bottom: 50px; font-family: 'Segoe UI', sans-serif; }
        .analytics-card { border: none; border-radius: 25px; background: #fff; }
        .progress { height: 10px; border-radius: 10px; background: #f1f5f9; }
        .course-item { 
            background: white; 
            border-radius: 20px; 
            padding: 20px; 
            margin-bottom: 12px; 
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .rate-circle {
            width: 120px;
            height: 120px;
            border: 8px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border-top-color: #2563eb; /* Primary Color Indicator */
            transform: rotate(45deg);
        }
        .rate-text { transform: rotate(-45deg); }
        
        @media print {
            .btn-print, .sticky-top { display: none; }
            body { background: #fff; }
            .course-item { border: 1px solid #ddd; box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="p-3 bg-white border-bottom mb-4 d-flex align-items-center sticky-top shadow-sm">
        <a href="admin_dashboard.php" class="btn btn-light rounded-circle me-3">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="fw-bold text-dark">University Compliance Report</span>
    </div>

    <div class="container">
        
        <div class="card analytics-card shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <h6 class="text-muted small fw-bold text-uppercase mb-4">Overall Clearance Rate</h6>
                
                <div class="rate-circle mb-3">
                    <div class="rate-text text-center">
                        <h2 class="fw-bold text-primary mb-0"><?php echo $cleared_percent; ?>%</h2>
                        <small class="text-muted" style="font-size: 10px;">SUCCESS</small>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-6 border-end">
                        <h5 class="fw-bold mb-0 text-success"><?php echo $cleared_count; ?></h5>
                        <small class="text-muted">Cleared</small>
                    </div>
                    <div class="col-6">
                        <h5 class="fw-bold mb-0 text-secondary"><?php echo $total_students; ?></h5>
                        <small class="text-muted">Total Enrollees</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <h6 class="fw-bold m-0">Top 5 Compliant Groups</h6>
            <i class="fas fa-filter text-muted small"></i>
        </div>
        
        <?php if ($course_stats->num_rows > 0): ?>
            <?php while($row = $course_stats->fetch_assoc()): 
                $c_total = $row['total'];
                $c_cleared = $row['cleared'];
                $c_percent = ($c_total > 0) ? round(($c_cleared / $c_total) * 100) : 0;
            ?>
                <div class="course-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div style="max-width: 80%;">
                            <div class="fw-bold small text-dark"><?php echo $row['course']; ?></div>
                            <div class="badge bg-primary-subtle text-primary p-1 px-2 mt-1" style="font-size: 9px; border-radius: 5px;">
                                <?php echo $row['year']; ?> Year Level
                            </div>
                        </div>
                        <div class="fw-bold text-primary"><?php echo $c_percent; ?>%</div>
                    </div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-primary shadow-sm" style="width: <?php echo $c_percent; ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size: 10px; color: #64748b;">
                        <span>Progress: <?php echo $c_cleared; ?> / <?php echo $c_total; ?></span>
                        <span><?php echo ($c_total - $c_cleared); ?> Pending</span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card analytics-card text-center py-5">
                <i class="fas fa-chart-line fa-3x mb-3 text-light"></i>
                <p class="text-muted">No student data found yet.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <button onclick="window.print()" class="btn btn-dark w-100 py-3 rounded-pill fw-bold shadow-sm btn-print">
                <i class="fas fa-file-pdf me-2 text-warning"></i> Export Compliance Report
            </button>
        </div>

    </div>

</body>
</html>
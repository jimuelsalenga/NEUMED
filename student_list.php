<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Enrollment Officer') {
    header("Location: index.php");
    exit();
}

$query = "SELECT id, fullname, course, year, clearance_status FROM users 
          WHERE role = 'Student' 
          ORDER BY course ASC, year ASC, fullname ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Student List | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; padding-bottom: 80px; font-family: 'Segoe UI', sans-serif; }
        .student-card { 
            border: none; 
            border-radius: 20px; 
            margin-bottom: 12px; 
            transition: 0.2s;
            background: #fff;
        }
        .student-card:active { transform: scale(0.98); }
        .status-badge { font-size: 0.65rem; padding: 6px 12px; border-radius: 50px; font-weight: 700; text-transform: uppercase; }
        .course-text { font-size: 10px; color: #6c757d; font-weight: 600; line-height: 1.2; }
        .year-pill { 
            background: #e9ecef; 
            color: #495057; 
            padding: 2px 8px; 
            border-radius: 6px; 
            font-size: 9px; 
            margin-left: 5px;
            vertical-align: middle;
        }
        .sticky-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="p-3 bg-white border-bottom mb-3 sticky-top sticky-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="officer_dashboard.php" class="text-dark me-3">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h6 class="m-0 fw-bold">Compliance List</h6>
        </div>
        <span class="badge bg-light text-dark border rounded-pill small"><?php echo $result->num_rows; ?> Students</span>
    </div>

    <div class="container">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card student-card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div style="max-width: 70%;">
                            <div class="d-flex align-items-center mb-1">
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo $row['fullname']; ?></span>
                                <span class="year-pill"><?php echo $row['year']; ?> Year</span>
                            </div>
                            <div class="course-text">
                                <i class="fas fa-graduation-cap me-1 opacity-50"></i>
                                <?php echo $row['course']; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <?php 
                                $status = $row['clearance_status'] ?? 'Not Cleared';
                                if ($status == 'Cleared') {
                                    $class = 'bg-success text-white';
                                    $icon = '<i class="fas fa-check-circle me-1"></i>';
                                } else {
                                    $class = 'bg-warning text-dark';
                                    $icon = '<i class="fas fa-clock me-1"></i>';
                                }
                            ?>
                            <span class="badge <?php echo $class; ?> status-badge">
                                <?php echo $icon . $status; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center mt-5">
                <i class="fas fa-users fa-3x text-light mb-3"></i>
                <p class="text-muted">No students found in the records.</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="bulk_notification.php" class="btn btn-primary shadow-lg position-fixed" style="bottom: 20px; right: 20px; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 999;">
        <i class="fas fa-bullhorn"></i>
    </a>

</body>
</html>
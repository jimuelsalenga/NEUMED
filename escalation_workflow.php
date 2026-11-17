<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

$query = "SELECT s.*, u.fullname, u.course_year 
          FROM submissions s 
          JOIN users u ON s.student_id = u.id 
          WHERE s.status = 'Resubmit Required' 
          ORDER BY s.uploaded_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalation Workflow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #fff5f5; } /* Slight red tint for attention */
        .escalate-card { border-left: 4px solid #dc3545; border-radius: 12px; }
    </style>
</head>
<body>
    <div class="p-3 bg-white border-bottom mb-3 d-flex align-items-center">
        <a href="officer_dashboard.php" class="btn btn-light rounded-circle me-3 shadow-sm">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Escalation Workflow</h6>
    </div>

    <div class="container">
        <div class="alert alert-danger py-2 small shadow-sm mb-4" style="border-radius:12px;">
            <i class="fas fa-info-circle me-2"></i> These cases require urgent follow-up with the medical staff.
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card escalate-card shadow-sm mb-3 border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1 small text-danger"><?php echo $row['fullname']; ?></h6>
                                <p class="text-muted mb-2" style="font-size: 11px;">
                                    Problem: <?php echo $row['document_type']; ?> rejected.<br>
                                    Remarks: <?php echo $row['remarks']; ?>
                                </p>
                            </div>
                            <button class="btn btn-sm btn-danger rounded-pill px-3" style="font-size: 10px;">
                                Notify Staff
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-check-double fa-3x text-success mb-3 opacity-25"></i>
                <p class="text-muted">No unresolved escalations.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
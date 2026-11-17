<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

$sub_query = "SELECT document_type, status FROM submissions WHERE student_id = '$student_id'";
$sub_result = $conn->query($sub_query);
$statuses = [];
while($row = $sub_result->fetch_assoc()) {
    $statuses[$row['document_type']] = $row['status'];
}

$req_query = "SELECT req_name, description FROM requirements WHERE is_active = 1 ORDER BY id ASC";
$requirements = $conn->query($req_query);

function getStatusBadge($status) {
    if (!$status) return '<span class="badge bg-secondary opacity-50" style="font-size: 10px;">Not Started</span>';
    if ($status == 'Pending') return '<span class="badge bg-warning text-dark" style="font-size: 10px;">Pending Review</span>';
    if ($status == 'Approved') return '<span class="badge bg-success" style="font-size: 10px;">Approved</span>';
    if ($status == 'Resubmit Required') return '<span class="badge bg-danger" style="font-size: 10px;">Resubmit</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NEUMED | Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .app-bar { background: #fff; padding: 15px; border-bottom: 1px solid #eee; }
        .welcome-section { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; padding: 25px 20px; border-radius: 0 0 25px 25px; }
        .stat-card { border: none; border-radius: 15px; transition: 0.3s; margin-bottom: 12px; }
        .stat-card:active { transform: scale(0.98); }
        .offline-indicator { font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

    <div id="sync-status" class="bg-dark text-white text-center py-1 offline-indicator d-none">
        <i class="fas fa-wifi-slash"></i> Offline Mode - Records will sync once online
    </div>

    <div class="app-bar d-flex justify-content-between align-items-center shadow-sm sticky-top">
        <span class="fw-bold text-primary"><i class="fas fa-notes-medical me-2"></i>NEUMED</span>
        <div class="text-muted small"><i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Online</div>
    </div>

    <div class="welcome-section mb-4 shadow">
        <h4 class="m-0 fw-bold">Hi, <?php echo explode(' ', $fullname)[0]; ?>!</h4>
        <p class="small opacity-75 mb-0">University Medical Clearance Tracker</p>
    </div>

    <div class="container">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; background: #e7f1ff;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 fs-3 text-primary"><i class="fas fa-qrcode"></i></div>
                <div>
                    <small class="d-block fw-bold">Medical ID</small>
                    <small class="text-muted" style="font-size: 11px;">Tap to show your clearance status to guards.</small>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Requirement Checklist</h6>

        <?php if ($requirements->num_rows > 0): ?>
            <?php while($req = $requirements->fetch_assoc()): 
                $r_name = $req['req_name'];
                $current_status = $statuses[$r_name] ?? null;
            ?>
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php if($current_status == 'Approved'): ?>
                                    <i class="fas fa-check-circle text-success fs-4"></i>
                                <?php else: ?>
                                    <i class="fas fa-file-alt text-light fs-4 border rounded p-1"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold small"><?php echo $r_name; ?></div>
                                <?php echo getStatusBadge($current_status); ?>
                            </div>
                        </div>
                        <a href="upload.php?type=<?php echo urlencode($r_name); ?>" class="btn btn-light btn-sm rounded-circle shadow-sm">
                            <i class="fas fa-chevron-right text-primary" style="font-size: 12px;"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted small">No requirements found in the database.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/navbar.php'); ?>

    <script>
        window.addEventListener('offline', () => {
            document.getElementById('sync-status').classList.remove('d-none');
        });
        window.addEventListener('online', () => {
            document.getElementById('sync-status').classList.add('d-none');
        });
    </script>
</body>
</html>
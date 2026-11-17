<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_docs = $conn->query("SELECT COUNT(*) as total FROM submissions")->fetch_assoc()['total'];
$pending_docs = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status = 'Pending'")->fetch_assoc()['total'];
$cleared_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Student' AND clearance_status = 'Cleared'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; padding-bottom: 90px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header { 
            background: linear-gradient(135deg, #212529 0%, #343a40 100%); 
            color: white; 
            padding: 40px 20px; 
            border-radius: 0 0 30px 30px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .stat-card { 
            border: none; 
            border-radius: 20px; 
            transition: transform 0.2s;
        }
        .stat-card:active { transform: scale(0.95); }
        .menu-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            margin-bottom: 15px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .menu-card:hover { background: #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <div class="admin-header text-center mb-4">
        <div class="mb-2">
            <i class="fas fa-user-shield fa-2x text-info"></i>
        </div>
        <h4 class="fw-bold mb-0">System Administrator</h4>
        <p class="small opacity-75">NEUMED Infrastructure & Control</p>
    </div>

    <div class="container">
        <div class="row g-2 mb-4">
            <div class="col-6">
                <div class="card stat-card shadow-sm p-3 border-start border-info border-4">
                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Total Users</small>
                    <h4 class="fw-bold m-0"><?php echo $total_users; ?></h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card stat-card shadow-sm p-3 border-start border-success border-4">
                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Cleared</small>
                    <h4 class="fw-bold m-0"><?php echo $cleared_students; ?></h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card stat-card shadow-sm p-3 border-start border-warning border-4">
                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Pending Review</small>
                    <h4 class="fw-bold m-0 text-warning"><?php echo $pending_docs; ?></h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card stat-card shadow-sm p-3 border-start border-primary border-4">
                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Total Files</small>
                    <h4 class="fw-bold m-0"><?php echo $total_docs; ?></h4>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mb-3 px-1">Infrastructure Management</h6>

        <a href="manage_users.php" class="menu-card shadow-sm">
            <div class="icon-box bg-primary text-white shadow-sm"><i class="fas fa-users-cog"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold">Role Access Control</div>
                <small class="text-muted">Manage staff & officer permissions</small>
            </div>
            <i class="fas fa-chevron-right text-muted small"></i>
        </a>

        <a href="policy_management.php" class="menu-card shadow-sm">
            <div class="icon-box bg-success text-white shadow-sm"><i class="fas fa-file-medical"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold">Policy Management</div>
                <small class="text-muted">Update medical requirements</small>
            </div>
            <i class="fas fa-chevron-right text-muted small"></i>
        </a>

        <a href="system_maintenance.php" class="menu-card shadow-sm">
            <div class="icon-box bg-warning text-dark shadow-sm"><i class="fas fa-tools"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold">System Maintenance</div>
                <small class="text-muted">Backup, restore & error logs</small>
            </div>
            <i class="fas fa-chevron-right text-muted small"></i>
        </a>

        <a href="compliance_analytics.php" class="menu-card shadow-sm">
            <div class="icon-box bg-info text-white shadow-sm"><i class="fas fa-chart-bar"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold">Compliance Analytics</div>
                <small class="text-muted">University-wide statistics</small>
            </div>
            <i class="fas fa-chevron-right text-muted small"></i>
        </a>

    </div>

    <nav class="fixed-bottom bg-white border-top shadow-sm">
        <div class="row g-0 text-center py-2">
            <a href="admin_dashboard.php" class="col text-decoration-none text-info">
                <i class="fas fa-shield-alt d-block mb-1"></i>
                <small style="font-size: 10px;">Dashboard</small>
            </a>
            <a href="manage_users.php" class="col text-decoration-none text-muted">
                <i class="fas fa-users d-block mb-1"></i>
                <small style="font-size: 10px;">Users</small>
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
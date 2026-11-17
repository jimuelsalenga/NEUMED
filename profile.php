<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$dept = ($role == 'Medical Staff') ? 'University Clinic' : 'Student Body';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .profile-header { 
            background: <?php echo ($role == 'Medical Staff') ? '#198754' : '#007bff'; ?>; 
            padding: 50px 20px; 
            text-align: center; 
            color: white;
            border-radius: 0 0 30px 30px;
        }
        .avatar-circle { 
            width: 90px; height: 90px; 
            background: white; 
            color: <?php echo ($role == 'Medical Staff') ? '#198754' : '#007bff'; ?>; 
            font-size: 2.5rem; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 50%; margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .list-group-item { border: none; padding: 15px 20px; margin-bottom: 2px; border-radius: 10px !important; }
    </style>
</head>
<body>

    <div class="profile-header shadow">
        <div class="avatar-circle fw-bold">
            <?php echo strtoupper(substr($fullname, 0, 1)); ?>
        </div>
        <h4 class="fw-bold mb-0"><?php echo $fullname; ?></h4>
        <p class="small opacity-75 mb-0"><?php echo $role; ?> • <?php echo $dept; ?></p>
    </div>

    <div class="container mt-4 px-3">
        <div class="list-group shadow-sm mb-3">
            <a href="edit_profile.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-cog me-3 text-secondary"></i> Account Settings</span>
                <i class="fas fa-chevron-right text-muted small"></i>
            </a>

            <?php if($role == 'Medical Staff'): ?>
                <a href="update_signature.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-signature me-3 text-success"></i> Update Digital Signature</span>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
            <?php endif; ?>

            <a href="privacy_policy.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="fas fa-shield-alt me-3 text-secondary"></i> Privacy Policy</span>
                <i class="fas fa-chevron-right text-muted small"></i>
            </a>
        </div>

        <div class="list-group shadow-sm">
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">
                <i class="fas fa-sign-out-alt me-3"></i> Logout Account
            </a>
        </div>
    </div>

    <?php 
        if($role == 'Student') {
            include('includes/navbar.php'); 
        } else {
            ?>
            <nav class="fixed-bottom bg-white border-top shadow-sm">
                <div class="row g-0 text-center py-2">
                    <a href="staff_dashboard.php" class="col text-decoration-none text-muted">
                        <i class="fas fa-clipboard-list d-block mb-1"></i>
                        <small style="font-size: 10px;">Requests</small>
                    </a>
                    <a href="batch_approval.php" class="col text-decoration-none text-muted">
                        <i class="fas fa-layer-group d-block mb-1"></i>
                        <small style="font-size: 10px;">Batch</small>
                    </a>
                    <a href="profile.php" class="col text-decoration-none text-success">
                        <i class="fas fa-user-circle d-block mb-1"></i>
                        <small style="font-size: 10px;">Profile</small>
                    </a>
                </div>
            </nav>
            <?php
        }
    ?>

</body>
</html>
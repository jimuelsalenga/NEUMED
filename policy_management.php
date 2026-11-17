<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$msg = "";

if (isset($_POST['add_req'])) {
    $name = mysqli_real_escape_string($conn, $_POST['req_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $sql = "INSERT INTO requirements (req_name, description) VALUES ('$name', '$desc')";
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success py-2 small'>New requirement added!</div>";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM requirements WHERE id = '$id'");
    header("Location: policy_management.php");
}

$requirements = $conn->query("SELECT * FROM requirements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Management | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding-bottom: 50px; }
        .req-card { border: none; border-radius: 15px; margin-bottom: 10px; transition: 0.3s; }
        .req-card:hover { transform: translateX(5px); }
    </style>
</head>
<body>

    <div class="p-3 bg-white border-bottom mb-4 d-flex align-items-center sticky-top shadow-sm">
        <a href="admin_dashboard.php" class="btn btn-light rounded-circle me-3">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Medical Policy Management</h6>
    </div>

    <div class="container">
        <?php echo $msg; ?>

        <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
            <h6 class="fw-bold mb-3 small text-muted text-uppercase">Add Requirement</h6>
            <form action="" method="POST">
                <div class="mb-3">
                    <input type="text" name="req_name" class="form-control border-0 bg-light py-3" placeholder="Requirement Name (e.g. CBC)" required style="border-radius:12px;">
                </div>
                <div class="mb-3">
                    <textarea name="description" class="form-control border-0 bg-light py-3" placeholder="Brief description..." rows="2" style="border-radius:12px;"></textarea>
                </div>
                <button type="submit" name="add_req" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i> Register Requirement
                </button>
            </form>
        </div>

        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Active Requirements</h6>
        <?php if ($requirements->num_rows > 0): ?>
            <?php while($row = $requirements->fetch_assoc()): ?>
                <div class="card req-card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-dark"><?php echo $row['req_name']; ?></div>
                            <small class="text-muted d-block" style="font-size: 11px;"><?php echo $row['description']; ?></small>
                        </div>
                        <a href="?delete=<?php echo $row['id']; ?>" class="text-danger p-2" onclick="return confirm('Delete this requirement?')">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted opacity-25 mb-3"></i>
                <p class="text-muted small">No requirements registered yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
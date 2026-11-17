<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['bulk_approve'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = implode(',', $_POST['selected_ids']);
        $staff_sig = $_SESSION['fullname'];
        $query = "UPDATE submissions SET status = 'Approved', staff_signature = '$staff_sig' WHERE id IN ($ids)";
        if ($conn->query($query)) {
            $msg = "<div class='alert alert-success'>Successfully approved selected records.</div>";
        }
    }
}

$query = "SELECT s.*, u.fullname FROM submissions s JOIN users u ON s.student_id = u.id WHERE s.status = 'Pending'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light" style="padding-bottom: 80px;">
    <div class="p-3 bg-white border-bottom d-flex align-items-center sticky-top">
        <a href="staff_dashboard.php" class="text-dark me-3"><i class="fas fa-arrow-left"></i></a>
        <h6 class="m-0 fw-bold">Batch Approval Mode</h6>
    </div>

    <div class="container mt-3">
        <?php echo $msg ?? ''; ?>
        <form action="" method="POST">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">Select multiple to approve</small>
                <button type="submit" name="bulk_approve" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                    Approve Selected
                </button>
            </div>

            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card border-0 shadow-sm mb-2 rounded-3">
                    <div class="card-body d-flex align-items-center py-2">
                        <input type="checkbox" name="selected_ids[]" value="<?php echo $row['id']; ?>" class="form-check-input me-3">
                        <div>
                            <div class="fw-bold small"><?php echo $row['fullname']; ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?php echo $row['document_type']; ?></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </form>
    </div>
</body>
</html>
<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$query = "SELECT * FROM submissions WHERE student_id = '$student_id' ORDER BY uploaded_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; }
        .record-card { border: none; border-radius: 15px; margin-bottom: 12px; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white shadow-sm">
        <h6 class="m-0 fw-bold text-center">My Medical Records</h6>
    </div>

    <div class="container mt-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card record-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo $row['document_type']; ?></h6>
                                <small class="text-muted d-block mb-2">
                                    Uploaded: <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                </small>
                                
                                <?php 
                                    $status = $row['status'];
                                    $dot_color = ($status == 'Approved') ? 'bg-success' : (($status == 'Pending') ? 'bg-warning' : 'bg-danger');
                                ?>
                                <small><span class="status-dot <?php echo $dot_color; ?>"></span> <?php echo $status; ?></small>
                            </div>
                            <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-light btn-sm rounded-pill px-3">View</a>
                        </div>
                        
                        <?php if($row['remarks']): ?>
                            <div class="mt-2 p-2 bg-light rounded small text-danger">
                                <strong>Remarks:</strong> <?php echo $row['remarks']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center mt-5 text-muted">
                <i class="fas fa-folder-open fa-3x mb-3"></i>
                <p>No records found.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/navbar.php'); ?>
</body>
</html>
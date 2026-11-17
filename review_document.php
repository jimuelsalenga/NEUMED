<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: staff_dashboard.php");
    exit();
}

$query = "SELECT s.*, u.fullname, u.course_year 
          FROM submissions s 
          JOIN users u ON s.student_id = u.id 
          WHERE s.id = '$id'";
$result = $conn->query($query);
$data = $result->fetch_assoc();

if (!$data) {
    die("Record not found.");
}

if (isset($_POST['submit_review'])) {
    $status = $_POST['status'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $staff_id = $_SESSION['user_id'];

    $update = "UPDATE submissions SET 
                status = '$status', 
                remarks = '$remarks', 
                staff_signature = (SELECT digital_signature FROM users WHERE id = '$staff_id') 
               WHERE id = '$id'";

    if ($conn->query($update)) {
        echo "<script>alert('Review submitted successfully!'); window.location='staff_dashboard.php';</script> text-success";
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Document | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; padding-bottom: 50px; }
        .doc-preview {
            background: #333;
            border-radius: 15px;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doc-preview img { max-width: 100%; height: auto; }
        .card { border: none; border-radius: 20px; }
        .btn-approve { background: #198754; color: white; border-radius: 12px; }
        .btn-history { border-radius: 12px; }
    </style>
</head>
<body>

    <div class="p-3 bg-white border-bottom d-flex align-items-center sticky-top">
        <a href="staff_dashboard.php" class="text-dark me-3 text-decoration-none">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Review Submission</h6>
    </div>

    <div class="container mt-3">
        <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0"><?php echo $data['fullname']; ?></h5>
                    <small class="text-muted"><?php echo $data['course_year']; ?> • <?php echo $data['document_type']; ?></small>
                </div>
                <span class="badge <?php echo ($data['status'] == 'Pending') ? 'bg-warning text-dark' : 'bg-success'; ?>">
                    <?php echo $data['status']; ?>
                </span>
            </div>
        </div>

        <div class="mb-3">
            <a href="student_history.php?id=<?php echo $data['student_id']; ?>" class="btn btn-outline-primary btn-sm w-100 btn-history shadow-sm py-2">
                <i class="fas fa-history me-2"></i> View Past Submissions & History
            </a>
        </div>

        <div class="doc-preview shadow-sm mb-4">
            <?php 
                $file_ext = strtolower(pathinfo($data['file_path'], PATHINFO_EXTENSION));
                if ($file_ext == 'pdf'): 
            ?>
                <embed src="<?php echo $data['file_path']; ?>" type="application/pdf" width="100%" height="400px">
            <?php else: ?>
                <img src="<?php echo $data['file_path']; ?>" alt="Medical Record">
            <?php endif; ?>
        </div>

        <div class="card shadow-sm p-4">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Decision</label>
                    <select name="status" class="form-select border-0 bg-light py-3" style="border-radius: 12px;" required>
                        <option value="Approved" <?php if($data['status'] == 'Approved') echo 'selected'; ?>>Approve Document</option>
                        <option value="Resubmit Required" <?php if($data['status'] == 'Resubmit Required') echo 'selected'; ?>>Request Resubmission (Reject)</option>
                        <option value="Pending" <?php if($data['status'] == 'Pending') echo 'selected'; ?>>Keep as Pending</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Findings & Remarks</label>
                    <textarea name="remarks" class="form-control border-0 bg-light py-3" style="border-radius: 12px;" rows="4" placeholder="Enter findings or reason for rejection..."><?php echo $data['remarks']; ?></textarea>
                </div>

                <div class="bg-light p-3 rounded-4 mb-4 text-center border">
					<small class="text-muted d-block mb-2 text-uppercase fw-bold" style="font-size: 10px;">Reviewer Authentication</small>
					
					<?php 
					$staff_id = $_SESSION['user_id'];
					$staff_query = $conn->query("SELECT digital_signature FROM users WHERE id = '$staff_id'");
					$staff_data = $staff_query->fetch_assoc();

					if (!empty($staff_data['digital_signature'])): ?>
						<div class="mb-2">
							<img src="<?php echo $staff_data['digital_signature']; ?>" alt="Staff Signature" style="max-height: 80px; filter: contrast(150%);">
						</div>
						<h6 class="mb-0 fw-bold border-top d-inline-block pt-1 px-4"><?php echo $_SESSION['fullname']; ?></h6>
						<p class="text-success small mb-0 mt-1"><i class="fas fa-check-circle"></i> Signature Linked</p>
					<?php else: ?>
						<h6 class="mb-0 fw-bold text-muted"><?php echo $_SESSION['fullname']; ?></h6>
						<p class="text-danger small mb-2"><i class="fas fa-exclamation-triangle"></i> No digital signature found.</p>
						<a href="update_signature.php" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size: 11px;">
							Set Signature Now
						</a>
					<?php endif; ?>
				</div>

                <button type="submit" name="submit_review" class="btn btn-approve w-100 py-3 fw-bold shadow">
                    Submit Final Review
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
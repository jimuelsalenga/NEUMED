<?php
session_start();
include('includes/db_conn.php');

$student_id = $_GET['id'];
$query = "SELECT * FROM submissions WHERE student_id = '$student_id' ORDER BY uploaded_at DESC";
$history = $conn->query($query);

$user_q = "SELECT fullname FROM users WHERE id = '$student_id'";
$user_data = $conn->query($user_q)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="p-3 bg-white border-bottom">
        <h6 class="m-0 fw-bold">History: <?php echo $user_data['fullname']; ?></h6>
    </div>

    <div class="container mt-3">
        <ul class="list-group border-0 shadow-sm rounded-4 overflow-hidden">
            <?php while($row = $history->fetch_assoc()): ?>
                <li class="list-group-item border-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><?php echo $row['document_type']; ?></div>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></small>
                        </div>
                        <span class="badge <?php echo ($row['status'] == 'Approved') ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>
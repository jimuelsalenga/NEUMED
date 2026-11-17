<?php
session_start();
include('includes/db_conn.php');

$search_result = null;
if (isset($_GET['search_query'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_query']);
    $q = "SELECT s.*, u.fullname, u.course_year 
          FROM users u 
          LEFT JOIN submissions s ON u.id = s.student_id 
          WHERE u.fullname LIKE '%$search%' AND u.role = 'Student'";
    $search_result = $conn->query($q);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Override</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-danger text-white"> <div class="p-3 d-flex align-items-center">
        <a href="staff_dashboard.php" class="text-white me-3"><i class="fas fa-arrow-left"></i></a>
        <h6 class="m-0 fw-bold">EMERGENCY OVERRIDE</h6>
    </div>

    <div class="container mt-2">
        <form action="" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search_query" class="form-control border-0 py-3 rounded-start-pill" placeholder="Search Student Name..." required>
                <button class="btn btn-dark rounded-end-pill px-4" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <?php if ($search_result): ?>
            <h6 class="fw-bold mb-3">Records Found:</h6>
            <?php while($row = $search_result->fetch_assoc()): ?>
                <div class="card border-0 text-dark mb-3 rounded-4 shadow">
                    <div class="card-body">
                        <div class="fw-bold"><?php echo $row['fullname']; ?></div>
                        <small class="text-muted d-block mb-2"><?php echo $row['document_type'] ?: 'No records uploaded yet'; ?></small>
                        <?php if($row['file_path']): ?>
                            <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-danger w-100 btn-sm rounded-pill mt-2">
                                VIEW MEDICAL RECORD
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>
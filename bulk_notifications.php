<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

$msg = "";
if (isset($_POST['send_bulk'])) {
    $course = mysqli_real_escape_string($conn, $_POST['course_select']);
    $year = mysqli_real_escape_string($conn, $_POST['year_select']);
    $reminder = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO notifications (user_id, message, type) 
            SELECT id, '$reminder', 'Reminder' FROM users 
            WHERE course = '$course' 
            AND year = '$year' 
            AND role = 'Student'";
    
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success py-2 small shadow-sm border-0' style='border-radius:12px;'>
                <i class='fas fa-check-circle me-1'></i> Reminders sent to $course ($year Year) Students!
                </div>";
    }
}

$course_list = $conn->query("SELECT DISTINCT course FROM users 
                             WHERE role = 'Student' 
                             AND course IS NOT NULL 
                             AND course != '' 
                             ORDER BY course ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bulk Notifications | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-custom { background: #fff; border-radius: 25px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .form-select, .form-control { border-radius: 12px; border: 1px solid #eee; padding: 12px; background: #f9fbff; font-size: 0.9rem; }
        .btn-send { background: #0d6efd; border: none; border-radius: 15px; padding: 15px; font-weight: 700; transition: 0.3s; }
    </style>
</head>
<body class="bg-light">

    <div class="p-3 bg-white border-bottom mb-4 d-flex align-items-center sticky-top">
        <a href="officer_dashboard.php" class="text-dark me-3 text-decoration-none">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="fw-bold">Bulk Notifications</span>
    </div>

    <div class="container">
        <?php echo $msg; ?>
        
        <div class="card-custom">
            <form action="" method="POST">
                <div class="row g-2 mb-3">
                    <div class="col-12 mb-2">
                        <label class="form-label small fw-bold text-muted ms-1 text-uppercase">Target Program</label>
                        <select name="course_select" class="form-select" required>
                            <option value="" selected disabled>Select Course</option>
                            <?php if($course_list->num_rows > 0): ?>
                                <?php while($row = $course_list->fetch_assoc()): ?>
                                    <option value="<?php echo $row['course']; ?>"><?php echo $row['course']; ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option disabled>No students registered yet</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted ms-1 text-uppercase">Year Level</label>
                        <select name="year_select" class="form-select" required>
                            <option value="1st">1st Year</option>
                            <option value="2nd">2nd Year</option>
                            <option value="3rd">3rd Year</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted ms-1 text-uppercase">Message Content</label>
                    <textarea name="message" class="form-control" rows="4">Good day! This is a reminder to submit your pending medical requirements to the Health Office. Thank you!</textarea>
                </div>

                <button type="submit" name="send_bulk" class="btn btn-primary w-100 btn-send shadow">
                    <i class="fas fa-paper-plane me-2"></i> Broadcast to Students
                </button>
            </form>
        </div>
    </div>

</body>
</html>
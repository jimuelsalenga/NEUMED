<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id']; 
$message = "";

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['update_profile'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);

    $email = $user['email'];
    $course = $user['course'];
    $year = $user['year'];

    if ($user['role'] == 'Student') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $course = mysqli_real_escape_string($conn, $_POST['course']);
        $year = mysqli_real_escape_string($conn, $_POST['year']);
    }

    $update_sql = "UPDATE users SET fullname = '$fullname', email = '$email', course = '$course', year = '$year' WHERE id = '$user_id'";
    
    if ($conn->query($update_sql)) {
        $_SESSION['fullname'] = $fullname;
        $message = "<div class='alert alert-success py-2 small border-0 shadow-sm mb-3' style='border-radius:12px;'>
                        <i class='fas fa-check-circle me-1'></i> Profile updated successfully!
                    </div>";

        $user['fullname'] = $fullname;
        $user['email'] = $email;
        $user['course'] = $course;
        $user['year'] = $year;
    } else {
        $message = "<div class='alert alert-danger py-2 small border-0 shadow-sm mb-3' style='border-radius:12px;'>
                        <i class='fas fa-exclamation-triangle me-1'></i> Error updating profile.
                    </div>";
    }
}

$courses = [
    "BS in Astronomy", "AB in Broadcasting", "AB in Communication", "AB in Economics", 
    "AB in Journalism", "AB in Political Science", "Bachelor of Elementary Education", 
    "Bachelor of Library and Information Science", "Bachelor of Music", 
    "Bachelor of Music Major in Music Education", "Bachelor of Public Administration", 
    "Bachelor of Secondary Education", "BS in Accountancy", "BS in Accounting Information System", 
    "BS in Accounting Technology", "BS in Architecture", "BS in Biology", 
    "BS in Business Administration", "BS in Civil Engineering", "BS in Computer Science", 
    "BS in Criminology", "BS in Electrical Engineering", "BS in Electronics Engineering", 
    "BS in Entertainment and Multimedia Computing", "BS in Entrepreneurship", 
    "BS in Foreign Service", "BS in Industrial Engineering", "BS in Information Systems", 
    "BS in Information Technology", "BS in Mechanical Engineering", "BS in Medical Technology", 
    "BS in Nursing", "BS in Physical Therapy", "BS in Psychology", 
    "BS in Real Estate Management", "BS in Respiratory Therapy"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Profile | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding-bottom: 90px; font-family: 'Segoe UI', sans-serif; }
        .card-custom { background: #fff; border-radius: 25px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .form-control, .form-select { border-radius: 15px; padding: 12px 15px; border: 1px solid #eee; background: #fff; font-size: 0.95rem; }
        .readonly-field { background: #f1f3f5; color: #6c757d; cursor: not-allowed; }
        .btn-save { background: #0d6efd; border-radius: 15px; padding: 14px; font-weight: 700; border: none; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="profile.php" class="text-dark me-3 text-decoration-none"><i class="fas fa-chevron-left"></i></a>
        <h6 class="m-0 fw-bold">Edit Profile</h6>
    </div>

    <div class="container mt-4">
        <?php echo $message; ?>
        
        <div class="card-custom shadow-sm">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2 ms-1">FULL NAME</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2 ms-1">EMAIL ADDRESS</label>
                    <input type="email" name="email" 
                           class="form-control <?php echo ($user['role'] != 'Student') ? 'readonly-field' : ''; ?>" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           <?php echo ($user['role'] != 'Student') ? 'readonly' : ''; ?> required>
                    <?php if($user['role'] != 'Student'): ?>
                        <small class="text-muted ms-1" style="font-size: 0.7rem;">Only students can modify their email portal.</small>
                    <?php endif; ?>
                </div>

                <?php if($user['role'] == 'Student'): ?>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2 ms-1">UNDERGRADUATE PROGRAM</label>
                    <select name="course" class="form-select" required>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo ($user['course'] == $c) ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="small fw-bold text-muted mb-2 ms-1">YEAR LEVEL</label>
                    <select name="year" class="form-select" required>
                        <?php 
                        $years = ["1st", "2nd", "3rd", "4th", "5th"];
                        foreach($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($user['year'] == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?> Year
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit" name="update_profile" class="btn btn-primary w-100 btn-save shadow">
                    <i class="fas fa-save me-2"></i> Save Changes
                </button>
            </form>
        </div>

        <div class="text-center mt-4 mb-5">
             <span class="badge bg-light text-muted rounded-pill p-2 px-3 border" style="font-size: 0.7rem;">
                LOGGED IN AS: <?php echo strtoupper($user['role']); ?>
             </span>
        </div>
    </div>

    <?php include('includes/navbar.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
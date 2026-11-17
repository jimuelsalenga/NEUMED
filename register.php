<?php
session_start();
include('includes/db_conn.php');

$msg = "";
if (isset($_POST['register'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 
    $role = "Student";

    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger py-2 small border-0' style='border-radius:10px;'>Email already registered.</div>";
    } else {
        $sql = "INSERT INTO users (fullname, email, password, role, course, year) 
                VALUES ('$fullname', '$email', '$password', '$role', '$course', '$year')";
        
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success py-2 small border-0' style='border-radius:10px;'>Registered! <a href='index.php' class='fw-bold text-decoration-none'>Login here</a></div>";
        }
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
    <title>NEUMED | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px 0; }
        .register-card { width: 95%; max-width: 420px; padding: 30px; border-radius: 25px; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .btn-register { background: #007bff; border: none; border-radius: 12px; padding: 12px; font-weight: 600; color: #fff; }
        .form-control, .form-select { border-radius: 12px; padding: 12px; background: #f8f9fa; border: 1px solid #eee; font-size: 0.9rem; }
        .brand-logo { font-size: 1.8rem; font-weight: 800; color: #007bff; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="register-card text-center">
    <div class="brand-logo">Create Account</div>
    <p class="text-muted mb-4 small">Please fill in your student details</p>

    <?php echo $msg; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
        </div>
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="University Email" required>
        </div>
        
        <div class="row g-2 mb-3 text-start">
            <div class="col-8">
                <label class="small text-muted ms-2">Course</label>
                <select name="course" class="form-select" required>
                    <option value="" selected disabled>Select Course</option>
                    <?php foreach($courses as $c): ?>
                        <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4">
                <label class="small text-muted ms-2">Year</label>
                <select name="year" class="form-select" required>
                    <option value="1st">1st</option>
                    <option value="2nd">2nd</option>
                    <option value="3rd">3rd</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <input type="password" name="password" class="form-control" placeholder="Create Password" required>
        </div>
        <button type="submit" name="register" class="btn btn-primary w-100 btn-register shadow-sm">Sign Up</button>
    </form>
    
    <div class="mt-4">
        <small class="text-muted">Already have an account? <a href="index.php" class="text-decoration-none fw-bold">Sign In</a></small>
    </div>
</div>

</body>
</html>
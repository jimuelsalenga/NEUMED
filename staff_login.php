<?php
session_start();
include('includes/db_conn.php');

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'Student') header("Location: student_dashboard.php");
    elseif ($_SESSION['role'] == 'Medical Staff') header("Location: staff_dashboard.php");
    elseif ($_SESSION['role'] == 'Enrollment Officer') header("Location: officer_dashboard.php");
    else header("Location: admin_dashboard.php");
    exit();
}

$error = "";
if (isset($_POST['staff_login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 

    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password' AND role != 'Student'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];

        if ($_SESSION['role'] == 'Medical Staff') header("Location: staff_dashboard.php");
        elseif ($_SESSION['role'] == 'Enrollment Officer') header("Location: officer_dashboard.php");
        else header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Access Denied. Invalid staff credentials.";
    }
}
?>

<!DOCTYPE html>
<link rel="stylesheet" href="css/campus-bg.css">
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEUMED | Staff Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --staff-dark: #1e293b; --primary-blue: #3d5af1; }
        body { background: #f1f5f9; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .staff-card { width: 90%; max-width: 380px; padding: 40px 30px; border-radius: 25px; background: #fff; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border-top: 5px solid var(--staff-dark); }
        .form-control { border-radius: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .btn-staff { background: var(--staff-dark); color: white; border-radius: 12px; padding: 12px; font-weight: 700; border: none; }
        .btn-staff:hover { background: #0f172a; color: white; }
    </style>
</head>
<body>

<div class="staff-card text-center">
    <i class="fas fa-user-shield fa-3x mb-3" style="color: var(--staff-dark);"></i>
    <h4 class="fw-bold mb-1">Health Office</h4>
    <p class="text-muted small mb-4">Authorized Personnel Access Only</p>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small border-0 mb-3" style="border-radius:10px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3 text-start">
            <label class="small fw-bold text-muted ms-2">Staff Email</label>
            <input type="email" name="email" class="form-control" placeholder="admin@neu.edu.ph" required>
        </div>
        <div class="mb-4 text-start">
            <label class="small fw-bold text-muted ms-2">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" name="staff_login" class="btn btn-staff w-100 shadow">Portal Login</button>
    </form>
    
    <div class="mt-4">
        <a href="index.php" class="text-decoration-none small fw-bold text-primary"><i class="fas fa-arrow-left me-1"></i> Back to Student Login</a>
    </div>
</div>

</body>
</html>
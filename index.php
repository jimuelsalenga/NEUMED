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
if (isset($_POST['login'])) {
    $selected_course = mysqli_real_escape_string($conn, $_POST['course']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 

    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password' AND course = '$selected_course'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['course'] = $user['course'];
        header("Location: index.php");
        exit();
    } else {
        $error = "The credentials or the selected program do not match our records.";
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
    <title>NEUMED Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-blue: #2563eb; --bg-gray: #f9fafb; }
        body { background: var(--bg-gray); min-height: 100vh; display: flex; flex-direction: column; justify-content: center; font-family: 'Segoe UI', Roboto, sans-serif; overflow: hidden; }
        .login-container { width: 100%; max-width: 400px; margin: auto; padding: 20px; }
        .app-card { background: #fff; padding: 30px 25px; border-radius: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
        .logo-box img { width: 65px; margin-bottom: 10px; }
        .app-title { font-size: 1.6rem; font-weight: 800; color: #111; margin-bottom: 2px; }
        .app-subtitle { font-size: 0.75rem; color: #6b7280; margin-bottom: 25px; }
        .input-group-custom { margin-bottom: 12px; text-align: left; }
        .input-group-custom label { font-size: 0.75rem; font-weight: 700; color: #374151; margin-left: 5px; margin-bottom: 4px; display: block; }
        .form-control, .form-select { border-radius: 12px; padding: 10px 15px; background: #f3f4f6; border: 1.5px solid transparent; font-size: 0.9rem; height: 48px; }
        .form-control:focus, .form-select:focus { background: #fff; border-color: var(--primary-blue); box-shadow: none; }
        .btn-login { background: var(--primary-blue); border: none; border-radius: 12px; padding: 12px; font-weight: 700; font-size: 0.95rem; margin-top: 15px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .helper-links { font-size: 0.8rem; margin-top: 20px; color: #6b7280; }
        .helper-links a { color: var(--primary-blue); text-decoration: none; font-weight: 600; }
        .logo-box { animation: float 3s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-5px); } 100% { transform: translateY(0px); } }
        .forgot-link { font-size: 0.7rem; float: right; margin-top: 5px; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="app-card text-center">
        <div class="logo-box">
            <img src="img/logo.png" alt="Logo">
        </div>
        <div class="app-title">NEUMED</div>
        <p class="app-subtitle">NEU Student Medical Record System</p>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small border-0 mb-3" style="border-radius:10px; font-size:0.75rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="input-group-custom">
                <label>Program</label>
                <select name="course" class="form-select">
                    <?php foreach($courses as $c): ?>
                        <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group-custom">
                <label>Institutional Email</label>
                <input type="email" name="email" class="form-control" placeholder="name@neu.edu.ph" required>
            </div>

            <div class="input-group-custom">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <a href="forgot_password.php" class="forgot-link text-primary fw-bold">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 btn-login text-white mt-4">Sign In</button>
        </form>

        <div class="helper-links">
            <p class="mb-1"><i class="fas fa-user-shield me-1 opacity-50"></i> <a href="staff_login.php">Health Office Portal</a></p>
            <p class="mb-0">New student? <a href="register.php">Create Account</a></p>
        </div>
    </div>
</div>

</body>
</html>
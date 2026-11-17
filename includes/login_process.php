<?php
session_start();
include('includes/db_conn.php');

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Plain text input

    // I-check kung tugma ang email AT password sa database
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Isave ang session data para sa role-based access [cite: 81]
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];

        // Role-Based Redirect base sa features [cite: 83]
        if ($user['role'] == 'Student') {
            header("Location: student_dashboard.php");
        } elseif ($user['role'] == 'Medical Staff') {
            header("Location: staff_dashboard.php");
        } elseif ($user['role'] == 'Enrollment Officer') {
            header("Location: officer_dashboard.php");
        } else {
            header("Location: admin_dashboard.php");
        }
        exit();
    } else {
        echo "<script>alert('Maling email o password!'); window.location='login.php';</script>";
    }
}
?>
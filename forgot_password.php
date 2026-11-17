<?php
session_start();
include('includes/db_conn.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$msg = "";
$step = 1; 

if (isset($_POST['verify'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);

    $check = $conn->query("SELECT id, fullname FROM users WHERE email = '$email' AND course = '$course'");
    
    if ($check->num_rows > 0) {
        $user_data = $check->fetch_assoc();
        $user_id = $user_data['id'];
        $user_name = $user_data['fullname'];

        $temp_pass = substr(md5(time()), 0, 8);

        if ($conn->query("UPDATE users SET password = '$temp_pass' WHERE id = '$user_id'")) {

            if (sendResetEmail($email, $user_name, $temp_pass)) {
                $msg = "<div class='alert alert-success py-3 small border-0 shadow-sm' style='border-radius:15px;'>
                        <i class='fas fa-paper-plane me-2'></i> Temporary password has been sent to your email! 
                        Please check your inbox (and spam folder).
                        </div>";
                $step = 1;
            } else {
                $msg = "<div class='alert alert-danger py-2 small border-0' style='border-radius:10px;'>Verification success, but email failed to send.</div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger py-2 small border-0' style='border-radius:10px;'>Identity not found. Please check your email and program.</div>";
    }
}

function sendResetEmail($recipientEmail, $name, $newPass) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jrqconnect@gmail.com'; 
        $mail->Password   = 'rgbieypenmyzscpg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('jrqconnect@gmail.com', 'NEUMED NEU Support');
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = "NEUMED Password Recovery";
        
        $mail->Body = "
            <div style='font-family: sans-serif; padding: 25px; border: 1px solid #e1e8ed; border-radius: 20px; max-width: 500px; margin: auto;'>
                <h2 style='color: #2563eb; text-align: center;'>NEUMED Recovery</h2>
                <p>Hi <b>$name</b>,</p>
                <p>We received a request to reset your medical portal password. Here is your temporary access code:</p>
                <div style='background: #f3f4f6; padding: 20px; text-align: center; border-radius: 12px; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #1f2937;'>
                    $newPass
                </div>
                <p style='color: #ef4444; font-size: 12px; margin-top: 15px;'>Note: Please change this password immediately after logging in for your security.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 11px; color: #9ca3af; text-align: center;'>This is an automated message. Please do not reply.</p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        return false;
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
    <title>Forgot Password | NEUMED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-blue: #2563eb; }
        body { background: #f9fafb; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .reset-card { width: 90%; max-width: 400px; padding: 35px 30px; border-radius: 30px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        .form-control, .form-select { border-radius: 12px; padding: 12px; background: #f3f4f6; border: 1.5px solid transparent; font-size: 0.9rem; }
        .form-control:focus { background: #fff; border-color: var(--primary-blue); box-shadow: none; }
        .btn-verify { background: var(--primary-blue); border: none; border-radius: 12px; padding: 14px; font-weight: 700; }
    </style>
</head>
<body>

<div class="reset-card text-center">
    <div class="mb-3">
        <i class="fas fa-lock-open fa-3x text-primary opacity-25"></i>
    </div>
    <h4 class="fw-bold mb-1">Account Recovery</h4>
    <p class="text-muted small mb-4">We will send a temporary password to your email.</p>

    <?php echo $msg; ?>

    <form action="" method="POST">
        <div class="mb-3 text-start">
            <label class="small fw-bold text-muted ms-2">Select Your Program</label>
            <select name="course" class="form-select" required>
                <?php foreach($courses as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4 text-start">
            <label class="small fw-bold text-muted ms-2">Institutional Email</label>
            <input type="email" name="email" class="form-control" placeholder="name@neu.edu.ph" required>
        </div>
        <button type="submit" name="verify" class="btn btn-primary w-100 btn-verify shadow-sm">
            Verify & Send Email
        </button>
    </form>

    <div class="mt-4">
        <a href="index.php" class="text-decoration-none small text-muted fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Back to Sign In
        </a>
    </div>
</div>

</body>
</html>
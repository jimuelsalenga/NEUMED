<?php
date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_OFF); // keep existing "if ($conn->query(...))" code working

$host   = "127.0.0.1";
$user   = "root";
$pass   = "";               // On your hosted site, use a real DB user + strong password, not root
$dbname = "meditrack_db";

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("NeuMed DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'"); // timestamps display in Philippine time
?>

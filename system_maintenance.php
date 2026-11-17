<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$msg = "";

if (isset($_POST['backup'])) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $name = "NEUMED_db";
    
    $filename = "backup_" . $name . "_" . date("Y-m-d_H-i-s") . ".sql";
    $command = "C:\\xampp\\mysql\\bin\\mysqldump --user=$user --password=$pass --host=$host $name > $filename";
    
    system($command, $output);

    if (file_exists($filename)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($filename));
        readfile($filename);
        unlink($filename);
        exit;
    } else {
        $msg = "<div class='alert alert-danger'>Backup failed. Check mysqldump path.</div>";
    }
}

$error_logs = [];
$log_file = ini_get('error_log');

if (isset($_POST['view_logs'])) {
    if (file_exists($log_file) && is_readable($log_file)) {
        $logs = file($log_file);
        $error_logs = array_slice($logs, -20);
    } else {
        $msg = "<div class='alert alert-warning'>No readable logs found at: $log_file</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; padding-bottom: 50px; }
        .maint-card { border: none; border-radius: 20px; transition: 0.3s; }
        .log-container { background: #212529; color: #00ff00; padding: 15px; border-radius: 10px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>

    <div class="p-3 bg-white border-bottom mb-4 d-flex align-items-center sticky-top shadow-sm">
        <a href="admin_dashboard.php" class="btn btn-light rounded-circle me-3">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="fw-bold text-dark">System Maintenance</span>
    </div>

    <div class="container">
        <?php echo $msg; ?>

        <div class="card maint-card shadow-sm p-4 text-center mb-4 border-0">
            <div class="mb-3">
                <span class="bg-warning bg-opacity-10 p-4 rounded-circle d-inline-block">
                    <i class="fas fa-database fa-2x text-warning"></i>
                </span>
            </div>
            <h6 class="fw-bold">Database Management</h6>
            <p class="text-muted small">Generate and download a full SQL backup of the current system data.</p>
            <form action="" method="POST">
                <button type="submit" name="backup" class="btn btn-warning w-100 py-3 rounded-pill fw-bold shadow-sm">
                    <i class="fas fa-download me-2"></i> Download Full SQL Backup
                </button>
            </form>
        </div>

        <div class="card maint-card shadow-sm p-4 mb-4 border-0">
            <div class="text-center mb-3">
                <span class="bg-primary bg-opacity-10 p-4 rounded-circle d-inline-block">
                    <i class="fas fa-server fa-2x text-primary"></i>
                </span>
                <h6 class="fw-bold mt-3">Uptime & Error Logs</h6>
                <p class="text-muted small">Monitor server errors and system health logs.</p>
            </div>

            <form action="" method="POST" class="mb-3">
                <button type="submit" name="view_logs" class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                    <i class="fas fa-terminal me-2"></i> View Recent Error Logs
                </button>
            </form>

            <?php if (!empty($error_logs)): ?>
                <div class="log-container shadow-inner">
                    <div class="mb-2 text-info border-bottom border-secondary pb-1 small">Showing last 20 entries:</div>
                    <?php foreach ($error_logs as $log): ?>
                        <div class="mb-1 border-bottom border-dark pb-1"><?php echo htmlspecialchars($log); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>
<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'Document'; 
$student_id = $_SESSION['user_id'];
$message = "";

if (isset($_POST['upload'])) {
    $target_dir = "uploads/";
    
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES["fileToUpload"]["name"]));
    $target_file = $target_dir . $file_name;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed_types = array("jpg", "jpeg", "png", "pdf");

    if (!in_array($fileType, $allowed_types)) {
        $message = "<div class='alert alert-danger rounded-4 py-2 small shadow-sm'><i class='fas fa-exclamation-circle me-2'></i>Only JPG, JPEG, PNG & PDF files are allowed.</div>";
    } elseif ($_FILES["fileToUpload"]["size"] > 5000000) {
        $message = "<div class='alert alert-danger rounded-4 py-2 small shadow-sm'>File is too large. Max 5MB allowed.</div>";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

            $check_existing = "SELECT id FROM submissions WHERE student_id = '$student_id' AND document_type = '$type'";
            $exist_result = $conn->query($check_existing);

            if ($exist_result->num_rows > 0) {
                $sql = "UPDATE submissions SET file_path='$target_file', status='Pending', uploaded_at=NOW(), remarks=NULL WHERE student_id = '$student_id' AND document_type = '$type'";
            } else {
                $sql = "INSERT INTO submissions (student_id, document_type, file_path, status) 
                        VALUES ('$student_id', '$type', '$target_file', 'Pending')";
            }
            
            if ($conn->query($sql)) {
                echo "<script>window.location='student_dashboard.php?success=1';</script>";
                exit();
            }
        } else {
            $message = "<div class='alert alert-danger rounded-4 py-2 small shadow-sm'>Error uploading file to server.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload <?php echo htmlspecialchars($type); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 80px; font-family: 'Segoe UI', sans-serif; }
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 25px;
            padding: 50px 20px;
            background: #fff;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .upload-area:active { background: #f0f7ff; transform: scale(0.98); }
        .btn-submit { border-radius: 15px; padding: 15px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="student_dashboard.php" class="text-dark me-3 text-decoration-none">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Upload <?php echo htmlspecialchars($type); ?></h6>
    </div>

    <div class="container mt-4">
        <?php echo $message; ?>
        
        <div class="text-center mb-4">
            <p class="text-muted small">Please ensure the document is clear and readable before submitting.</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area shadow-sm mb-4" onclick="document.getElementById('fileInput').click();">
                <div class="icon-circle bg-light d-inline-block p-4 rounded-circle mb-3">
                    <i class="fas fa-file-upload fa-2x text-primary"></i>
                </div>
                <p class="mb-1 fw-bold">Choose a file or take a photo</p>
                <small class="text-muted d-block">PDF, PNG, or JPG (Max 5MB)</small>
                
                <input type="file" name="fileToUpload" id="fileInput" class="d-none" required onchange="previewName()" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            
            <div id="file-info" class="mb-4 text-center d-none">
                <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    <span class="small fw-bold text-truncate" id="file-name-display" style="max-width: 200px;"></span>
                    <i class="fas fa-times-circle ms-2 text-danger" onclick="location.reload()" style="cursor:pointer"></i>
                </div>
            </div>

            <button type="submit" name="upload" class="btn btn-primary w-100 btn-submit shadow">
                <i class="fas fa-paper-plane me-2"></i>Submit for Review
            </button>
        </form>

        <div class="mt-5 p-3 rounded-4 border-0 shadow-sm bg-white">
            <h6 class="fw-bold small mb-2"><i class="fas fa-info-circle text-primary me-1"></i> Reminders:</h6>
            <ul class="text-muted small mb-0 ps-3">
                <li>Make sure all four corners of the page are visible.</li>
                <li>Avoid blurry images or dark lighting.</li>
                <li>Ensure the file name is recognizable.</li>
            </ul>
        </div>
    </div>

    <script>
        function previewName() {
            const input = document.getElementById('fileInput');
            const info = document.getElementById('file-info');
            const display = document.getElementById('file-name-display');
            if(input.files.length > 0) {
                info.classList.remove('d-none');
                display.innerText = input.files[0].name;
            }
        }
    </script>
</body>
</html>
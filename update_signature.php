<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Medical Staff') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_POST['save_signature'])) {
    $signature_data = $_POST['signature_image'];
    
    $query = "UPDATE users SET digital_signature = '$signature_data' WHERE id = '$user_id'";
    if ($conn->query($query)) {
        $message = "<div class='alert alert-success py-2 small'>Signature updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger py-2 small'>Error saving signature.</div>";
    }
}

$res = $conn->query("SELECT digital_signature FROM users WHERE id = '$user_id'");
$user = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; }
        #signature-pad {
            border: 2px dashed #ccc;
            border-radius: 15px;
            background: #fff;
            width: 100%;
            height: 250px;
            touch-action: none; /* Importante para sa mobile touch */
        }
        .preview-sig {
            max-width: 200px;
            border-bottom: 1px solid #000;
        }
    </style>
</head>
<body>

    <div class="p-3 border-bottom bg-white d-flex align-items-center">
        <a href="profile.php" class="text-dark me-3"><i class="fas fa-arrow-left"></i></a>
        <h6 class="m-0 fw-bold">Update Digital Signature</h6>
    </div>

    <div class="container mt-4">
        <?php echo $message; ?>

        <?php if($user['digital_signature']): ?>
            <div class="text-center mb-4">
                <small class="text-muted d-block mb-2">Current Signature:</small>
                <img src="<?php echo $user['digital_signature']; ?>" class="preview-sig" alt="Signature">
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm p-3 rounded-4">
            <label class="small fw-bold text-muted mb-2 text-center">Sign inside the box below:</label>
            <canvas id="signature-pad"></canvas>
            
            <div class="d-flex gap-2 mt-3">
                <button type="button" id="clear" class="btn btn-light btn-sm w-100 rounded-pill">Clear</button>
                <form action="" method="POST" id="sig-form" class="w-100">
                    <input type="hidden" name="signature_image" id="signature_image">
                    <button type="submit" name="save_signature" id="save" class="btn btn-success btn-sm w-100 rounded-pill">Save Signature</button>
                </form>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-light rounded-3 small text-muted text-center">
            <i class="fas fa-info-circle me-1"></i> This signature will be automatically attached to all medical documents you approve.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }

        window.onresize = resizeCanvas;
        resizeCanvas();

        document.getElementById('clear').addEventListener('click', () => {
            signaturePad.clear();
        });

        document.getElementById('sig-form').addEventListener('submit', (e) => {
            if (signaturePad.isEmpty()) {
                alert("Please provide a signature first.");
                e.preventDefault();
            } else {
                const dataURL = signaturePad.toDataURL();
                document.getElementById('signature_image').value = dataURL;
            }
        });
    </script>
</body>
</html>
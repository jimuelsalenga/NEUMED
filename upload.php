<?php
require_once 'includes/security.php';
require_once 'includes/db_conn.php';
require_once 'includes/status.php';
require_login(['Student']);

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5MB (images are compressed in the browser first)

$student_id = (int)$_SESSION['user_id'];
$type = trim($_GET['type'] ?? '');
$message = "";

// Active requirements, keyed by name (so ?type= can only be a real requirement)
$requirements = [];
$res = $conn->query("SELECT req_name, description FROM requirements WHERE is_active = 1 ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $requirements[$row['req_name']] = $row;
}

if ($type !== '' && !isset($requirements[$type])) {
    header("Location: upload.php");
    exit();
}

// This student's current submissions
$subs = [];
$stmt = $conn->prepare("SELECT id, document_type, status, remarks, file_path FROM submissions WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $subs[$row['document_type']] = $row;
}
$stmt->close();

$existing = ($type !== '') ? ($subs[$type] ?? null) : null;
$locked = $existing && $existing['status'] === 'Approved';

if ($type !== '' && !$locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $f = $_FILES['fileToUpload'] ?? null;

    if (!csrf_valid()) {
        $message = "Your session expired. Please try again.";
    } elseif (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Choose a file first.";
    } elseif ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > MAX_UPLOAD_BYTES) {
        $message = "That file is too large. The maximum is 5MB.";
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $message = "The upload didn't finish. Please try again.";
    } else {
        // Check what the file really is, not just its extension
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

        if (!isset($allowed[$mime])) {
            $message = "Only JPG, PNG, or PDF files are allowed.";
        } else {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }

            // Random name: nobody's personal file name ends up on the server
            $name = $student_id . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];

            if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                $path = 'uploads/' . $name;
                $stmt = $conn->prepare(
                    "INSERT INTO submissions (student_id, document_type, file_path, status)
                     VALUES (?, ?, ?, 'Pending')
                     ON DUPLICATE KEY UPDATE
                        file_path = VALUES(file_path), status = 'Pending', remarks = NULL,
                        staff_signature = NULL, reviewed_by = NULL, reviewed_at = NULL,
                        uploaded_at = NOW()"
                );
                $stmt->bind_param('iss', $student_id, $type, $path);

                if ($stmt->execute()) {
                    if ($existing && !empty($existing['file_path'])) {
                        $old = $dir . basename($existing['file_path']);
                        if (is_file($old)) { @unlink($old); } // remove the replaced file
                    }
                    header("Location: student_dashboard.php?success=1");
                    exit();
                }
                error_log("Upload DB error: " . $stmt->error);
                @unlink($dir . $name);
                $message = "We couldn't save your submission. Please try again.";
            } else {
                $message = "Error uploading file to the server.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $type !== '' ? 'Upload ' . h($type) : 'Upload' ?> | MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f9fc; padding-bottom: 90px; font-family: 'Segoe UI', sans-serif; }
        .upload-area { border: 2px dashed #007bff; border-radius: 25px; padding: 40px 20px; background: #fff; text-align: center; cursor: pointer; transition: 0.3s; }
        .upload-area:active { background: #f0f7ff; transform: scale(0.98); }
        .btn-submit { border-radius: 15px; padding: 15px; font-weight: bold; }
        #thumb { max-width: 100%; max-height: 220px; border-radius: 12px; }
    </style>
</head>
<body>

<?php if ($type === ''): /* ---------- Chooser: which document are you uploading? ---------- */ ?>

    <div class="p-3 border-bottom bg-white text-center">
        <h6 class="m-0 fw-bold">Upload a document</h6>
    </div>
    <div class="container mt-3">
        <p class="text-muted small">Choose the requirement you want to submit.</p>
        <?php foreach ($requirements as $name => $req): $st = $subs[$name]['status'] ?? null; ?>
            <a href="upload.php?type=<?= urlencode($name) ?>" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm mb-2" style="border-radius:15px;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold small"><?= h($name) ?></div>
                            <?= status_badge($st) ?>
                        </div>
                        <i class="fas fa-chevron-right text-primary small"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (!$requirements): ?>
            <p class="text-center text-muted small mt-5">No requirements have been set up yet.</p>
        <?php endif; ?>
    </div>
    <?php include('includes/navbar.php'); ?>

<?php else: /* ---------- Upload form for one requirement ---------- */ ?>

    <div class="p-3 border-bottom bg-white d-flex align-items-center sticky-top">
        <a href="student_dashboard.php" class="text-dark me-3 text-decoration-none" aria-label="Back">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h6 class="m-0 fw-bold">Upload <?= h($type) ?></h6>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-danger rounded-4 py-2 small shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($requirements[$type]['description'])): ?>
            <p class="text-muted small text-center mb-3"><?= h($requirements[$type]['description']) ?></p>
        <?php endif; ?>

        <?php if ($existing && is_resubmit($existing['status']) && !empty($existing['remarks'])): ?>
            <div class="alert alert-warning border-0 shadow-sm small" style="border-radius:12px;">
                <strong>Why this needs resubmission:</strong> <?= h($existing['remarks']) ?>
            </div>
        <?php endif; ?>

        <?php if ($locked): ?>
            <div class="alert alert-success border-0 shadow-sm small" style="border-radius:12px;">
                <i class="fas fa-check-circle me-1"></i> This requirement is already approved. You don't need to upload it again.
            </div>
            <a href="student_dashboard.php" class="btn btn-outline-primary w-100 rounded-4">Back to Home</a>
        <?php else: ?>

        <form action="upload.php?type=<?= urlencode($type) ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
            <?= csrf_field() ?>

            <div class="upload-area shadow-sm mb-3" id="dropZone" role="button" tabindex="0">
                <div class="bg-light d-inline-block p-4 rounded-circle mb-3">
                    <i class="fas fa-file-upload fa-2x text-primary"></i>
                </div>
                <p class="mb-1 fw-bold">Choose a file or take a photo</p>
                <small class="text-muted d-block">PDF, PNG, or JPG (max 5MB)</small>
                <input type="file" name="fileToUpload" id="fileInput" class="d-none" accept=".jpg,.jpeg,.png,.pdf,image/*">
            </div>

            <div id="file-info" class="mb-3 text-center d-none">
                <img id="thumb" class="mb-2 d-none" alt="Preview of the selected file">
                <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    <span class="small fw-bold text-truncate" id="file-name-display" style="max-width: 180px;"></span>
                    <span class="small text-muted ms-2" id="file-size-display"></span>
                    <button type="button" class="btn btn-link text-danger p-0 ms-2" id="removeFile" aria-label="Remove file">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="upload" id="submitBtn" class="btn btn-primary w-100 btn-submit shadow" disabled>
                <i class="fas fa-paper-plane me-2"></i>Submit for Review
            </button>
        </form>

        <div class="mt-4 p-3 rounded-4 border-0 shadow-sm bg-white">
            <h6 class="fw-bold small mb-2"><i class="fas fa-info-circle text-primary me-1"></i> Before you submit</h6>
            <ul class="text-muted small mb-0 ps-3">
                <li>Make sure the whole document is visible and the text is readable.</li>
                <li>Avoid blurry photos and dark lighting.</li>
                <li>Photos are shrunk automatically so they upload quickly.</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function () {
        var input = document.getElementById('fileInput');
        if (!input) return;
        var zone = document.getElementById('dropZone'),
            info = document.getElementById('file-info'),
            nameEl = document.getElementById('file-name-display'),
            sizeEl = document.getElementById('file-size-display'),
            thumb = document.getElementById('thumb'),
            btn = document.getElementById('submitBtn'),
            form = document.getElementById('uploadForm'),
            MAX = 5 * 1024 * 1024;

        zone.addEventListener('click', function () { input.click(); });
        zone.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });

        function fmt(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }

        // Shrink big phone photos (max 2000px, JPEG) so they fit the 5MB limit.
        async function compress(file) {
            if (!file.type.startsWith('image/')) return file;
            try {
                var bmp = await createImageBitmap(file);
                var scale = Math.min(1, 2000 / Math.max(bmp.width, bmp.height));
                if (scale === 1 && file.size <= 1.5 * 1048576) return file;
                var c = document.createElement('canvas');
                c.width = Math.round(bmp.width * scale);
                c.height = Math.round(bmp.height * scale);
                c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
                var blob = await new Promise(function (r) { c.toBlob(r, 'image/jpeg', 0.85); });
                if (!blob || blob.size >= file.size) return file;
                return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
            } catch (e) { return file; }
        }

        function reset() {
            input.value = '';
            info.classList.add('d-none');
            thumb.classList.add('d-none');
            btn.disabled = true;
        }

        input.addEventListener('change', async function () {
            if (!input.files.length) { reset(); return; }
            var file = await compress(input.files[0]);
            if (file !== input.files[0]) {
                var dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            }
            if (file.size > MAX) {
                alert('That file is larger than 5MB. Please choose a smaller one.');
                reset();
                return;
            }
            nameEl.textContent = file.name;
            sizeEl.textContent = fmt(file.size);
            info.classList.remove('d-none');
            if (file.type.startsWith('image/')) {
                thumb.src = URL.createObjectURL(file);
                thumb.classList.remove('d-none');
            } else {
                thumb.classList.add('d-none');
            }
            btn.disabled = false;
        });

        document.getElementById('removeFile').addEventListener('click', reset);

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
        });
    })();
    </script>

<?php endif; ?>
</body>
</html>

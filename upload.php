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

// A file above the server's limit makes PHP drop the whole form, so nothing seems to happen
if ($type !== '' && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $message = "That file is too large for the server. Please choose a smaller one (max 5MB).";
}

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

// Status color (from status.php) -> pill/bubble style
$style_of = ['success' => 'ok', 'warning' => 'wait', 'danger' => 'bad', 'secondary' => 'idle'];
$icon_of  = ['ok' => 'fa-check', 'wait' => 'fa-hourglass-half', 'bad' => 'fa-rotate-right', 'idle' => 'fa-file-arrow-up'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title><?= $type !== '' ? 'Upload ' . h($type) : 'Upload' ?> | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app<?= $type !== '' ? ' no-dock' : '' ?>">

<?php if ($type === ''): /* ---------- Chooser: which document are you uploading? ---------- */ ?>

    <header class="n-sticky">
        <div class="n-topbar center"><h1 class="title">Upload</h1></div>
    </header>

    <main class="n-page pt-4">
        <p class="muted small fw-bold mb-3">What are you submitting today?</p>

        <?php $i = 0; foreach ($requirements as $name => $req):
            $st = $subs[$name]['status'] ?? null;
            $style = $style_of[status_color($st)] ?? 'idle';
        ?>
            <a href="upload.php?type=<?= urlencode($name) ?>" class="n-card n-item mb-3 rise" style="--i: <?= $i++ ?>">
                <div class="n-bubble <?= $style ?>"><i class="fas <?= $icon_of[$style] ?>"></i></div>
                <div class="flex-fill">
                    <div class="title"><?= h($name) ?></div>
                    <?php if (!empty($req['description'])): ?><div class="sub"><?= h($req['description']) ?></div><?php endif; ?>
                    <span class="n-pill <?= $style ?>"><?= h(status_label($st)) ?></span>
                </div>
                <i class="fas fa-chevron-right muted small"></i>
            </a>
        <?php endforeach; ?>

        <?php if (!$requirements): ?>
            <div class="n-empty">
                <div class="bubble"><i class="fas fa-clipboard-list"></i></div>
                <h2>Nothing to upload yet</h2>
                <p class="small mb-0">No requirements have been set up.</p>
            </div>
        <?php endif; ?>
    </main>

    <?php include('includes/navbar.php'); ?>

<?php else: /* ---------- Upload form for one requirement ---------- */ ?>

    <header class="n-sticky">
        <div class="n-topbar">
            <a href="student_dashboard.php" class="n-iconbtn" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
            <h1 class="title">Upload</h1>
        </div>
    </header>

    <main class="n-page pt-4">

        <section class="mb-4 rise">
            <h2 class="fw-bold m-0" style="font-size: 1.5rem;"><?= h($type) ?></h2>
            <?php if (!empty($requirements[$type]['description'])): ?>
                <p class="muted small mt-1 mb-0"><?= h($requirements[$type]['description']) ?></p>
            <?php endif; ?>
        </section>

        <?php if ($message): ?>
            <div class="n-notice bad" role="alert"><i class="fas fa-circle-exclamation mt-1"></i><span><?= h($message) ?></span></div>
        <?php endif; ?>

        <?php if ($existing && is_resubmit($existing['status']) && !empty($existing['remarks'])): ?>
            <div class="n-notice warn">
                <i class="fas fa-circle-info mt-1"></i>
                <span><strong>Why this was sent back:</strong> <?= h($existing['remarks']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($locked): ?>
            <div class="n-card text-center p-4 rise">
                <div class="n-bubble ok mx-auto mb-3" style="width:72px;height:72px;border-radius:50%;font-size:1.8rem;"><i class="fas fa-check"></i></div>
                <h3 class="fw-bold" style="font-size:1.15rem;">Already approved</h3>
                <p class="muted small">You don't need to upload this requirement again.</p>
                <a href="student_dashboard.php" class="n-btn n-btn-primary n-btn-block">Back to Home</a>
            </div>
        <?php else: ?>

        <form action="upload.php?type=<?= urlencode($type) ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
            <?= csrf_field() ?>
            <input type="hidden" name="upload" value="1">

            <div class="n-drop mb-3 rise" style="--i:1" id="dropZone" role="button" tabindex="0" aria-label="Choose a file or take a photo">
                <div class="icon"><i class="fas fa-cloud-arrow-up"></i></div>
                <p class="fw-bold mb-1" style="font-size:1.05rem;">Tap to choose a file or take a photo</p>
                <p class="muted small mb-0">PDF, PNG or JPG &middot; up to 5MB</p>
                <input type="file" name="fileToUpload" id="fileInput" class="d-none" accept=".jpg,.jpeg,.png,.pdf,image/*">
            </div>

            <div id="file-info" class="n-preview mb-3 d-none">
                <img id="thumb" class="d-none" alt="Preview of the selected file">
                <div class="meta">
                    <i class="fas fa-file-lines"></i>
                    <div class="flex-fill" style="min-width:0;">
                        <div class="name" id="file-name-display"></div>
                        <div class="size" id="file-size-display"></div>
                    </div>
                    <button type="button" class="n-iconbtn" id="removeFile" aria-label="Remove file"><i class="fas fa-xmark"></i></button>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="n-btn n-btn-primary n-btn-block" disabled>
                <i class="fas fa-paper-plane"></i> Submit for review
            </button>
        </form>

        <div class="n-tips mt-4 rise" style="--i:2">
            <h3><i class="fas fa-lightbulb me-1" style="color: var(--sun);"></i> For a quick approval</h3>
            <ul>
                <li>Make sure the whole document is visible and readable.</li>
                <li>Avoid blurry photos and dark lighting.</li>
                <li>Big photos are shrunk automatically, so uploads stay fast.</li>
            </ul>
        </div>
        <?php endif; ?>
    </main>

    <script src="js/app.js"></script>
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

        // Drag and drop (handy on a laptop)
        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('drag'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('drag'); });
        });
        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                try { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); } catch (err) {}
            }
        });

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
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                } catch (e) {
                    file = input.files[0]; // couldn't swap in the smaller photo, use the original
                }
            }
            if (file.size > MAX) {
                NeuUI.toast('That file is larger than 5MB. Please choose a smaller one.', 'fa-triangle-exclamation');
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
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
        });
    })();
    </script>

<?php endif; ?>
</body>
</html>

<?php
require_once 'includes/security.php';
require_login(['Student']);

// TODO: replace with the real University Clinic contact details before your defense
$support_email = 'clinic@neu.edu.ph';
$support_hours = '8 AM - 5 PM';

$faqs = [
    ['How do I upload a requirement?',
     'Go to Home, tap the requirement (for example, Chest X-Ray), choose a file or take a photo, then tap Submit for Review.'],
    ['Which files can I upload?',
     'JPG, PNG, or PDF files up to 5MB. Photos are shrunk automatically, so large phone pictures work too.'],
    ['What do the statuses mean?',
     'Not Started: you haven\'t uploaded yet. Pending Review: the clinic received it and will check it. Approved: it was accepted. Needs Resubmission: the reviewer left a note explaining what to fix, and you can upload a new file.'],
    ['My document needs resubmission. What now?',
     'Open the requirement from Home or Records, read the reviewer\'s note, and upload a clearer or corrected file. It goes back to Pending Review.'],
    ['Can I replace a file I already submitted?',
     'Yes, while it is still Pending Review. Once a requirement is Approved, it is locked.'],
    ['How will I know when my document is reviewed?',
     'Check the bell icon on Home. You\'ll see a message there when a reviewer updates your document.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help | NeuMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: #f7f9fc; padding-bottom: 90px;">
    <div class="p-3 border-bottom bg-white">
        <h6 class="m-0 fw-bold text-center">Help Center</h6>
    </div>

    <div class="container mt-3">
        <div class="accordion border-0" id="faq">
            <?php foreach ($faqs as $i => [$q, $a]): ?>
                <div class="accordion-item mb-2 border-0 shadow-sm rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed rounded" type="button"
                                data-bs-toggle="collapse" data-bs-target="#q<?= $i ?>">
                            <?= h($q) ?>
                        </button>
                    </h2>
                    <div id="q<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faq">
                        <div class="accordion-body small text-muted"><?= h($a) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 p-4 text-center bg-white shadow-sm rounded-4">
            <i class="fas fa-envelope-open-text fa-2x text-primary mb-2"></i>
            <h6>Still need help?</h6>
            <p class="small text-muted mb-3">The University Clinic replies <?= h($support_hours) ?>.</p>
            <a href="mailto:<?= h($support_email) ?>?subject=NeuMed%20help"
               class="btn btn-outline-primary btn-sm px-4 rounded-pill">Email the clinic</a>
        </div>
    </div>

    <?php include('includes/navbar.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

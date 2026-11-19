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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#137a3a">
    <title>Help | NEUMed</title>
    <script src="js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app">

    <header class="n-sticky">
        <div class="n-topbar center"><h1 class="title">Help</h1></div>
    </header>

    <main class="n-page pt-4">

        <section class="mb-4 rise">
            <h2 class="fw-bold m-0" style="font-size: 1.5rem;">How can we help?</h2>
            <p class="muted small mt-1 mb-3">Search the questions below or message the clinic.</p>
            <div class="n-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" id="faqSearch" class="form-control n-control" placeholder="Search help..." autocomplete="off">
            </div>
        </section>

        <div id="faqList">
            <?php foreach ($faqs as $i => [$q, $a]): ?>
                <details class="n-card n-faq rise" style="--i: <?= $i + 1 ?>">
                    <summary><span><?= h($q) ?></span><i class="fas fa-chevron-down"></i></summary>
                    <div class="answer"><?= h($a) ?></div>
                </details>
            <?php endforeach; ?>
        </div>

        <div id="faqEmpty" class="n-empty d-none" style="padding: 30px 10px;">
            <h2 style="font-size: 1rem;">No answers found</h2>
            <p class="small mb-0">Try different words, or email the clinic below.</p>
        </div>

        <section class="n-contact mt-4 rise" style="--i: 8">
            <div class="n-bubble mx-auto mb-3" style="background: rgba(255,255,255,.18); color: #fff; width: 56px; height: 56px; border-radius: 18px; font-size: 1.4rem;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h3 class="fw-bold" style="font-size: 1.15rem;">Still need help?</h3>
            <p class="small mb-3" style="opacity: .88;">The University Clinic replies <?= h($support_hours) ?>.</p>
            <a href="mailto:<?= h($support_email) ?>?subject=NEUMed%20help" class="n-btn n-btn-light">Email the clinic</a>
        </section>

    </main>

    <?php include('includes/navbar.php'); ?>

    <script>
    (function () {
        var input = document.getElementById('faqSearch'),
            items = document.querySelectorAll('#faqList details'),
            empty = document.getElementById('faqEmpty');

        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase(), shown = 0;
            items.forEach(function (d) {
                var match = q === '' || d.textContent.toLowerCase().indexOf(q) !== -1;
                d.classList.toggle('d-none', !match);
                if (match) shown++;
            });
            empty.classList.toggle('d-none', shown > 0);
        });
    })();
    </script>
</body>
</html>

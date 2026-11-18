<?php
// Floating bottom navigation. Highlights the tab for the page you're on.
$__page = basename($_SERVER['PHP_SELF']);
$__tabs = [
    ['href' => 'student_dashboard.php', 'icon' => 'fa-house',           'label' => 'Home',    'match' => ['student_dashboard.php']],
    ['href' => 'records.php',           'icon' => 'fa-file-medical',    'label' => 'Records', 'match' => ['records.php', 'student_history.php']],
    ['href' => 'upload.php',            'icon' => 'fa-cloud-arrow-up',  'label' => 'Upload',  'match' => ['upload.php'], 'center' => true],
    ['href' => 'help.php',              'icon' => 'fa-circle-question', 'label' => 'Help',    'match' => ['help.php']],
    ['href' => 'profile.php',           'icon' => 'fa-user',            'label' => 'Profile', 'match' => ['profile.php', 'edit_profile.php']],
];
?>
<!-- Loaded here too, so the nav looks right even on pages that haven't been redesigned yet -->
<link rel="stylesheet" href="css/app.css">
<nav class="dock" aria-label="Main navigation">
    <?php foreach ($__tabs as $__t):
        $__active = in_array($__page, $__t['match'], true);
        $__class  = trim(($__active ? 'active ' : '') . (!empty($__t['center']) ? 'center' : ''));
    ?>
        <a href="<?= $__t['href'] ?>" class="<?= $__class ?>" <?= $__active ? 'aria-current="page"' : '' ?>>
            <?php if (!empty($__t['center'])): ?>
                <span class="fab"><i class="fas <?= $__t['icon'] ?>"></i></span>
            <?php else: ?>
                <span class="ico"><i class="fas <?= $__t['icon'] ?>"></i></span>
            <?php endif; ?>
            <span><?= $__t['label'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

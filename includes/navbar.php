<?php
// Highlights the tab for the page you're on (fixes "Home always blue").
$__page = basename($_SERVER['PHP_SELF']);
$__tabs = [
    ['href' => 'student_dashboard.php', 'icon' => 'fa-home',          'label' => 'Home',    'match' => ['student_dashboard.php']],
    ['href' => 'records.php',           'icon' => 'fa-file-medical',  'label' => 'Records', 'match' => ['records.php', 'student_history.php']],
    ['href' => 'upload.php',            'icon' => 'fa-camera',        'label' => 'Upload',  'match' => ['upload.php'], 'center' => true],
    ['href' => 'help.php',              'icon' => 'fa-question-circle','label' => 'Help',   'match' => ['help.php']],
    ['href' => 'profile.php',           'icon' => 'fa-user-circle',   'label' => 'Profile', 'match' => ['profile.php', 'edit_profile.php']],
];
?>
<nav class="fixed-bottom bg-white border-top shadow-sm" aria-label="Main navigation">
    <div class="row g-0 text-center py-2">
        <?php foreach ($__tabs as $__t):
            $__active = in_array($__page, $__t['match'], true);
            $__color  = $__active ? 'text-primary' : 'text-muted';
        ?>
            <a href="<?= $__t['href'] ?>" class="col text-decoration-none <?= $__color ?>"
               <?= $__active ? 'aria-current="page"' : '' ?>>
                <?php if (!empty($__t['center'])): ?>
                    <div style="background: <?= $__active ? '#0056b3' : '#007bff' ?>; width: 45px; height: 45px; border-radius: 50%; margin: -25px auto 5px; display: flex; align-items: center; justify-content: center; color: white; border: 4px solid #fff;">
                        <i class="fas <?= $__t['icon'] ?>"></i>
                    </div>
                <?php else: ?>
                    <i class="fas <?= $__t['icon'] ?> d-block mb-1"></i>
                <?php endif; ?>
                <small style="font-size: 10px;<?= $__active ? ' font-weight:700;' : '' ?>"><?= $__t['label'] ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

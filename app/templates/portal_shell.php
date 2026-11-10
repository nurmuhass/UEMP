<?php
$portalRole = $data['user']['role'] ?? null;
$portalLinks = match ($portalRole) {
    'student' => ['student' => 'Dashboard', 'registration_summary' => 'Registration summary',
        'academic_history' => 'Academic history', 'student_profile' => 'My profile'],
    'lecturer' => ['lecturer' => 'Dashboard', 'lecturer_results' => 'Class lists and results',
        'lecturer_reports' => 'Course performance'],
    'department_admin' => ['department_admin' => 'Dashboard', 'review_results' => 'Review results',
        'manage_department' => 'Manage department', 'department_reports' => 'Department reports'],
    'super_admin' => ['super_admin' => 'Dashboard', 'manage_university' => 'Manage university',
        'university_reports' => 'Monitoring and audit'],
    default => [],
};
if ($view === 'academic_history' && $data['print']) {
    $portalLinks['result_statement'] = 'Result statement';
}
$portalSection = match ($portalRole) {
    'student' => 'Student workspace',
    'lecturer' => 'Lecturer workspace',
    'department_admin' => 'Department workspace',
    'super_admin' => 'University administration',
    default => 'University portal',
};
$activePage = $view === 'dashboard' ? $portalRole : ($_GET['r'] ?? null);
?>
<a class="portal-skip" href="#portal-main">Skip to content</a>
<div class="portal-shell">
    <aside class="portal-sidebar">
        <div class="portal-brand-row">
            <a class="portal-brand" href="index.php">
                <span class="portal-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m2 9 10-5 10 5-10 5L2 9Zm4 2v5c3 3 9 3 12 0v-5m4-2v6"/>
                    </svg>
                </span>
                <span><strong>UEMP</strong><small>University Education<br>Management Portal</small></span>
            </a>
            <?php if ($portalLinks): ?>
                <button class="portal-menu-toggle" type="button" aria-expanded="true" aria-controls="portal-navigation" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Menu
                </button>
            <?php endif ?>
        </div>
        <?php if ($portalLinks): ?>
            <div class="portal-navigation" id="portal-navigation">
                <p class="portal-nav-label"><?= e($portalSection) ?></p>
                <nav aria-label="<?= e($portalSection) ?>">
                    <?php foreach ($portalLinks as $link => $label): ?>
                        <a href="index.php?r=<?= e($link) ?>"<?= $activePage === $link ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                    <?php endforeach ?>
                </nav>
                <form method="post" action="index.php?r=logout" class="portal-signout">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <button class="btn portal-sidebar-button">Sign out</button>
                </form>
            </div>
        <?php elseif ($view === 'login'): ?>
            <div class="portal-login-intro">
                <p class="portal-nav-label">University portal</p>
                <p class="portal-login-statement">Your academic workspace.</p>
                <p>Access course registration, academic records, and university tools in one place.</p>
            </div>
        <?php endif ?>
    </aside>
    <main id="portal-main" class="portal-main" tabindex="-1">
        <?php if ($view !== 'login'): ?>
            <header class="portal-page-header">
                <p class="portal-eyebrow"><?= e($portalSection) ?></p>
                <h1><?= e($title) ?></h1>
                <?php if ($view === 'dashboard'): ?>
                    <p>Welcome, <?= e($data['user']['name']) ?>.</p>
                <?php endif ?>
            </header>
        <?php endif ?>
        <?php require __DIR__ . '/' . ($studentDashboard ? 'student_dashboard' : $view) . '.php'; ?>
    </main>
</div>

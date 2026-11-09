<?php
$studentDashboard = $view === 'dashboard' && ($data['user']['role'] ?? null) === 'student';
$workflowRoute = $_GET['r'] ?? '';
$workflowError = $view === 'message' && in_array($workflowRoute,
    ['register', 'lecturer_results', 'review_results', 'registration_summary', 'academic_history', 'result_statement'], true);
$portalTheme = $studentDashboard || $workflowError ||
    in_array($view, ['login', 'results', 'academic_history', 'registration_summary'], true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | UEMP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <?php if ($portalTheme): ?>
        <link rel="stylesheet" href="assets/portal-theme.css">
    <?php endif ?>
    <script src="assets/portal.js" defer></script>
</head>
<body class="<?= $portalTheme ? 'portal-themed' . ($view === 'login' ? ' portal-login' : '') : 'bg-body-tertiary' ?>">
<?php if ($portalTheme): ?>
    <?php require __DIR__ . '/portal_shell.php'; ?>
<?php else: ?>
<header class="bg-primary text-white py-3 d-print-none">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <a class="text-white fw-semibold text-decoration-none" href="index.php">University Education Management Portal</a>
        <?php if (isset($data['user'])): ?>
            <form method="post" action="index.php?r=logout">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <button class="btn btn-sm btn-outline-light text-nowrap">Sign out</button>
            </form>
        <?php endif ?>
    </div>
</header>
<main class="container py-5">
    <h1 class="h2 mb-4"><?= e($title) ?></h1>
    <?php require __DIR__ . '/' . $view . '.php'; ?>
</main>
<?php endif ?>
</body>
</html>

<?php
$studentDashboard = $view === 'dashboard' && ($data['user']['role'] ?? null) === 'student';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | UEMP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/portal-theme.css">
    <script src="assets/portal.js" defer></script>
</head>
<body class="portal-themed<?= $view === 'login' ? ' portal-login' : '' ?>">
<?php require __DIR__ . '/portal_shell.php'; ?>
</body>
</html>

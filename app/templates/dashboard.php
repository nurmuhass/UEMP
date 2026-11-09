<p class="lead">Welcome, <?= e($data['user']['name']) ?>.</p>
<p class="text-body-secondary">
    <?= $data['term'] ? e($data['term']['label'] . ' · ' . $data['term']['name']) : 'No current semester is configured.' ?>
</p>
<?php
$links = match ($data['user']['role']) {
    'lecturer' => ['lecturer_results' => 'Class lists and results', 'lecturer_reports' => 'Course performance'],
    'department_admin' => ['manage_department' => 'Manage department', 'review_results' => 'Review results',
        'department_reports' => 'Department reports'],
    'super_admin' => ['manage_university' => 'Manage university', 'university_reports' => 'Monitoring and audit'],
};
?>
<nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Dashboard tools">
    <?php foreach ($links as $route => $label): ?>
        <a class="btn btn-outline-primary" href="index.php?r=<?= e($route) ?>"><?= e($label) ?></a>
    <?php endforeach ?>
</nav>
<?php require __DIR__ . '/records.php'; ?>
<?php if ($data['user']['role'] === 'department_admin'): ?>
    <div class="mt-4">
        <?php
        $data['heading'] = 'Student registrations';
        $data['columns'] = ['Matriculation number', 'Student', 'Code', 'Course', 'Units'];
        $data['records'] = $data['departmentRegistrations'];
        require __DIR__ . '/records.php';
        ?>
    </div>
<?php endif ?>

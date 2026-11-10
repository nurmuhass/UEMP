<?php
$dashboardActions = match ($data['user']['role']) {
    'lecturer' => ['lecturer_results' => 'Class lists and results', 'lecturer_reports' => 'Course performance'],
    'department_admin' => ['review_results' => 'Review results', 'manage_department' => 'Manage department',
        'department_reports' => 'Department reports'],
    'super_admin' => ['manage_university' => 'Manage university', 'university_reports' => 'Monitoring and audit'],
};
?>
<section class="portal-panel portal-term mb-4" aria-labelledby="dashboard-term">
    <div>
        <p class="portal-eyebrow">Current semester</p>
        <h2 id="dashboard-term"><?= $data['term'] ? e($data['term']['label'] . ' · ' . $data['term']['name']) : 'No current semester is configured.' ?></h2>
    </div>
    <nav class="portal-actions d-print-none" aria-label="Dashboard tools">
        <?php foreach ($dashboardActions as $destination => $label): ?>
            <a class="btn <?= $destination === array_key_first($dashboardActions) ? 'portal-primary' : 'btn-outline-primary' ?>"
               href="index.php?r=<?= e($destination) ?>"><?= e($label) ?></a>
        <?php endforeach ?>
    </nav>
</section>
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

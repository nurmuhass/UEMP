<p class="lead">Welcome, <?= e($data['user']['name']) ?>.</p>
<p class="text-body-secondary">
    <?= $data['term'] ? e($data['term']['label'] . ' · ' . $data['term']['name']) : 'No current semester is configured.' ?>
</p>
<?php if ($data['profile']): ?>
    <dl class="row bg-white border rounded p-3">
        <?php foreach ($data['profile'] as $key => $value): ?>
            <dt class="col-sm-4"><?= e(ucfirst(str_replace('_', ' ', $key))) ?></dt>
            <dd class="col-sm-8"><?= e((string) $value) ?></dd>
        <?php endforeach ?>
    </dl>
<?php endif ?>
<?php require __DIR__ . '/records.php'; ?>
<?php if ($data['user']['role'] === 'student'): ?>
    <p class="mt-3"><strong>Total units:</strong> <?= $data['totalUnits'] ?></p>
    <a class="btn btn-outline-secondary" href="index.php?r=registration_summary">Registration summary</a>
    <section class="mt-4">
        <h2 class="h5">Available offerings</h2>
        <?php if ($data['notice']): ?>
            <p class="alert alert-success" role="status">Course registered.</p>
        <?php endif ?>
        <?php if (!$data['available']): ?>
            <p>No eligible unregistered offerings this semester.</p>
        <?php endif ?>
        <ul class="list-group">
            <?php foreach ($data['available'] as $offering): ?>
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span><?= e($offering['code'] . ' · ' . $offering['title']) ?>
                        (<?= e((string) $offering['units']) ?> units)</span>
                    <form method="post" action="index.php?r=register">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="offering_id" value="<?= e((string) $offering['id']) ?>">
                        <button class="btn btn-sm btn-primary">Register</button>
                    </form>
                </li>
            <?php endforeach ?>
        </ul>
    </section>
<?php elseif ($data['user']['role'] === 'department_admin'): ?>
    <div class="mt-4">
        <?php
        $data['heading'] = 'Student registrations';
        $data['columns'] = ['Matriculation number', 'Student', 'Code', 'Course', 'Units'];
        $data['records'] = $data['departmentRegistrations'];
        require __DIR__ . '/records.php';
        ?>
    </div>
<?php endif ?>

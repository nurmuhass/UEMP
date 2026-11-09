<?php
$route = $data['route'];
$entity = $data['entity'];
$record = $data['record'] ?? [];
$url = 'index.php?r=' . $route . '&entity=' . $entity;
$home = $data['super'] ? 'super_admin' : 'department_admin';
?>
<p><a href="index.php?r=<?= e($home) ?>">Back to dashboard</a></p>
<nav class="nav nav-pills flex-wrap gap-1 mb-4" aria-label="Management sections">
    <?php foreach ($data['entities'] as $key => $section): ?>
        <a class="nav-link<?= $entity === $key ? ' active' : '' ?>"
           href="index.php?r=<?= e($route) ?>&amp;entity=<?= e($key) ?>"><?= e($section[0]) ?></a>
    <?php endforeach ?>
</nav>
<?php if (($_GET['saved'] ?? '') === '1'): ?>
    <p class="alert alert-success" role="status">Changes saved.</p>
<?php endif ?>
<section class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= $record ? 'Edit record' : 'Create record' ?></h2>
        <?php if ($entity === 'users'): ?>
            <p class="text-body-secondary">Department is required for departmental roles. Student fields apply only to students. Leave the password blank when keeping an existing password.</p>
        <?php endif ?>
        <?php if ($entity === 'offerings'): ?>
            <p class="text-body-secondary">Choose a course, lecturer, and eligible programs from the same department.</p>
        <?php endif ?>
        <form method="post" action="<?= e($url . ($data['id'] ? '&id=' . $data['id'] : '')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <div class="row g-3">
                <?php foreach ($data['fields'] as $key => [$label, $type]): ?>
                    <?php $value = $type === 'password' ? '' : ($record[$key] ?? ''); ?>
                    <div class="col-md-6">
                        <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                        <?php if (is_int($type) || in_array($type, ['email','phone','number','password','matric'], true)): ?>
                            <?php $input = in_array($type, ['email','number','password'], true) ? $type : 'text'; ?>
                            <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($input) ?>"
                                   <?= $type === 'password' ? 'autocomplete="new-password"' : '' ?> value="<?= e((string) $value) ?>">
                        <?php else: ?>
                            <?php $options = management_options($type, $data['scope'], $data['super']); ?>
                            <select class="form-select" id="<?= e($key) ?>" name="<?= e($key) ?><?= $type === 'multiple' ? '[]' : '' ?>"
                                <?= $type === 'multiple' ? 'multiple size="5"' : '' ?>>
                                <?php if ($type !== 'multiple'): ?>
                                    <option value="">Choose <?= e(strtolower($label)) ?></option>
                                <?php endif ?>
                                <?php foreach ($options as $option => $caption): ?>
                                    <option value="<?= e((string) $option) ?>"
                                        <?= in_array((string) $option, array_map('strval', (array) $value), true) ? 'selected' : '' ?>>
                                        <?= e((string) $caption) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
            <button class="btn btn-primary mt-3">Save record</button>
            <?php if ($record): ?>
                <a class="btn btn-outline-secondary mt-3" href="<?= e($url) ?>">New record</a>
            <?php endif ?>
        </form>
    </div>
</section>
<?php $listFields = array_diff_key($data['fields'], array_flip(['password', 'program_ids'])); ?>
<?php if ($entity === 'users' && !$data['super']): $listFields['status'] = ['Status']; endif ?>
<?php
$labels = [];
foreach ($listFields as $key => $field) {
    $type = $field[1] ?? null;
    if (is_string($type) && !in_array($type, ['email','phone','number','matric'], true)) {
        $labels[$key] = management_options($type, $data['scope'], $data['super']);
    }
}
?>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr>
            <?php foreach ($listFields as [$label]): ?><th scope="col"><?= e($label) ?></th><?php endforeach ?>
            <th scope="col">Actions</th>
        </tr></thead>
        <tbody>
            <?php foreach ($data['records'] as $row): ?>
                <tr>
                    <?php foreach ($listFields as $key => $field): ?>
                        <?php $value = $row[$key] ?? null; ?>
                        <td><?= e((string) ($labels[$key][$value ?? ''] ?? $value ?? '—')) ?></td>
                    <?php endforeach ?>
                    <td>
                        <a href="<?= e($url . '&id=' . $row['id']) ?>">Edit</a>
                        <?php if ($entity === 'semesters'): ?>
                            <?php if ((int) $data['current'] === (int) $row['id']): ?>
                                <span class="badge text-bg-success">Current</span>
                            <?php else: ?>
                                <form method="post" action="<?= e($url . '&id=' . $row['id']) ?>">
                                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                    <button class="btn btn-sm btn-outline-primary" name="action" value="current">Set current</button>
                                </form>
                            <?php endif ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

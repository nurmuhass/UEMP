<?php
$route = $data['route'];
$entity = $data['entity'];
$record = $data['record'] ?? [];
$url = 'index.php?r=' . $route . '&entity=' . $entity;
$home = $data['super'] ? 'super_admin' : 'department_admin';
?>
<p class="d-print-none"><a class="btn btn-outline-secondary" href="index.php?r=<?= e($home) ?>">Back to dashboard</a></p>
<nav class="portal-subnav d-print-none mb-4" aria-label="Management sections">
    <?php foreach ($data['entities'] as $key => $section): ?>
        <a href="index.php?r=<?= e($route) ?>&amp;entity=<?= e($key) ?>"<?= $entity === $key ? ' aria-current="page"' : '' ?>><?= e($section[0]) ?></a>
    <?php endforeach ?>
</nav>
<?php if (($_GET['saved'] ?? '') === '1'): ?>
    <p class="portal-notice portal-notice-success d-print-none" role="status">Changes saved.</p>
<?php endif ?>
<section class="portal-panel portal-section portal-form-panel mb-4 d-print-none" aria-labelledby="management-form">
    <div>
        <h2 id="management-form"><?= $record ? 'Edit record' : 'Create record' ?></h2>
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
            <div class="portal-actions mt-3">
                <button class="btn portal-primary">Save record</button>
                <?php if ($record): ?>
                    <a class="btn btn-outline-secondary" href="<?= e($url) ?>">New record</a>
                <?php endif ?>
            </div>
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
<section class="card" aria-labelledby="management-list">
    <h2 class="card-header h5" id="management-list">Existing records</h2>
    <?php if ($data['records']): ?>
        <?php
        $tableLabel = $data['entities'][$entity][0];
        $tableColumns = array_column($listFields, 0);
        $tableRows = $data['records'];
        $tableRowTemplate = 'manage_row';
        require __DIR__ . '/table.php';
        ?>
    <?php else: ?>
        <p class="portal-empty m-3">No records yet.</p>
    <?php endif ?>
</section>

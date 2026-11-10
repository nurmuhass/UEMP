<p class="d-print-none"><a class="btn btn-outline-secondary" href="index.php?r=student">Back to dashboard</a></p>
<?php if (isset($data['error'])): ?>
    <p class="portal-notice portal-notice-error" role="alert"><?= e($data['error']) ?></p>
<?php elseif (($_GET['saved'] ?? '') === '1'): ?>
    <p class="portal-notice portal-notice-success" role="status">Profile saved.</p>
<?php endif ?>
<dl class="portal-profile portal-panel portal-section mb-4">
    <?php foreach (['email' => 'Email', 'matric_number' => 'Matriculation number', 'program' => 'Program', 'department' => 'Department', 'academic_level' => 'Academic level'] as $key => $label): ?>
        <div><dt><?= e($label) ?></dt><dd><?= e((string) $data['record'][$key]) ?></dd></div>
    <?php endforeach ?>
</dl>
<form method="post" action="index.php?r=student_profile" class="portal-panel portal-section portal-form-panel d-print-none">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <label for="name" class="form-label">Name</label>
    <input class="form-control mb-3" id="name" name="name" maxlength="100" value="<?= e($data['record']['name']) ?>" required>
    <label for="phone" class="form-label">Phone (optional)</label>
    <input class="form-control mb-3" id="phone" name="phone" type="tel" maxlength="32" value="<?= e($data['record']['phone'] ?? '') ?>">
    <div><button class="btn portal-primary">Save profile</button></div>
</form>

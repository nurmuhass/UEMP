<p><a href="index.php?r=student">Back to dashboard</a></p>
<?php if (isset($data['error'])): ?>
    <p class="alert alert-danger" role="alert"><?= e($data['error']) ?></p>
<?php elseif (($_GET['saved'] ?? '') === '1'): ?>
    <p class="alert alert-success" role="status">Profile saved.</p>
<?php endif ?>
<dl class="row">
    <?php foreach (['email' => 'Email', 'matric_number' => 'Matriculation number', 'program' => 'Program', 'department' => 'Department', 'academic_level' => 'Academic level'] as $key => $label): ?>
        <dt class="col-sm-4"><?= e($label) ?></dt><dd class="col-sm-8"><?= e((string) $data['record'][$key]) ?></dd>
    <?php endforeach ?>
</dl>
<form method="post" action="index.php?r=student_profile" class="card card-body">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <label for="name" class="form-label">Name</label>
    <input class="form-control mb-3" id="name" name="name" maxlength="100" value="<?= e($data['record']['name']) ?>" required>
    <label for="phone" class="form-label">Phone (optional)</label>
    <input class="form-control mb-3" id="phone" name="phone" type="tel" maxlength="32" value="<?= e($data['record']['phone'] ?? '') ?>">
    <div><button class="btn btn-primary">Save profile</button></div>
</form>

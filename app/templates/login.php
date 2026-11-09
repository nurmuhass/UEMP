<div class="card shadow-sm col-md-7 col-lg-5">
    <div class="card-body p-4">
        <?php if (isset($data['error'])): ?>
            <p class="alert alert-danger" role="alert"><?= e($data['error']) ?></p>
        <?php endif ?>
        <form method="post" action="index.php?r=login">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <label class="form-label" for="email">Email address</label>
            <input class="form-control mb-3" id="email" name="email" type="email" autocomplete="username" maxlength="254" required>
            <label class="form-label" for="password">Password</label>
            <div class="input-group mb-4">
                <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" id="password-toggle" aria-controls="password" hidden>Show</button>
            </div>
            <button class="btn btn-primary">Sign in</button>
        </form>
    </div>
</div>

<section class="portal-term portal-panel" aria-labelledby="current-term">
    <div>
        <p class="portal-eyebrow">Current semester</p>
        <h2 id="current-term"><?= $data['term'] ? e($data['term']['label'] . ' · ' . $data['term']['name']) : 'No current semester is configured.' ?></h2>
        <p><?= $data['available'] ? 'Review the courses you can register for this semester.' : 'Review your registered courses and credit units.' ?></p>
    </div>
    <?php if ($data['available']): ?>
        <a class="btn portal-primary" href="#available-offerings">View available offerings <span aria-hidden="true">↓</span></a>
    <?php else: ?>
        <a class="btn portal-primary" href="index.php?r=registration_summary">View registration summary</a>
    <?php endif ?>
</section>

<div class="portal-stats" aria-label="Semester overview">
    <div class="portal-panel portal-stat"><p>Registered courses</p><strong><?= count($data['records']) ?></strong></div>
    <div class="portal-panel portal-stat"><p><strong>Total units:</strong> <?= $data['totalUnits'] ?></p></div>
    <div class="portal-panel portal-stat"><p>Available offerings</p><strong><?= count($data['available']) ?></strong></div>
</div>

<?php require __DIR__ . '/records.php'; ?>

<section id="available-offerings" class="portal-panel portal-section" aria-labelledby="offerings-heading">
    <h2 id="offerings-heading">Available offerings</h2>
    <?php if ($data['notice']): ?>
        <p class="portal-notice portal-notice-success" role="status">Course registered.</p>
    <?php endif ?>
    <?php if (!$data['available']): ?>
        <p class="portal-empty">No eligible unregistered offerings this semester.</p>
    <?php else: ?>
        <ul class="portal-offerings">
            <?php foreach ($data['available'] as $offering): ?>
                <li>
                    <form class="portal-registration-row" method="post" action="index.php?r=register">
                        <span class="portal-course-code"><?= e($offering['code']) ?></span>
                        <strong class="portal-registration-title"><?= e($offering['title']) ?></strong>
                        <span class="portal-registration-units"><?= e((string) $offering['units']) ?> credit units</span>
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="offering_id" value="<?= e((string) $offering['id']) ?>">
                        <button class="btn portal-primary">Register</button>
                    </form>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>

<?php if ($data['profile']): ?>
    <section class="portal-panel portal-section" aria-labelledby="student-details">
        <div class="portal-section-heading">
            <h2 id="student-details">Student details</h2>
            <a href="index.php?r=student_profile">Edit profile</a>
        </div>
        <dl class="portal-profile">
            <?php foreach ($data['profile'] as $key => $value): ?>
                <div>
                    <dt><?= e(ucfirst(str_replace('_', ' ', $key))) ?></dt>
                    <dd><?= e((string) $value) ?></dd>
                </div>
            <?php endforeach ?>
        </dl>
    </section>
<?php endif ?>

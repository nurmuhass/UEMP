<?php
$route = $data['route'];
$review = $data['review'];
$home = $review ? 'department_admin' : 'lecturer';
?>
<nav class="portal-actions mb-4" aria-label="Result navigation">
    <a class="btn btn-outline-secondary" href="index.php?r=<?= e($home) ?>">Back to dashboard</a>
    <?php if ($data['id'] !== null): ?>
        <a class="btn btn-outline-secondary" href="index.php?r=<?= e($route) ?>">All offerings</a>
    <?php endif ?>
</nav>
<?php if ($data['id'] === null): ?>
    <section class="portal-panel portal-section" aria-labelledby="result-offerings">
        <h2 id="result-offerings"><?= $review ? 'Department offerings' : 'Assigned offerings' ?></h2>
        <?php if (!$data['offerings']): ?>
            <p class="portal-empty">No offerings available.</p>
        <?php else: ?>
            <ul class="portal-offerings">
                <?php foreach ($data['offerings'] as $offering): ?>
                    <li>
                        <div>
                            <a class="portal-course-link" href="index.php?r=<?= e($route) ?>&amp;offering=<?= (int) $offering['id'] ?>">
                                <span class="portal-course-code"><?= e($offering['code']) ?></span>
                                <?= e($offering['title']) ?>
                            </a>
                            <span class="portal-muted"><?= e($offering['label'] . ' · ' . $offering['semester']) ?></span>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
<?php else: ?>
    <?php $offering = $data['offerings'][0]; ?>
    <section class="portal-panel portal-section mb-4" aria-labelledby="result-course">
        <p class="portal-eyebrow"><?= e($offering['label'] . ' · ' . $offering['semester']) ?></p>
        <h2 class="mb-0" id="result-course"><?= e($offering['code'] . ' · ' . $offering['title']) ?></h2>
        <?php if (!$data['class']): ?>
            <p class="portal-empty mt-3"><?= $review ? 'No results have been submitted for this offering.' : 'No students are registered for this offering.' ?></p>
        <?php endif ?>
    </section>
    <?php foreach ($data['class'] as $student): ?>
        <?php
        $status = $student['status'];
        $id = (int) $student['user_id'];
        $editable = !$review && !in_array($status, ['submitted', 'approved'], true);
        $total = $status === null ? null : (int) $student['ca'] + (int) $student['exam'];
        ?>
        <article class="portal-panel portal-section portal-result-card" aria-labelledby="student-result-<?= $id ?>">
            <header class="portal-result-header">
                <div>
                    <h3 id="student-result-<?= $id ?>"><?= e($student['name']) ?></h3>
                    <p class="portal-muted mb-0"><?= e($student['matric_number']) ?></p>
                </div>
                <span class="portal-status portal-status-<?= e($status ?? 'none') ?>"><?= e(ucfirst($status ?? 'No result')) ?></span>
            </header>
            <?php if (!$editable): ?>
                <?php require __DIR__ . '/result_scores.php'; ?>
            <?php endif ?>
            <?php if ($student['feedback'] !== null): ?>
                <div class="portal-feedback"><strong>Review feedback</strong><p><?= e($student['feedback']) ?></p></div>
            <?php endif ?>
            <?php if ($editable || ($review && $status === 'submitted')): ?>
                <form method="post" action="index.php?r=<?= e($route) ?>&amp;offering=<?= (int) $offering['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="student_id" value="<?= $id ?>">
                    <input type="hidden" name="revision" value="<?= (int) ($student['revision'] ?? 0) ?>">
                    <?php if ($editable): ?>
                        <?php require __DIR__ . '/result_scores.php'; ?>
                        <div class="portal-actions">
                            <button class="btn btn-outline-primary" name="action" value="save"><?= $status === 'rejected' ? 'Save correction' : 'Save draft' ?></button>
                            <button class="btn portal-primary" name="action" value="submit"><?= $status === 'rejected' ? 'Resubmit for review' : 'Submit for review' ?></button>
                        </div>
                    <?php else: ?>
                        <div class="portal-review-actions">
                            <label class="form-label" for="feedback-<?= $id ?>">Feedback required for rejection</label>
                            <textarea class="form-control mb-3" id="feedback-<?= $id ?>" name="feedback" maxlength="1000" rows="2"></textarea>
                            <div class="portal-actions">
                                <button class="btn portal-primary" name="action" value="approve">Approve and publish</button>
                                <button class="btn btn-outline-danger" name="action" value="reject">Reject</button>
                            </div>
                        </div>
                    <?php endif ?>
                </form>
            <?php endif ?>
        </article>
    <?php endforeach ?>
<?php endif ?>

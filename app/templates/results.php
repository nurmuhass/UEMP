<?php
$route = $data['route'];
$review = $data['review'];
$home = $review ? 'department_admin' : 'lecturer';
?>
<p><a href="index.php?r=<?= e($home) ?>">Back to dashboard</a></p>
<?php if ($data['id'] === null): ?>
    <?php if (!$data['offerings']): ?>
        <p>No offerings available.</p>
    <?php endif ?>
    <ul class="list-group">
        <?php foreach ($data['offerings'] as $offering): ?>
            <li class="list-group-item">
                <a href="index.php?r=<?= e($route) ?>&amp;offering=<?= (int) $offering['id'] ?>">
                    <?= e($offering['code'] . ' · ' . $offering['title']) ?>
                </a>
                <span class="text-body-secondary"><?= e($offering['label'] . ' · ' . $offering['semester']) ?></span>
            </li>
        <?php endforeach ?>
    </ul>
<?php else: ?>
    <?php $offering = $data['offerings'][0]; ?>
    <p><a href="index.php?r=<?= e($route) ?>">All offerings</a></p>
    <h2 class="h4"><?= e($offering['code'] . ' · ' . $offering['title']) ?></h2>
    <p class="text-body-secondary"><?= e($offering['label'] . ' · ' . $offering['semester']) ?></p>
    <?php if (!$data['class']): ?>
        <p><?= $review ? 'No results have been submitted for this offering.' : 'No students are registered for this offering.' ?></p>
    <?php endif ?>
    <?php foreach ($data['class'] as $student): ?>
        <?php
        $status = $student['status'];
        $id = (int) $student['user_id'];
        $editable = !$review && !in_array($status, ['submitted', 'approved'], true);
        ?>
        <article class="card mb-3">
            <div class="card-body">
                <h3 class="h5"><?= e($student['name'] . ' · ' . $student['matric_number']) ?></h3>
                <p>Status <strong><?= e(ucfirst($status ?? 'No result')) ?></strong></p>
                <?php if ($status !== null): ?>
                    <?php $total = (int) $student['ca'] + (int) $student['exam']; ?>
                    <p>CA <?= (int) $student['ca'] ?>/40 · Exam <?= (int) $student['exam'] ?>/60 · Total and grade <?= $total ?> · <?= e(result_grade($total)) ?></p>
                <?php endif ?>
                <?php if ($student['feedback'] !== null): ?>
                    <p>Review feedback: <?= e($student['feedback']) ?></p>
                <?php endif ?>
                <?php if ($editable || ($review && $status === 'submitted')): ?>
                    <form method="post" action="index.php?r=<?= e($route) ?>&amp;offering=<?= (int) $offering['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="student_id" value="<?= $id ?>">
                        <input type="hidden" name="revision" value="<?= (int) ($student['revision'] ?? 0) ?>">
                        <?php if ($editable): ?>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label" for="ca-<?= $id ?>">CA (0–40)</label>
                                    <input class="form-control" id="ca-<?= $id ?>" name="ca" type="number" min="0" max="40" required value="<?= e((string) $student['ca']) ?>">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="exam-<?= $id ?>">Exam (0–60)</label>
                                    <input class="form-control" id="exam-<?= $id ?>" name="exam" type="number" min="0" max="60" required value="<?= e((string) $student['exam']) ?>">
                                </div>
                            </div>
                            <button class="btn btn-outline-primary" name="action" value="save"><?= $status === 'rejected' ? 'Save correction' : 'Save draft' ?></button>
                            <button class="btn btn-primary" name="action" value="submit"><?= $status === 'rejected' ? 'Resubmit for review' : 'Submit for review' ?></button>
                        <?php else: ?>
                            <label class="form-label" for="feedback-<?= $id ?>">Feedback required for rejection</label>
                            <textarea class="form-control mb-3" id="feedback-<?= $id ?>" name="feedback" maxlength="1000" rows="2"></textarea>
                            <button class="btn btn-primary" name="action" value="approve">Approve and publish</button>
                            <button class="btn btn-outline-danger" name="action" value="reject">Reject</button>
                        <?php endif ?>
                    </form>
                <?php endif ?>
            </div>
        </article>
    <?php endforeach ?>
<?php endif ?>

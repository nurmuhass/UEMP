<div class="portal-score-grid">
    <div>
        <?php if ($editable): ?>
            <label for="ca-<?= $id ?>">CA <span>/ 40</span></label>
            <input class="form-control" id="ca-<?= $id ?>" name="ca" type="number" min="0" max="40" required value="<?= e((string) $student['ca']) ?>">
        <?php else: ?>
            <span class="portal-score-label">CA <span>/ 40</span></span>
            <strong><?= e((string) ($student['ca'] ?? '—')) ?></strong>
        <?php endif ?>
    </div>
    <div>
        <?php if ($editable): ?>
            <label for="exam-<?= $id ?>">Exam <span>/ 60</span></label>
            <input class="form-control" id="exam-<?= $id ?>" name="exam" type="number" min="0" max="60" required value="<?= e((string) $student['exam']) ?>">
        <?php else: ?>
            <span class="portal-score-label">Exam <span>/ 60</span></span>
            <strong><?= e((string) ($student['exam'] ?? '—')) ?></strong>
        <?php endif ?>
    </div>
    <div><span class="portal-score-label">Saved total</span><strong><?= $total ?? '—' ?></strong></div>
    <div><span class="portal-score-label">Saved grade</span><strong><?= $total === null ? '—' : e(result_grade($total)) ?></strong></div>
</div>

<div class="portal-actions d-print-none mb-4">
    <a href="index.php?r=<?= $data['print'] ? 'academic_history' : 'student' ?>" class="btn btn-outline-secondary">Back</a>
    <?php if ($data['print']): ?>
        <button type="button" class="btn portal-primary" id="print-summary" hidden>Print statement</button>
    <?php else: ?>
        <a class="btn portal-primary" href="index.php?r=result_statement">Printable result statement</a>
    <?php endif ?>
</div>
<section class="portal-panel portal-section portal-academic-summary" aria-labelledby="academic-summary">
    <div>
        <p class="portal-eyebrow">Student record</p>
        <h2 id="academic-summary"><?= e($data['user']['name']) ?></h2>
        <p class="portal-muted mb-0"><?= e($data['student']['matric_number'] ?? '') ?> · <?= e($data['student']['program'] ?? '') ?></p>
    </div>
    <div class="portal-academic-metrics">
        <p><strong>CGPA:</strong> <?= e($data['cgpa']) ?></p>
        <p><strong>Graded credit units:</strong> <?= $data['credits'] ?></p>
    </div>
</section>
<?php if (!$data['terms']): ?>
    <p class="portal-empty mt-4">No approved results are available.</p>
<?php endif ?>
<?php foreach ($data['terms'] as $term): ?>
    <div class="portal-academic-term">
        <?php
        $data['heading'] = $term['title'];
        $data['columns'] = ['Code', 'Course', 'Units', 'Total', 'Grade', 'Grade points'];
        $data['records'] = $term['records'];
        require __DIR__ . '/records.php';
        ?>
        <p class="portal-term-gpa"><strong>Semester GPA:</strong> <?= e(number_format($term['weighted'] / $term['credits'], 2, '.', '')) ?></p>
    </div>
<?php endforeach ?>

<div class="d-print-none mb-4">
    <a href="index.php?r=<?= $data['print'] ? 'academic_history' : 'student' ?>" class="btn btn-outline-secondary">Back</a>
    <?php if ($data['print']): ?>
        <button type="button" class="btn btn-primary" id="print-summary" hidden>Print statement</button>
    <?php else: ?>
        <a class="btn btn-primary" href="index.php?r=result_statement">Printable result statement</a>
    <?php endif ?>
</div>
<p class="lead"><?= e($data['user']['name']) ?></p>
<p><?= e($data['student']['matric_number'] ?? '') ?> · <?= e($data['student']['program'] ?? '') ?></p>
<p><strong>CGPA:</strong> <?= e($data['cgpa']) ?> · <strong>Graded credit units:</strong> <?= $data['credits'] ?></p>
<?php if (!$data['terms']): ?>
    <p>No approved results are available.</p>
<?php endif ?>
<?php foreach ($data['terms'] as $term): ?>
    <?php
    $data['heading'] = $term['title'];
    $data['columns'] = ['Code', 'Course', 'Units', 'Total', 'Grade', 'Grade points'];
    $data['records'] = $term['records'];
    require __DIR__ . '/records.php';
    ?>
    <p class="mt-2 mb-4"><strong>Semester GPA:</strong> <?= e(number_format($term['weighted'] / $term['credits'], 2, '.', '')) ?></p>
<?php endforeach ?>

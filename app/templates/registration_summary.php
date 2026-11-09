<div class="portal-actions d-print-none mb-4">
    <a href="index.php?r=student" class="btn btn-outline-secondary">Back to dashboard</a>
    <button type="button" class="btn portal-primary" id="print-summary" hidden>Print summary</button>
</div>
<p class="lead"><?= e($data['user']['name']) ?></p>
<p><?= e($data['profile']['matric_number'] ?? '') ?> · <?= e($data['profile']['program'] ?? '') ?></p>
<p><?= $data['term'] ? e($data['term']['label'] . ' · ' . $data['term']['name']) : 'No current semester is configured.' ?></p>
<?php require __DIR__ . '/records.php'; ?>
<p class="mt-3"><strong>Total units:</strong> <?= $data['totalUnits'] ?></p>

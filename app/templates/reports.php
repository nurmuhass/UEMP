<div class="portal-actions d-print-none mb-4">
    <a class="btn btn-outline-secondary" href="index.php?r=<?= e($data['user']['role']) ?>">Back to dashboard</a>
    <button class="btn portal-primary" id="print-summary" type="button" hidden>Print report</button>
</div>
<p class="portal-notice">Performance averages and pass rates use approved results. A score of 40 or higher is a pass.</p>
<?php foreach ($data['sections'] as $section): ?>
    <div class="mb-4">
        <?php
        $data = $section + $data;
        require __DIR__ . '/records.php';
        ?>
    </div>
<?php endforeach ?>

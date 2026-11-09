<p><a href="index.php?r=<?= e($data['user']['role']) ?>">Back to dashboard</a></p>
<p>Performance averages and pass rates use approved results. A score of 40 or higher is a pass.</p>
<?php foreach ($data['sections'] as $section): ?>
    <div class="mb-4">
        <?php
        $data = $section + $data;
        require __DIR__ . '/records.php';
        ?>
    </div>
<?php endforeach ?>

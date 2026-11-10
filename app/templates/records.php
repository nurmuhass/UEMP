<section class="card shadow-sm">
    <h2 class="card-header h5"><?= e($data['heading']) ?></h2>
    <?php if ($data['records']): ?>
        <?php
        $tableLabel = $data['heading'];
        $tableColumns = $data['columns'];
        $tableRows = $data['records'];
        $tableRowTemplate = null;
        require __DIR__ . '/table.php';
        ?>
    <?php else: ?>
        <p class="portal-empty m-3"><?= $view === 'reports' ? 'No records to display.' : 'No records for this semester.' ?></p>
    <?php endif ?>
</section>

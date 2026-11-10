<div class="table-responsive" tabindex="0" role="region" aria-label="<?= e($tableLabel) ?>">
    <table class="table table-striped align-middle mb-0<?= $view === 'reports' ? ' portal-report-table' : '' ?>">
        <thead><tr>
            <?php foreach ($tableColumns as $column): ?>
                <th scope="col"><?= e($column) ?></th>
            <?php endforeach ?>
            <?php if ($tableRowTemplate !== null): ?><th class="d-print-none" scope="col">Actions</th><?php endif ?>
        </tr></thead>
        <tbody>
            <?php foreach ($tableRows as $tableRow): ?>
                <tr>
                    <?php if ($tableRowTemplate !== null): ?>
                        <?php require __DIR__ . '/' . $tableRowTemplate . '.php'; ?>
                    <?php else: ?>
                        <?php foreach ($tableRow as $value): ?>
                            <td><?= e((string) ($value ?? '—')) ?></td>
                        <?php endforeach ?>
                    <?php endif ?>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

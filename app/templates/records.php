<section class="card shadow-sm">
    <h2 class="card-header h5"><?= e($data['heading']) ?></h2>
    <?php if ($data['records']): ?>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr>
                    <?php foreach ($data['columns'] as $column): ?>
                        <th scope="col"><?= e($column) ?></th>
                    <?php endforeach ?>
                </tr></thead>
                <tbody>
                    <?php foreach ($data['records'] as $record): ?>
                        <tr>
                            <?php foreach ($record as $value): ?>
                                <td><?= e((string) ($value ?? '—')) ?></td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="card-body mb-0">No records for this semester.</p>
    <?php endif ?>
</section>

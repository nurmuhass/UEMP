<?php foreach ($listFields as $key => $field): ?>
    <?php $value = $tableRow[$key] ?? null; ?>
    <td>
        <?php if ($key === 'status'): ?>
            <?php
            $badgeKind = (string) $value;
            $badgeText = ucfirst((string) $value);
            require __DIR__ . '/status_badge.php';
            ?>
        <?php elseif ($key === 'name' && $entity === 'semesters' && (int) $data['current'] === (int) $tableRow['id']): ?>
            <?= e((string) $value) ?>
            <?php
            $badgeKind = 'current';
            $badgeText = 'Current';
            require __DIR__ . '/status_badge.php';
            ?>
        <?php else: ?>
            <?= e((string) ($labels[$key][$value ?? ''] ?? $value ?? '—')) ?>
        <?php endif ?>
    </td>
<?php endforeach ?>
<td class="d-print-none">
    <div class="portal-actions portal-list-actions">
        <a class="btn btn-sm btn-outline-primary" href="<?= e($url . '&id=' . $tableRow['id']) ?>">Edit</a>
        <?php if ($entity === 'semesters' && (int) $data['current'] !== (int) $tableRow['id']): ?>
            <form method="post" action="<?= e($url . '&id=' . $tableRow['id']) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <button class="btn btn-sm btn-outline-secondary" name="action" value="current">Set current</button>
            </form>
        <?php endif ?>
    </div>
</td>

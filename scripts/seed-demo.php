<?php
declare(strict_types=1);

require __DIR__ . '/../app/db.php';

if (PHP_SAPI !== 'cli' || !preg_match('/^uemp_demo(?:_[a-z0-9]+)?$/D', getenv('DB_NAME') ?: '')) {
    fwrite(STDERR, "Run from CLI against a dedicated uemp_demo database.\n");
    exit(1);
}

$pdo = db();
$lock = $pdo->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':seed'), 10)")->fetchColumn();
if ((int) $lock !== 1) {
    throw new RuntimeException('Could not acquire seed lock.');
}
try {
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        echo "Seed skipped because the database already has users.\n";
    } else {
        $statement = $pdo->prepare('SET @demo_hash = ?');
        $statement->execute([password_hash('PortalDemo!2026', PASSWORD_DEFAULT)]);
        foreach (explode(';', file_get_contents(__DIR__ . '/../database/002_demo.sql')) as $sql) {
            if (trim($sql) !== '') {
                $pdo->exec($sql);
            }
        }
        echo "Demo seeded.\n";
    }
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':seed'))");
}

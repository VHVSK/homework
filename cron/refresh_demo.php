<?php
declare(strict_types=1);

// Run from CLI only. Never expose database maintenance as a public URL.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$arguments = array_slice($argv, 1);
if (array_diff($arguments, ['--dry-run', '--help'])) {
    fwrite(STDERR, "Unknown argument. Use --help.\n");
    exit(2);
}
if (in_array('--help', $arguments, true)) {
    echo "Usage: php cron/refresh_demo.php [--dry-run]\n",
        "Refresh only unchanged HW-DEMO-v2 homework dates. No records are deleted or created.\n",
        "--dry-run executes the update in a transaction, then rolls it back.\n";
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';
$connection = null;
$lockName = null;
$hasLock = false;
try {
    $connection = db();
    $lockName = 'hw-demo-dates-' . substr(hash('sha256', (string)config('db_name')), 0, 32);
    $lock = $connection->prepare('SELECT GET_LOCK(?, 0)');
    $lock->execute([$lockName]);
    if ((int)$lock->fetchColumn() !== 1) {
        throw new RuntimeException('Another refresh is running or the database lock is unavailable.');
    }
    $hasLock = true;
    $sql = file_get_contents(__DIR__ . '/../database/refresh-demo-dates.sql');
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Refresh SQL is missing.');
    }
    $today = date('Y-m-d');
    $connection->prepare('SET @demo_today = ?')->execute([$today]);
    $connection->beginTransaction();
    $changed = $connection->exec($sql);
    $dryRun = in_array('--dry-run', $arguments, true);
    if ($dryRun) {
        $connection->rollBack();
    } else {
        $connection->commit();
    }
    echo ($dryRun ? 'DRY RUN (rolled back)' : 'OK'), ': ', $changed,
        ' homework dates ', ($dryRun ? 'would change' : 'changed'),
        '; reference date ', $today, ' (', date_default_timezone_get(), ").\n";
} catch (Throwable $error) {
    if ($connection?->inTransaction()) {
        $connection->rollBack();
    }
    // Database exceptions can contain connection information. Do not log credentials.
    fwrite(STDERR, "Demo refresh failed. Check database access, schema and refresh SQL.\n");
    exit(1);
} finally {
    if ($hasLock && $connection) {
        try {
            $connection->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
        } catch (Throwable) {
            // Closing the connection also releases its advisory lock.
        }
    }
}

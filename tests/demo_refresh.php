<?php
declare(strict_types=1);

// CLI-only integration check in a newly created, disposable MySQL database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$host = getenv('APP_DB_HOST') ?: '127.0.0.1';
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "This check requires loopback MySQL.\n"); exit(1);
}
$name = 'homework_refresh_' . bin2hex(random_bytes(6)) . '_test';
$server = new PDO('mysql:host='.$host.';port='.(getenv('APP_DB_PORT') ?: '3306').';charset=utf8mb4',
    getenv('APP_DB_USER') ?: 'root', getenv('APP_DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$checks = 0;
function refresh_check(bool $condition): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('Demo refresh assertion failed.'); }
    $checks++;
}
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $server->exec('USE `'.$name.'`');
    $server->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
    $server->exec(file_get_contents(__DIR__.'/../database/demo.sql'));
    refresh_check((int)$server->query('SELECT COUNT(*) FROM homework')->fetchColumn() === 720);
    $sql = file_get_contents(__DIR__.'/../database/refresh-demo-dates.sql');
    $server->exec("SET @demo_today = '2030-02-10'");
    refresh_check($server->exec($sql) === 720);
    refresh_check($server->exec($sql) === 0);
    refresh_check($server->query('SELECT MAX(published_date) FROM homework')->fetchColumn() === '2030-02-10');
    refresh_check($server->query('SELECT MIN(published_date) FROM homework')->fetchColumn() === '2030-02-03');
    refresh_check($server->query('SELECT MIN(due_date) FROM homework')->fetchColumn() === '2030-02-04');
    refresh_check($server->query('SELECT MAX(due_date) FROM homework')->fetchColumn() === '2030-02-15');
    $ids = $server->query('SELECT id FROM homework ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
    $server->prepare("UPDATE homework SET body_html = '<p>Custom edit</p>' WHERE id = ?")->execute([$ids[0]]);
    $server->prepare('INSERT INTO homework (assignment_id,title,body_html,published_date,due_date) SELECT assignment_id, ?, body_html, published_date, due_date FROM homework WHERE id = ?')
        ->execute(['Custom homework', $ids[1]]);
    $before = $server->query('SELECT id,title,body_html,published_date,due_date FROM homework ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $server->beginTransaction();
    $server->exec("SET @demo_today = '2030-02-11'");
    refresh_check($server->exec($sql) === 719);
    $server->rollBack();
    refresh_check($before === $server->query('SELECT id,title,body_html,published_date,due_date FROM homework ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
    refresh_check($server->exec($sql) === 719);
    $after = $server->query('SELECT id,title,body_html,published_date,due_date FROM homework ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    refresh_check(count($before) === count($after));
    foreach ($before as $index => $row) {
        refresh_check($row['id'] === $after[$index]['id'] && $row['title'] === $after[$index]['title'] && $row['body_html'] === $after[$index]['body_html']);
    }
    refresh_check($before[0] === $after[0]);
    refresh_check(end($before) === end($after));
    echo 'OK: ', $checks, " demo refresh checks in a disposable MySQL database.\n";
} finally {
    // Only the randomly named database created by this test can be removed.
    if ($created && preg_match('/^homework_refresh_[a-f0-9]{12}_test$/D', $name)) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
}

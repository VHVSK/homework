<?php
declare(strict_types=1);

function setup_token_ready(): bool {
    $token = (string)config('setup_token', '');
    return strlen($token) >= 24 && mb_strlen($token) <= 200 && trim($token) === $token;
}

/** Read-only checks. Return safe categories, never database error messages or credentials. */
function installation_status(): array {
    $status = ['database'=>false, 'schema'=>false, 'token'=>setup_token_ready(), 'installed'=>false, 'issue'=>null];
    if (!extension_loaded('pdo_mysql')) {
        $status['issue'] = 'driver';
        return $status;
    }
    try {
        $connection = db();
        $connection->query('SELECT 1');
        $status['database'] = true;
    } catch (PDOException $error) {
        $code = (int)($error->errorInfo[1] ?? 0);
        $status['issue'] = match ($code) { 1045, 1044=>'credentials', 1049=>'database_missing', default=>'connection' };
        return $status;
    }

    $checks = [
        'SELECT id, name, city, address, is_active, created_at FROM schools LIMIT 0',
        'SELECT id, school_id, name, email, password_hash, google_sub, role, is_active, admin_guard, created_at FROM users LIMIT 0',
        'SELECT id, school_id, name, sort_order, created_at FROM classes LIMIT 0',
        'SELECT id, school_id, name, color, created_at FROM subjects LIMIT 0',
        'SELECT id, school_id, teacher_id, subject_id, class_id, created_at FROM teaching_assignments LIMIT 0',
        'SELECT id, assignment_id, title, body_html, published_date, due_date, created_at, updated_at FROM homework LIMIT 0',
        'SELECT id, identifier_hash, attempted_at FROM login_attempts LIMIT 0',
    ];
    try {
        foreach ($checks as $sql) $connection->query($sql);
        $status['installed'] = (bool)$connection->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        $status['schema'] = true;
    } catch (PDOException $error) {
        $code = (int)($error->errorInfo[1] ?? 0);
        $state = (string)($error->errorInfo[0] ?? $error->getCode());
        if ($code === 1146 || $state === '42S02') {
            $status['issue'] = 'tables_missing';
        } elseif ($code === 1054 || $state === '42S22') {
            $status['issue'] = 'schema_incompatible';
        } else {
            $status['issue'] = 'schema_unreadable';
        }
    }
    return $status;
}

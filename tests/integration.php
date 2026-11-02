<?php
declare(strict_types=1);

/** CLI-only. Creates schema in a disposable *_test database; fixture rows roll back. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$testName = getenv('APP_TEST_DB_NAME') ?: 'homework_test';
if (!preg_match('/^[a-zA-Z0-9_]+_test$/D', $testName)) {
    fwrite(STDERR, "Відмова: APP_TEST_DB_NAME має містити лише латинські літери, цифри, _ та закінчуватися на _test.\n");
    exit(1);
}
$host = getenv('APP_DB_HOST') ?: '127.0.0.1';
$port = getenv('APP_DB_PORT') ?: '3306';
$username = getenv('APP_DB_USER') ?: 'root';
$password = getenv('APP_DB_PASSWORD') ?: '';
date_default_timezone_set('Europe/Kyiv');
$passed = 0;
function db(): PDO { global $testPdo; return $testPdo; }
function integration_check(bool $condition, string $description): void
{
    global $passed;
    if (!$condition) throw new RuntimeException($description);
    $passed++;
}
function integration_execute(string $sql, array $params = []): int
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return (int) db()->lastInsertId();
}
function constraint_rejected(callable $callback, string $description): void
{
    try {
        $callback();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            integration_check(true, $description);
            return;
        }
        throw $exception;
    }
    throw new RuntimeException($description);
}
require __DIR__ . '/../app/repository.php';

try {
    $testPdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $testPdo->exec("CREATE DATABASE IF NOT EXISTS `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $testPdo->exec("USE `$testName`");
    $testPdo->exec("SET time_zone = '+00:00'");
    $testPdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    $testPdo->beginTransaction();
    $tag = bin2hex(random_bytes(5));
    $city = '__integration_' . $tag;
    $schoolA = integration_execute('INSERT INTO schools(name,city) VALUES(?,?)', ['Тестова школа А ' . $tag, $city]);
    $schoolB = integration_execute('INSERT INTO schools(name,city) VALUES(?,?)', ['Тестова школа Б ' . $tag, $city]);
    if (repo_count("SELECT COUNT(*) FROM users WHERE role='admin'") === 0) {
        integration_execute("INSERT INTO users(name,email,role) VALUES(?,?,'admin')", ['Тестовий адміністратор', "admin-$tag@example.invalid"]);
    }
    constraint_rejected(static fn() => integration_execute("INSERT INTO users(name,email,role) VALUES(?,?,'admin')", ['Другий адміністратор', "second-admin-$tag@example.invalid"]), 'База повинна відхиляти другого адміністратора.');
    $teacherA = integration_execute("INSERT INTO users(school_id,name,email,role) VALUES(?,?,?,'teacher')", [$schoolA, 'Вчитель А', "a-$tag@example.invalid"]);
    $teacherB = integration_execute("INSERT INTO users(school_id,name,email,role) VALUES(?,?,?,'teacher')", [$schoolB, 'Вчитель Б', "b-$tag@example.invalid"]);
    $inactiveTeacher = integration_execute("INSERT INTO users(school_id,name,email,role,is_active) VALUES(?,?,?,'teacher',0)", [$schoolA, 'Неактивний вчитель', "inactive-$tag@example.invalid"]);
    $classA = integration_execute('INSERT INTO classes(school_id,name) VALUES(?,?)', [$schoolA, '7-А']);
    $classB = integration_execute('INSERT INTO classes(school_id,name) VALUES(?,?)', [$schoolB, '8-Б']);
    $subjectA = integration_execute('INSERT INTO subjects(school_id,name) VALUES(?,?)', [$schoolA, 'Математика']);
    $subjectB = integration_execute('INSERT INTO subjects(school_id,name) VALUES(?,?)', [$schoolB, 'Історія']);
    constraint_rejected(static fn() => integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $teacherA, $subjectB, $classA]), 'Предмет іншої школи повинен відхилятися зовнішнім ключем.');
    constraint_rejected(static fn() => integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $teacherB, $subjectA, $classA]), 'Вчитель іншої школи повинен відхилятися зовнішнім ключем.');
    constraint_rejected(static fn() => integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $teacherA, $subjectA, $classB]), 'Клас іншої школи повинен відхилятися зовнішнім ключем.');
    $assignmentA = integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $teacherA, $subjectA, $classA]);
    $assignmentB = integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolB, $teacherB, $subjectB, $classB]);
    $inactiveAssignment = integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $inactiveTeacher, $subjectA, $classA]);
    constraint_rejected(static fn() => integration_execute('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolA, $teacherA, $subjectA, $classA]), 'Повторне призначення повинно відхилятися.');
    $today = new DateTimeImmutable(date('Y-m-d'));
    for ($i = 0; $i < 14; $i++) {
        $lastHomework = integration_execute('INSERT INTO homework(assignment_id,title,body_html,published_date) VALUES(?,?,?,?)', [$assignmentA, 'Рівняння ' . $i, '<p>Тестове завдання</p>', $today->modify('-' . intdiv($i, 2) . ' days')->format('Y-m-d')]);
    }
    integration_execute('INSERT INTO homework(assignment_id,title,body_html,published_date) VALUES(?,?,?,?)', [$assignmentA, 'Майбутня публікація', '<p>Приховано</p>', $today->modify('+2 days')->format('Y-m-d')]);
    integration_execute('INSERT INTO homework(assignment_id,title,body_html,published_date) VALUES(?,?,?,?)', [$inactiveAssignment, 'Неактивний автор', '<p>Приховано</p>', $today->format('Y-m-d')]);
    $otherHomework = integration_execute('INSERT INTO homework(assignment_id,title,body_html,published_date) VALUES(?,?,?,?)', [$assignmentB, 'Інша школа', '<p>Інша школа</p>', $today->format('Y-m-d')]);

    $filters = public_filters(['city' => $city, 'school' => $schoolA, 'class' => $classA, 'subject' => $subjectA]);
    $first = public_homework($filters);
    integration_check($first['total'] === 14 && count($first['items']) === 12 && $first['pages'] === 2, 'Публічна видача повинна приховувати майбутні записи та неактивних авторів і мати пагінацію.');
    integration_check(count(array_filter($first['items'], static fn(array $h): bool => (int) $h['school_id'] === $schoolA && (int) $h['teacher_id'] === $teacherA)) === 12, 'Фільтри повинні ізолювати школу, клас та предмет.');
    $ordered = $first['items'];
    usort($ordered, static fn(array $a, array $b): int => [$b['published_date'], (int) $b['id']] <=> [$a['published_date'], (int) $a['id']]);
    integration_check(array_column($ordered, 'id') === array_column($first['items'], 'id'), 'Записи повинні сортуватися за датою та id від нових до старих.');
    $second = public_homework(array_replace($filters, ['page' => 2]));
    integration_check(count($second['items']) === 2 && !array_intersect(array_column($first['items'], 'id'), array_column($second['items'], 'id')), 'Сторінки не повинні повторювати записи.');
    $beyond = public_homework(array_replace($filters, ['page' => 9999]));
    integration_check($beyond['page'] === 2 && count($beyond['items']) === 2, 'Сторінка поза діапазоном повинна обмежуватися останньою.');
    integration_check(public_homework(array_replace($filters, ['subject' => $subjectB]))['total'] === 0, 'Предмет іншої школи не повинен повертати записи.');
    integration_check(public_homework(array_replace($filters, ['q' => "' OR 1=1 --"]))['total'] === 0, 'Пошуковий запит не повинен виконувати SQL.');
    integration_check(public_homework(array_replace($filters, ['q' => '%']))['total'] === 0, 'Символ % у пошуку повинен бути буквальним.');
    $options = filter_options($filters);
    integration_check(array_map('intval', array_column($options['classes'], 'id')) === [$classA] && array_map('intval', array_column($options['subjects'], 'id')) === [$subjectA], 'Списки класів та предметів повинні належати обраній школі.');
    $teacherDashboard = dashboard_data(['id' => $teacherA, 'role' => 'teacher', 'school_id' => $schoolA]);
    integration_check($teacherDashboard['counts']['homework'] === 15 && count($teacherDashboard['assignments']) === 1, 'Кабінет вчителя повинен бачити власні, включно із запланованими, записи.');
    integration_check((int) homework_row($otherHomework)['teacher_id'] === $teacherB, 'Детальний запис повинен містити власника для перевірки дозволів.');
    constraint_rejected(static fn() => integration_execute('DELETE FROM teaching_assignments WHERE id=?', [$assignmentA]), 'Видалення призначення не повинно знищувати домашні завдання.');
    integration_check(homework_row($lastHomework) !== null, 'Запис повинен зберігатися після відхиленого видалення призначення.');
    integration_execute('UPDATE schools SET is_active=0 WHERE id=?', [$schoolA]);
    integration_check(public_homework($filters)['total'] === 0, 'Неактивна школа повинна зникати з публічної видачі.');
    $testPdo->rollBack();
    echo "OK: $passed інтеграційних перевірок MySQL; тестові записи відкочено ($testName).\n";
} catch (Throwable $exception) {
    if (isset($testPdo) && $testPdo->inTransaction()) $testPdo->rollBack();
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n");
    exit(1);
}

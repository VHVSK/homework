<?php
declare(strict_types=1);

/** @return array<int, array<string, mixed>> */
function repo_rows(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function repo_count(string $sql, array $params = []): int
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function repo_text(mixed $value, int $limit): string
{
    if (!is_scalar($value)) {
        return '';
    }
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
}

function repo_id(mixed $value): int
{
    return is_scalar($value) ? max(0, (int) filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) : 0;
}

function school_list(): array
{
    return repo_rows(
        "SELECT s.*, (SELECT u.name FROM users u WHERE u.school_id = s.id AND u.role = 'secretary' ORDER BY u.id LIMIT 1) AS secretary_name,
         (SELECT u.email FROM users u WHERE u.school_id = s.id AND u.role = 'secretary' ORDER BY u.id LIMIT 1) AS secretary_email,
         (SELECT COUNT(*) FROM users u WHERE u.school_id = s.id AND u.role = 'teacher') AS teacher_count,
         (SELECT COUNT(*) FROM classes c WHERE c.school_id = s.id) AS class_count
         FROM schools s ORDER BY s.city, s.name, s.id"
    );
}

function public_filters(array $input): array
{
    $period = repo_text($input['period'] ?? 'all', 10);
    $dateBy = repo_text($input['date_by'] ?? 'published', 12);
    $date = repo_text($input['date'] ?? date('Y-m-d'), 10);
    $parsed = preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $date)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $date = date('Y-m-d');
    }
    return [
        'city' => repo_text($input['city'] ?? '', 120),
        'school' => repo_id($input['school'] ?? 0),
        'class' => repo_id($input['class'] ?? 0),
        'subject' => repo_id($input['subject'] ?? 0),
        'q' => repo_text($input['q'] ?? '', 100),
        'page' => max(1, repo_id($input['page'] ?? 1)),
        'period' => in_array($period, ['all', 'day', 'week'], true) ? $period : 'all',
        'date' => $date,
        'date_by' => in_array($dateBy, ['published', 'due'], true) ? $dateBy : 'published',
    ];
}

/** @return array{from:?string,to:?string} */
function period_bounds(array $filters): array
{
    $filters = public_filters($filters);
    if ($filters['period'] === 'all') {
        return ['from' => null, 'to' => null];
    }
    $start = new DateTimeImmutable($filters['date']);
    if ($filters['period'] === 'week') {
        $start = $start->modify('-' . ((int)$start->format('N') - 1) . ' days');
    }
    $end = $filters['period'] === 'week' ? $start->modify('+6 days') : $start;
    // MySQL DATE supports years 1000..9999; boundary weeks stay within that range.
    return ['from' => $start < new DateTimeImmutable('1000-01-01') ? '1000-01-01' : $start->format('Y-m-d'), 'to' => $end > new DateTimeImmutable('9999-12-31') ? '9999-12-31' : $end->format('Y-m-d')];
}

/** Match the same date interval in the built-in demonstration as in SQL. */
function homework_matches_period(array $row, array $filters): bool
{
    $filters = public_filters($filters);
    $bounds = period_bounds($filters);
    if ($bounds['from'] === null) return true;
    $column = $filters['date_by'] === 'due' ? 'due_date' : 'published_date';
    $value = $row[$column] ?? null;
    return is_string($value) && $value >= $bounds['from'] && $value <= $bounds['to'];
}

function homework_select(): string
{
    return 'SELECT h.*, a.school_id, a.teacher_id, a.subject_id, a.class_id,
        s.name AS school_name, s.city, c.name AS class_name, sub.name AS subject_name,
        sub.color AS subject_color, u.name AS teacher_name, u.is_active AS teacher_active
        FROM homework h
        INNER JOIN teaching_assignments a ON a.id = h.assignment_id
        INNER JOIN schools s ON s.id = a.school_id
        INNER JOIN users u ON u.id = a.teacher_id
        INNER JOIN classes c ON c.id = a.class_id
        INNER JOIN subjects sub ON sub.id = a.subject_id';
}

/** One public predicate for listings and complete print collections. */
function public_homework_predicate(array $filters): array
{
    $filters = public_filters($filters);
    $where = ['s.is_active = 1', 'u.is_active = 1', "u.role = 'teacher'", 'h.published_date <= ?'];
    $params = [date('Y-m-d')];
    if ($filters['city'] !== '') {
        $where[] = 's.city = ?';
        $params[] = $filters['city'];
    }
    foreach (['school' => 'a.school_id', 'class' => 'a.class_id', 'subject' => 'a.subject_id'] as $key => $column) {
        if ($filters[$key] > 0) {
            $where[] = $column . ' = ?';
            $params[] = $filters[$key];
        }
    }
    if ($filters['q'] !== '') {
        $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']) . '%';
        $where[] = "(h.title LIKE ? ESCAPE '!' OR h.body_html LIKE ? ESCAPE '!' OR sub.name LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!')";
        array_push($params, $term, $term, $term, $term);
    }
    $bounds = period_bounds($filters);
    if ($bounds['from'] !== null) {
        $column = $filters['date_by'] === 'due' ? 'h.due_date' : 'h.published_date';
        $where[] = "$column >= ? AND $column <= ?";
        array_push($params, $bounds['from'], $bounds['to']);
    }
    return ['predicate' => ' WHERE ' . implode(' AND ', $where), 'params' => $params];
}

function public_homework(array $filters): array
{
    $filters = public_filters($filters);
    ['predicate' => $predicate, 'params' => $params] = public_homework_predicate($filters);
    $countFrom = substr(homework_select(), strpos(homework_select(), 'FROM homework'));
    $total = repo_count('SELECT COUNT(*) ' . $countFrom . $predicate, $params);
    $pages = max(1, (int) ceil($total / 12));
    $page = min($filters['page'], $pages);
    $offset = ($page - 1) * 12;
    $items = repo_rows(homework_select() . $predicate . " ORDER BY h.published_date DESC, h.id DESC LIMIT 12 OFFSET $offset", $params);
    return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

/** All matching public rows; deliberately independent of listing pagination. */
function public_collection(array $filters): array
{
    ['predicate' => $predicate, 'params' => $params] = public_homework_predicate($filters);
    return repo_rows(homework_select() . $predicate . ' ORDER BY sub.name, h.published_date DESC, h.id DESC', $params);
}

function filter_options(array $filters): array
{
    $filters = public_filters($filters);
    $cities = array_column(repo_rows('SELECT DISTINCT city FROM schools WHERE is_active = 1 ORDER BY city'), 'city');
    $params = [];
    $sql = 'SELECT id, name, city FROM schools WHERE is_active = 1';
    if ($filters['city'] !== '') {
        $sql .= ' AND city = ?';
        $params[] = $filters['city'];
    }
    $schools = repo_rows($sql . ' ORDER BY city, name', $params);
    $selectedExists = in_array($filters['school'], array_map('intval', array_column($schools, 'id')), true);
    $classes = [];
    $subjects = [];
    if ($selectedExists) {
        $classes = repo_rows('SELECT id, name FROM classes WHERE school_id = ? ORDER BY sort_order, name', [$filters['school']]);
        $subjects = repo_rows('SELECT id, name, color FROM subjects WHERE school_id = ? ORDER BY name', [$filters['school']]);
    }
    return compact('cities', 'schools', 'classes', 'subjects');
}

function assignment_rows(int $schoolId, ?int $teacherId = null): array
{
    $sql = 'SELECT a.*, u.name AS teacher_name, u.email AS teacher_email, u.is_active AS teacher_active,
        sub.name AS subject_name, sub.color AS subject_color, c.name AS class_name, s.name AS school_name,
        (SELECT COUNT(*) FROM homework h WHERE h.assignment_id = a.id) AS homework_count
        FROM teaching_assignments a
        INNER JOIN users u ON u.id = a.teacher_id
        INNER JOIN subjects sub ON sub.id = a.subject_id
        INNER JOIN classes c ON c.id = a.class_id
        INNER JOIN schools s ON s.id = a.school_id
        WHERE a.school_id = ?';
    $params = [$schoolId];
    if ($teacherId !== null) {
        $sql .= ' AND a.teacher_id = ?';
        $params[] = $teacherId;
    }
    return repo_rows($sql . ' ORDER BY c.sort_order, c.name, sub.name, u.name', $params);
}

function homework_row(int $id): ?array
{
    return repo_rows(homework_select() . ' WHERE h.id = ?', [$id])[0] ?? null;
}

function dashboard_data(array $user): array
{
    $role = (string) $user['role'];
    $schoolId = (int) ($user['school_id'] ?? 0);
    $userId = (int) $user['id'];
    $schools = $role === 'admin' ? school_list() : repo_rows('SELECT * FROM schools WHERE id = ?', [$schoolId]);
    $users = [];
    $classes = [];
    $subjects = [];
    $assignments = [];
    if ($role === 'admin') {
        $users = repo_rows("SELECT u.*, s.name AS school_name, s.city FROM users u LEFT JOIN schools s ON s.id = u.school_id WHERE u.role <> 'admin' ORDER BY u.created_at DESC, u.id DESC");
        $classes = repo_rows('SELECT c.*, s.name AS school_name FROM classes c JOIN schools s ON s.id = c.school_id ORDER BY s.name, c.sort_order, c.name');
        $subjects = repo_rows('SELECT sub.*, s.name AS school_name FROM subjects sub JOIN schools s ON s.id = sub.school_id ORDER BY s.name, sub.name');
        foreach ($schools as $school) {
            array_push($assignments, ...assignment_rows((int) $school['id']));
        }
        $homework = repo_rows(homework_select() . ' ORDER BY h.published_date DESC, h.id DESC LIMIT 100');
        $homeworkCount = repo_count('SELECT COUNT(*) FROM homework');
    } elseif ($role === 'secretary') {
        $users = repo_rows("SELECT u.*, s.name AS school_name FROM users u JOIN schools s ON s.id = u.school_id WHERE u.school_id = ? AND u.role = 'teacher' ORDER BY u.name", [$schoolId]);
        $classes = repo_rows('SELECT * FROM classes WHERE school_id = ? ORDER BY sort_order, name', [$schoolId]);
        $subjects = repo_rows('SELECT * FROM subjects WHERE school_id = ? ORDER BY name', [$schoolId]);
        $assignments = assignment_rows($schoolId);
        $homework = repo_rows(homework_select() . ' WHERE a.school_id = ? ORDER BY h.published_date DESC, h.id DESC LIMIT 100', [$schoolId]);
        $homeworkCount = repo_count('SELECT COUNT(*) FROM homework h JOIN teaching_assignments a ON a.id = h.assignment_id WHERE a.school_id = ?', [$schoolId]);
    } else {
        $assignments = assignment_rows($schoolId, $userId);
        $homework = repo_rows(homework_select() . ' WHERE a.teacher_id = ? AND a.school_id = ? ORDER BY h.published_date DESC, h.id DESC LIMIT 100', [$userId, $schoolId]);
        $homeworkCount = repo_count('SELECT COUNT(*) FROM homework h JOIN teaching_assignments a ON a.id = h.assignment_id WHERE a.teacher_id = ? AND a.school_id = ?', [$userId, $schoolId]);
        $classMap = [];
        $subjectMap = [];
        foreach ($assignments as $assignment) {
            $classMap[$assignment['class_id']] = ['id' => $assignment['class_id'], 'name' => $assignment['class_name'], 'school_id' => $schoolId];
            $subjectMap[$assignment['subject_id']] = ['id' => $assignment['subject_id'], 'name' => $assignment['subject_name'], 'color' => $assignment['subject_color'], 'school_id' => $schoolId];
        }
        $classes = array_values($classMap);
        $subjects = array_values($subjectMap);
    }
    $counts = [
        'schools' => count($schools),
        'teachers' => count(array_filter($users, static fn(array $u): bool => $u['role'] === 'teacher')),
        'users' => count($users),
        'classes' => count($classes),
        'subjects' => count($subjects),
        'assignments' => count($assignments),
        'homework' => $homeworkCount,
    ];
    return compact('schools', 'users', 'classes', 'subjects', 'assignments', 'homework', 'counts');
}

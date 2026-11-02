<?php
declare(strict_types=1);

/** Only normalized public filters enter shared URLs. Never copy session/action fields. */
function share_query(array $input): array
{
    $filters = public_filters($input);
    unset($filters['page']);
    if ($filters['period'] === 'all') {
        unset($filters['date'], $filters['date_by']);
    }
    return array_filter($filters, static fn(mixed $value): bool => $value !== '' && $value !== 0);
}

function share_url(array $filters, bool $demo = false): string
{
    return route_url($demo ? 'demo' : 'home', share_query($filters));
}

function print_url(array $filters, bool $demo = false): string
{
    return route_url('print', share_query($filters) + ($demo ? ['demo' => 1] : []));
}

function collection_ready(array $filters): bool
{
    $filters = public_filters($filters);
    return $filters['school'] > 0 && $filters['class'] > 0;
}

function selection_summary(array $filters): string
{
    $filters = public_filters($filters);
    $bounds = period_bounds($filters);
    if ($bounds['from'] === null) return 'Усі опубліковані завдання за вибраними фільтрами';
    $basis = $filters['date_by'] === 'due' ? 'За строком виконання' : 'За датою публікації';
    $interval = $bounds['from'] === $bounds['to'] ? $bounds['from'] : $bounds['from'] . ' — ' . $bounds['to'];
    return $basis . ': ' . $interval;
}

/** @return array<string,mixed>|null Public metadata for a valid class in an active school. */
function collection_context(array $filters): ?array
{
    $filters = public_filters($filters);
    if (!collection_ready($filters)) return null;
    $rows = repo_rows('SELECT s.id AS school_id, s.name AS school_name, s.city, c.id AS class_id, c.name AS class_name FROM schools s INNER JOIN classes c ON c.school_id=s.id WHERE s.id=? AND c.id=? AND s.is_active=1', [$filters['school'], $filters['class']]);
    $context = $rows[0] ?? null;
    if (!$context || ($filters['city'] !== '' && $context['city'] !== $filters['city'])) return null;
    $context['subject_name'] = 'Усі предмети';
    if ($filters['subject'] > 0) {
        $subject = repo_rows('SELECT name FROM subjects WHERE school_id=? AND id=?', [$filters['school'], $filters['subject']])[0] ?? null;
        if (!$subject) return null;
        $context['subject_name'] = $subject['name'];
    }
    return $context;
}

/** Human-readable filename segments; callers still escape the result in HTML. */
function print_name_part(mixed $value, int $limit, string $fallback = ''): string
{
    $text = is_scalar($value) ? (string)$value : '';
    $text = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $text = preg_replace('~[\\\\/:*?"<>|\p{C}]+~u', ' ', $text) ?? '';
    $text = preg_replace('/[\s\p{Z}]+/u', ' ', $text) ?? '';
    $text = trim($text, " .\t\n\r\0\x0B");
    if ($text === '') $text = $fallback;
    return rtrim(mb_substr($text, 0, $limit, 'UTF-8'), " .");
}

function print_name_date(mixed $value): ?string
{
    if (!is_string($value) || !preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value)) return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

/** Leave room for .pdf on filesystems that limit UTF-8 filename bytes. */
function print_name_join(array $parts): string
{
    $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
    $name = implode(' — ', $parts);
    while (mb_strlen($name, 'UTF-8') > 160 || strlen($name) > 240) {
        $candidate = null;
        $largest = 0;
        foreach ($parts as $index => $part) {
            // Preserve the full deadline, search marker and school suffix first.
            if ($index === 2 || $part === 'пошук' || $index === count($parts)-1) continue;
            $minimum = $index === 0 ? 4 : ($index === 1 ? 8 : 0);
            if (mb_strlen($part, 'UTF-8') > $minimum && strlen($part) > $largest) {
                $candidate = $index;
                $largest = strlen($part);
            }
        }
        if ($candidate === null) {
            $last = count($parts)-1;
            if (mb_strlen($parts[$last], 'UTF-8') > 12) $candidate = $last;
        }
        if ($candidate === null) break;
        $parts[$candidate] = rtrim(mb_substr($parts[$candidate], 0, -1, 'UTF-8'), " .");
        $name = implode(' — ', $parts);
    }
    return $name;
}

/** Suggested filename without extension for one public homework item. */
function homework_print_name(array $item): string
{
    $due = print_name_date($item['due_date'] ?? null);
    $parts = [
        print_name_part($item['class_name'] ?? '', 16, 'Клас'),
        print_name_part($item['subject_name'] ?? '', 32, 'Домашнє завдання'),
        $due ? 'виконати до '.$due : 'без строку',
        print_name_part($item['title'] ?? '', 28),
        print_name_part($item['school_name'] ?? '', 34, 'Школа'),
    ];
    return print_name_join($parts);
}

/** Suggested filename without extension for all selected public records. */
function collection_print_name(array $context, array $filters, array $items = []): string
{
    $filters = public_filters($filters);
    $bounds = period_bounds($filters);
    if ($bounds['from'] !== null) {
        $interval = $bounds['from'] === $bounds['to'] ? $bounds['from'] : $bounds['from'].'_'.$bounds['to'];
        $label = $filters['date_by'] === 'due'
            ? ($bounds['from'] === $bounds['to'] ? 'виконати до ' : 'строки ')
            : 'опубліковано ';
        $period = $label.$interval;
    } else {
        $dates = [];
        $withoutDate = false;
        foreach ($items as $item) {
            $due = is_array($item) ? print_name_date($item['due_date'] ?? null) : null;
            if ($due === null) $withoutDate = true;
            else $dates[] = $due;
        }
        sort($dates, SORT_STRING);
        if (!$dates) $period = 'усі дати';
        elseif ($dates[0] === $dates[count($dates)-1]) $period = 'виконати до '.$dates[0];
        else $period = 'строки '.$dates[0].'_'.$dates[count($dates)-1];
        if ($dates && $withoutDate) $period .= ' і без строку';
    }
    $parts = [
        print_name_part($context['class_name'] ?? '', 16, 'Клас'),
        print_name_part($context['subject_name'] ?? '', 32, 'Усі предмети'),
        print_name_part($period, 50, 'усі дати'),
        $filters['q'] !== '' ? 'пошук' : '',
        print_name_part($context['school_name'] ?? '', 34, 'Школа'),
    ];
    return print_name_join($parts);
}

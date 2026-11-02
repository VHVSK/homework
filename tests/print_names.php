<?php
declare(strict_types=1);

// Pure filename helpers; no application configuration, database or file writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('mbstring')) { fwrite(STDERR, "Потрібне розширення mbstring.\n"); exit(1); }
date_default_timezone_set('Europe/Kyiv');
require __DIR__.'/../app/repository.php';
require __DIR__.'/../app/sharing.php';
$passed = 0;
function print_name_check(bool $condition, string $message): void {
    global $passed;
    if (!$condition) throw new RuntimeException($message);
    $passed++;
}

try {
    $item = ['class_name'=>'7-А','subject_name'=>'Математика','due_date'=>'2026-11-01','title'=>'Звичайні дроби','school_name'=>'Ліцей «Світанок»'];
    $name = homework_print_name($item);
    print_name_check($name === '7-А — Математика — виконати до 2026-11-01 — Звичайні дроби — Ліцей «Світанок»', 'Single filenames must identify class, subject, due date, task and school.');
    print_name_check(!str_ends_with($name,'.pdf'), 'The caller/browser must supply the file extension once.');
    print_name_check(str_contains(homework_print_name(array_replace($item,['due_date'=>null])),'без строку'), 'An item without a deadline must not invent a due date.');
    print_name_check(str_contains(homework_print_name(array_replace($item,['due_date'=>'2026-02-30'])),'без строку'), 'Impossible deadlines must not roll into another date.');
    print_name_check(str_contains(homework_print_name(array_replace($item,['due_date'=>['2026-11-01']])),'без строку'), 'Malformed deadline arrays must remain harmless.');
    $unsafe = homework_print_name(array_replace($item,['class_name'=>"7/А\n",'subject_name'=>"<b>Математика</b>: *?\"<>|\\ /\u{202E}",'school_name'=>"...Ліцей\0\u{200B}   № 1... "]));
    print_name_check(!preg_match('~[\\\\/:*?"<>|\p{C}]~u',$unsafe), 'Filenames must exclude Windows-forbidden characters, control characters and hidden format controls.');
    print_name_check(str_contains($unsafe,'Математика') && str_ends_with($unsafe,'Ліцей № 1'), 'Sanitization must preserve readable Ukrainian while trimming unsafe punctuation and whitespace.');
    print_name_check(print_name_part('&lt;script&gt;alert(1)&lt;/script&gt; Школа',34) === 'alert(1) Школа', 'Encoded HTML markup must not remain in names.');
    $long = homework_print_name(array_replace($item,['class_name'=>str_repeat('Клас ',20),'subject_name'=>str_repeat('Предмет ',40),'title'=>str_repeat('Тема ',80),'school_name'=>str_repeat('Школа ',70)]));
    print_name_check(mb_strlen($long,'UTF-8') <= 160 && str_contains($long,'2026-11-01'), 'Long text must not truncate the due-date portion or exceed the portable length budget.');
    print_name_check(str_ends_with($long,print_name_part(str_repeat('Школа ',70),34)), 'The school suffix must remain recognizable even for long names.');
    $context = ['class_name'=>'7-А','subject_name'=>'Усі предмети','school_name'=>'Ліцей «Світанок»'];
    $week = collection_print_name($context,['period'=>'week','date'=>'2026-01-01','date_by'=>'due']);
    print_name_check(str_contains($week,'строки 2025-12-29_2026-01-04') && str_contains($week,'Усі предмети'), 'A week filename must identify all subjects and the complete Monday-Sunday due interval.');
    $day = collection_print_name($context,['period'=>'day','date'=>'2026-10-30','date_by'=>'published']);
    print_name_check(str_contains($day,'опубліковано 2026-10-30') && !str_contains($day,'виконати до'), 'Publication filtering must never be mislabeled as a due date.');
    print_name_check(str_contains(collection_print_name($context,['period'=>'day','date'=>'2026-11-01','date_by'=>'due']),'виконати до 2026-11-01'), 'A due-day selection must identify its explicit deadline.');
    $all = collection_print_name($context,[],[['due_date'=>'2026-11-03'],['due_date'=>'2026-10-30']]);
    print_name_check(str_contains($all,'строки 2026-10-30_2026-11-03'), 'All-time collections must summarize the actual deadline range in chronological order.');
    $same = collection_print_name($context,[],[['due_date'=>'2026-11-01'],['due_date'=>'2026-11-01']]);
    print_name_check(str_contains($same,'виконати до 2026-11-01'), 'A collection with one shared deadline should use that date.');
    $mixed = collection_print_name($context,[],[['due_date'=>'2026-11-01'],['due_date'=>null]]);
    print_name_check(str_contains($mixed,'2026-11-01 і без строку'), 'Mixed known and missing deadlines must be disclosed in the suggested filename.');
    print_name_check(str_contains(collection_print_name($context,[],[['due_date'=>null]]),'усі дати'), 'A collection without known deadlines must use an honest undated label.');
    $search = collection_print_name($context,['q'=>'таємний текст пошуку']);
    print_name_check(str_contains($search,' — пошук — ') && !str_contains($search,'таємний'), 'Filtered search collections must be marked without copying arbitrary search text into the name.');
    $malformed = collection_print_name(['class_name'=>[],'subject_name'=>[],'school_name'=>[]],['period'=>[],'date'=>[],'date_by'=>[],'q'=>[]],[[],null]);
    print_name_check($malformed === 'Клас — Усі предмети — усі дати — Школа', 'Malformed context and filters must fall back to safe readable names.');
    $longCollection = collection_print_name(['class_name'=>str_repeat('Клас',20),'subject_name'=>str_repeat('Предмет',20),'school_name'=>str_repeat('Школа',30)],['period'=>'week','date'=>'2026-01-01','date_by'=>'published','q'=>'abc']);
    print_name_check(mb_strlen($longCollection,'UTF-8') <= 160 && str_contains($longCollection,'2025-12-29_2026-01-04') && str_contains($longCollection,' — пошук — '), 'Long collection metadata must retain its period, search marker and school within the length budget.');
    print_name_check(print_name_date("2026-11-\0") === null && print_name_date('0000-01-01') === null && print_name_date('2026-02-29') === null, 'Null bytes, zero years and impossible calendar dates must never become filename dates.');
    $emoji = homework_print_name(array_replace($item,['class_name'=>str_repeat('🌟',30),'subject_name'=>str_repeat('📖',40),'title'=>str_repeat('📝',50),'school_name'=>str_repeat('🏫',40)]));
    print_name_check(strlen($emoji) <= 240 && mb_strlen($emoji,'UTF-8') <= 160 && str_contains($emoji,'2026-11-01') && str_ends_with($emoji,'🏫'), 'Multibyte names must leave a safe byte budget for .pdf while preserving dates and the school suffix.');
    echo "OK: $passed перевірок описових назв PDF, дат і безпечних символів.\n";
} catch (Throwable $error) {
    fwrite(STDERR,'FAIL: '.$error->getMessage()."\n");
    exit(1);
}

<?php
declare(strict_types=1);

// CLI-only, in-memory SQLite fixtures. Never reads the configured application DB.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('pdo_sqlite')) { fwrite(STDERR, "Потрібне розширення pdo_sqlite.\n"); exit(1); }
date_default_timezone_set('Europe/Kyiv');
$testPdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db(): PDO { global $testPdo; return $testPdo; }
function route_url(string $route='home', array $query=[]): string { return 'https://example.invalid/index.php?'.http_build_query(['r'=>$route]+$query); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function layout_start(string $title, string $active='home', bool $guest=false): void { echo '<main>'; }
function layout_end(): void { echo '</main>'; }
class HttpError extends RuntimeException { public function __construct(public int $status, string $message) { parent::__construct($message); } }
require __DIR__.'/../app/repository.php';
require __DIR__.'/../app/security.php';
require __DIR__.'/../app/sharing.php';
require __DIR__.'/../app/sharing_views.php';
$passed=0;
function sharing_check(bool $result, string $message): void { global $passed; if(!$result) throw new RuntimeException($message); $passed++; }
function fixture(string $sql, array $params=[]): void { $statement=db()->prepare($sql); $statement->execute($params); }

try {
    $normalized=public_filters(['period'=>['week'],'date'=>['bad'],'date_by'=>'due_date); DROP TABLE users;--','page'=>['bad']]);
    sharing_check($normalized['period']==='all' && $normalized['date']===date('Y-m-d') && $normalized['date_by']==='published' && $normalized['page']===1,'Malformed query values must normalize safely.');
    sharing_check(public_filters(['date'=>'2026-02-30'])['date']===date('Y-m-d'),'Impossible dates must not roll into another month.');
    sharing_check(public_filters(['date'=>"2026-01-\0"])['date']===date('Y-m-d'),'Embedded null characters must not reach the PHP date parser.');
    sharing_check(period_bounds(['period'=>'week','date'=>'2026-01-01'])===['from'=>'2025-12-29','to'=>'2026-01-04'],'Week must cover Monday through Sunday across a year boundary.');
    sharing_check(period_bounds(['period'=>'week','date'=>'2026-01-04'])===['from'=>'2025-12-29','to'=>'2026-01-04'],'Sunday must belong to the preceding Monday.');
    sharing_check(period_bounds(['period'=>'day','date'=>'2026-01-01'])===['from'=>'2026-01-01','to'=>'2026-01-01'],'Day must remain a single inclusive calendar date.');
    sharing_check(period_bounds([])===['from'=>null,'to'=>null],'All-time filter must not create an implicit date restriction.');
    sharing_check(period_bounds(['period'=>'week','date'=>'9999-12-31'])['to']==='9999-12-31','Boundary calendar weeks must remain inside the MySQL DATE range.');
    sharing_check(!homework_matches_period(['published_date'=>'2026-01-01','due_date'=>null],['period'=>'day','date'=>'2026-01-01','date_by'=>'due']),'A due-date filter must exclude assignments without a due date.');
    $shared=share_url(['city'=>'Київ','school'=>1,'class'=>1,'subject'=>2,'q'=>'дроби & рівняння','period'=>'week','date'=>'2026-01-01','date_by'=>'due','page'=>9,'action'=>'delete','csrf'=>'secret','user_id'=>20]);
    parse_str((string)parse_url($shared, PHP_URL_QUERY),$sharedQuery);
    sharing_check($sharedQuery['date']==='2026-01-01' && $sharedQuery['date_by']==='due' && $sharedQuery['q']==='дроби & рівняння','Shared date interval and Unicode search must be stable and encoded.');
    sharing_check(!array_intersect(['page','action','csrf','user_id'],array_keys($sharedQuery)),'Shared URLs must omit pagination and private/action parameters.');
    sharing_check(str_contains(share_url(['school'=>1,'class'=>1],true),'r=demo') && str_contains(print_url(['school'=>1,'class'=>1],true),'demo=1'),'Demonstration URLs must stay explicitly in demo mode.');
    sharing_check(!collection_ready(['school'=>1]) && collection_ready(['school'=>1,'class'=>1]),'Print collections require both school and class.');

    db()->exec('CREATE TABLE schools(id INTEGER PRIMARY KEY,name TEXT,city TEXT,is_active INTEGER); CREATE TABLE users(id INTEGER PRIMARY KEY,school_id INTEGER,name TEXT,email TEXT,role TEXT,is_active INTEGER); CREATE TABLE classes(id INTEGER PRIMARY KEY,school_id INTEGER,name TEXT); CREATE TABLE subjects(id INTEGER PRIMARY KEY,school_id INTEGER,name TEXT,color TEXT); CREATE TABLE teaching_assignments(id INTEGER PRIMARY KEY,school_id INTEGER,teacher_id INTEGER,subject_id INTEGER,class_id INTEGER); CREATE TABLE homework(id INTEGER PRIMARY KEY,assignment_id INTEGER,title TEXT,body_html TEXT,published_date TEXT,due_date TEXT);');
    db()->exec("INSERT INTO schools VALUES(1,'Школа А','Київ',1),(2,'Школа Б','Львів',1),(3,'Закрита школа','Київ',0); INSERT INTO users VALUES(1,1,'Учитель А','private-a@example.invalid','teacher',1),(2,2,'Учитель Б','private-b@example.invalid','teacher',1),(3,1,'Неактивний','private-c@example.invalid','teacher',0),(4,3,'Закритий','private-d@example.invalid','teacher',1),(5,1,'Адмін','admin@example.invalid','admin',1); INSERT INTO classes VALUES(1,1,'7-А'),(2,2,'7-Б'),(3,3,'8-А'); INSERT INTO subjects VALUES(1,1,'Математика','#123456'),(2,2,'Історія','#654321'),(3,3,'Мова','#112233'); INSERT INTO teaching_assignments VALUES(1,1,1,1,1),(2,2,2,2,2),(3,1,3,1,1),(4,3,4,3,3),(5,1,5,1,1);");
    $today=new DateTimeImmutable(date('Y-m-d'));
    for($i=0;$i<14;$i++) {
        fixture('INSERT INTO homework VALUES(?,?,?,?,?,?)',[$i+1,1,'Завдання '.$i,$i===0?'<p>Текст <script>alert(1)</script><a href="javascript:alert(1)">Небезпечне</a></p>':'<p>Матеріал</p>',$today->modify('-'.intdiv($i,2).' days')->format('Y-m-d'),$today->modify(($i%3-1).' days')->format('Y-m-d')]);
    }
    foreach([[15,3,'Неактивний автор'],[16,2,'Інша школа'],[17,4,'Неактивна школа'],[18,5,'Не вчитель']] as [$id,$assignment,$title]) fixture('INSERT INTO homework VALUES(?,?,?,?,?,?)',[$id,$assignment,$title,'<p>Приховано</p>',$today->format('Y-m-d'),null]);
    fixture('INSERT INTO homework VALUES(?,?,?,?,?,?)',[19,1,'Майбутня публікація','<p>Ще не опубліковано</p>',$today->modify('+1 day')->format('Y-m-d'),$today->format('Y-m-d')]);
    $filters=['school'=>1,'class'=>1];
    $page=public_homework($filters);
    $collection=public_collection($filters);
    sharing_check(count($page['items'])===12 && $page['total']===14 && count($collection)===14,'PDF collection must include every match beyond the first twelve-item listing page.');
    sharing_check(count(public_collection([]))===15,'Public collections must hide inactive schools, inactive authors, nonteacher authors and future posts.');
    sharing_check(count(public_collection($filters+['period'=>'day','date'=>$today->format('Y-m-d')]))===2,'Publication-day filter must select exactly the two published today.');
    sharing_check(count(public_collection($filters+['period'=>'day','date'=>$today->format('Y-m-d'),'date_by'=>'due']))===5,'Due-day filter must use the due date while still hiding future publications.');
    sharing_check(public_collection(['school'=>1,'class'=>2])===[] && public_collection(['school'=>1,'class'=>1,'subject'=>2])===[],'Cross-school class or subject selection must not leak records.');
    sharing_check(public_collection($filters+['q'=>"' OR 1=1 --"])===[],'Search terms must remain query parameters.');
    sharing_check(!array_key_exists('email',$collection[0]) && !array_key_exists('google_sub',$collection[0]),'Public collection data must exclude private account fields.');
    sharing_check(collection_context(['school'=>1,'class'=>2])===null && collection_context(['school'=>1,'class'=>1,'subject'=>2])===null && collection_context(['school'=>3,'class'=>3])===null,'Print metadata must validate active school and related class/subject.');
    $_GET=['school'=>'1','class'=>'1'];
    ob_start(); print_view(); $print=ob_get_clean();
    sharing_check(substr_count($print,'class="print-homework"')===14 && str_contains($print,'Завдань: 14'),'Rendered print view must include the complete collection.');
    sharing_check(!str_contains($print,'<script>') && !str_contains($print,'javascript:') && !str_contains($print,'private-a@'),'Print output must sanitize rich text and omit private email addresses.');
    $_GET=[];
    ob_start(); print_view(); $missing=ob_get_clean();
    sharing_check(str_contains($missing,'Спершу оберіть школу та клас'),'Missing scope must show an actionable selection prompt.');
    $_GET=['school'=>'1','class'=>'2'];
    try { ob_start(); print_view(); ob_end_clean(); throw new RuntimeException('Invalid print scope was accepted.'); } catch(HttpError $error) { if(ob_get_level())ob_end_clean(); sharing_check($error->status===404,'Invalid school/class pairing must return not found.'); }
    echo "OK: $passed перевірок добірок, дат і друку; лише SQLite у пам’яті.\n";
} catch(Throwable $error) {
    while(ob_get_level())ob_end_clean();
    fwrite(STDERR,'FAIL: '.$error->getMessage()."\n");
    exit(1);
}

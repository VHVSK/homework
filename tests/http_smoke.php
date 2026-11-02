<?php
declare(strict_types=1);

/** Local HTTP smoke test. Leaves sample school data in the explicitly selected *_test DB. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Kyiv');

$base = rtrim((string) getenv('APP_TEST_BASE_URL'), '/');
$setupToken = (string) getenv('APP_TEST_SETUP_TOKEN');
$testName = (string) getenv('APP_TEST_DB_NAME');
$dbHost = getenv('APP_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('APP_DB_PORT') ?: '3306';
$dbUser = getenv('APP_DB_USER') ?: 'root';
$dbPassword = getenv('APP_DB_PASSWORD') ?: '';
$host = strtolower((string) parse_url($base, PHP_URL_HOST));
if (!in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
    || !in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)
    || parse_url($base, PHP_URL_USER) !== null || parse_url($base, PHP_URL_QUERY) !== null
    || parse_url($base, PHP_URL_FRAGMENT) !== null
    || !in_array(strtolower($dbHost), ['localhost', '127.0.0.1', '::1'], true)
    || !preg_match('/^[a-zA-Z0-9_]+_test$/D', $testName)
    || !ctype_digit((string) $dbPort) || strlen($setupToken) < 24) {
    fwrite(STDERR, "Відмова: вкажіть локальний APP_TEST_BASE_URL, APP_TEST_SETUP_TOKEN (24+ символів), явну APP_TEST_DB_NAME з суфіксом _test і локальний MySQL.\n");
    exit(1);
}
$runtime = __DIR__ . '/.runtime';
if (!is_dir($runtime)) mkdir($runtime, 0700, true);
$passed = 0;

function smoke_check(bool $condition, string $description): void {
    global $passed;
    if (!$condition) throw new RuntimeException($description);
    $passed++;
}
function smoke_sql(string $sql, array $params = []): PDOStatement {
    global $pdo;
    $statement = $pdo->prepare($sql); $statement->execute($params); return $statement;
}
function smoke_insert(string $sql, array $params = []): int {
    global $pdo;
    smoke_sql($sql, $params); return (int) $pdo->lastInsertId();
}

final class SmokeBrowser {
    public function __construct(private string $base, private string $jar) {}
    /** @return array{status:int,body:string,url:string} */
    public function request(string $method, string $path = 'index.php', array $data = []): array {
        $target = $this->base . '/' . ltrim($path, '/');
        $origin = parse_url($this->base);
        for ($redirects = 0; $redirects < 6; $redirects++) {
            $headers = [];
            $handle = curl_init($target);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
                CURLOPT_COOKIEFILE => $this->jar, CURLOPT_COOKIEJAR => $this->jar,
                CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                    if (str_contains($line, ':')) { [$key, $value] = explode(':', $line, 2); $headers[strtolower(trim($key))] = trim($value); }
                    return strlen($line);
                },
            ]);
            if ($method === 'POST') {
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            $body = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $error = curl_error($handle); unset($handle);
            if (!is_string($body)) throw new RuntimeException('HTTP request failed: ' . $error);
            if (!in_array($status, [301, 302, 303, 307, 308], true)) return compact('status', 'body') + ['url' => $target];
            $location = $headers['location'] ?? '';
            if (!str_starts_with($location, 'http://') && !str_starts_with($location, 'https://')) {
                $location = $this->base . '/' . ltrim($location, '/');
            }
            $destination = parse_url($location);
            foreach (['scheme', 'host', 'port'] as $part) {
                if (($destination[$part] ?? null) !== ($origin[$part] ?? null)) throw new RuntimeException('Redirect outside test origin refused.');
            }
            $target = $location;
            if (in_array($status, [301, 302, 303], true)) { $method = 'GET'; $data = []; }
        }
        throw new RuntimeException('Too many HTTP redirects.');
    }
    public function submit(string $action, array $data = [], string $source = 'dashboard'): array {
        $page = $this->request('GET', 'index.php?r=' . rawurlencode($source));
        if (!preg_match('/name="csrf" value="([a-f0-9]{64})"/', $page['body'], $match)) {
            throw new RuntimeException('CSRF form unavailable for ' . $action . ' (HTTP ' . $page['status'] . ').');
        }
        return $this->request('POST', 'index.php', ['action' => $action, 'csrf' => $match[1]] + $data);
    }
    public function login(string $email, string $password): void {
        $response = $this->submit('login', compact('email', 'password'), 'login');
        smoke_check($response['status'] === 200 && str_contains($response['url'], 'r=dashboard'), 'Login must open staff dashboard.');
    }
}

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;charset=utf8mb4", $dbUser, $dbPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$testName`"); $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    $tag = bin2hex(random_bytes(4));
    $stateFile = $runtime . '/http-smoke-' . $testName . '.json';
    $state = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true, 8, JSON_THROW_ON_ERROR) : null;
    $owner = new SmokeBrowser($base, $runtime . '/owner-' . $tag . '.cookies');
    $secretary = new SmokeBrowser($base, $runtime . '/secretary-' . $tag . '.cookies');
    $teacher = new SmokeBrowser($base, $runtime . '/teacher-' . $tag . '.cookies');
    $visitor = new SmokeBrowser($base, $runtime . '/visitor-' . $tag . '.cookies');

    if (!(int) smoke_sql("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) {
        $state = ['email' => 'owner@example.invalid', 'password' => 'Owner-' . bin2hex(random_bytes(18))];
        file_put_contents($stateFile, json_encode($state, JSON_THROW_ON_ERROR));
        $response = $owner->submit('install', ['setup_token' => $setupToken, 'name' => 'Олександр Адміністратор', 'email' => $state['email'], 'password' => $state['password']], 'install');
        smoke_check($response['status'] === 200 && str_contains($response['url'], 'r=dashboard'), 'Installer must create owner and open dashboard.');
        smoke_check((int) smoke_sql("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 1, 'Exactly one administrator must exist.');
    } else {
        if (!is_array($state) || !isset($state['email'], $state['password'])) throw new RuntimeException('Test DB already has an owner without matching local runtime credentials. Use a fresh dedicated test DB.');
        $owner->login($state['email'], $state['password']);
    }

    $secretaryEmail = 'secretary-' . $tag . '@example.invalid';
    $secretaryPassword = 'Secretary-' . bin2hex(random_bytes(16));
    $response = $owner->submit('create_school', ['name' => 'Ліцей «Світанок»', 'city' => 'Київ', 'address' => 'вул. Освітня, 12', 'secretary_name' => 'Ірина Бондаренко', 'secretary_email' => $secretaryEmail, 'password' => $secretaryPassword]);
    $secretaryRow = smoke_sql('SELECT * FROM users WHERE email=?', [$secretaryEmail])->fetch();
    smoke_check($response['status'] === 200 && $secretaryRow && $secretaryRow['role'] === 'secretary', 'Owner must create school and secretary.');
    $schoolId = (int) $secretaryRow['school_id'];
    $secretary->login($secretaryEmail, $secretaryPassword);
    $response = $secretary->submit('create_school');
    smoke_check($response['status'] === 403, 'Secretary cannot create schools or secretaries.');
    $response = $secretary->submit('save_subject', ['name' => 'Математика', 'color' => '#7866b6']);
    $subjectId = (int) smoke_sql('SELECT id FROM subjects WHERE school_id=? AND name=?', [$schoolId, 'Математика'])->fetchColumn();
    smoke_check($response['status'] === 200 && $subjectId > 0, 'Secretary must create subject.');
    $response = $secretary->submit('save_class', ['name' => '7-А', 'sort_order' => '7']);
    $classId = (int) smoke_sql('SELECT id FROM classes WHERE school_id=? AND name=?', [$schoolId, '7-А'])->fetchColumn();
    smoke_check($response['status'] === 200 && $classId > 0, 'Secretary must create class.');
    $teacherEmail = 'teacher.test.' . $tag . '@gmail.com';
    $teacherPassword = 'Teacher-' . bin2hex(random_bytes(16));
    $response = $secretary->submit('save_teacher', ['name' => 'Олена Коваленко', 'email' => $teacherEmail, 'password' => $teacherPassword, 'is_active' => '1']);
    $teacherId = (int) smoke_sql('SELECT id FROM users WHERE email=?', [$teacherEmail])->fetchColumn();
    smoke_check($response['status'] === 200 && $teacherId > 0, 'Secretary must add Gmail teacher.');
    $response = $secretary->submit('save_assignment', ['teacher_id' => $teacherId, 'subject_id' => $subjectId, 'class_id' => $classId]);
    $assignmentId = (int) smoke_sql('SELECT id FROM teaching_assignments WHERE school_id=? AND teacher_id=?', [$schoolId, $teacherId])->fetchColumn();
    smoke_check($response['status'] === 200 && $assignmentId > 0, 'Secretary must assign subject and class.');
    $response = $secretary->request('POST', 'index.php', ['action' => 'save_class', 'name' => 'Небезпечний запит']);
    smoke_check($response['status'] === 419, 'POST without CSRF must fail.');

    $teacher->login($teacherEmail, $teacherPassword);
    $editorPage = $teacher->request('GET', 'index.php?r=editor');
    smoke_check($editorPage['status'] === 200 && str_contains($editorPage['body'], 'quill@2.0.3')
        && str_contains($editorPage['body'], 'data-editor-form'), 'Teacher editor must load Quill assets and editor form.');
    $today = date('Y-m-d'); $due = date('Y-m-d', strtotime('+2 days'));
    $content = '<p onclick="alert(1)">Повторіть <strong>§ 12</strong> та розв’яжіть вправи № 324, 326, 328.</p><script>window.smokeXss=1</script><p><a href="https://drive.google.com/">Додаткові матеріали на Google Drive</a></p>';
    $homeworkFields = ['assignment_id' => $assignmentId, 'title' => 'Лінійні рівняння — перша редакція', 'body_html' => $content, 'published_date' => $today, 'due_date' => $due];
    $response = $teacher->submit('save_homework', $homeworkFields, 'editor');
    $homeworkId = (int) smoke_sql('SELECT id FROM homework WHERE assignment_id=? ORDER BY id DESC LIMIT 1', [$assignmentId])->fetchColumn();
    smoke_check($response['status'] === 200 && $homeworkId > 0, 'Teacher must publish homework.');
    $homeworkFields['id'] = $homeworkId; $homeworkFields['title'] = 'Лінійні рівняння з однією змінною';
    $response = $teacher->submit('save_homework', $homeworkFields, 'editor');
    $saved = smoke_sql('SELECT * FROM homework WHERE id=?', [$homeworkId])->fetch();
    smoke_check($response['status'] === 200 && $saved['title'] === $homeworkFields['title'], 'Teacher must edit own homework.');
    smoke_check(!str_contains($saved['body_html'], 'window.smokeXss') && !str_contains($saved['body_html'], 'onclick'), 'Stored HTML must remove script and event handler.');
    smoke_check(str_contains($saved['body_html'], 'noopener noreferrer') && str_contains($saved['body_html'], 'https://drive.google.com/'), 'Stored Drive link must be safe and preserved.');
    $response = $visitor->request('GET', 'index.php?r=homework&id=' . $homeworkId);
    smoke_check($response['status'] === 200 && str_contains($response['body'], $homeworkFields['title']) && !str_contains($response['body'], 'window.smokeXss'), 'Anonymous public detail must show sanitized homework.');
    $response = $visitor->request('GET', 'index.php?' . http_build_query(['r' => 'home', 'city' => 'Київ', 'school' => $schoolId, 'class' => $classId, 'subject' => $subjectId]));
    smoke_check($response['status'] === 200 && str_contains($response['body'], $homeworkFields['title']), 'Anonymous school/class/subject filters must find homework.');

    $otherSchool = smoke_insert('INSERT INTO schools(name,city) VALUES(?,?)', ['Інша тестова школа', 'Львів']);
    $otherTeacher = smoke_insert("INSERT INTO users(school_id,name,email,role) VALUES(?,?,?,'teacher')", [$otherSchool, 'Учитель іншої школи', 'other.' . $tag . '@gmail.com']);
    $otherClass = smoke_insert('INSERT INTO classes(school_id,name) VALUES(?,?)', [$otherSchool, '8-А']);
    $otherSubject = smoke_insert('INSERT INTO subjects(school_id,name) VALUES(?,?)', [$otherSchool, 'Історія']);
    $otherAssignment = smoke_insert('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$otherSchool, $otherTeacher, $otherSubject, $otherClass]);
    $otherHomework = smoke_insert('INSERT INTO homework(assignment_id,title,body_html,published_date) VALUES(?,?,?,?)', [$otherAssignment, 'Заплановане завдання іншої школи', '<p>Не змінювати</p>', date('Y-m-d', strtotime('+2 days'))]);
    $response = $teacher->submit('save_homework', array_replace($homeworkFields, ['id' => $otherHomework]), 'editor');
    smoke_check($response['status'] === 403, 'Teacher cannot edit another teacher homework.');
    $response = $teacher->submit('save_homework', array_replace($homeworkFields, ['id' => '', 'assignment_id' => $otherAssignment]), 'editor');
    smoke_check($response['status'] === 403, 'Teacher cannot publish to another school assignment.');
    $response = $teacher->submit('delete_homework', ['id' => $otherHomework]);
    smoke_check($response['status'] === 403 && smoke_sql('SELECT id FROM homework WHERE id=?', [$otherHomework])->fetchColumn(), 'Teacher cannot delete another teacher homework.');
    $response = $secretary->submit('save_teacher', ['id' => $otherTeacher, 'name' => 'Спроба зміни', 'email' => 'intruder.' . $tag . '@gmail.com']);
    smoke_check($response['status'] === 403, 'Secretary cannot edit teacher from another school.');
    $response = $visitor->request('GET', 'index.php?r=homework&id=' . $otherHomework);
    smoke_check($response['status'] === 404, 'Future homework must not be publicly readable.');
    $response = $teacher->submit('install', ['setup_token' => $setupToken, 'name' => 'Другий власник', 'email' => 'second.' . $tag . '@example.invalid', 'password' => 'Another-' . bin2hex(random_bytes(16))]);
    smoke_check($response['status'] === 403 && (int) smoke_sql("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 1, 'Second owner installation must be forbidden.');

    $scratch = array_replace($homeworkFields, ['id' => '', 'title' => 'Тимчасове завдання для перевірки видалення']);
    $teacher->submit('save_homework', $scratch, 'editor');
    $scratchId = (int) smoke_sql('SELECT id FROM homework WHERE assignment_id=? AND title=?', [$assignmentId, $scratch['title']])->fetchColumn();
    $response = $teacher->submit('delete_homework', ['id' => $scratchId]);
    smoke_check($response['status'] === 200 && !smoke_sql('SELECT id FROM homework WHERE id=?', [$scratchId])->fetchColumn(), 'Teacher must delete own homework.');

    // Additional public samples make browser QA useful; credentials stay in ignored local runtime only.
    $samples = [
        ['Українська мова', '#d69642', 'Наталія Шевченко', 'Дієприкметниковий зворот', '<p>Повторіть правило на <strong>сторінках 84–86</strong>. Виконайте вправу 182 письмово.</p><p>Складіть п’ять речень про улюблену пору року й підкресліть дієприкметникові звороти.</p>', 0],
        ['Англійська мова', '#4d91ad', 'Андрій Мельник', 'My favourite place in the city', '<p>Let’s talk about the places we love!</p><ul><li>Вивчіть слова з <strong>Unit 4, Lesson 2.</strong></li><li>Напишіть опис улюбленого місця: 8–10 речень.</li><li>Підготуйте усну розповідь.</li></ul>', 1],
    ];
    foreach ($samples as $number => [$subjectName, $color, $teacherName, $title, $html, $daysAgo]) {
        $sampleSubject = smoke_insert('INSERT INTO subjects(school_id,name,color) VALUES(?,?,?)', [$schoolId, $subjectName, $color]);
        $sampleTeacher = smoke_insert("INSERT INTO users(school_id,name,email,role) VALUES(?,?,?,'teacher')", [$schoolId, $teacherName, 'sample.' . $tag . '.' . $number . '@gmail.com']);
        $sampleAssignment = smoke_insert('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)', [$schoolId, $sampleTeacher, $sampleSubject, $classId]);
        smoke_insert('INSERT INTO homework(assignment_id,title,body_html,published_date,due_date) VALUES(?,?,?,?,?)', [$sampleAssignment, $title, $html, date('Y-m-d', strtotime('-' . $daysAgo . ' days')), $due]);
    }
    smoke_insert('INSERT INTO homework(assignment_id,title,body_html,published_date,due_date) VALUES(?,?,?,?,?)', [$assignmentId, 'Готуємося до самостійної роботи', '<p>Повторіть тему <strong>«Тотожні перетворення виразів»</strong>.</p><p>Виконайте завдання № 1–4 у розділі «Перевір себе». Запишіть запитання до наступного уроку.</p>', date('Y-m-d', strtotime('-2 days')), $due]);
    $state['school_id'] = $schoolId; $state['homework_id'] = $homeworkId;
    file_put_contents($stateFile, json_encode($state, JSON_THROW_ON_ERROR));
    echo "OK: $passed HTTP-перевірок; ролі, CRUD, CSRF, ізоляція шкіл та публічна видача. Приклади залишено в $testName.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n"); exit(1);
}

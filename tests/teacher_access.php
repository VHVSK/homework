<?php
declare(strict_types=1);

// Exercise the real save_teacher action against SQLite memory only, never config.php.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('pdo_sqlite')) { fwrite(STDERR,"Потрібне розширення pdo_sqlite.\n"); exit(1); }

final class TeacherTestRedirect extends RuntimeException {}
class HttpError extends RuntimeException {
    public function __construct(public int $status,string $message) { parent::__construct($message); }
}
$connection=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$connection->exec('CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,school_id INTEGER,name TEXT,email TEXT UNIQUE,password_hash TEXT,google_sub TEXT,role TEXT,is_active INTEGER DEFAULT 1)');
function db(): PDO { global $connection; return $connection; }
function query(string $sql,array $params=[]): PDOStatement { $statement=db()->prepare($sql);$statement->execute($params);return $statement; }
function require_role(string ...$roles): array {
    if (!in_array('secretary',$roles,true)) throw new HttpError(403,'Unexpected test role');
    return ['id'=>100,'school_id'=>11,'role'=>'secretary'];
}
function verify_csrf(): void {}
function flash(string $type,string $message): void { $_SESSION['flashes'][]=[$type,$message]; }
function route_url(string $route='home',array $params=[]): string { return '/index.php?'.http_build_query(['r'=>$route]+$params); }
function redirect(string $url): never { throw new TeacherTestRedirect($url); }
require __DIR__.'/../app/security.php';
require __DIR__.'/../app/repository.php';
require __DIR__.'/../app/actions.php';
$checks=0;
function teacher_check(bool $condition,string $message): void { global $checks;if(!$condition)throw new RuntimeException($message);$checks++; }
function save_test_teacher(array $fields): array {
    $_SESSION=[];
    $_POST=$fields+['action'=>'save_teacher','name'=>'Учитель тесту','is_active'=>'1'];
    try { handle_post(); } catch(TeacherTestRedirect $redirect) { return ['url'=>$redirect->getMessage(),'flashes'=>$_SESSION['flashes']??[]]; }
}

try {
    $rejected=save_test_teacher(['email'=>'teacher@school.example','password'=>'']);
    teacher_check((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'A new non-Gmail account without a password must not be inserted.');
    teacher_check(($rejected['flashes'][0][0]??'')==='error' && str_contains($rejected['flashes'][0][1],'пароль'),'Missing-password rejection must explain how to obtain access.');

    $password='SchoolPassword-2026!';
    $created=save_test_teacher(['email'=>'teacher@school.example','password'=>$password]);
    $teacher=query('SELECT * FROM users WHERE email=?',['teacher@school.example'])->fetch();
    teacher_check(($created['flashes'][0][0]??'')==='success' && $teacher!==false,'A valid non-Gmail teacher with a password must be accepted.');
    teacher_check($teacher['role']==='teacher' && (int)$teacher['school_id']===11 && password_verify($password,$teacher['password_hash']),'The action must preserve the role/school boundary and store a usable password hash.');
    teacher_check($teacher['password_hash']!==$password && $teacher['google_sub']===null,'A password account must not store a plaintext password or acquire a Google identity.');

    $originalHash=$teacher['password_hash'];
    save_test_teacher(['id'=>(string)$teacher['id'],'name'=>'Оновлене ім’я','email'=>$teacher['email'],'password'=>'']);
    $edited=query('SELECT * FROM users WHERE id=?',[$teacher['id']])->fetch();
    teacher_check($edited['password_hash']===$originalHash && $edited['name']==='Оновлене ім’я','Editing an existing non-Gmail teacher must preserve its working password when none is supplied.');

    query("INSERT INTO users(school_id,name,email,google_sub,role) VALUES(11,'Google учитель','invited@gmail.com','stable-google-sub','teacher')");
    $googleId=(int)db()->lastInsertId();
    $changed=save_test_teacher(['id'=>(string)$googleId,'email'=>'new@school.example','password'=>'']);
    $googleTeacher=query('SELECT * FROM users WHERE id=?',[$googleId])->fetch();
    teacher_check(($changed['flashes'][0][0]??'')==='error' && $googleTeacher['email']==='invited@gmail.com' && $googleTeacher['google_sub']==='stable-google-sub','Changing a Google-only teacher to non-Gmail without a password must reject before modifying the email or binding.');

    save_test_teacher(['id'=>(string)$googleId,'email'=>'new@school.example','password'=>$password]);
    $converted=query('SELECT * FROM users WHERE id=?',[$googleId])->fetch();
    teacher_check($converted['email']==='new@school.example' && password_verify($password,$converted['password_hash']) && $converted['google_sub']===null,'Supplying a password when changing email must enable password access and invalidate the previous Google binding.');

    $gmail=save_test_teacher(['email'=>'another.teacher@gmail.com','password'=>'']);
    $gmailTeacher=query('SELECT * FROM users WHERE email=?',['another.teacher@gmail.com'])->fetch();
    teacher_check(($gmail['flashes'][0][0]??'')==='success' && $gmailTeacher['password_hash']===null,'Gmail teachers may retain the Google-only invitation flow.');

    query("INSERT INTO users(school_id,name,email,role,password_hash) VALUES(22,'Чужий учитель','other@school.example','teacher',?)",[$originalHash]);
    $otherId=(int)db()->lastInsertId();
    $schoolRejected=false;
    try { save_test_teacher(['id'=>(string)$otherId,'email'=>'stolen@school.example','password'=>$password]); } catch(HttpError $error) { $schoolRejected=$error->status===403; }
    teacher_check($schoolRejected && query('SELECT email FROM users WHERE id=?',[$otherId])->fetchColumn()==='other@school.example','The extended email flow must not permit editing a teacher from another school.');

    echo "OK: {$checks} перевірок доступу вчителів через Gmail або іншу пошту з паролем.\n";
} catch(Throwable $error) { fwrite(STDERR,'FAIL: '.$error->getMessage()."\n");exit(1); }

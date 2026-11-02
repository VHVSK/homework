<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('pdo_sqlite') || !extension_loaded('pdo_mysql')) {
    fwrite(STDERR,"Для перевірки потрібні pdo_sqlite та pdo_mysql.\n"); exit(1);
}

final class InstallationTestConnection extends PDO {
    public ?PDOException $probeFailure=null;
    public function __construct() { parent::__construct('sqlite::memory:'); $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); }
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false {
        if ($this->probeFailure && str_contains($query,'FROM schools')) throw $this->probeFailure;
        return parent::query($query);
    }
}
$testSettings=['setup_token'=>str_repeat('a',64)];
$testConnection=new InstallationTestConnection();
$connectFailure=null;
function config(string $key,mixed $default=null): mixed { global $testSettings; return $testSettings[$key]??$default; }
function db(): PDO { global $testConnection,$connectFailure; if($connectFailure)throw $connectFailure; return $testConnection; }
require __DIR__.'/../app/installation.php';
$passed=0;
function installation_check(bool $condition,string $description): void {
    global $passed; if(!$condition)throw new RuntimeException($description); $passed++;
}
function test_database_error(string $state,int $driverCode): PDOException {
    $error=new PDOException('Simulated private database message; never display this.');
    $error->errorInfo=[$state,$driverCode,'Private connection details']; return $error;
}
try {
    $schema=[
        'schools'=>'id INTEGER,name TEXT,city TEXT,address TEXT,is_active INTEGER,created_at TEXT',
        'users'=>'id INTEGER,school_id INTEGER,name TEXT,email TEXT,password_hash TEXT,google_sub TEXT,role TEXT,is_active INTEGER,admin_guard INTEGER,created_at TEXT',
        'classes'=>'id INTEGER,school_id INTEGER,name TEXT,sort_order INTEGER,created_at TEXT',
        'subjects'=>'id INTEGER,school_id INTEGER,name TEXT,color TEXT,created_at TEXT',
        'teaching_assignments'=>'id INTEGER,school_id INTEGER,teacher_id INTEGER,subject_id INTEGER,class_id INTEGER,created_at TEXT',
        'homework'=>'id INTEGER,assignment_id INTEGER,title TEXT,body_html TEXT,published_date TEXT,due_date TEXT,created_at TEXT,updated_at TEXT',
        'login_attempts'=>'id INTEGER,identifier_hash TEXT,attempted_at TEXT',
    ];
    foreach($schema as $table=>$columns)$testConnection->exec("CREATE TABLE $table ($columns)");
    $status=installation_status();
    installation_check($status['database']&&$status['schema']&&$status['token']&&!$status['installed'],'Complete empty schema must allow the installer.');
    $testSettings['setup_token']=''; $status=installation_status();
    installation_check($status['schema']&&!$status['token'],'Missing key must be separate from schema readiness.');
    $testSettings['setup_token']=' '.str_repeat('a',30); installation_check(!setup_token_ready(),'Whitespace-padded key cannot match trimmed form input.');
    $testSettings['setup_token']=str_repeat('a',201); installation_check(!setup_token_ready(),'Key must fit the installer form.');
    $testSettings['setup_token']=str_repeat('a',64);
    foreach([[1045,'credentials'],[1044,'credentials'],[1049,'database_missing'],[2002,'connection']] as [$code,$expected]) {
        $connectFailure=test_database_error('HY000',$code); $status=installation_status();
        installation_check(!$status['database']&&!$status['schema']&&$status['issue']===$expected,'Connection failure must have a safe actionable category.');
        installation_check(!str_contains(json_encode($status),'Private')&&!str_contains(json_encode($status),'Simulated'),'Diagnostic state must exclude raw error details.');
    }
    $connectFailure=null;
    foreach([['42S02',1146,'tables_missing'],['42S22',1054,'schema_incompatible'],['42000',1142,'schema_unreadable']] as [$state,$code,$expected]) {
        $testConnection->probeFailure=test_database_error($state,$code); $status=installation_status();
        installation_check($status['database']&&!$status['schema']&&$status['issue']===$expected,'Missing tables, missing fields and denied reads must be distinguished.');
    }
    $testConnection->probeFailure=null;
    $testConnection->exec('DROP TABLE login_attempts'); $status=installation_status();
    installation_check($status['database']&&!$status['schema'],'Partial schema must not show the admin form.');
    $testConnection->exec('CREATE TABLE login_attempts ('.$schema['login_attempts'].')');
    $testConnection->exec("INSERT INTO users(id,role) VALUES(1,'admin')");
    $testSettings['setup_token']=''; $status=installation_status();
    installation_check($status['installed']&&$status['schema']&&!$status['token'],'Existing administrator must be detected after the setup key is cleared.');
    echo "OK: $passed перевірок встановлення.\n";
} catch(Throwable $error) { fwrite(STDERR,'FAIL: '.$error->getMessage()."\n"); exit(1); }

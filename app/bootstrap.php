<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
$settings = is_file(APP_ROOT . '/config/config.php')
    ? require APP_ROOT . '/config/config.php'
    : require APP_ROOT . '/config/config.example.php';
foreach (['app_name','base_url','timezone','db_host','db_port','db_name','db_user','db_password','setup_token','google_client_id','google_client_secret'] as $key) {
    $env = getenv(str_starts_with($key, 'google_') ? strtoupper($key) : 'APP_' . strtoupper($key));
    if ($env !== false) $settings[$key] = $env;
}
function config(string $key, mixed $default = null): mixed { global $settings; return $settings[$key] ?? $default; }
date_default_timezone_set((string) config('timezone', 'Europe/Kyiv'));
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Cache-Control: no-store');
    ini_set('session.use_strict_mode', '1');
    session_name('afterbell_session');
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>str_starts_with((string)config('base_url'), 'https://'), 'httponly'=>true, 'samesite'=>'Lax']);
    session_start();
}
function e(mixed $text): string { return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = 'index.php', array $query = []): string {
    return rtrim((string)config('base_url'), '/') . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
}
function asset_url(string $file): string {
    // SCRIPT_NAME is a URL path. Filesystem directories and Host headers must not enter asset URLs.
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $directory = trim(str_replace('\\', '/', dirname($scriptName)), '/.');
    $assetPath = defined('APP_ENTRY_ASSET_PATH') ? APP_ENTRY_ASSET_PATH : 'assets/';
    return '/' . ($directory === '' ? '' : $directory . '/') . $assetPath . ltrim($file, '/');
}
function route_url(string $route = 'home', array $query = []): string { return url('index.php', ['r'=>$route] + $query); }
function redirect(string $url): never { header('Location: ' . $url, true, 303); exit; }
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host='.config('db_host').';port='.config('db_port').';dbname='.config('db_name').';charset=utf8mb4', (string)config('db_user'), (string)config('db_password'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement { $st=db()->prepare($sql); $st->execute($params); return $st; }
function user(): ?array {
    static $loaded=false, $current=null;
    if (!$loaded) {
        $loaded=true;
        if (!empty($_SESSION['user_id'])) {
            $current=query('SELECT u.*, s.name AS school_name FROM users u LEFT JOIN schools s ON s.id=u.school_id WHERE u.id=? AND u.is_active=1 AND (u.role=\'admin\' OR s.is_active=1)', [(int)$_SESSION['user_id']])->fetch() ?: null;
            if (!$current) unset($_SESSION['user_id']);
        }
    }
    return $current;
}
function login_user(array $account): void { session_regenerate_id(true); $_SESSION['user_id']=(int)$account['id']; $_SESSION['csrf']=bin2hex(random_bytes(32)); }
function require_role(string ...$roles): array {
    $account=user();
    if (!$account) { flash('info', 'Увійдіть, щоб відкрити кабінет.'); redirect(route_url('login')); }
    if (!in_array($account['role'], $roles, true)) throw new HttpError(403, 'У вас немає доступу до цієї дії.');
    return $account;
}
function flash(string $type, string $message): void { $_SESSION['flashes'][]=[$type,$message]; }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void {
    if (!is_string($_POST['csrf']??null) || !hash_equals(csrf_token(), $_POST['csrf'])) throw new HttpError(419, 'Сеанс форми завершився. Оновіть сторінку та спробуйте ще раз.');
}
class HttpError extends RuntimeException { public function __construct(public int $status, string $message) { parent::__construct($message); } }
require_once __DIR__ . '/installation.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/google_auth.php';

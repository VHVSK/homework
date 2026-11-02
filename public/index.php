<?php
declare(strict_types=1);
if (!defined('APP_ENTRY_ASSET_PATH')) {
    define('APP_ENTRY_ASSET_PATH', 'assets/');
}
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/actions.php';
require __DIR__ . '/../app/views.php';
require __DIR__ . '/../app/dashboard_views.php';
require __DIR__ . '/../app/legal_views.php';
require __DIR__ . '/../app/sharing.php';
require __DIR__ . '/../app/sharing_views.php';
require __DIR__ . '/../app/pwa.php';
require __DIR__ . '/../app/public_controls.php';

try {
    if ($_SERVER['REQUEST_METHOD']==='POST') handle_post();
    if ($_SERVER['REQUEST_METHOD']!=='GET') throw new HttpError(405,'Метод запиту не підтримується.');
    $route=is_string($_GET['r']??null)?$_GET['r']:'home';
    switch($route) {
        case 'home':
            try { db()->query('SELECT id FROM schools LIMIT 1'); $demo=false; } catch(PDOException) { $demo=true; }
            home_view($demo); break;
        case 'demo': home_view(true); break;
        case 'homework':
            $item=homework_row(repo_id($_GET['id']??0));
            if (!$item || !(int)$item['teacher_active'] || $item['published_date']>date('Y-m-d') || !query('SELECT id FROM schools WHERE id=? AND is_active=1',[$item['school_id']])->fetchColumn()) throw new HttpError(404,'Це завдання недоступне.');
            homework_view($item); break;
        case 'login': login_view(); break;
        case 'google-start': google_start();
        case 'google-callback': google_callback();
        case 'install': install_view(); break;
        case 'help': help_view(); break;
        case 'owner': owner_view(); break;
        case 'privacy': privacy_view(); break;
        case 'terms': terms_view(); break;
        case 'print': print_view(); break;
        case 'manifest': pwa_manifest();
        case 'dashboard': dashboard_view(require_role('admin','secretary','teacher')); break;
        case 'editor': editor_view(require_role('teacher')); break;
        case 'profile': profile_view(require_role('admin','secretary','teacher')); break;
        default: throw new HttpError(404,'Сторінку не знайдено.');
    }
} catch(HttpError $error) {
    if(db_in_transaction())db()->rollBack();
    error_view($error->status,$error->getMessage());
} catch(Throwable $error) {
    if(db_in_transaction())db()->rollBack();
    error_log('Application failure: '.get_class($error).': '.$error->getMessage());
    // The public error contains no database credentials, SQL or filesystem paths.
    unset($_SESSION['user_id']);
    error_view(503,'Сервіс тимчасово недоступний. Перевірте конфігурацію та підключення до бази.');
}
function db_in_transaction(): bool { try { return db()->inTransaction(); } catch(Throwable) { return false; } }

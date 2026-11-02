<?php
declare(strict_types=1);

/** Paths belong to the requested URL directory, never to a filesystem path or Host header. */
function pwa_metadata(): array {
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??'/index.php'));
    if (!str_starts_with($script,'/') || str_starts_with($script,'//') || preg_match('~[\x00-\x20?#:]|(?:^|/)\.\.(?:/|$)~',$script)) {
        $script='/index.php';
    }
    $directory=trim(str_replace('\\','/',dirname($script)),'/.');
    $base=$directory===''?'/':'/'.$directory.'/';
    return [
        'base_path'=>$base,
        'manifest_url'=>$base.'index.php?r=manifest',
        'worker_url'=>$base.'sw.js',
        'start_url'=>$base.'index.php',
        'scope'=>$base,
        'icon192'=>asset_url('app-icon-192.png'),
        'icon512'=>asset_url('app-icon-512.png'),
    ];
}

function pwa_manifest_document(): array {
    $metadata=pwa_metadata();
    return [
        'id'=>$metadata['start_url'],
        'name'=>(string)config('app_name','Після дзвоника'),
        'short_name'=>'Після дзвоника',
        'description'=>'Домашні завдання вашої школи без учнівської реєстрації. Для роботи потрібен інтернет.',
        'lang'=>'uk',
        'dir'=>'ltr',
        'start_url'=>$metadata['start_url'],
        'scope'=>$metadata['scope'],
        'display'=>'standalone',
        'background_color'=>'#f5f8f6',
        'theme_color'=>'#163d31',
        'categories'=>['education'],
        'prefer_related_applications'=>false,
        'icons'=>[
            ['src'=>$metadata['icon192'],'sizes'=>'192x192','type'=>'image/png','purpose'=>'any maskable'],
            ['src'=>$metadata['icon512'],'sizes'=>'512x512','type'=>'image/png','purpose'=>'any maskable'],
        ],
    ];
}

function pwa_manifest(): never {
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(pwa_manifest_document(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    exit;
}

function pwa_head(): void {
    $metadata=pwa_metadata();
    ?><link rel="manifest" href="<?=e($metadata['manifest_url'])?>"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Після дзвоника"><meta name="apple-mobile-web-app-status-bar-style" content="default"><link rel="apple-touch-icon" href="<?=e($metadata['icon192'])?>"><link rel="stylesheet" href="<?=e(asset_url('pwa.css'))?>"><script defer src="<?=e(asset_url('pwa.js'))?>" data-pwa-script data-worker-url="<?=e($metadata['worker_url'])?>" data-worker-scope="<?=e($metadata['scope'])?>"></script><?php
}

function pwa_footer(): void {
    ?><a class="pwa-install-button" href="<?=e(route_url('help'))?>#install" data-pwa-install aria-haspopup="dialog" aria-controls="install-dialog"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 6v8m-3-3 3 3 3-3M11 18h2"/></svg>Встановити на телефон</a><dialog id="install-dialog" class="pwa-dialog" aria-labelledby="install-dialog-title"><div class="pwa-dialog-heading"><h2 id="install-dialog-title">«Після дзвоника» на телефоні</h2><button type="button" class="pwa-dialog-close" data-pwa-close aria-label="Закрити інструкцію встановлення">×</button></div><p>Додайте сайт на головний екран, щоб відкривати завдання одним дотиком. Для перегляду актуальних завдань потрібен інтернет.</p><p class="pwa-secure-note" data-pwa-insecure hidden>Встановлення потребує HTTPS. На локальному HTTP-домені браузер може не запропонувати цю можливість.</p><h3>Android · Chrome або Edge</h3><ol><li>Відкрийте меню браузера <strong>⋮</strong>.</li><li>Оберіть <strong>«Встановити додаток»</strong> або <strong>«Додати на головний екран»</strong>.</li><li>Підтвердьте додавання.</li></ol><h3>iPhone · Safari</h3><ol><li>Відкрийте сайт у Safari та натисніть <strong>«Поділитися»</strong>.</li><li>Оберіть <strong>«На початковий екран»</strong> та натисніть <strong>«Додати»</strong>.</li></ol><p class="pwa-dialog-note">Назви пунктів залежать від браузера та мови телефона. Якщо пункту немає, відкрийте сайт у звичайному браузері, а не у вікні месенджера. На комп’ютері перевірте меню встановлення в Chrome або Edge.</p><button type="button" class="btn btn-primary" data-pwa-close>Зрозуміло</button></dialog><?php
}

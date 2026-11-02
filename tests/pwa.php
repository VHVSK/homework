<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

$assetPrefix='/assets/';
function config(string $key,mixed $default=null): mixed { return $key==='app_name'?'Після дзвоника':$default; }
function e(mixed $text): string { return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function asset_url(string $file): string { global $assetPrefix; return $assetPrefix.$file; }
function route_url(string $route='home',array $query=[]): string { return '/index.php?'.http_build_query(['r'=>$route]+$query); }
require __DIR__.'/../app/pwa.php';
$passed=0;
function pwa_check(bool $condition,string $description): void {
    global $passed;
    if (!$condition) throw new RuntimeException($description);
    $passed++;
}
try {
    foreach ([
        ['/index.php','/','/public/assets/'],
        ['/index.php','/','/assets/'],
        ['/HW/index.php','/HW/','/HW/public/assets/'],
        ['/HW/public/index.php','/HW/public/','/HW/public/assets/'],
    ] as [$script,$base,$prefix]) {
        $_SERVER['SCRIPT_NAME']=$script;
        $_SERVER['HTTP_HOST']='attacker.invalid';
        $assetPrefix=$prefix;
        $metadata=pwa_metadata();
        $manifest=pwa_manifest_document();
        pwa_check($metadata['scope']===$base && $metadata['worker_url']===$base.'sw.js','Worker scope must match the URL directory.');
        pwa_check($manifest['start_url']===$base.'index.php' && $manifest['id']===$base.'index.php','Start URL and app identity must support subdirectories.');
        pwa_check($manifest['icons'][0]['src']===$prefix.'app-icon-192.png','Icons must support both document roots.');
        pwa_check($manifest['display']==='standalone' && $manifest['lang']==='uk','Manifest must describe a Ukrainian installable app.');
        pwa_check(!str_contains(json_encode($manifest),'attacker.invalid'),'Host headers must not influence the manifest.');
        ob_start(); pwa_head(); $head=ob_get_clean();
        pwa_check(str_contains($head,'rel="manifest"') && str_contains($head,e($metadata['manifest_url'])),'Head must expose its own manifest URL.');
        pwa_check(str_contains($head,'data-worker-url="'.e($base.'sw.js').'"') && !str_contains($head,'<script>'),'Worker configuration must not require inline script.');
    }
    foreach (['C:\\private\\index.php','//attacker.invalid/index.php','/HW/../index.php','/HW/index.php?bad=1'] as $invalid) {
        $_SERVER['SCRIPT_NAME']=$invalid;
        pwa_check(pwa_metadata()['base_path']==='/','Unusable script paths must fall back to the root.');
    }
    ob_start(); pwa_footer(); $footer=ob_get_clean();
    pwa_check(str_contains($footer,'data-pwa-install') && str_contains($footer,'#install'),'Without JavaScript, installation must link to help.');
    pwa_check(str_contains($footer,'aria-labelledby="install-dialog-title"') && str_contains($footer,'data-pwa-close'),'The instruction dialog must be labelled and closable.');
    foreach ([192,512] as $size) {
        $dimensions=getimagesize(__DIR__.'/../public/assets/app-icon-'.$size.'.png');
        pwa_check($dimensions!==false && $dimensions[0]===$size && $dimensions[1]===$size && $dimensions['mime']==='image/png','Manifest icons must have their declared dimensions.');
    }
    pwa_check(hash_file('sha256',__DIR__.'/../sw.js')===hash_file('sha256',__DIR__.'/../public/sw.js'),'Both deployment roots must have the same worker.');
    echo 'OK: '.$passed.' перевірок PWA. База й приватна конфігурація не використовуються.'.PHP_EOL;
} catch (Throwable $error) { fwrite(STDERR,$error->getMessage().PHP_EOL); exit(1); }

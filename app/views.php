<?php
declare(strict_types=1);

function icon(string $name, int $size=20): string {
    $paths=[
        'home'=>'<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'book'=>'<path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1zM12 5v15"/>',
        'school'=>'<path d="m3 9 9-6 9 6M5 8v13h14V8M9 21v-7h6v7M10 9h4"/>',
        'users'=>'<circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M16 3a4 4 0 0 1 0 8M22 21v-3a7 7 0 0 0-4-6"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'edit'=>'<path d="m16 3 5 5-12 12-6 1 1-6zM14 5l5 5"/>',
        'trash'=>'<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
        'settings'=>'<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 3 2 2-1 4 3 2 3-1 2 4 3-2 1-3 4-1 1-3-3-2V6l-3-1-2-3z"/>',
        'logout'=>'<path d="M9 21H4V3h5M9 12h12m-5-5 5 5-5 5"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
        'filter'=>'<path d="M4 7h16M7 12h10M10 17h4"/>',
        'chevron'=>'<path d="m9 5 7 7-7 7"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'link'=>'<path d="m10 13 4-4M8 16l-1 1a4 4 0 0 1-6-6l4-4a4 4 0 0 1 6 0M16 8l1-1a4 4 0 0 1 6 6l-4 4a4 4 0 0 1-6 0" transform="translate(0 -1)"/>',
        'google'=>'<path d="M20 12h-8m8 0a8 8 0 1 1-2.3-5.7M20 12v5"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'share'=>'<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m9 10 6-4M9 14l6 4"/>',
        'print'=>'<path d="M6 9V3h12v6M6 18H3V9h18v9h-3"/><path d="M6 14h12v7H6zM17 11h1"/>',
        'help'=>'<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 0 1 6 0c0 2-3 2-3 4M12 17h.01"/>',
    ];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['book']).'</svg>';
}
function old(string $key,mixed $default=''): mixed { $value=$_SESSION['old'][$key]??$default; return is_scalar($value)?$value:$default; }
function hidden(string $name,mixed $value): void { echo '<input type="hidden" name="'.e($name).'" value="'.e($value).'">'; }
function form_start(string $action,string $extraClass=''): void { echo '<form method="post" action="'.e(url()).'" class="'.e($extraClass).'">'.csrf_field(); hidden('action',$action); }
function form_end(): void { echo '</form>'; }
function field(string $name,string $label,mixed $value='',string $type='text',bool $required=true,string $extra=''): void { echo '<div class="field"><label for="'.e($name).'">'.e($label).'</label><input id="'.e($name).'" name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" '.($required?'required ':'').$extra.'></div>'; }
function select_field(string $name,string $label,array $options,mixed $selected=0,string $placeholder='Оберіть…'): void {
    echo '<div class="field"><label for="'.e($name).'">'.e($label).'</label><select id="'.e($name).'" name="'.e($name).'" required><option value="">'.e($placeholder).'</option>';
    foreach($options as $value=>$text) echo '<option value="'.e($value).'"'.((string)$value===(string)$selected?' selected':'').'>'.e($text).'</option>';
    echo '</select></div>';
}
function layout_start(string $title,string $active='home',bool $guest=false,?string $documentTitle=null): void {
    $account=$guest?null:user(); $name=(string)config('app_name');
    $roleNames=['admin'=>'Адміністратор','secretary'=>'Уповноважена особа школи','teacher'=>'Учитель'];
    ?><!doctype html><html lang="uk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Домашні завдання вашої школи. Знайдіть місто, школу, клас і предмет — без реєстрації."><meta name="theme-color" content="#163d31"><title><?=e($documentTitle??($title.' · '.$name))?></title><link rel="icon" type="image/svg+xml" href="<?=e(asset_url('favicon.svg'))?>"><link rel="stylesheet" href="<?=e(asset_url('site.css'))?>"><?php public_tools_head(); if($active==='editor'):?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css"><script defer src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script><?php endif?><script defer src="<?=e(asset_url('site.js'))?>"></script></head><body><a class="skip-link" href="#main-content">Перейти до вмісту</a><aside class="sidebar" id="sidebar"><a class="brand" href="<?=e(route_url())?>"><span class="brand-mark"><?=icon('book')?></span><span>Після дзвоника<small>Домашні завдання під рукою</small></span></a><div class="nav-label">Навчання</div><nav aria-label="Головна навігація"><a class="nav-link <?=$active==='home'?'active':''?>" href="<?=e(route_url())?>"><?=icon('book')?>Домашні завдання</a><?php if($account):?><a class="nav-link <?=$active==='dashboard'||$active==='editor'?'active':''?>" href="<?=e(route_url('dashboard'))?>"><?=icon('home')?>Мій кабінет</a><a class="nav-link <?=$active==='profile'?'active':''?>" href="<?=e(route_url('profile'))?>"><?=icon('settings')?>Мій профіль</a><?php else:?><a class="nav-link <?=$active==='login'?'active':''?>" href="<?=e(route_url('login'))?>"><?=icon('users')?>Для вчителів</a><?php endif?><hr class="sidebar-separator"><div class="nav-label">Корисне</div><a class="nav-link <?=$active==='help'?'active':''?>" href="<?=e(route_url('help'))?>"><?=icon('help')?>Як це працює</a><a class="nav-link" href="<?=e(route_url('help',['section'=>'schools']))?>#schools"><?=icon('school')?>Для школи</a></nav><div class="sidebar-bottom"><div class="student-note"><?=icon('check',22)?><strong>Навчання починається з ясності</strong><p>Усі завдання та матеріали — під рукою. Навіть коли дзвоник уже пролунав.</p></div><div class="sidebar-footer">З турботою про навчання<span>© <?=date('Y')?> Після дзвоника</span></div></div></aside><button class="sidebar-backdrop" data-sidebar-close aria-label="Закрити меню"></button><div class="app-main"><header class="topbar"><div class="topbar-title"><button class="mobile-toggle" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Відкрити меню"><?=icon('menu')?></button><span class="breadcrumb-root">Навчальний простір</span><span class="divider">/</span><strong><?=e($title)?></strong></div><div class="topbar-actions"><?php if($account):?><a class="user-menu" href="<?=e(route_url('profile'))?>"><span class="user-avatar"><?=e(initials($account['name']))?></span><span><?=e($account['name'])?><small><?=e($roleNames[$account['role']]??'')?></small></span></a><?php form_start('logout','inline-form');?><button class="btn btn-ghost btn-sm" title="Вийти" aria-label="Вийти"><?=icon('logout')?></button><?php form_end(); else:?><span class="topbar-note">Учням реєстрація не потрібна</span><a class="btn btn-secondary" href="<?=e(route_url('login'))?>"><?=icon('users')?>Увійти в кабінет</a><?php endif?></div></header><main class="container" id="main-content"><?php
    foreach($_SESSION['flashes']??[] as [$type,$message]) echo '<div role="status" class="notice notice-'.e($type).'">'.e($message).'</div>';
    unset($_SESSION['flashes']);
}
function layout_end(): void { ?><footer class="page-footer"><span>Після дзвоника · Більше часу на головне</span><nav class="footer-links" aria-label="Інформація про сайт"><a href="<?=e(route_url('owner'))?>">Про власника</a><a href="<?=e(route_url('privacy'))?>">Конфіденційність</a><a href="<?=e(route_url('terms'))?>">Умови користування</a><a href="<?=e(route_url('help'))?>">Допомога <?=icon('arrow',13)?></a><?php pwa_footer(); ?></nav></footer></main></div></body></html><?php unset($_SESSION['old']); }
function page_heading(string $title,string $subtitle='',string $eyebrow='Навчальний простір'): void { ?><div class="page-heading"><div class="eyebrow"><?=e($eyebrow)?></div><div class="heading-row"><div class="heading-copy"><h1><?=e($title)?></h1><?php if($subtitle):?><p><?=e($subtitle)?></p><?php endif?></div><div class="page-date"><?=icon('calendar',15)?><?=e(pretty_date(date('Y-m-d')))?></div></div></div><?php }

function demo_data(array $filters): array {
    $filters=public_filters($filters);
    $base=['school_id'=>1,'school_name'=>'Ліцей «Світанок»','city'=>'Київ','class_id'=>1,'class_name'=>'7-А','teacher_id'=>1,'teacher_active'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
    $items=[
        ['id'=>1,'assignment_id'=>1,'subject_id'=>1,'subject_name'=>'Математика','subject_color'=>'#7866b6','title'=>'Лінійні рівняння з однією змінною','body_html'=>'<p>Закріплюємо те, що вивчили на уроці. У вас усе вийде!</p><ol><li>Опрацювати <strong>§ 12</strong> у підручнику.</li><li>Розв’язати вправи <strong>№ 324, 326, 328.</strong></li><li>Виписати основні властивості рівнянь у зошит.</li></ol>','teacher_name'=>'Олена Коваленко','published_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d',strtotime('+2 days'))],
        ['id'=>2,'assignment_id'=>2,'subject_id'=>2,'subject_name'=>'Українська мова','subject_color'=>'#d69642','title'=>'Дієприкметниковий зворот','body_html'=>'<p>Повторити правило на <strong>сторінках 84–86</strong> та виконати вправу 182 письмово.</p><p>Складіть 5 речень із дієприкметниковими зворотами про свою улюблену пору року. Підкресліть звороти.</p>','teacher_name'=>'Наталія Шевченко','published_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d',strtotime('+1 day'))],
        ['id'=>3,'assignment_id'=>3,'subject_id'=>3,'subject_name'=>'Англійська мова','subject_color'=>'#4d91ad','title'=>'My favourite place in the city','body_html'=>'<p>Let’s talk about the places we love!</p><ul><li>Вивчити нові слова з <strong>Unit 4, Lesson 2.</strong></li><li>Написати короткий опис улюбленого місця (8–10 речень).</li><li>Підготуватися розповісти про нього на уроці.</li></ul>','teacher_name'=>'Андрій Мельник','published_date'=>date('Y-m-d',strtotime('-1 day')),'due_date'=>date('Y-m-d',strtotime('+3 days'))],
        ['id'=>4,'assignment_id'=>1,'subject_id'=>1,'subject_name'=>'Математика','subject_color'=>'#7866b6','title'=>'Готуємося до самостійної роботи','body_html'=>'<p>Повторіть тему <strong>«Тотожні перетворення виразів»</strong>.</p><p>Виконайте завдання № 1–4 у розділі «Перевір себе». Запишіть запитання, які хочете розібрати на наступному уроці.</p>','teacher_name'=>'Олена Коваленко','published_date'=>date('Y-m-d',strtotime('-2 days')),'due_date'=>date('Y-m-d',strtotime('+1 day'))],
    ];
    $items=array_map(fn($row)=>$row+$base,$items);
    $items=array_values(array_filter($items,function($row)use($filters){
        if (!homework_matches_period($row,$filters)) return false;
        foreach(['city'=>'city','school'=>'school_id','class'=>'class_id','subject'=>'subject_id'] as $filter=>$column) if($filters[$filter] && (string)$filters[$filter] !== (string)$row[$column]) return false;
        return !$filters['q'] || mb_stripos(strip_tags($row['title'].' '.$row['body_html'].' '.$row['subject_name'].' '.$row['teacher_name']),$filters['q'])!==false;
    }));
    return ['items'=>$items,'total'=>count($items),'page'=>1,'pages'=>1];
}
function homework_card(array $item,bool $demo=false): void {
    $href=route_url($demo?'demo':'homework',['id'=>$item['id']]);
    ?><article class="homework-card" style="--subject-color:<?=e(safe_color($item['subject_color']))?>"><div class="homework-card-body"><div class="card-top"><div class="subject-group"><span class="subject-icon"><?=icon('book')?></span><span class="subject-badge"><?=e($item['subject_name'])?></span><span class="class-badge"><?=e($item['class_name'])?></span></div><time class="date-chip" datetime="<?=e($item['published_date'])?>"><?=icon('calendar',12)?><?=e(pretty_date($item['published_date']))?></time></div><h3 class="card-title"><a href="<?=e($href)?>"><?=e($item['title'])?></a></h3><div class="card-meta"><?=icon('school',12)?><?=e($item['school_name'])?><span class="dot"></span><?=e($item['city'])?></div><div class="rich-text homework-excerpt"><?=clean_rich_html($item['body_html'])?></div><a class="resource-link" href="<?=e($href)?>"><?=icon('book',14)?><span>Переглянути повністю</span><span class="external-icon"><?=icon('arrow',12)?></span></a></div><div class="card-footer"><div class="teacher"><span class="teacher-avatar"><?=e(initials($item['teacher_name']))?></span><span class="teacher-info"><?=e($item['teacher_name'])?><small>Учитель</small></span></div><span class="due-date deadline-badge<?=$item['due_date']?'':' deadline-badge-empty'?>"><?=icon('clock',14)?><span class="deadline-copy"><?php if($item['due_date']):?><span class="deadline-label">Виконати до</span><time class="deadline-value" datetime="<?=e($item['due_date'])?>"><?=e(pretty_date($item['due_date']))?></time><?php else:?><span class="deadline-label">Без строку виконання</span><?php endif?></span></span></div></article><?php
}
function home_view(bool $demo=false): void {
    $filters=public_filters($_GET);
    if($demo) {
        $data=demo_data($filters);
        $options=['cities'=>['Київ'],'schools'=>[['id'=>1,'name'=>'Ліцей «Світанок»','city'=>'Київ']],'classes'=>$filters['school']===1?[['id'=>1,'name'=>'7-А']]:[],'subjects'=>$filters['school']===1?[['id'=>1,'name'=>'Математика'],['id'=>2,'name'=>'Українська мова'],['id'=>3,'name'=>'Англійська мова']]:[]];
    } else { $data=public_homework($filters); $options=filter_options($filters); }
    if($demo && repo_id($_GET['id']??0)) {
        $all=demo_data([])['items']; $item=null; foreach($all as $row)if((int)$row['id']===repo_id($_GET['id']))$item=$row;
        if(!$item)throw new HttpError(404,'Завдання не знайдено.');
        homework_view($item,true); return;
    }
    layout_start('Домашні завдання'); page_heading('Домашні завдання','Менше пошуків — більше часу на навчання.');
    if($demo):?><div class="notice notice-info demo-notice">Демонстраційні дані · Щоб почати роботу зі своєю школою, <a class="link" href="<?=e(route_url('install'))?>">налаштуйте сайт</a>.</div><?php endif?>
    
    <form class="filter-panel" action="<?=e(url())?>" method="get" data-filters><input type="hidden" name="r" value="<?=$demo?'demo':'home'?>"><div class="filter-header"><h2><?=icon('filter',16)?>Знайдіть свої завдання</h2><a class="filter-reset" href="<?=e(route_url($demo?'demo':'home'))?>">Скинути фільтри ↺</a></div><div class="filter-grid"><?php
    $fields=[['city','Місто','Усі міста',array_combine($options['cities'],$options['cities'])?:[]],['school','Школа','Усі школи',array_column($options['schools'],'name','id')],['class','Клас','Усі класи',array_column($options['classes'],'name','id')],['subject','Предмет','Усі предмети',array_column($options['subjects'],'name','id')]];
    foreach($fields as [$key,$label,$placeholder,$choices]):?><div class="field"><label for="filter-<?=e($key)?>"><?=e($label)?></label><select id="filter-<?=e($key)?>" name="<?=e($key)?>" data-filter="<?=e($key)?>"><option value="<?=$key==='city'?'':'0'?>"><?=e($placeholder)?></option><?php foreach($choices as $value=>$text):?><option value="<?=e($value)?>"<?=(string)$filters[$key]===(string)$value?' selected':''?>><?=e($text)?></option><?php endforeach?></select></div><?php endforeach?>
    <?php period_fields($filters); ?><div class="filter-search"><div class="field field-icon"><label class="sr-only" for="search">Пошук у завданнях</label><?=icon('search',17)?><input id="search" type="search" name="q" value="<?=e($filters['q'])?>" maxlength="100" placeholder="Знайти тему, матеріал або вчителя…"></div><button class="btn btn-primary" type="submit"><?=icon('search',16)?>Знайти завдання</button></div></div><div class="filter-bottom"><span class="filter-hint"><?=icon('check',13)?>Відкритий доступ. Без зайвих кроків.</span><span class="small text-muted">Спершу оберіть школу, щоб побачити її класи й предмети</span></div></form>
    <?php if(collection_ready($filters)): share_panel(share_url($filters,$demo),'Домашні завдання · Після дзвоника',print_url($filters,$demo)); else: ?><p class="period-note mb-3">Оберіть школу й клас, щоб поділитися добіркою всіх предметів або зберегти її у PDF.</p><?php endif?>
    <div class="content-heading"><h2>Останні завдання <span class="results-count"><?=(int)$data['total']?></span></h2><span class="sort-label"><?=icon('calendar',13)?>Спочатку <strong>найновіші</strong></span></div><?php if(!$data['items']):?><div class="empty-state"><div class="empty-icon"><?=icon('book',35)?></div><h3>Поки що без завдань</h3><p>Спробуйте інші фільтри або поверніться, коли вчитель опублікує перше завдання.</p><a class="btn btn-secondary" href="<?=e(route_url($demo?'demo':'home'))?>">Скинути пошук</a></div><?php else:?><div class="homework-grid"><?php foreach($data['items'] as $item)homework_card($item,$demo);?></div><?php endif;
    if($data['pages']>1):?><nav class="pagination" aria-label="Сторінки завдань"><?php for($p=max(1,$data['page']-2);$p<=min($data['pages'],$data['page']+2);$p++):?><a class="<?=$p===$data['page']?'active':''?>" href="<?=e(route_url('home',array_merge($filters,['page'=>$p])))?>" <?=$p===$data['page']?'aria-current="page"':''?>><?=$p?></a><?php endfor?></nav><?php endif;
    layout_end();
}
function homework_view(array $item,bool $demo=false): void {
    layout_start('Домашнє завдання','home',false,homework_print_name($item));
    share_panel(route_url($demo?'demo':'homework',['id'=>$item['id']]),$item['title'].' · '.$item['class_name'],null,true);
    ?><a class="back-link" href="<?=e(route_url($demo?'demo':'home',['school'=>$item['school_id'],'class'=>$item['class_id'],'subject'=>$item['subject_id']]))?>">← До завдань класу</a><div class="detail-layout"><article class="panel detail-card" style="--subject-color:<?=e(safe_color($item['subject_color']))?>"><?php if($demo):?><div class="notice notice-info">Це демонстраційне завдання.</div><?php endif?><div class="card-top"><span class="subject-badge"><?=e($item['subject_name'])?></span><span class="class-badge"><?=e($item['class_name'])?></span></div><h1><?=e($item['title'])?></h1><div class="card-meta"><?=icon('school',14)?><?=e($item['city'].' · '.$item['school_name'])?></div><div class="rich-text"><?=clean_rich_html($item['body_html'])?></div><div class="card-footer"><div class="teacher"><span class="teacher-avatar"><?=e(initials($item['teacher_name']))?></span><span class="teacher-info"><?=e($item['teacher_name'])?><small>Опубліковано <?=e(pretty_date($item['published_date']))?></small></span></div><span class="due-date deadline-badge<?=$item['due_date']?'':' deadline-badge-empty'?>"><?=icon('clock',14)?><span class="deadline-copy"><?php if($item['due_date']):?><span class="deadline-label">Виконати до</span><time class="deadline-value" datetime="<?=e($item['due_date'])?>"><?=e(pretty_date($item['due_date']))?></time><?php else:?><span class="deadline-label">Без строку виконання</span><?php endif?></span></span></div></article><aside class="panel"><h3>Крок за кроком</h3><p class="text-muted">Прочитайте завдання, відкрийте матеріали за посиланнями та зверніть увагу на строк виконання.</p><span class="pill"><?=icon('check',14)?>Ви все знайдете тут</span></aside></div><?php layout_end();
}
function login_view(): void {
    if(user())redirect(route_url('dashboard'));
    layout_start('Вхід у кабінет','login');
    ?><div class="auth-layout"><section class="auth-aside"><div class="eyebrow">Навчання без зайвого клопоту</div><h1>Добре, коли все<br>на своїх місцях.</h1><p>Предмети, класи та домашні завдання вашої школи — в одному спокійному просторі.</p><div class="hero-icon"><?=icon('book',40)?></div><p class="small">Учням вхід не потрібен. Завдання завжди у відкритому доступі.</p><a class="link" href="<?=e(route_url())?>">Переглянути завдання <?=icon('arrow',16)?></a></section><section class="auth-card"><span class="eyebrow">Для працівників школи</span><h2>Раді вас бачити</h2><p class="text-muted">Увійдіть у свій кабінет, щоб продовжити.</p><a class="btn btn-google" href="<?=e(route_url('google-start'))?>"><?=icon('google',19)?>Увійти через Google</a><p class="auth-footnote">Gmail потрібен лише для зручного входу через Google. Для входу за паролем підійде інша електронна адреса, яку додала уповноважена особа школи. Доступу до вашої пошти чи файлів сайт не запитує.</p><?php if(!google_configured()):?><div class="notice notice-info small">Вхід Google стане доступним після налаштування адміністратором.</div><?php endif?><div class="auth-divider"><span>або за поштою та паролем</span></div><?php form_start('login','form-grid'); field('email','Електронна пошта',old('email'),'email',true,'autocomplete="username" maxlength="190"'); field('password','Пароль','','password',true,'autocomplete="current-password" maxlength="72"');?><button class="btn btn-primary btn-block" type="submit">Увійти в кабінет <?=icon('arrow',16)?></button><?php form_end();?><p class="auth-footnote">Немає доступу чи забули пароль? Зверніться до уповноваженої особи школи. Представнику школи допоможе власник сайту.</p></section></div><?php layout_end();
}
function install_view(): void {
    $status=installation_status();
    // A stale login cookie must not cause another failed DB query while showing diagnostics.
    layout_start('Налаштування сайту','install',true);
    page_heading('Почнімо з вашої школи','Початкове налаштування виконує лише власник сайту.');
    if($status['installed']): ?>
        <div class="panel"><h2>Сайт уже налаштований</h2><p>Єдиного адміністратора вже створено. Увійдіть у кабінет, щоб додати школу та уповноважену особу.</p><a class="btn btn-primary" href="<?=e(route_url('login'))?>">Увійти</a></div>
    <?php else:
        $databaseLabels=[
            'driver'=>'У PHP не ввімкнено розширення pdo_mysql. Увімкніть його в налаштуваннях PHP хостингу.',
            'credentials'=>'MySQL відхилив доступ. Перевірте db_user, db_password і права цього користувача на серверну базу.',
            'database_missing'=>'Базу даних не знайдено. Створіть її в панелі хостингу та перевірте db_name.',
            'connection'=>'Не вдалося підключитися до MySQL. Перевірте db_host, db_port і параметри бази на хостингу.',
        ];
        $schemaLabels=[
            'tables_missing'=>'Схема неповна: потрібних таблиць немає. Імпортуйте database/schema.sql у вибрану серверну базу через phpMyAdmin.',
            'schema_incompatible'=>'Структура таблиць не відповідає сайту. Звірте її з database/schema.sql.',
            'schema_unreadable'=>'Не вдалося перевірити таблиці. Перевірте їхню структуру та права користувача MySQL на читання.',
        ]; ?>
        <section class="panel"><h2>Перевірка перед встановленням</h2><ul>
            <li><strong>Підключення до MySQL:</strong> <?=e($status['database']?'успішне.':($databaseLabels[$status['issue']]??$databaseLabels['connection']))?></li>
            <li><strong>Таблиці та поля сайту:</strong> <?=e(!$status['database']?'перевірка стане доступною після підключення до MySQL.':($status['schema']?'доступні для читання.':($schemaLabels[$status['issue']]??$schemaLabels['schema_unreadable'])))?></li>
            <li><strong>Ключ встановлення:</strong> <?=$status['token']?'налаштований.':'задайте setup_token із 24–200 символів без пробілів на початку та в кінці.'?></li>
        </ul><p class="small text-muted">Налаштування беруться із серверного <code>config/config.php</code>. Змінні середовища <code>APP_DB_*</code> та <code>APP_SETUP_TOKEN</code> мають пріоритет над файлом. Значення паролів і ключа тут не показуються.</p></section>
        <?php if($status['database'] && $status['schema'] && $status['token']): ?>
            <div class="panel form-card"><h2>Єдиний адміністратор</h2><p class="text-muted">Лише цей обліковий запис зможе створювати школи та призначати уповноважених осіб.</p>
            <?php form_start('install','form-grid');
            field('name','Ваше ім’я',old('name'),'text',true,'maxlength="160" autocomplete="name"');
            field('email','Електронна пошта',old('email'),'email',true,'maxlength="190" autocomplete="username"');
            field('password','Пароль (від 12 символів)','','password',true,'minlength="12" maxlength="72" autocomplete="new-password"');
            field('setup_token','Ключ встановлення з конфігурації','','password',true,'autocomplete="off" maxlength="200"'); ?>
            <button class="btn btn-primary" type="submit">Створити адміністратора <?=icon('arrow',16)?></button><?php form_end(); ?></div>
        <?php else: ?>
            <div class="notice notice-info">Виправте пункти вище та оновіть цю сторінку. Коли підключення, таблиці й ключ будуть готові, тут з’явиться форма створення адміністратора.</div>
        <?php endif;
    endif;
    layout_end();
}
function help_view(): void {
    layout_start('Як це працює','help');
    page_heading('Кожному — свій простір','Допоміжна дошка домашніх завдань для співпраці учнів, учителів і батьків.');
    ?><section class="hero-banner"><div class="hero-icon"><?=icon('book',34)?></div><div class="hero-copy"><h2>Домашні завдання завжди під рукою</h2><p>Оберіть школу, клас і предмет. Завдання доступні без реєстрації — зокрема коли пропустили урок. Зручне спільне місце для вчителів, учнів і батьків.</p></div><div class="hero-art" aria-hidden="true"></div></section>
    <div class="help-grid"><section class="help-card panel"><?=icon('book',30)?><h2>Учням і батькам</h2><p>Оберіть місто, школу, клас і предмет. Завдання можна знайти без реєстрації, навіть якщо учень пропустив урок. Нові публікації відображаються першими.</p><p>Адресу легко запам’ятати: <strong>homework.org.ua</strong>. Англійське слово <em>homework</em> означає «домашнє завдання».</p><a class="btn btn-secondary" href="<?=e(route_url())?>">Знайти завдання <?=icon('arrow',16)?></a></section>
    <section class="help-card panel"><?=icon('users',30)?><h2>Учителям</h2><p>Після погодженого школою підключення уповноважена особа додає вашу електронну адресу та призначає предмети й класи. Gmail потрібен лише для простішої авторизації через Google; за паролем можна входити й з іншою адресою.</p><p>Публікуйте завдання після уроку, уточнюйте текст і строк виконання. Щоб прикріпити файл, завантажте його у хмарне сховище, наприклад Google Drive, та вставте посилання. Відкрийте учням доступ <strong>до читання</strong> й перевірте посилання перед публікацією. Не відкривайте персональні документи учнів.</p><a class="btn btn-secondary" href="<?=e(route_url('login'))?>">Увійти в кабінет <?=icon('arrow',16)?></a></section>
    <section class="help-card panel" id="schools"><?=icon('school',30)?><h2>Керівнику та уповноваженій особі школи</h2><p><strong>Підключення добровільне.</strong> Лист або перегляд сайту не підключає школу автоматично. Спершу представник погоджує використання з керівником чи іншою особою, яка має право прийняти рішення від школи.</p><p>Після шкільного погодження власник надає доступ уповноваженій особі. Вона додає класи, предмети та вчителів, організовує ознайомлення й поширює посилання через погоджені школою канали.</p></section></div>
    <section class="panel school-adoption" aria-labelledby="adoption-title"><h2 id="adoption-title">Як запровадити сервіс узгоджено</h2><ol><li><strong>Погодити рішення на рівні школи.</strong> Визначте відповідальну особу, спосіб використання й те, як сервіс доповнює вже наявні шкільні інструменти.</li><li><strong>Підготувати доступ і правила.</strong> Узгодьте внесення даних працівників та ознайомте їх із <a class="link" href="<?=e(route_url('privacy'))?>">конфіденційністю</a> й <a class="link" href="<?=e(route_url('terms'))?>">умовами користування</a>. Імена, оцінки, контакти та інші персональні дані учнів не публікуються у відкритих завданнях.</li><li><strong>Навчити вчителів.</strong> Покажіть на одному прикладі вхід, створення й редагування завдання, строк виконання та перевірку доступу до файла. Погодьте, куди звертатися по допомогу.</li><li><strong>Повідомити всіх централізовано.</strong> Школа надсилає єдину коротку інструкцію та посилання вчителям, учням і батькам через свої звичні канали.</li></ol><p>«Після дзвоника» допомагає співпрацювати й знаходити завдання, але не замінює офіційний електронний журнал. Для офіційного діловодства діють окремі вимоги. <a class="link" href="https://mon.gov.ua/news/elektronnyi-zhurnal-u-shkoli-pro-holovne" target="_blank" rel="noopener noreferrer">Пояснення МОН</a>.</p></section>
    <section class="panel school-adoption" id="sharing"><h2>Одне посилання для чату класу</h2><p>На сторінці завдань оберіть школу й клас. Залиште «Усі предмети» для всього класу або виберіть окремий предмет. Для дня чи тижня задайте період, дату та спосіб відбору: за датою публікації або строком виконання. Натисніть «Знайти завдання».</p><p>Кнопка <strong>«Скопіювати»</strong> з іконкою посилання відразу копіює адресу завдання чи добірки. Вставте її у звичний чат класу. Поруч є кнопка з іконкою принтера. Адресата обираєте ви, автоматичних повідомлень сайт не надсилає.</p><p>Кнопка <strong>«Друк»</strong> відкриває повну добірку, включно з іншими сторінками списку. У вікні друку оберіть «Зберегти як PDF» і за потреби надішліть збережений файл у чат. Для одного завдання така кнопка є на його сторінці. PDF є копією на момент збереження; актуальні зміни перевіряйте на сайті.</p></section>
    <section class="panel school-adoption" id="install"><h2>Сайт на головному екрані телефону</h2><p>Натисніть <strong>«Встановити на телефон»</strong> з іконкою внизу сторінки. Якщо браузер підтримує встановлення, він запропонує підтвердити його. Інакше кнопка покаже інструкцію для вашого браузера.</p><p>На iPhone відкрийте сайт у Safari, натисніть «Поширити» й «На початковий екран»; за наявності ввімкніть «Відкрити як вебпрограму». На Android скористайтеся пунктом «Встановити застосунок» або «Додати на головний екран» у меню браузера. Назви пунктів залежать від версії пристрою.</p><p>Для роботи із завданнями потрібен інтернет. Сайт не кешує завдання або приватні сторінки для офлайн-перегляду й не надсилає push-сповіщень.</p></section><?php
    layout_end();
}
function error_view(int $status,string $message): void {
    http_response_code($status); layout_start('Повідомлення'); ?><div class="empty-state"><div class="eyebrow">Код <?=$status?></div><h1><?=e($message)?></h1><p>Скористайтеся навігацією, щоб продовжити.</p><a class="btn btn-primary" href="<?=e(route_url())?>">До домашніх завдань</a></div><?php layout_end();
}

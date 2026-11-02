<?php
declare(strict_types=1);

function dash_form(string $action, string $class = '', string $confirm = '', bool $editor = false): void
{
    echo '<form method="post" action="'.e(route_url()).'" class="'.e($class).'"';
    if ($confirm !== '') echo ' data-confirm="'.e($confirm).'"';
    if ($editor) echo ' data-editor-form';
    echo '>'.csrf_field().'<input type="hidden" name="action" value="'.e($action).'">';
}

function dash_input(string $name, string $label, mixed $value = '', string $type = 'text', bool $required = true, string $extra = ''): void
{
    static $sequence = 0;
    $id = 'dash-input-'.(++$sequence);
    echo '<div class="field"><label for="'.$id.'">'.e($label).'</label><input id="'.$id.'" name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" '.($required?'required ':'').$extra.'></div>';
}

function dash_select(string $name, string $label, array $options, mixed $selected = 0): void
{
    static $sequence = 0;
    $id='dash-select-'.(++$sequence);
    echo '<div class="field"><label for="'.$id.'">'.e($label).'</label><select id="'.$id.'" name="'.e($name).'" required><option value="">Оберіть…</option>';
    foreach ($options as $key=>$text) echo '<option value="'.e($key).'"'.((string)$selected===(string)$key?' selected':'').'>'.e($text).'</option>';
    echo '</select></div>';
}

function dash_old(string $action, string $key, mixed $default = '', int $id = 0): mixed
{
    $previous=$_SESSION['old']??[];
    if (($previous['action']??'')!==$action || repo_id($previous['id']??0)!==$id) return $default;
    return is_scalar($previous[$key]??null)?$previous[$key]:$default;
}

function dash_active(bool $active, string $label = 'Обліковий запис активний'): void
{
    static $sequence = 0;
    $id='dash-active-'.(++$sequence);
    echo '<label class="small text-muted" for="'.$id.'"><input id="'.$id.'" type="checkbox" name="is_active" value="1"'.($active?' checked':'').'> '.e($label).'</label>';
}

function dash_edit_open(string $action, int $id): string
{
    $previous=$_SESSION['old']??[];
    return ($previous['action']??'')===$action&&repo_id($previous['id']??0)===$id?' open':'';
}

function dash_status(bool $active): void
{
    echo '<span class="status-badge'.($active?'':' inactive').'">'.($active?'Активно':'Вимкнено').'</span>';
}

function dash_options(array $rows): array
{
    $options=[];
    foreach ($rows as $row) $options[$row['id']]=$row['name'];
    return $options;
}

function dash_date(?string $date): string
{
    return $date?date('d.m.Y',strtotime($date)):'—';
}

function dashboard_view(array $account): void
{
    $role=$account['role'];
    $tabs=match($role){
        'admin'=>['overview'=>'Огляд','schools'=>'Школи та представники'],
        'secretary'=>['overview'=>'Огляд','teachers'=>'Учителі','subjects'=>'Предмети','classes'=>'Класи','assignments'=>'Призначення'],
        default=>['overview'=>'Огляд','homework'=>'Мої завдання'],
    };
    $tab=is_string($_GET['tab']??null)&&isset($tabs[$_GET['tab']])?$_GET['tab']:'overview';
    $data=dashboard_data($account);
    $title=match($role){'admin'=>'Кабінет адміністратора','secretary'=>'Кабінет уповноваженої особи школи',default=>'Кабінет учителя'};
    layout_start($title,'dashboard');
    ?>
    <div class="page-heading"><div class="eyebrow">Ваш робочий простір</div><div class="heading-row"><div class="heading-copy"><h1><?=e($title)?></h1><p><?=e($role==='admin'?'Керуйте школами та обліковими записами уповноважених осіб школи.':($account['school_name']??$data['schools'][0]['name']??'Ваша школа'))?></p></div><?php if($role==='teacher'):?><a class="btn btn-primary" href="<?=e(route_url('editor'))?>"><?=icon('plus',16)?> Нове завдання</a><?php endif?></div></div>
    <nav class="tab-nav" aria-label="Розділи кабінету"><?php foreach($tabs as $key=>$label):?><a href="<?=e(route_url('dashboard',['tab'=>$key]))?>" class="<?=$key===$tab?'active':''?>" <?=$key===$tab?'aria-current="page"':''?>><?=e($label)?></a><?php endforeach?></nav>
    <?php
    if($tab==='overview') dash_overview($account,$data);
    elseif($role==='admin'&&$tab==='schools') dash_schools($data);
    elseif($role==='secretary') match($tab){'teachers'=>dash_teachers($data),'subjects'=>dash_subjects($data),'classes'=>dash_classes($data),'assignments'=>dash_assignments($data),default=>null};
    elseif($role==='teacher'&&$tab==='homework') dash_homework($account,$data['homework'],$data['counts']['homework']);
    layout_end();
}

function dash_overview(array $account, array $data): void
{
    $role=$account['role'];
    $stats=match($role){
        'admin'=>[['schools','Шкіл','school'],['teachers','Учителів','users'],['classes','Класів','book'],['homework','Завдань','calendar']],
        'secretary'=>[['teachers','Учителів','users'],['subjects','Предметів','book'],['classes','Класів','school'],['assignments','Призначень','calendar']],
        default=>[['subjects','Моїх предметів','book'],['classes','Моїх класів','school'],['assignments','Призначень','users'],['homework','Моїх завдань','calendar']],
    };
    echo '<div class="dashboard-grid">';
    foreach($stats as [$key,$label,$symbol]) echo '<div class="stat-card"><div class="stat-icon">'.icon($symbol,18).'</div><strong class="stat-value">'.e($data['counts'][$key]).'</strong><span class="stat-label">'.e($label).'</span></div>';
    echo '</div>';
    if($role==='admin'){
        ?>
        <section class="hero-banner"><div class="hero-icon"><?=icon('school',32)?></div><div class="hero-copy"><h2>Починаємо з погодження школи</h2><p>Підключайте школу лише після рішення керівника або уповноваженої ним особи. Представник школи організує початкове навчання вчителів і централізовано поширить адресу дошки серед учнів та батьків.</p><a class="btn btn-primary btn-sm mt-2" href="<?=e(route_url('dashboard',['tab'=>'schools']))?>"><?=icon('plus',14)?> Додати школу</a></div><div class="hero-art" aria-hidden="true"></div></section>
        <?php
    } elseif($role==='secretary'){
        $steps=[['teachers','Учителі','Додайте email учителя. Gmail спрощує Google-вхід; для іншої адреси встановіть пароль сайту. Доступу до пошти або хмарних файлів сайт не отримує.'],['subjects','Предмети та класи','Створіть предмети, а потім додайте класи вашої школи.'],['assignments','Призначення','Поєднайте вчителя, предмет і клас. Після цього проведіть початковий інструктаж із публікації завдань.']];
        echo '<div class="help-grid mb-3">';
        foreach($steps as $index=>[$key,$label,$text]) echo '<a class="help-card" href="'.e(route_url('dashboard',['tab'=>$key])).'"><span class="step-number">'.($index+1).'</span><h3>'.e($label).'</h3><p>'.e($text).'</p></a>';
        echo '</div>';
        ?>
        <div class="notice notice-info"><?=icon('link',16)?><p>Поділіться з учнями посиланням на школу: <a class="link" href="<?=e(route_url('home',['school'=>$account['school_id']]))?>"><?=e(route_url('home',['school'=>$account['school_id']]))?></a></p></div>
        <?php
    } else {
        if(!$data['assignments']) echo '<div class="notice notice-warning">'.icon('help',16).'<p>Уповноважена особа школи ще не призначила вам предмет і клас. Зверніться до неї, щоб почати публікувати завдання.</p></div>';
        else {
            ?>
            <section class="panel"><div class="panel-header"><div><h2>Мої предмети та класи</h2><p>Ви можете створювати завдання лише для цих призначень.</p></div></div><div class="inline-form"><?php foreach($data['assignments'] as $assignment):?><a class="btn btn-secondary btn-sm" href="<?=e(route_url('editor',['assignment'=>$assignment['id']]))?>"><?=e($assignment['class_name'].' · '.$assignment['subject_name'])?> <?=icon('plus',13)?></a><?php endforeach?></div></section>
            <?php
        }
    }
    dash_homework($account,array_slice($data['homework'],0,8),$data['counts']['homework'],true);
}

function dash_schools(array $data): void
{
    ?>
    <section class="form-card"><h2>Додати школу</h2><p class="text-muted small">Додавайте школу лише після погодженого нею рішення про використання сервісу. Перевірте повноваження представника; отримання запрошення саме по собі не є згодою на підключення.</p>
    <?php dash_form('create_school');?><div class="form-grid">
    <?php
    dash_input('name','Назва школи',dash_old('create_school','name'),'text',true,'maxlength="180"');
    dash_input('city','Місто / населений пункт',dash_old('create_school','city'),'text',true,'maxlength="120"');
    dash_input('address','Адреса (необов’язково)',dash_old('create_school','address'),'text',false,'maxlength="255"');
    dash_input('secretary_name','Ім’я та прізвище уповноваженої особи школи',dash_old('create_school','secretary_name'),'text',true,'maxlength="160"');
    dash_input('secretary_email','Електронна пошта уповноваженої особи школи',dash_old('create_school','secretary_email'),'email',true,'maxlength="190" autocomplete="off"');
    dash_input('password','Початковий пароль уповноваженої особи школи','','password',true,'minlength="12" maxlength="72" autocomplete="new-password"');
    ?></div><div class="form-actions"><span class="form-hint">Пароль: від 12 до 72 символів. Передайте його уповноваженій особі школи особисто.</span><button class="btn btn-primary" type="submit"><?=icon('plus',16)?> Створити школу</button></div></form></section>
    <div class="content-heading"><h2>Школи <span class="results-count"><?=count($data['schools'])?></span></h2></div>
    <?php
    if(!$data['schools']){dash_empty('Поки що немає шкіл','Додайте першу школу за допомогою форми вище.');return;}
    foreach($data['schools'] as $school):
    ?>
    <details class="panel"<?=dash_edit_open('update_school',(int)$school['id'])?>><summary class="panel-header"><div><h2><?=e($school['name'])?></h2><p><?=e($school['city'])?> · <?=e($school['teacher_count'])?> учителів · <?=e($school['class_count'])?> класів · Налаштувати школу ↓</p></div><?php dash_status((bool)$school['is_active']);?></summary>
    <?php dash_form('update_school');hidden('id',$school['id']);?><div class="form-grid">
    <?php
    $id=(int)$school['id'];
    dash_input('name','Назва школи',dash_old('update_school','name',$school['name'],$id),'text',true,'maxlength="180"');
    dash_input('city','Місто / населений пункт',dash_old('update_school','city',$school['city'],$id),'text',true,'maxlength="120"');
    dash_input('address','Адреса (необов’язково)',dash_old('update_school','address',$school['address'],$id),'text',false,'maxlength="255"');
    dash_input('secretary_name','Ім’я та прізвище уповноваженої особи школи',dash_old('update_school','secretary_name',$school['secretary_name']??'',$id),'text',true,'maxlength="160"');
    dash_input('secretary_email','Електронна пошта уповноваженої особи школи',dash_old('update_school','secretary_email',$school['secretary_email']??'',$id),'email',true,'maxlength="190"');
    dash_input('password','Новий пароль уповноваженої особи школи (необов’язково)','','password',false,'minlength="12" maxlength="72" autocomplete="new-password"');
    ?></div><div class="form-actions"><?php dash_active((bool)$school['is_active'],'Школа активна й доступна учням');?><button class="btn btn-primary" type="submit">Зберегти зміни</button></div></form></details>
    <?php endforeach;
}

function dash_teachers(array $data): void
{
    ?>
    <section class="form-card"><h2>Додати вчителя</h2><p class="text-muted small">Вкажіть email працівника після його інформування та погодження використання школою. Gmail дає зручний Google-вхід; для іншої адреси потрібен пароль сайту. Сайт не отримує доступу до листів або файлів працівника.</p>
    <?php dash_form('save_teacher');?><div class="form-grid">
    <?php
    dash_input('name','Ім’я та прізвище',dash_old('save_teacher','name'),'text',true,'maxlength="160"');
    dash_input('email','Email учителя',dash_old('save_teacher','email'),'email',true,'maxlength="190" placeholder="teacher@example.com"');
    dash_input('password','Пароль сайту (обов’язковий для адреси не Gmail)','','password',false,'minlength="12" maxlength="72" autocomplete="new-password"');
    ?></div><div class="form-actions"><span class="form-hint">Для Gmail пароль необов’язковий, якщо налаштовано Google-вхід. Після додавання призначте предмет і клас.</span><button class="btn btn-primary" type="submit"><?=icon('plus',16)?> Додати вчителя</button></div></form></section>
    <div class="content-heading"><h2>Учителі школи <span class="results-count"><?=count($data['users'])?></span></h2><a class="link small" href="<?=e(route_url('dashboard',['tab'=>'assignments']))?>">До призначень →</a></div>
    <?php
    if(!$data['users']){dash_empty('Додайте першого вчителя','Внесіть email, налаштуйте спосіб входу та призначте предмет і клас.');return;}
    foreach($data['users'] as $teacher): $id=(int)$teacher['id'];
    ?>
    <details class="panel"<?=dash_edit_open('save_teacher',$id)?>><summary class="panel-header"><div><h2><?=e($teacher['name'])?></h2><p><?=e($teacher['email'])?> · Редагувати ↓</p></div><?php dash_status((bool)$teacher['is_active']);?></summary>
    <?php dash_form('save_teacher');hidden('id',$id);?><div class="form-grid">
    <?php
    dash_input('name','Ім’я та прізвище',dash_old('save_teacher','name',$teacher['name'],$id),'text',true,'maxlength="160"');
    dash_input('email','Email учителя',dash_old('save_teacher','email',$teacher['email'],$id),'email',true,'maxlength="190"');
    dash_input('password','Новий пароль (необов’язково)','','password',false,'minlength="12" maxlength="72" autocomplete="new-password"');
    ?></div><div class="form-actions"><?php dash_active((bool)$teacher['is_active']);?><button class="btn btn-primary" type="submit">Зберегти зміни</button></div><p class="small text-muted mb-0">Для адреси не Gmail потрібен новий або вже встановлений пароль сайту. Зміна email скидає попередню прив’язку Google. Після вимкнення вчителя його завдання зникнуть із відкритого перегляду. Історія зберігається.</p></form></details>
    <?php endforeach;
}

function dash_subjects(array $data): void
{
    ?>
    <section class="form-card"><h2>Додати предмет</h2><p class="text-muted small">Предмети належать лише вашій школі. Колір допомагає учням швидко їх розрізняти.</p>
    <?php dash_form('save_subject');?><div class="form-grid"><?php
    dash_input('name','Назва предмета',dash_old('save_subject','name'),'text',true,'maxlength="120" placeholder="Наприклад, Математика"');
    dash_input('color','Колір предмета',dash_old('save_subject','color','#528868'),'color');
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit"><?=icon('plus',16)?> Додати предмет</button></div></form></section>
    <div class="content-heading"><h2>Предмети <span class="results-count"><?=count($data['subjects'])?></span></h2></div>
    <?php
    if(!$data['subjects']){dash_empty('Предметів ще немає','Додайте предмети, які викладають у вашій школі.');return;}
    foreach($data['subjects'] as $subject): $id=(int)$subject['id'];
    ?>
    <details class="panel"<?=dash_edit_open('save_subject',$id)?>><summary class="panel-header"><h2><?=e($subject['name'])?></h2><span class="subject-badge" style="--subject-color:<?=e(safe_color($subject['color']))?>">Редагувати</span></summary>
    <?php dash_form('save_subject');hidden('id',$id);?><div class="form-grid"><?php
    dash_input('name','Назва предмета',dash_old('save_subject','name',$subject['name'],$id),'text',true,'maxlength="120"');
    dash_input('color','Колір предмета',dash_old('save_subject','color',$subject['color'],$id),'color');
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit">Зберегти зміни</button></div></form></details>
    <?php endforeach;
}

function dash_classes(array $data): void
{
    ?>
    <section class="form-card"><h2>Додати клас</h2><p class="text-muted small">Використовуйте знайомі учням назви: 5-А, 7-Б, 10-А. Порядок визначає позицію в списку.</p>
    <?php dash_form('save_class');?><div class="form-grid"><?php
    dash_input('name','Назва класу',dash_old('save_class','name'),'text',true,'maxlength="60" placeholder="7-А"');
    dash_input('sort_order','Порядок у списку',dash_old('save_class','sort_order','7'),'number',true,'min="0" max="999"');
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit"><?=icon('plus',16)?> Додати клас</button></div></form></section>
    <div class="content-heading"><h2>Класи <span class="results-count"><?=count($data['classes'])?></span></h2></div>
    <?php
    if(!$data['classes']){dash_empty('Класів ще немає','Додайте класи, для яких учителі публікуватимуть завдання.');return;}
    foreach($data['classes'] as $class): $id=(int)$class['id'];
    ?>
    <details class="panel"<?=dash_edit_open('save_class',$id)?>><summary class="panel-header"><h2><?=e($class['name'])?></h2><span class="small text-muted">Порядок: <?=e($class['sort_order'])?> · Редагувати</span></summary>
    <?php dash_form('save_class');hidden('id',$id);?><div class="form-grid"><?php
    dash_input('name','Назва класу',dash_old('save_class','name',$class['name'],$id),'text',true,'maxlength="60"');
    dash_input('sort_order','Порядок у списку',dash_old('save_class','sort_order',$class['sort_order'],$id),'number',true,'min="0" max="999"');
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit">Зберегти зміни</button></div></form></details>
    <?php endforeach;
}

function dash_assignments(array $data): void
{
    $teachers=array_values(array_filter($data['users'],static fn(array $teacher):bool=>(bool)$teacher['is_active']));
    ?>
    <section class="form-card"><h2>Призначити предмет і клас</h2><p class="text-muted small">Кожне призначення дозволяє вчителю публікувати завдання з одного предмета для одного класу.</p>
    <?php if(!$teachers||!$data['subjects']||!$data['classes']):?>
    <div class="notice notice-warning mb-0"><?=icon('help',16)?><p>Спочатку додайте щонайменше одного активного вчителя, предмет і клас у відповідних розділах.</p></div>
    <?php else: dash_form('save_assignment');?><div class="form-grid"><?php
    dash_select('teacher_id','Учитель',dash_options($teachers),dash_old('save_assignment','teacher_id'));
    dash_select('subject_id','Предмет',dash_options($data['subjects']),dash_old('save_assignment','subject_id'));
    dash_select('class_id','Клас',dash_options($data['classes']),dash_old('save_assignment','class_id'));
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit"><?=icon('plus',16)?> Додати призначення</button></div></form><?php endif?></section>
    <section class="panel"><div class="panel-header"><div><h2>Призначення вчителів</h2><p>Призначення із завданнями зберігаються разом з історією навчання.</p></div><span class="results-count"><?=count($data['assignments'])?></span></div>
    <?php if(!$data['assignments']):?><p class="small text-muted mb-0">Призначень поки що немає.</p><?php else:?>
    <div class="table-wrap"><table><thead><tr><th>Учитель</th><th>Предмет</th><th>Клас</th><th>Завдань</th><th><span class="visually-hidden">Дії</span></th></tr></thead><tbody>
    <?php foreach($data['assignments'] as $assignment):?><tr><td><strong><?=e($assignment['teacher_name'])?></strong><?php if(!(int)$assignment['teacher_active']):?><small>Обліковий запис вимкнено</small><?php endif?></td><td><?=e($assignment['subject_name'])?></td><td><?=e($assignment['class_name'])?></td><td><?=e($assignment['homework_count'])?></td><td><div class="table-actions"><?php if(!(int)$assignment['homework_count']):dash_form('delete_assignment','','Видалити це призначення? Учитель втратить можливість додавати до нього завдання.');hidden('id',$assignment['id']);?><button class="btn btn-danger btn-sm" type="submit" aria-label="<?=e('Видалити призначення: '.$assignment['teacher_name'].', '.$assignment['subject_name'].', '.$assignment['class_name'])?>"><?=icon('trash',13)?> Видалити</button></form><?php else:?><span class="small text-muted">Історію збережено</span><?php endif?></div></td></tr><?php endforeach?>
    </tbody></table></div><?php endif?></section>
    <?php
}

function dash_homework(array $account, array $homework, int $total, bool $overview = false): void
{
    $teacher=$account['role']==='teacher';
    ?>
    <section class="panel"><div class="panel-header"><div><h2><?=$overview?'Останні завдання':($teacher?'Мої домашні завдання':'Домашні завдання')?></h2><p><?=$overview?'Найновіші записи у вашому робочому просторі.':'Показано останні 100 записів. Усі опубліковані завдання доступні у відкритому перегляді.'?></p></div><span class="results-count"><?=e($total)?></span></div>
    <?php if(!$homework):?><div class="empty-state"><div class="empty-icon"><?=icon('book',30)?></div><h3>Тут з’являться завдання</h3><p><?=$teacher?'Оберіть свій предмет і клас та створіть перше домашнє завдання.':'Щойно вчителі опублікують завдання, ви побачите їх тут.'?></p><?php if($teacher):?><a class="btn btn-primary" href="<?=e(route_url('editor'))?>"><?=icon('plus',16)?> Створити завдання</a><?php endif?></div>
    <?php else:?>
    <div class="table-wrap"><table><thead><tr><th>Завдання</th><th>Предмет / клас</th><?php if(!$teacher):?><th>Учитель</th><?php endif?><th>Публікація</th><th>Виконати до</th><?php if($teacher):?><th><span class="visually-hidden">Дії</span></th><?php endif?></tr></thead><tbody>
    <?php foreach($homework as $record):?><tr><td><strong><?php if($record['published_date']<=date('Y-m-d')):?><a href="<?=e(route_url('homework',['id'=>$record['id']]))?>"><?=e($record['title'])?></a><?php else:?><?=e($record['title'])?><?php endif?></strong><?php if($record['published_date']>date('Y-m-d')):?><small>Заплановано</small><?php endif?><?php if($account['role']==='admin'):?><small><?=e($record['city'].' · '.$record['school_name'])?></small><?php endif?></td><td><?=e($record['subject_name'])?><small><?=e($record['class_name'])?></small></td><?php if(!$teacher):?><td><?=e($record['teacher_name'])?></td><?php endif?><td class="nowrap"><?=e(dash_date($record['published_date']))?></td><td class="nowrap"><?=e(dash_date($record['due_date']))?></td><?php if($teacher):?><td><div class="table-actions"><a class="btn btn-secondary btn-sm" href="<?=e(route_url('editor',['id'=>$record['id']]))?>" aria-label="<?=e('Редагувати: '.$record['title'])?>"><?=icon('edit',13)?> Змінити</a><?php dash_form('delete_homework','','Видалити домашнє завдання? Цю дію неможливо скасувати.');hidden('id',$record['id']);?><button class="btn btn-danger btn-sm" type="submit" aria-label="<?=e('Видалити: '.$record['title'])?>"><?=icon('trash',13)?></button></form></div></td><?php endif?></tr><?php endforeach?>
    </tbody></table></div><?php if($overview&&$teacher&&$total>8):?><div class="mt-3 text-right"><a class="link small" href="<?=e(route_url('dashboard',['tab'=>'homework']))?>">Усі мої завдання →</a></div><?php endif?>
    <?php endif?></section>
    <?php
}

function dash_empty(string $title, string $text): void
{
    echo '<div class="empty-state"><div class="empty-icon">'.icon('book',29).'</div><h3>'.e($title).'</h3><p>'.e($text).'</p></div>';
}

function editor_view(array $account): void
{
    if($account['role']!=='teacher') throw new HttpError(403,'Лише вчителі можуть створювати домашні завдання.');
    $id=repo_id($_GET['id']??0);
    $record=$id?homework_row($id):null;
    if($id&&(!$record||(int)$record['teacher_id']!==(int)$account['id']||(int)$record['school_id']!==(int)$account['school_id'])) throw new HttpError(403,'Ви можете редагувати лише власні завдання.');
    $assignments=assignment_rows((int)$account['school_id'],(int)$account['id']);
    layout_start($id?'Редагувати завдання':'Нове домашнє завдання','editor');
    ?>
    <div class="detail-layout"><a class="back-link" href="<?=e(route_url('dashboard',['tab'=>'homework']))?>">← До моїх завдань</a><div class="page-heading"><div class="eyebrow">Кабінет учителя</div><div class="heading-copy"><h1><?=$id?'Редагувати завдання':'Нове домашнє завдання'?></h1><p>Поясніть, що потрібно зробити, і додайте корисні матеріали.</p></div></div>
    <?php if(!$assignments):dash_empty('Потрібне призначення предмета','Зверніться до уповноваженої особи школи: вона має призначити вам предмет і клас, перш ніж ви зможете створити завдання.');else:
    $options=[];foreach($assignments as $assignment)$options[$assignment['id']]=$assignment['class_name'].' · '.$assignment['subject_name'];
    $selected=$record['assignment_id']??repo_id($_GET['assignment']??0);
    if(!isset($options[$selected]))$selected=count($options)===1?array_key_first($options):0;
    ?>
    <section class="form-card"><?php dash_form('save_homework','', '',true);hidden('id',$id);?><div class="form-grid">
    <div class="full-width"><?php dash_select('assignment_id','Клас і предмет',$options,dash_old('save_homework','assignment_id',$selected,$id));?></div>
    <div class="full-width"><?php dash_input('title','Тема домашнього завдання',dash_old('save_homework','title',$record['title']??'',$id),'text',true,'maxlength="200" placeholder="Наприклад, Лінійні рівняння: практика"');?></div>
    <?php
    dash_input('published_date','Дата публікації',dash_old('save_homework','published_date',$record['published_date']??date('Y-m-d'),$id),'date');
    dash_input('due_date','Виконати до (необов’язково)',dash_old('save_homework','due_date',$record['due_date']??'',$id),'date',false);
    ?>
    <div class="field full-width"><label for="body_html">Що потрібно зробити</label><textarea id="body_html" name="body_html" class="editor-fallback" rows="12" required maxlength="60000" placeholder="Опишіть завдання та додайте посилання на матеріали…"><?=e(clean_rich_html(mb_strcut((string)dash_old('save_homework','body_html',$record['body_html']??'',$id),0,60000,'UTF-8')))?></textarea><div id="homework-editor" class="quill-editor" hidden aria-label="Текст домашнього завдання"></div><small>Для файлу скористайтеся хмарним сховищем, наприклад Google Drive, і вставте посилання через значок редактора. Надайте учням доступ для читання та перевірте його. Завдання відкриті всім: не додавайте персональних даних учнів або закритих документів.</small></div>
    </div><div class="notice notice-info mt-3"><?=icon('calendar',16)?><p>Завдання бачать усі відвідувачі сайту. Якщо оберете майбутню дату, запис автоматично стане доступним у цей день.</p></div><div class="form-actions"><a class="btn btn-secondary" href="<?=e(route_url('dashboard',['tab'=>'homework']))?>">Скасувати</a><button class="btn btn-primary" type="submit"><?=icon('check',16)?> <?=$id?'Зберегти зміни':'Зберегти завдання'?></button></div></form></section>
    <?php endif?></div>
    <?php layout_end();
}

function profile_view(array $account): void
{
    $role=match($account['role']){'admin'=>'Адміністратор','secretary'=>'Уповноважена особа школи',default=>'Учитель'};
    layout_start('Мій обліковий запис','profile');
    ?>
    <div class="detail-layout"><div class="page-heading"><div class="eyebrow">Налаштування</div><div class="heading-copy"><h1>Мій обліковий запис</h1><p>Ваші дані та безпека входу.</p></div></div>
    <section class="panel"><div class="panel-header"><h2>Особисті дані</h2><span class="status-badge"><?=e($role)?></span></div><div class="form-grid"><div><span class="small text-muted">Ім’я та прізвище</span><p class="mb-0"><?=e($account['name'])?></p></div><div><span class="small text-muted">Електронна пошта</span><p class="mb-0"><?=e($account['email'])?></p></div><?php if(!empty($account['school_name'])):?><div class="full-width"><span class="small text-muted">Школа</span><p class="mb-0"><?=e($account['school_name'])?></p></div><?php endif?></div><p class="small text-muted mt-3 mb-0"><?=$account['role']==='teacher'?'Для зміни імені чи email зверніться до уповноваженої особи школи.':($account['role']==='secretary'?'Для зміни імені чи пошти зверніться до адміністратора сайту.':'Ви керуєте школами та призначаєте їхніх уповноважених осіб.')?></p></section>
    <section class="form-card"><h2>Змінити пароль</h2><p class="text-muted small">Використовуйте унікальний пароль від 12 до 72 символів.</p>
    <?php if(empty($account['password_hash'])):?><div class="notice notice-info mb-0"><?=icon('google',16)?><p>Ви входите через Google. Щоб додатково входити за паролем, зверніться до уповноваженої особи школи.</p></div>
    <?php else:dash_form('change_password');?><div class="form-grid"><?php
    dash_input('current_password','Поточний пароль','','password',true,'autocomplete="current-password"');
    dash_input('password','Новий пароль','','password',true,'minlength="12" maxlength="72" autocomplete="new-password"');
    ?></div><div class="form-actions"><button class="btn btn-primary" type="submit"><?=icon('check',16)?> Оновити пароль</button></div></form><?php endif?></section></div>
    <?php layout_end();
}

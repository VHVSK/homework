<?php
declare(strict_types=1);

function assert_school_row(string $table, int $id, int $schoolId): array {
    if (!in_array($table,['users','classes','subjects','teaching_assignments'],true)) throw new LogicException('Unknown table');
    $row=query("SELECT * FROM $table WHERE id=? AND school_id=?",[$id,$schoolId])->fetch();
    if (!$row) throw new HttpError(403,'Цей запис не належить вашій школі.');
    return $row;
}
function handle_post(): never {
    verify_csrf();
    $action=is_string($_POST['action']??null)?$_POST['action']:'';
    $back='dashboard'; $tab='overview';
    try {
        switch ($action) {
            case 'install':
                $back='install';
                if (query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) throw new HttpError(403,'Адміністратора вже створено. Повторне встановлення недоступне.');
                $token=(string)config('setup_token','');
                if (!setup_token_ready() || !hash_equals($token,input_text('setup_token',200))) throw new InvalidArgumentException('Некоректний ключ встановлення. Вкажіть setup_token з конфігурації (24–200 символів без пробілів на початку та в кінці).');
                $name=input_text('name',160); $email=email_value(); $password=password_value();
                query("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'admin')",[$name,$email,$password]);
                login_user(query("SELECT * FROM users WHERE role='admin'")->fetch());
                flash('success','Адміністратора створено. Тепер додайте школу та призначте її уповноважену особу.');
                redirect(route_url('dashboard'));
            case 'login':
                $back='login';
                $email=email_value();
                $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
                $accountHash=hash('sha256','account:'.$email); $ipHash=hash('sha256','ip:'.$ip);
                foreach ([[$accountHash,10],[$ipHash,40]] as [$hash,$limit]) {
                    if ((int)query('SELECT COUNT(*) FROM login_attempts WHERE identifier_hash=? AND attempted_at > UTC_TIMESTAMP() - INTERVAL 15 MINUTE',[$hash])->fetchColumn()>=$limit) throw new InvalidArgumentException('Забагато спроб входу. Спробуйте через 15 хвилин.');
                }
                query('INSERT INTO login_attempts(identifier_hash) VALUES(?),(?)',[$accountHash,$ipHash]);
                $account=query("SELECT u.* FROM users u LEFT JOIN schools s ON s.id=u.school_id WHERE u.email=? AND u.is_active=1 AND (u.role='admin' OR s.is_active=1)",[$email])->fetch();
                $password=is_string($_POST['password']??null)?$_POST['password']:'';
                $dummy='$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
                $verified=password_verify($password,$account['password_hash']??$dummy);
                if (!$account || !$account['password_hash'] || !$verified) throw new InvalidArgumentException('Неправильна пошта або пароль. Учителі також можуть увійти через Google.');
                query('DELETE FROM login_attempts WHERE identifier_hash=? OR attempted_at < UTC_TIMESTAMP() - INTERVAL 1 DAY',[$accountHash]);
                if (password_needs_rehash($account['password_hash'],PASSWORD_DEFAULT)) query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$account['id']]);
                login_user($account); redirect(route_url('dashboard'));
            case 'logout':
                $_SESSION=[]; session_regenerate_id(true); flash('success','Ви вийшли з облікового запису.'); redirect(route_url());
            case 'create_school':
                require_role('admin'); $tab='schools';
                $name=input_text('name',180); $city=input_text('city',120); $address=input_text('address',255,false);
                $secretaryName=input_text('secretary_name',160); $email=email_value('secretary_email'); $password=password_value();
                db()->beginTransaction();
                query('INSERT INTO schools(name,city,address) VALUES(?,?,?)',[$name,$city,$address]); $schoolId=(int)db()->lastInsertId();
                query("INSERT INTO users(school_id,name,email,password_hash,role) VALUES(?,?,?,?,'secretary')",[$schoolId,$secretaryName,$email,$password]);
                db()->commit(); flash('success','Школу та обліковий запис уповноваженої особи школи додано.'); break;
            case 'update_school':
                require_role('admin'); $tab='schools'; $schoolId=input_id('id');
                if (!query('SELECT id FROM schools WHERE id=?',[$schoolId])->fetchColumn()) throw new HttpError(404,'Школу не знайдено.');
                $name=input_text('name',180); $city=input_text('city',120); $address=input_text('address',255,false);
                $secretaryName=input_text('secretary_name',160); $email=email_value('secretary_email'); $password=password_value('password',false);
                db()->beginTransaction();
                query('UPDATE schools SET name=?,city=?,address=?,is_active=? WHERE id=?',[$name,$city,$address,isset($_POST['is_active'])?1:0,$schoolId]);
                $secretary=query("SELECT * FROM users WHERE school_id=? AND role='secretary' ORDER BY id LIMIT 1",[$schoolId])->fetch();
                if ($secretary) {
                    query('UPDATE users SET name=?,email=?,is_active=1 WHERE id=?',[$secretaryName,$email,$secretary['id']]);
                    if ($password) query('UPDATE users SET password_hash=? WHERE id=?',[$password,$secretary['id']]);
                } else {
                    if (!$password) throw new InvalidArgumentException('Встановіть пароль для нової уповноваженої особи школи.');
                    query("INSERT INTO users(school_id,name,email,password_hash,role) VALUES(?,?,?,?,'secretary')",[$schoolId,$secretaryName,$email,$password]);
                }
                db()->commit(); flash('success','Налаштування школи збережено.'); break;
            case 'save_teacher':
                $account=require_role('secretary'); $tab='teachers'; $schoolId=(int)$account['school_id'];
                $id=repo_id($_POST['id']??0); $existing=$id?assert_school_row('users',$id,$schoolId):null;
                if ($existing && $existing['role']!=='teacher') throw new HttpError(403,'Можна редагувати лише вчителів своєї школи.');
                $name=input_text('name',160); $email=email_value(); $password=password_value('password',false);
                if (!str_ends_with($email,'@gmail.com') && !$password && empty($existing['password_hash'])) {
                    throw new InvalidArgumentException('Для адреси не @gmail.com установіть пароль сайту: вхід через Google доступний лише з Gmail.');
                }
                if ($existing) {
                    query('UPDATE users SET name=?,email=?,is_active=?,google_sub=? WHERE id=?',[$name,$email,isset($_POST['is_active'])?1:0,$existing['email']===$email?$existing['google_sub']:null,$id]);
                    if ($password) query('UPDATE users SET password_hash=? WHERE id=?',[$password,$id]);
                } else query("INSERT INTO users(school_id,name,email,password_hash,role) VALUES(?,?,?,?,'teacher')",[$schoolId,$name,$email,$password]);
                flash('success','Обліковий запис учителя збережено.'); break;
            case 'save_subject':
                $account=require_role('secretary'); $tab='subjects'; $schoolId=(int)$account['school_id']; $id=repo_id($_POST['id']??0);
                $name=input_text('name',120); $color=safe_color(input_text('color',7,false));
                if ($id) { assert_school_row('subjects',$id,$schoolId); query('UPDATE subjects SET name=?,color=? WHERE id=?',[$name,$color,$id]); }
                else query('INSERT INTO subjects(school_id,name,color) VALUES(?,?,?)',[$schoolId,$name,$color]);
                flash('success','Предмет збережено.'); break;
            case 'save_class':
                $account=require_role('secretary'); $tab='classes'; $schoolId=(int)$account['school_id']; $id=repo_id($_POST['id']??0);
                $name=input_text('name',60); $order=max(0,min(999,(int)($_POST['sort_order']??0)));
                if ($id) { assert_school_row('classes',$id,$schoolId); query('UPDATE classes SET name=?,sort_order=? WHERE id=?',[$name,$order,$id]); }
                else query('INSERT INTO classes(school_id,name,sort_order) VALUES(?,?,?)',[$schoolId,$name,$order]);
                flash('success','Клас збережено.'); break;
            case 'save_assignment':
                $account=require_role('secretary'); $tab='assignments'; $schoolId=(int)$account['school_id'];
                $teacher=assert_school_row('users',input_id('teacher_id'),$schoolId);
                if ($teacher['role']!=='teacher' || !(int)$teacher['is_active']) throw new InvalidArgumentException('Оберіть активного вчителя своєї школи.');
                $subject=assert_school_row('subjects',input_id('subject_id'),$schoolId); $class=assert_school_row('classes',input_id('class_id'),$schoolId);
                query('INSERT INTO teaching_assignments(school_id,teacher_id,subject_id,class_id) VALUES(?,?,?,?)',[$schoolId,$teacher['id'],$subject['id'],$class['id']]);
                flash('success','Предмет і клас призначено вчителю.'); break;
            case 'delete_assignment':
                $account=require_role('secretary'); $tab='assignments'; $id=input_id('id'); assert_school_row('teaching_assignments',$id,(int)$account['school_id']);
                if ((int)query('SELECT COUNT(*) FROM homework WHERE assignment_id=?',[$id])->fetchColumn()) throw new InvalidArgumentException('Призначення вже містить завдання. Історію навчання потрібно зберегти.');
                query('DELETE FROM teaching_assignments WHERE id=?',[$id]); flash('success','Призначення видалено.'); break;
            case 'save_homework':
                $account=require_role('teacher'); $back='editor'; $id=repo_id($_POST['id']??0);
                $assignment=assert_school_row('teaching_assignments',input_id('assignment_id'),(int)$account['school_id']);
                if ((int)$assignment['teacher_id']!==(int)$account['id']) throw new HttpError(403,'Ви можете додавати завдання лише до своїх предметів і класів.');
                if ($id) { $record=homework_row($id); if (!$record || (int)$record['teacher_id']!==(int)$account['id']) throw new HttpError(403,'Це завдання іншого вчителя.'); }
                $title=input_text('title',200); $body=clean_rich_html(input_text('body_html',60000));
                if (trim(html_entity_decode(strip_tags($body),ENT_QUOTES,'UTF-8'))==='') throw new InvalidArgumentException('Додайте текст домашнього завдання.');
                $published=date_value('published_date'); $due=date_value('due_date',false);
                if ($due && $due<$published) throw new InvalidArgumentException('Строк виконання не може передувати даті публікації.');
                if ($id) query('UPDATE homework SET assignment_id=?,title=?,body_html=?,published_date=?,due_date=? WHERE id=?',[$assignment['id'],$title,$body,$published,$due,$id]);
                else query('INSERT INTO homework(assignment_id,title,body_html,published_date,due_date) VALUES(?,?,?,?,?)',[$assignment['id'],$title,$body,$published,$due]);
                flash('success',$published>date('Y-m-d')?'Завдання збережено. Воно з’явиться у відкритому доступі у вказану дату.':'Домашнє завдання опубліковано.');
                redirect(route_url('dashboard',['tab'=>'homework']));
            case 'delete_homework':
                $account=require_role('teacher'); $id=input_id('id'); $record=homework_row($id);
                if (!$record || (int)$record['teacher_id']!==(int)$account['id'] || (int)$record['school_id']!==(int)$account['school_id']) throw new HttpError(403,'Ви можете видаляти лише власні завдання.');
                query('DELETE FROM homework WHERE id=?',[$id]); flash('success','Завдання видалено.'); $tab='homework'; break;
            case 'change_password':
                $account=require_role('admin','secretary','teacher'); $back='profile';
                $current=is_string($_POST['current_password']??null)?$_POST['current_password']:'';
                if (!$account['password_hash'] || !password_verify($current,$account['password_hash'])) throw new InvalidArgumentException('Поточний пароль неправильний. Якщо ви входите лише через Google, зверніться до уповноваженої особи школи.');
                query('UPDATE users SET password_hash=? WHERE id=?',[password_value(),$account['id']]); session_regenerate_id(true); flash('success','Пароль змінено.'); redirect(route_url('profile'));
            default: throw new HttpError(400,'Невідома дія.');
        }
        unset($_SESSION['old']); redirect(route_url('dashboard',['tab'=>$tab]));
    } catch (InvalidArgumentException | PDOException $exception) {
        if (db()->inTransaction()) db()->rollBack();
        if ($exception instanceof PDOException) {
            error_log('Database action failed: '.(string)$exception->getCode());
            $message=$exception->getCode()==='23000'?'Такий запис уже існує або пов’язаний з іншими даними. Перевірте пошту, назву та призначення.':'Не вдалося зберегти дані. Спробуйте ще раз або зверніться до адміністратора.';
        } else $message=$exception->getMessage();
        flash('error',$message);
        $_SESSION['old']=array_filter($_POST,static fn($key)=>!in_array($key,['password','current_password','setup_token','csrf'],true),ARRAY_FILTER_USE_KEY);
        $params=$back==='dashboard'?['tab'=>$tab]:($back==='editor'&&repo_id($_POST['id']??0)?['id'=>repo_id($_POST['id'])]:[]);
        redirect(route_url($back,$params));
    }
}

<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

$passed = 0;
function security_check(bool $condition, string $description): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException($description);
    }
    $passed++;
}
function security_rejects(callable $callback, string $description): void
{
    try {
        $callback();
    } catch (InvalidArgumentException | HttpError $exception) {
        security_check(true, $description);
        return;
    }
    throw new RuntimeException($description);
}

try {
    $clean = clean_rich_html('<p onclick="alert(1)">Завдання <strong>№ 12</strong></p><script>alert("bad")</script><svg onload="alert(1)"><circle /></svg><img src=x onerror="alert(1)">');
    security_check(str_contains($clean, '<strong>№ 12</strong>'), 'Корисне форматування повинно зберігатися.');
    security_check(!preg_match('/script|svg|onload|onclick|onerror|alert|<img/i', $clean), 'Активний вміст та обробники подій повинні видалятися.');
    foreach (['javascript:alert(1)', 'java&#x73;cript:alert(1)', 'data:text/html,hello', '//example.com/file'] as $href) {
        $clean = clean_rich_html('<a href="' . $href . '">Матеріали</a>');
        security_check(!str_contains($clean, 'href='), 'Небезпечне або відносне посилання повинно втратити href.');
        security_check(str_contains($clean, 'Матеріали'), 'Підпис посилання повинен зберігатися.');
    }
    $clean = clean_rich_html('<a href="https://drive.google.com/file/d/example/view?usp=sharing" style="color:red">Google Drive</a>');
    security_check(str_contains($clean, 'href="https://drive.google.com/'), 'HTTPS-посилання повинно зберігатися.');
    security_check(str_contains($clean, 'rel="noopener noreferrer"'), 'Зовнішнє посилання повинно мати безпечний rel.');
    security_check(!str_contains($clean, 'style='), 'Довільні стилі повинні видалятися.');
    $clean = clean_rich_html('<div><h2>Повторення</h2><ol><li data-list="bullet" class="foo">Розділ 2</li></ol><!--secret--></div>');
    security_check(str_contains($clean, '<h2>Повторення</h2>') && str_contains($clean, 'data-list="bullet"'), 'Структура редактора повинна зберігатися.');
    security_check(!str_contains($clean, '<div') && !str_contains($clean, '<!--') && !str_contains($clean, 'class='), 'Непідтримувані обгортки, коментарі та атрибути повинні видалятися.');
    security_rejects(static fn() => clean_rich_html(str_repeat('x', 60001)), 'Завеликий текст повинен відхилятися.');
    security_check(e('<script>"&\'</script>') === '&lt;script&gt;&quot;&amp;&#039;&lt;/script&gt;', 'Текстовий HTML-вивід повинен екрануватися.');
    security_check(safe_color('red; background:url(https://example.com)') === '#38765b', 'CSS-ін’єкція повинна відхилятися.');
    security_check(safe_color('#Ab12Cd') === '#Ab12Cd', 'Коректний HEX-колір повинен прийматися.');

    $_POST = ['email' => ' TEST.TEACHER@GMAIL.COM '];
    security_check(email_value('email', true) === 'test.teacher@gmail.com', 'Адреса повинна нормалізуватися.');
    $_POST = ['email' => 'teacher@example.com'];
    security_rejects(static fn() => email_value('email', true), 'Для Gmail-входу потрібна адреса gmail.com.');
    $_POST = ['name' => ['unexpected']];
    security_rejects(static fn() => input_text('name'), 'Масив замість тексту повинен відхилятися.');
    $_POST = ['id' => '-1'];
    security_rejects(static fn() => input_id('id'), 'Від’ємний ідентифікатор повинен відхилятися.');
    $_POST = ['published_date' => '2026-02-30'];
    security_rejects(static fn() => date_value('published_date'), 'Неіснуюча дата повинна відхилятися.');
    $_POST = ['password' => 'short'];
    security_rejects(static fn() => password_value(), 'Короткий пароль повинен відхилятися.');
    $_POST = ['password' => 'Test-password-123'];
    security_check(password_verify('Test-password-123', (string) password_value()), 'Пароль повинен зберігатися як перевірюваний хеш.');

    $_SESSION = [];
    $csrf = csrf_token();
    $_POST = ['csrf' => $csrf];
    verify_csrf();
    security_check(strlen($csrf) === 64, 'CSRF-токен повинен бути випадковим 256-бітним значенням.');
    $_POST = ['csrf' => 'wrong'];
    security_rejects(static fn() => verify_csrf(), 'Неправильний CSRF-токен повинен відхилятися.');
    $_POST = [];
    security_rejects(static fn() => verify_csrf(), 'Відсутній CSRF-токен повинен відхилятися.');
    $filters = public_filters(['school' => ['1'], 'class' => '-5', 'page' => '0', 'q' => str_repeat('я', 150)]);
    security_check($filters['school'] === 0 && $filters['class'] === 0 && $filters['page'] === 1 && mb_strlen($filters['q']) === 100, 'Пошукові фільтри повинні нормалізуватися.');
    echo "OK: $passed перевірок безпеки.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n");
    exit(1);
}

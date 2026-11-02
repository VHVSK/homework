<?php
declare(strict_types=1);

/** Google authorization-code login. Tokens never leave the server or enter the database. */
function google_configured(): bool
{
    return trim((string) config('google_client_id', '')) !== ''
        && trim((string) config('google_client_secret', '')) !== ''
        && extension_loaded('curl');
}

function google_base64url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function google_login_failed(string $message): never
{
    flash('error', $message);
    redirect(url('index.php', ['r' => 'login']));
}

function google_start(): never
{
    if (!google_configured()) {
        google_login_failed('Вхід через Google ще не налаштовано. Зверніться до адміністратора сайту.');
    }

    $baseUrl = (string) config('base_url', '');
    $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
    if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https'
        && !in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
        google_login_failed('Для входу через Google сайт повинен використовувати HTTPS.');
    }

    $state = google_base64url(random_bytes(32));
    $verifier = google_base64url(random_bytes(64));
    $_SESSION['google_oauth'] = [
        'state' => $state,
        'verifier' => $verifier,
        'created_at' => time(),
    ];

    $parameters = [
        'client_id' => (string) config('google_client_id'),
        'redirect_uri' => url('index.php', ['r' => 'google-callback']),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'code_challenge' => google_base64url(hash('sha256', $verifier, true)),
        'code_challenge_method' => 'S256',
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];
    redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
}

/** @return array<string, mixed> */
function google_http_json(string $endpoint, ?array $post = null, array $headers = []): array
{
    // Keep the destination fixed: no redirect or user-supplied URL can receive credentials.
    if (!in_array($endpoint, [
        'https://oauth2.googleapis.com/token',
        'https://openidconnect.googleapis.com/v1/userinfo',
    ], true)) {
        throw new RuntimeException('Unsupported Google endpoint.');
    }

    $handle = curl_init($endpoint);
    if ($handle === false) {
        throw new RuntimeException('Unable to initialize Google request.');
    }
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ];
    if ($post !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($post, '', '&', PHP_QUERY_RFC3986);
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
    }
    curl_setopt_array($handle, $options);
    try {
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if (!is_string($body) || $status !== 200 || strlen($body) > 131072) {
            throw new RuntimeException('Google request failed.');
        }
        $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || array_is_list($data)) {
            throw new RuntimeException('Unexpected Google response.');
        }
        return $data;
    } finally {
        // CurlHandle is released automatically on PHP 8; curl_close is deprecated on 8.5.
        unset($handle);
    }
}

function google_callback(): never
{
    $request = $_SESSION['google_oauth'] ?? null;
    // Consume before the network request so a callback cannot be replayed.
    unset($_SESSION['google_oauth']);
    $state = $_GET['state'] ?? null;
    $code = $_GET['code'] ?? null;
    if (!is_array($request) || !is_string($state)
        || !is_string($request['state'] ?? null)
        || !hash_equals($request['state'], $state)
        || !is_string($request['verifier'] ?? null)
        || !is_int($request['created_at'] ?? null)
        || $request['created_at'] > time()
        || time() - $request['created_at'] > 600) {
        google_login_failed('Спроба входу застаріла або не підтверджена. Натисніть «Увійти через Google» ще раз.');
    }
    if (isset($_GET['error'])) {
        google_login_failed('Вхід через Google скасовано. Ви можете спробувати ще раз.');
    }
    if (!google_configured() || !is_string($code) || $code === '' || strlen($code) > 4096) {
        google_login_failed('Не вдалося підтвердити вхід через Google. Спробуйте ще раз.');
    }

    try {
        $tokens = google_http_json('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('google_client_id'),
            'client_secret' => (string) config('google_client_secret'),
            'redirect_uri' => url('index.php', ['r' => 'google-callback']),
            'code' => $code,
            'code_verifier' => $request['verifier'],
            'grant_type' => 'authorization_code',
        ]);
        $accessToken = $tokens['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === ''
            || strlen($accessToken) > 8192 || preg_match('/[\r\n]/', $accessToken)) {
            throw new RuntimeException('Google did not issue an access token.');
        }

        // Identity is obtained from Google's HTTPS UserInfo response, never by
        // decoding an unverified JWT. The access token came from this client's
        // session-bound code exchange and is used only against Google's endpoint.
        $profile = google_http_json('https://openidconnect.googleapis.com/v1/userinfo', null, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        $email = is_string($profile['email'] ?? null) ? strtolower(trim($profile['email'])) : '';
        $subject = $profile['sub'] ?? null;
        if (($profile['email_verified'] ?? false) !== true
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !str_ends_with($email, '@gmail.com')
            || !is_string($subject) || $subject === '' || strlen($subject) > 255
            || preg_match('/[\x00-\x20\x7f]/', $subject)) {
            google_login_failed('Потрібен підтверджений Gmail-акаунт учителя, який додала уповноважена особа школи.');
        }
    } catch (Throwable $exception) {
        // Never expose Google's response, an access token, or the client secret.
        google_login_failed('Сервіс входу Google тимчасово недоступний. Спробуйте пізніше.');
    }

    $connection = db();
    try {
        $connection->beginTransaction();
        $query = $connection->prepare(
            'SELECT u.* FROM users u INNER JOIN schools s ON s.id = u.school_id '
            . "WHERE u.email = :email AND u.role = 'teacher' AND u.is_active = 1 AND s.is_active = 1 FOR UPDATE"
        );
        $query->execute(['email' => $email]);
        $teacher = $query->fetch(PDO::FETCH_ASSOC);
        if (!$teacher) {
            throw new DomainException('Teacher is not invited or is inactive.');
        }
        $boundSubject = $teacher['google_sub'] ?? null;
        if (is_string($boundSubject) && $boundSubject !== '') {
            if (!hash_equals($boundSubject, $subject)) {
                throw new DomainException('A different Google identity is already linked.');
            }
        } else {
            $update = $connection->prepare('UPDATE users SET google_sub = :subject WHERE id = :id AND google_sub IS NULL');
            $update->execute(['subject' => $subject, 'id' => $teacher['id']]);
            if ($update->rowCount() !== 1) {
                throw new DomainException('Google identity could not be linked.');
            }
            $teacher['google_sub'] = $subject;
        }
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        google_login_failed('Доступ не надано. Попросіть уповноважену особу школи перевірити ваш Gmail і стан облікового запису.');
    }

    login_user($teacher);
    flash('success', 'Ви увійшли через Google.');
    redirect(url('index.php', ['r' => 'dashboard']));
}

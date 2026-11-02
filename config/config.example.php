<?php

declare(strict_types=1);

// Copy to config.php. This directory must never be publicly accessible.
return [
    'app_name' => 'Після дзвоника',
    // Browser URL: https://your-domain.example when the domain opens the application directly.
    // Add /public only when that directory is intentionally part of the browser URL.
    'base_url' => 'http://localhost/HW/public',
    'timezone' => 'Europe/Kyiv',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'homework',
    'db_user' => 'root',
    'db_password' => '',
    // Generate: php -r "echo bin2hex(random_bytes(32));"
    'setup_token' => '',
    'google_client_id' => '',
    'google_client_secret' => '',
];

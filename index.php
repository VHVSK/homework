<?php
declare(strict_types=1);
// Support hosts that expose the project root as well as the recommended public/ root.
// Render the same application directly, without redirecting visitors to /public/.
define('APP_ENTRY_ASSET_PATH', 'public/assets/');
require __DIR__ . '/public/index.php';

<?php

declare(strict_types=1);

$settings = [
    'tmp' => __DIR__ . '/../var',
];

if (getenv('APP_ENV') === 'local') {
    define('APP_ENV', 'local');
    $settings = array_replace($settings, include __DIR__ . '/settings-local.php');
} elseif (getenv('APP_ENV') === 'staging') {
    define('APP_ENV', 'staging');
    $settings = array_replace($settings, include __DIR__ . '/settings-staging.php');
} else {
    define('APP_ENV', 'production');
    $settings = array_replace($settings, include __DIR__ . '/settings-production.php');
}

$filename = __DIR__ . '/../.env.php';
if (is_readable($filename)) {
    $more = include($filename);
    if (is_array($more)) {
        $settings = array_replace($settings, $more);
    }
}

return $settings;

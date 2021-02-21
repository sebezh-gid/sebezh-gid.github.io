<?php

declare(strict_types=1);

$settings = [
    'home.redirect' => '/wiki?name=%D0%92%D0%B2%D0%B5%D0%B4%D0%B5%D0%BD%D0%B8%D0%B5',

    'pdo.dsn' => 'mysql:dbname=sebezh_gid',
    'pdo.user' => 'sebgid',
    'pdo.password' => null,

    'template.path' => __DIR__ . '/../templates',

    'tmp' => __DIR__ . '/../var',

    'wiki.main-page' => 'Введение',
];


$_appenv = getenv('APP_ENV');
if ($_appenv === false) {
    $_appenv = 'local';
}

if ($_appenv === 'local') {
    define('APP_ENV', 'local');
    $settings = array_replace($settings, include __DIR__ . '/settings-local.php');
} elseif ($_appenv === 'staging') {
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

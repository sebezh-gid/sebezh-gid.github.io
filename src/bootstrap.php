<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('REQUEST_START_TS', microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

App\App::run();

<?php

declare(strict_types=1);

define('REQUEST_START_TS', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

App\App::run();

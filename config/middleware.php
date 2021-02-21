<?php

declare(strict_types=1);

$app->add(\App\Middleware\CachingMiddleware::class);
$app->add(new \App\Middleware\ErrorMiddleware($app->getResponseFactory()));

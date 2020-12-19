<?php

/**
 * Add middleware helpers.
 *
 * WARNING!  Executed in reverse order, last in -- first out.
 * So, middleware that converts exceptions to responses, must
 * be the first one.
 *
 * @param Slim\App $app Application to add the middleware to.
 * @param App\Config $config Configuration container.
 **/

declare(strict_types=1);

$app->add(
    new \App\Errors\ErrorMiddleware($config, $app->getResponseFactory())
);

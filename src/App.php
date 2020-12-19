<?php

declare(strict_types=1);

namespace App;

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

class App
{
    public static function run(): void
    {
        include __DIR__ . '/functions.php';

        $cb = new ContainerBuilder();
        $cb->useAnnotations(false);
        $cb->addDefinitions(include __DIR__ . '/../config/dependencies.php');
        $container = $cb->build();

        $config = $container->get('App\\Helpers\\Config');

        $app = \DI\Bridge\Slim\Bridge::create($container);

        // self::enableRouteCache($app);

        if (file_exists($fn = __DIR__ . '/../config/middleware.php')) {
            require $fn;
        }

        if (file_exists($fn = __DIR__ . '/../config/routes.php')) {
            require $fn;
        }

        $app->run();
    }

    protected static function enableRouteCache(\Slim\App $app): void
    {
        $fn = __DIR__ . '/../var/route_cache';

        $rc = $app->getRouteCollector();
        $rc->setCacheFile($fn);
    }
}

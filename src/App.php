<?php

declare(strict_types=1);

namespace App;

use DI\Container;
use DI\ContainerBuilder;
use RuntimeException;
use Slim\Factory\AppFactory;

class App
{
    public static function run(): void
    {
        $cb = new ContainerBuilder();
        //$cb->useAutowiring(false);
        $cb->useAnnotations(false);
        $cb->addDefinitions(self::getDependencies());
        $container = $cb->build();

        $config = $container->get('App\\Config');

        $app = \DI\Bridge\Slim\Bridge::create($container);

        // self::enableRouteCache($app);

        if (file_exists($fn = __DIR__ . '/../config/middleware.php')) {
            require $fn;
        }

        if (file_exists($fn = __DIR__ . '/../config/routes.php')) {
            require $fn;
        } else {
            throw new RuntimeException('route config not found');
        }

        $app->run();
    }

    protected static function enableRouteCache(\Slim\App $app): void
    {
        $fn = __DIR__ . '/../var/route_cache';

        $rc = $app->getRouteCollector();
        $rc->setCacheFile($fn);
    }

    protected static function getDependencies(): array
    {
        $fn = __DIR__ . '/../config/dependencies.php';
        if (!file_exists($fn)) {
            throw new \RuntimeException('dependencies not set');
        }

        return include $fn;
    }
}

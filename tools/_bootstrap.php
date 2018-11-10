<?php

function do_cli($path)
{
    chdir(dirname(__DIR__));
    require "./vendor/autoload.php";

    $settings = require __DIR__ . '/../src/settings.php';
    $settings["debug"] = false;
    $app = new Slim\App($settings);

    // Set up dependencies
    require __DIR__ . '/../src/dependencies.php';

    // Register middleware
    require __DIR__ . '/../src/middleware.php';

    // Register routes
    require __DIR__ . '/../src/routes.php';

    $environment = Slim\Http\Environment::mock([
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI" => $path,
    ]);

    $request = \Slim\Http\Request::createFromEnvironment($environment);

    $app->getContainer()["request"] = $request;

    $app->run();
}

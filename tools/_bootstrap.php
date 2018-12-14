<?php

function do_cli($path, array $args = [])
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

    $request_uri = $path;
    $query_string = "";

    if ($args) {
        $qs = [];

        foreach ($args as $arg)
            $qs[] = urlencode($arg[0]) . '=' . urlencode($arg[1]);

        $query_string = implode("&", $qs);
        $request_uri .= "?" . $query_string;
    }

    $environment = Slim\Http\Environment::mock([
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI" => $request_uri,
        "QUERY_STRING" => $query_string,
    ]);

    $request = \Slim\Http\Request::createFromEnvironment($environment);

    $app->getContainer()["request"] = $request;

    $app->run();
}

<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

$container['template'] = function ($c) {
    $settings = $c->get('settings')['templates'];
    $tpl = new \App\Template($c);
    return $tpl;
};

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $twig = $c->get("template");
        $html = $twig->render("notfound.twig");
        $response->getBody()->write($html);
        return $response->withStatus(404);
    };
};

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $e) use ($c) {
        $tpl = "error.twig";
        $status = 500;
        $data = [];
        $data["path"] = $request->getUri()->getPath();

        if ($e instanceof \App\Errors\Unauthorized) {
            $tpl = "unauthorized.twig";
            $status = 401;
        }

        $twig = $c->get("template");
        $html = $twig->render($tpl, $data);
        $response->getBody()->write($html);
        return $response->withStatus($status);
    };
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};


// database
$container['database'] = function ($c) {
    return new \App\Database($c->get("settings")["dsn"]);
};


function debug()
{
    while (ob_get_level())
        ob_end_clean();

    header("HTTP/1.0 503 Debug");
    header("Content-Type: text/plain; charset=utf-8");
    call_user_func_array("var_dump", func_get_args());
    print "---\n";

    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $stack = ob_get_clean();
    $stack = str_replace(dirname(__DIR__) . "/", "", $stack);
    print $stack;

    die();
}

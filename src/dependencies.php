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

$container['sphinx'] = function ($c) {
    $settings = $c->get('settings')['sphinx'];
    return new \App\Sphinx($settings, $c->get('database'));
};

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $twig = $c->get("template");
        return $twig->render("notfound.twig")
            ->withStatus(404);
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

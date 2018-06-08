<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
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
$container['database'] = new \Wiki\Database($container);


function debug()
{
    while (ob_get_level())
        ob_end_clean();

    header("HTTP/1.0 503 Debug");
    header("Content-Type: text/plain; charset=utf-8");
    call_user_func_array("var_dump", func_get_args());
    print "---\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    die();
}

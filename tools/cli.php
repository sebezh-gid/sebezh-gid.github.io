<?php

if (PHP_SAPI != "cli" or count($argv) < 2) {
    die("Usage: php -f tools/cli.php command\n");
}

require __DIR__ . "/_bootstrap.php";

$action = $argv[1];

$args = array_filter(array_map(function ($arg) {
    if (preg_match('@^--([^=]+)=(.+)$@', $arg, $m)) {
        return [$m[1], $m[2]];
    }
}, $argv));

do_cli("/cli/" . urlencode($action), $args);

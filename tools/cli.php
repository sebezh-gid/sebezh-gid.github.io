<?php

if (PHP_SAPI != "cli" or count($argv) != 2) {
    die("Usage: php -f tools/cli.php command\n");
}

require __DIR__ . "/_bootstrap.php";

$action = $argv[1];
do_cli("/cli/" . urlencode($action));

<?php
/**
 * Router hack for the PHP built in web server, which we use for development.
 ***/

/**
 * The built in server mishandles paths with dots.
 * We need this hack to be able to handle routes like </files/example.png>.
 * See https://stackoverflow.com/a/32098723/371526
 **/

if (PHP_SAPI == "cli-server") {
    $url = parse_url($_SERVER["REQUEST_URI"]);
    $file = __DIR__ . $url["path"];
    if (is_file($file))
        return false;
}

$_SERVER["SCRIPT_NAME"] = "index.php";
include "index.php";

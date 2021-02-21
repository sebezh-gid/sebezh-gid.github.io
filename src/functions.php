<?php

declare(strict_types=1);

function dd(): void
{
    header('Content-Type: text/plain');

    $args = func_get_args();
    var_dump($args);

    echo "---\n";

    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    die();
}

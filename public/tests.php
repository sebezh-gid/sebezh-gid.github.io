<?php

function fail($message)
{
    header("Content-Type: text/plain; charset=utf-8");
    header("Content-Length: " . strlen($message));
    die($message);
}


function debug()
{
    $args = func_get_args();
    ob_start();
    call_user_func_array("var_dump", $args);
    $text = ob_get_clean();
    fail($text);
}


function test_code_write()
{
    $code = file_get_contents(__FILE__);
    $res = @file_put_contents(__FILE__, $code);
    if ($res !== false) {
        fail("PHP code is writable.");
    }
}


function test_temp_dir()
{
    if (!($dir = sys_get_temp_dir()))
        fail("ini: sys_temp_dir not set.");

    if (!is_writable($dir))
        fail("sys_temp_dir {$dir} is not writable.");
}


function test_error_log()
{
    $fn = ini_get("error_log");

    if (file_exists($fn) and is_file($fn) and is_writable($fn))
        return;

    $dir = dirname($fn);
    if (file_exists($dir) and is_dir($dir) and is_writable($dir))
        return;

    fail("error_log {$fn} is not writable.");
}


function test_mbstring()
{
    $enc = mb_internal_encoding();
    if (empty($enc))
        fail("mbstring.internal_encoding not set");
}


function test_pdo()
{
    if (!extension_loaded("pdo"))
        fail("PDO not loaded.");
}


test_code_write();
test_temp_dir();
test_error_log();
test_mbstring();
test_pdo();

fail("Works well.");

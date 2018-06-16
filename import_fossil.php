<?php
/**
 * Fossil SCM wiki import script.
 **/
require __DIR__ . "/vendor/autoload.php";

if (count($argv) < 2) {
    printf("Usage: php -f %s filename.fossil\n", $argv[0]);
    exit(1);
} else {
    $repo = $argv[1];
    if (!file_exists($repo)) {
        printf("File %s does not exist.\n", $repo);
        exit(1);
    }
}

$settings = require __DIR__ . "/src/settings.php";
$db = new \Wiki\Database($settings["settings"]["dsn"]);

$out = run("fossil wiki -R %s list", escapeshellarg($repo));
foreach (explode("\n", $out) as $name) {
    $name = trim($name);
    if (empty($name))
        continue;

    $text = run("fossil wiki -R %s export %s", escapeshellarg($repo), escapeshellarg($name));

    $db->updatePage($name, $text);
    printf("Page \"%s\" updated.\n", $name);
}


function run()
{
    $args = func_get_args();
    $command = call_user_func_array("sprintf", $args);

    ob_start();
    system($command);
    return ob_get_clean();
}

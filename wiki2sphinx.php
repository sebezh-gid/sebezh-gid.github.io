<?php
/**
 * wiki2sphinx.php -- dump the wiki to format understood by Sphinx Search.
 *
 * See http://sphinxsearch.com/docs/current/xmlpipe2.html
 **/

require __DIR__ . "/vendor/autoload.php";

$settings = include __DIR__ . "/src/settings.php";
$db = new \Wiki\Database($settings["settings"]["dsn"]);

print "<?xml version='1.0' encoding='utf-8'?".">\n";
print "<sphinx:docset>\n";
print "<sphinx:schema>\n";
print "<sphinx:field name='title'/>\n";
print "<sphinx:field name='content'/>\n";
print "<sphinx:attr name='created' type='timestamp'/>\n";
print "<sphinx:attr name='updated' type='timestamp'/>\n";
print "</sphinx:schema>\n";

$pages = $db->listPages();
foreach ($pages as $page) {
    $page = $db->getPageByName($page["name"]);

    list($props, $html) = \Wiki\Parser::parse($page["name"], $page["source"]);
    $title = $props["title"];

    print "<sphinx:document id='{$page["id"]}'>\n";
    print "<title>" . htmlspecialchars($title) . "</title>";
    print "<content>" . htmlspecialchars($html) . "</content>\n";
    print "<created>{$page["created"]}</created>\n";
    print "<updated>{$page["updated"]}</updated>\n";
    print "</sphinx:document>\n";
}

print "</sphinx:docset>\n";

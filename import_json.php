<?php

$db = new PDO("sqlite:database.sqlite");
$sth = $db->prepare("INSERT INTO `pages` (`name`, `source`, `created`, `updated`) VALUES (?, ?, ?, ?)");

$now = time();

$data = json_decode(file_get_contents("pages.json"), true);
foreach ($data as $name => $text) {
    $sth->execute(array($name, $text, $now, $now));
}

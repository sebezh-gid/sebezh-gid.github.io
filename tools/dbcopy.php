<?php

if (count($argv) != 3) {
    $prog = basename($argv[0]);
    printf("Usage: php -f %s dsn_1 dsn_2\n", $prog);
    printf("Example: php -f %s sqlite:data/database.sqlite mysql://user:pass@localhost/dbname\n", $prog);
    exit(1);
}

$src = connect($argv[1]);
$dst = connect($argv[2]);

$tables = list_tables($src);
foreach ($tables as $table) {
    $dh = null;

    printf("Copying table %s...\n", $table);
    $dst->query("DELETE FROM `{$table}`");

    $sh = $src->query("SELECT * FROM `{$table}`");
    while ($row = $sh->fetch(PDO::FETCH_ASSOC)) {
        if ($dh == null) {
            $fields = array_keys($row);
            $marks = implode(", ", array_pad([], count($fields), "?"));
            $query = "INSERT INTO `{$table}` VALUES ({$marks})";
            $dh = $dst->prepare($query);
        }

        $dh->execute(array_values($row));
    }
}

printf("Done.\n");


function connect($dsn)
{
    $url = parse_url($dsn);
    if (empty($url["scheme"])) {
        printf("DSN without scheme: %s\n", $dsn);
        exit(1);
    }

    if ($url["scheme"] == "sqlite") {
        $pdo = new PDO($dsn, null, null);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
    }

    elseif ($url["scheme"] == "mysql") {
        $dsn = "mysql:dbname=" . substr($url["path"], 1);
        $pdo = new PDO($dsn, @$url["user"], @$url["pass"]);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->query("SET NAMES utf8");
    }

    return $pdo;
}


function list_tables($db)
{
    $type = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($type == "sqlite") {
        $sth = $db->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' ORDER BY `name`");
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($row) {
            return $row["name"];
        }, $rows);
    }

    elseif ($type == "mysql") {
        $sth = $db->query("SHOW TABLES");
        $rows = $sth->fetchAll();
        return array_map(function ($row) {
            return $row[0];
        }, $rows);
    }
}

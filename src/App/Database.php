<?php

namespace App;

class Database {
    /**
     * Return page contents by its name.
     *
     * @param string $name Page name;
     * @return array|false Page description.
     **/
    public static function getPageByName($name)
    {
        $res = self::dbFetchOne("SELECT `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `name` = ?", array($name));
        return $res;
    }

    public static function updatePage($name, $text)
    {
        $now = time();

        $html = \App\Template::renderFile("page.twig", array(
            "page_name" => $name,
            "page_text" => $text,
            ));

        $stmt = self::dbQuery("UPDATE `pages` SET `source` = ?, `html` = ?, `updated` = ? WHERE `name` = ?", array($text, $html, $now, $name));
        if ($stmt->rowCount() == 0) {
            self::dbQuery("INSERT INTO `pages` (`name`, `source`, `html`, `created`, `updated`) VALUES (?, ?, ?, ?, ?)", array($name, $text, $html, $now, $now));
        }
    }

    /**
     * Connect to the database.
     *
     * @return PDO Database connection.
     **/
    protected static function connect()
    {
        static $conn = null;

        if (is_null($conn)) {
            $dsn = "sqlite:" . $_SERVER["DOCUMENT_ROOT"] . "/database.sqlite";

            $conn = new \PDO($dsn);

            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        }

        return $conn;
    }

    protected static function fetch($query, array $params = array())
    {
        $sth = self::connect()->prepare($query);
        $sth->execute($params);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected static function dbFetchOne($query, array $params = array())
    {
        $sth = self::connect()->prepare($query);
        $sth->execute($params);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    protected static function dbQuery($query, array $params = array())
    {
        $sth = self::connect()->prepare($query);
        $sth->execute($params);
        return $sth;
    }
}

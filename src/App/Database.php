<?php
/**
 * Database interface class.
 *
 * This class has public methods that do the work directly: read pages,
 * update tables, etc.  Methods usually return arrays of data, or accept
 * arrays of data to store or update the database with.
 *
 * No active records, cursors or other stuff.  Just a PDO wrapper.
 *
 * This class should be accessed by handlers (based on \App\Handler)
 * using $this->db.
 **/

namespace App;

class Database {
    /**
     * Data source name (connection information).
     * An array of connection info in PDO format, keys: name, user, password.
     **/
    protected $dsn;

    /**
     * PDO instance.
     **/
    protected $conn;

    /**
     * Prepares the database connection.
     *
     * Does not actually connect.  This method is usually called during the application setup
     * process, when exception handling might not yet have been configured.  We'll connect later,
     * in a lazy manner.
     *
     * @param Container $container We extract settings from this.
     **/
    public function __construct(array $dsn)
    {
        $this->conn = null;
        $this->dsn = $dsn;
    }

    /**
     * Return page contents by its name.
     *
     * @param string $name Page name;
     * @return array|false Page description.
     **/
    public function getPageByName($name)
    {
        $res = $this->fetch("SELECT `id`, `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `name` = ?", array($name));
        return $res;
    }

    /**
     * Return page contents by its id.
     *
     * This is only for use by Sphinx Search, not for general use.
     *
     * @param string $name Page name;
     * @return array|false Page description.
     **/
    public function getPageById($id)
    {
        $res = $this->fetch("SELECT `id`, `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `id` = ?", [$id]);
        return $res;
    }

    public function updatePageHtml($pageName, $html)
    {
        // Do not cache pages with broken links (presumably still editing).
        if (preg_match('@"wiki broken"@', $html))
            return;

        $this->query("UPDATE `pages` SET `html` = ? WHERE `name` = ?", array($html, $pageName));
    }

    public function getAllPageNames()
    {
        $res = array();

        $rows = $this->fetch("SELECT `name` FROM `pages` ORDER BY `name`");
        foreach ($rows as $row)
            $res[] = $row["name"];

        return $res;
    }

    public function listPages($sort = null)
    {
        if ($sort == "length")
            return $this->fetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `length` DESC");
        elseif ($sort == "updated")
            return $this->fetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `updated` DESC");
        else
            return $this->fetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `name`");
    }

    /**
     * Save a file in the database.
     **/
    public function saveFile(array $fileInfo)
    {
        return $this->insert("files", [
            "name" => $fileInfo["name"],
            "real_name" => $fileInfo["real_name"],
            "type" => $fileInfo["type"],
            "length" => $fileInfo["length"],
            "created" => $fileInfo["created"],
            "uploaded" => time(),
            "body" => $fileInfo["body"],
            "hash" => md5($fileInfo["body"]),
        ]);
    }

    /**
     * Returns an array of all uploaded files.
     **/
    public function findFiles()
    {
        $rows = $this->fetch("SELECT `id`, `name`, `real_name`, `type`, `length`, `created`, `hash` FROM `files` ORDER BY `created`");
        return $rows;
    }

    /**
     * Returns file data by name.
     * This is how you normally access individual files.
     **/
    public function getFileByName($name)
    {
        $rows = $this->fetch("SELECT * FROM `files` WHERE `name` = ? OR `hash` = ?", [$name, $name]);
        return count($rows) > 0 ? $rows[0] : null;
    }

    /**
     * Returns file data by contents hash.
     * This is used for deduplication during upload.
     **/
    public function getFileByHash($hash)
    {
        $rows = $this->fetch("SELECT * FROM `files` WHERE `hash` = ?", [$hash]);
        return count($rows) > 0 ? $rows[0] : null;
    }

    public function shortAdd($russian, $english, $link)
    {
        $code = rand(1001, 9999);
        $date = strftime("%Y-%m-%d %H:%M:%S");

        $this->query("INSERT INTO `shorts` (`id`, `created`, `name1`, `name2`, `link`) VALUES (?, ?, ?, ?, ?)", [$code, $date, $russian, $english, $link]);

        return $code;
    }

    public function shortGetName($code)
    {
        $row = $this->fetch("SELECT `name` FROM `shorts` WHERE `id` = ?", [$code]);
        return $row ? $row["name"] : null;
    }

    public function shortGetCode($name)
    {
        $row = $this->fetch("SELECT `id` FROM `shorts` WHERE `name` = ?", [$name]);
        return isset($row["id"]) ? (int)$row["id"] : null;
    }

    public function shortsGetByCode($code)
    {
        return $this->fetch("SELECT * FROM `shorts` WHERE `id` = ?", [$code]);
    }

    public function shortsGetRecent()
    {
        return $this->fetch("SELECT * FROM `shorts` ORDER BY `created` DESC LIMIT 100");
    }

    public function sessionGet($id)
    {
        $row = $this->fetchOne("SELECT `data` FROM `sessions` WHERE `id` = ?", [$id]);
        return $row ? unserialize($row["data"]) : null;
    }

    public function sessionSave($id, array $data)
    {
        $updated = strftime("%Y-%m-%d %H:%M:%S");

        $this->query("REPLACE INTO `sessions` (`id`, `updated`, `data`) VALUES (?, ?, ?)", [$id, $updated, serialize($data)]);
    }

    public function accountGet($login)
    {
        return $this->fetch("SELECT * FROM `accounts` WHERE `login` = ?", [$login]);
    }

    public function filePut($name, $data)
    {
        $date = strftime("%Y-%m-%d %H:%M:%S");
        $this->query("REPLACE INTO `storage` (`updated`, `name`, `body`) VALUES (?, ?, ?)", [$date, $name, $data]);
    }

    public function fileGet($name)
    {
        return $this->fetch("SELECT `body` FROM `storage` WHERE `name` = ?", [$name]);
    }

    public function addFile($name, $type, $body)
    {
        $hash = md5($body);

        if ($old = $this->fetchOne("SELECT `id` FROM `files` WHERE `hash` = ?", [$hash]))
            return $old["id"];

        $kind = "other";
        if (0 === strpos($type, "image/"))
            $kind = "photo";
        elseif (0 === strpos($type, "video/"))
            $kind = "video";

        $now = time();

        $id = $this->insert("files", [
            "name" => $name,
            "real_name" => $name,
            "type" => $type,
            "kind" => $kind,
            "length" => strlen($body),
            "created" => $now,
            "uploaded" => $now,
            "body" => $body,
            "hash" => $hash,
        ]);

        return $id;
    }

    /**
     * Connect to the database.
     *
     * @return PDO Database connection.
     **/
    protected function connect()
    {
        if (is_null($this->conn)) {
            if (!is_array($this->dsn))
                throw new \RuntimeException("database not configured");
            $this->conn = new \PDO($this->dsn["name"], $this->dsn["user"], $this->dsn["password"]);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

            $type = $this->conn->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($type == "mysql") {
                $this->conn->query("SET NAMES utf8");
            }
        }

        return $this->conn;
    }

    public function fetch($query, array $params = array(), $callback = null)
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if ($callback)
            $rows = array_map($callback, $rows);

        return $rows;
    }

    public function fetchOne($query, array $params = array())
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchkv($query, array $params = [])
    {
        $rows = $this->fetch($query, $params);

        $res = [];
        foreach ($rows as $row) {
            $row = array_values($row);
                $res[$row[0]] = $row[1];
        }

        return $res;
    }

    public function fetchcell($query, array $params = array())
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);

        return $sth->fetchColumn(0);
    }

    public function query($query, array $params = array())
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        return $sth;
    }

    public function insert($tableName, array $fields)
    {
        $_fields = [];
        $_marks = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_fields[] = "`{$k}`";
            $_params[] = $v;
            $_marks[] = "?";
        }

        $_fields = implode(", ", $_fields);
        $_marks = implode(", ", $_marks);

        $query = "INSERT INTO `{$tableName}` ({$_fields}) VALUES ({$_marks})";
        $sth = $this->query($query, $_params);

        return $this->conn->lastInsertId();
    }

    public function update($tableName, array $fields, array $where)
    {
        $_set = [];
        $_where = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_set[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        foreach ($where as $k => $v) {
            $_where[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        $_set = implode(", ", $_set);
        $_where = implode(" AND ", $_where);

        $query = "UPDATE `{$tableName}` SET {$_set} WHERE {$_where}";
        $sth = $this->query($query, $_params);
        return $sth->rowCount();
    }

    public function getConnectionType()
    {
        return $this->connect()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function beginTransaction()
    {
        $this->connect()->beginTransaction();
    }

    public function commit()
    {
        $this->connect()->commit();
    }

    public function rollback()
    {
        $this->connect()->rollback();
    }

    public function cacheSet($key, $value)
    {
        $now = time();
        $this->query("REPLACE INTO `cache` (`key`, `added`, `value`) VALUES (?, ?, ?)", [$key, $now, $value]);
    }

    public function cacheGet($key)
    {
        return $this->fetchCell("SELECT `value` FROM `cache` WHERE `key` = ?", [$key]);
    }
}

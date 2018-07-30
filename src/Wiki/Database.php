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
 * This class should be accessed by handlers (based on \Wiki\Handler)
 * using $this->db.
 **/

namespace Wiki;

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
        $res = $this->dbFetchOne("SELECT `id`, `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `name` = ?", array($name));
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
        $res = $this->dbFetchOne("SELECT `id`, `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `id` = ?", [$id]);
        return $res;
    }

    /**
     * Update page contents.
     *
     * Saves current revision in the history table.
     **/
    public function updatePage($name, $text)
    {
        $now = time();
        $html = null;

        // Back up current revision.
        $this->dbQuery("INSERT INTO `history` (`name`, `source`, `created`) SELECT `name`, `source`, `updated` FROM `pages` WHERE `name` = ?", [$name]);

        $stmt = $this->dbQuery("UPDATE `pages` SET `source` = ?, `html` = ?, `updated` = ? WHERE `name` = ?", array($text, $html, $now, $name));
        if ($stmt->rowCount() == 0) {
            $this->dbQuery("INSERT INTO `pages` (`name`, `source`, `html`, `created`, `updated`) VALUES (?, ?, ?, ?, ?)", array($name, $text, $html, $now, $now));
        }
    }

    public function updatePageHtml($pageName, $html)
    {
        // Do not cache pages with broken links (presumably still editing).
        if (preg_match('@"wiki broken"@', $html))
            return;

        $this->dbQuery("UPDATE `pages` SET `html` = ? WHERE `name` = ?", array($html, $pageName));
    }

    public function getAllPageNames()
    {
        $res = array();

        $rows = $this->dbFetch("SELECT `name` FROM `pages` ORDER BY `name`");
        foreach ($rows as $row)
            $res[] = $row["name"];

        return $res;
    }

    public function listPages($sort = null)
    {
        if ($sort == "length")
            return $this->dbFetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `length` DESC");
        elseif ($sort == "updated")
            return $this->dbFetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `updated` DESC");
        else
            return $this->dbFetch("SELECT `id`, `name`, `created`, `updated`, LENGTH(`source`) AS `length` FROM `pages` ORDER BY `name`");
    }

    /**
     * Save a file in the database.
     **/
    public function saveFile(array $fileInfo)
    {
        $this->dbQuery("INSERT INTO `files` (`name`, `real_name`, `type`, `length`, `created`, `body`, `hash`) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
            $fileInfo["name"],
            $fileInfo["real_name"],
            $fileInfo["type"],
            $fileInfo["length"],
            $fileInfo["created"],
            $fileInfo["body"],
            md5($fileInfo["body"]),
            ));
    }

    /**
     * Returns an array of all uploaded files.
     **/
    public function findFiles()
    {
        $rows = $this->dbFetch("SELECT `id`, `name`, `real_name`, `type`, `length`, `created`, `hash` FROM `files` ORDER BY `created`");
        return $rows;
    }

    /**
     * Returns file data by name.
     * This is how you normally access individual files.
     **/
    public function getFileByName($name)
    {
        $rows = $this->dbFetch("SELECT * FROM `files` WHERE `name` = ?", [$name]);
        return count($rows) > 0 ? $rows[0] : null;
    }

    /**
     * Returns file data by contents hash.
     * This is used for deduplication during upload.
     **/
    public function getFileByHash($hash)
    {
        $rows = $this->dbFetch("SELECT * FROM `files` WHERE `hash` = ?", [$hash]);
        return count($rows) > 0 ? $rows[0] : null;
    }

    public function getThumbnail($name, $type)
    {
        $rows = $this->dbFetch("SELECT * FROM `thumbnails` WHERE `name` = ? AND `type` = ?", [$name, $type]);
        return $rows ? $rows[0] : null;
    }

    public function saveThumbnail($name, $type, $body)
    {
        $this->dbQuery("INSERT INTO `thumbnails` (`name`, `type`, `body`, `hash`) VALUES (?, ?, ?, ?)", [$name, $type, $body, md5($body)]);
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
        }

        return $this->conn;
    }

    protected function dbFetch($query, array $params = array(), $callback = null)
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function dbFetchOne($query, array $params = array())
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    protected function dbQuery($query, array $params = array())
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        return $sth;
    }
}

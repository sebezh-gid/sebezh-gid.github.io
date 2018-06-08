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
    public function __construct($container)
    {
        $this->conn = null;

        $this->dsn = $container->get("settings")["dsn"];
    }

    /**
     * Return page contents by its name.
     *
     * @param string $name Page name;
     * @return array|false Page description.
     **/
    public function getPageByName($name)
    {
        $res = $this->dbFetchOne("SELECT `name`, `source`, `html`, `created`, `updated` FROM `pages` WHERE `name` = ?", array($name));
        return $res;
    }

    public function updatePage($name, $text)
    {
        $now = time();

        $html = Template::renderPage($name, $text);

        $stmt = $this->dbQuery("UPDATE `pages` SET `source` = ?, `html` = ?, `updated` = ? WHERE `name` = ?", array($text, $html, $now, $name));
        if ($stmt->rowCount() == 0) {
            $this->dbQuery("INSERT INTO `pages` (`name`, `source`, `html`, `created`, `updated`) VALUES (?, ?, ?, ?, ?)", array($name, $text, $html, $now, $now));
        }
    }

    public function updatePageHtml($pageName, $html)
    {
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

    protected function dbFetch($query, array $params = array())
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

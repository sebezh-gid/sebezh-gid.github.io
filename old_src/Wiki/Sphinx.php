<?php
/**
 * Sphinx Search Engine connector.
 **/

namespace Wiki;

class Sphinx
{
    protected $conn;

    protected $index;

    protected $db;

    public function __construct(array $settings, $db)
    {
        $settings = array_merge([
            "host" => "127.0.0.1",
            "port" => 9306,
            "index" => "wiki",
        ], $settings);

        $this->conn = new \PDO("mysql:host={$settings["host"]};port={$settings["port"]}", null, null);
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        $this->index = $settings["index"];

        $this->db = $db;
    }

    public function search($query)
    {
        $res = $this->searchIds($query);
        return $res;
    }

    protected function searchIds($query)
    {
        $sth = $this->conn->prepare("SELECT * FROM `{$this->index}` WHERE MATCH (?)");
        $sth->execute([$query]);
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $res = array();
        foreach ($rows as $row) {
            $page = $this->db->getPageById($row["id"]);

            list($props, $html) = \Wiki\Parser::parse($page["name"], $page["source"]);
            $snip = $this->getSnippet($html, $query);

            $res[] = [
                "id" => $row["id"],
                "name" => $page["name"],
                "title" => $props["title"],
                "snippet" => $snip,
                "created" => $page["created"],
                "updated" => $page["updated"],
            ];
        }

        return $res;
    }

    /**
     * Returns search snippet for a document.
     *
     * Docs: http://sphinxsearch.com/docs/current.html#api-func-buildexcerpts
     *
     * @param string $source Document source.
     * @param string $query Search query.
     * @return string Display snippet.
     **/
    protected function getSnippet($source, $query)
    {
        $sth = $this->conn->prepare("CALL SNIPPETS(?, ?, ?, 10 AS around, 500 AS limit)");
        $sth->execute([$source, $this->index, $query]);
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC))
            return $row["snippet"];
    }
}

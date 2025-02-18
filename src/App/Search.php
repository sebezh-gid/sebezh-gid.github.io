<?php
/**
 * Full text search API.
 *
 * PLEASE.  No custom code here.  This should be easily reusable.
 *
 * To find something, use search().
 * To update an index entry, use reindexDocument().
 * To update the whole database, use reindexAll().
 **/

namespace App;

class Search
{
    protected $db;

    static private $stopWords = ["а", "и", "о", "об", "в", "на", "под", "из"];

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function search($query, $limit = 100)
    {
        // TODO: synonims
        $query = $this->normalizeText($query);

        switch ($this->db->getConnectionType()) {
            case "mysql":
                // https://dev.mysql.com/doc/refman/5.5/en/fulltext-boolean.html
                $query = "+" . str_replace(" ", " +", $query);
                $sql = "SELECT `key`, `meta`, MATCH(`title`) AGAINST (:query IN BOOLEAN MODE) * 10 AS `trel`, MATCH(`body`) AGAINST (:query IN BOOLEAN MODE) AS `brel` FROM `search` WHERE `key` NOT LIKE 'page:File:%' HAVING `trel` > 0 OR `brel` > 0 ORDER BY `trel` DESC, `brel` DESC LIMIT {$limit}";
                $params = [
                    ":query" => $query,
                ];
                break;
            case "sqlite":
                $sql = "SELECT `key`, `meta` FROM `search` WHERE `search` MATCH ? ORDER BY bm25(`search`, 0, 10) LIMIT {$limit}";
                $params = [$query];
            break;
        }

        $rows = $this->db->fetch($sql, $params, function ($em) {
            return [
                "key" => $em["key"],
                "meta" => unserialize($em["meta"]),
            ];
        });

        return $rows;
    }

    /**
     * Reindex a document.
     *
     * @param string $key Document key.
     * @param string $title Document title.
     * @param string $body Document body (plain text).
     * @param array $meta Extra data, optional.
     * @return void
     **/
    public function reindexDocument($key, $title, $body, array $meta = [])
    {
        $meta = $meta ? serialize($meta) : null;

        $this->db->query("DELETE FROM `search` WHERE `key` = ?", [$key]);

        if ($title and $body) {
            $title = $this->normalizeText($title);
            $body = $this->normalizeText($body);
            $this->db->query("INSERT INTO `search` (`key`, `meta`, `title`, `body`) VALUES (?, ?, ?, ?)", [$key, $meta, $title, $body]);
            error_log("page {$key} ({$title}) reindexed.");
        } else {
            error_log("page {$key} ({$title}) removed from index.");
        }

    }

    public function reindexAll(array $items)
    {
        error_log(sprintf("search: normalizing %u documents.", count($items)));

        $items = array_map(function ($item) {
            $item["title"] = $this->normalizeText($item["title"]);
            $item["body"] = $this->normalizeText($item["body"]);
            return $item;
        }, $items);

        error_log(sprintf("search: adding %u documents to the index.", count($items)));

        $this->db->beginTransaction();
        $this->db->query("DELETE FROM `search`");

        foreach ($items as $item) {
            error_log("index: adding {$item["key"]}");

            $this->db->query("DELETE FROM `search` WHERE `key` = ?", [$item["key"]]);

            $this->db->insert("search", [
                "key" => $item["key"],
                "meta" => serialize($item["meta"]),
                "title" => $item["title"],
                "body" => $item["body"],
            ]);
        }

        $this->db->commit();

        error_log(sprintf("search: index updated, has %u documents now.", count($items)));
    }

    public function normalizeText($text)
    {
        if ($words = $this->splitWords($text)) {
            if ($words = $this->normalizeWords($words)) {
                $words = array_unique($words);
                return implode(" ", $words);
            }
        }

        return "";
    }

    /**
     * Split text into separate words.
     *
     * TODO: don't, etc.
     *
     * @param string $text Source text.
     * @return array Words, lower case, excluding stop words.
     **/
    protected function splitWords($text)
    {
        $text = mb_strtolower($text);
        $words = preg_split('@[^a-zабвгдеёжзийклмнопрстуфхцчшщыьэъюя0-9]+@u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_diff($words, self::$stopWords);
        return $words;
    }

    /**
     * Returns normalized words.
     *
     * First, reads aliases from the database.
     * Second, uses the porter stemmer.
     *
     * @param array $words Source words.
     * @return array Normalized words.
     **/
    protected function normalizeWords(array $words)
    {
        static $aliases = null;

        if ($aliases === null)
            $aliases = $this->db->fetchkv("SELECT `src`, `dst` FROM `odict` LIMIT 10000");

        foreach ($words as $k => $word) {
            if (array_key_exists($word, $aliases)) {
                $words[$k] = $aliases[$word];
                continue;
            }

            if (strspn($word, "abcdefghijklmnopqrstuvwxyz") == strlen($word)) {
                // TODO: use english porter.
            } else {
                $base = \App\Stemmer::getWordBase($word);
                $words[$k] = $base;
            }
        }

        return $words;
    }
}

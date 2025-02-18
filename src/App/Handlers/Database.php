<?php
/**
 * Database status page.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;

class Database extends CommonHandler
{
    public function onStatus(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $tables = $this->getStats();

        $rows = $length = 0;
        foreach ($tables as $t) {
            $rows += $t["row_count"];
            $length += $t["length"];
        }

        return $this->render($request, "dbstats.twig", [
            "dbtype" => $this->db->getConnectionType(),
            "tables" => $tables,
            "db_rows" => $rows,
            "db_length" => $length,
        ]);
    }

    protected function getStats()
    {
        switch ($this->db->getConnectionType()) {
            case "sqlite":
                return $this->getSQLiteStats();

            case "mysql":
                $name = $this->db->fetchcell("SELECT DATABASE()");

                $tables = $this->db->fetch("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY `table_name`", [$name], function ($row) {
                    return [
                        "name" => $row["TABLE_NAME"],
                        "row_count" => (int)$row["TABLE_ROWS"],
                        "length" => (int)$row["DATA_LENGTH"] + (int)$row["INDEX_LENGTH"],
                    ];
                });

                break;
        }

        return $tables;
    }

    protected function getSQLiteStats()
    {
        $tables = [];

        $schema = $this->db->fetch("SELECT * FROM `sqlite_master` WHERE `type` = 'table' ORDER BY `name`");
        foreach ($schema as $row) {
            if (preg_match('@\((.+)\)@ms', $row["sql"], $m)) {
                // Delete comments.
                $fields = preg_replace('@--.*$@m', '', $m[1]);

                // Delete brackets, like key specs.
                $fields = preg_replace('@\([^)]+\)@', '', $fields);

                $fields = explode(",", $fields);

                $_parts = [];
                foreach ($fields as $field) {
                    $parts = explode(" ", mb_strtolower(trim($field)));
                    if (count($parts) > 1 and in_array($parts[1], ["blob"])) {
                        $_parts[] = "SUM(LENGTH(HEX({$parts[0]}))) / 2";
                    } elseif (!in_array($parts[0], ["primary", "key", "unique"])) {
                        $_parts[] = "SUM(LENGTH({$parts[0]}))";
                    }
                }

                $_sum = implode(" + ", $_parts);
                $_query = "SELECT COUNT(1) AS `rows`, {$_sum} AS `bytes` FROM `{$row["name"]}`";

                try {
                    $sel = $this->db->fetchOne($_query);
                } catch (\Exception $e) {
                    debug($row, $_query);
                }

                $tables[] = [
                    "name" => $row["name"],
                    "row_count" => (int)$sel["rows"],
                    "length" => (int)$sel["bytes"],
                ];
            }
        }

        return $tables;
    }
}

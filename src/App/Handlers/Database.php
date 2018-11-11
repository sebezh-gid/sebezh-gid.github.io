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
        $tables = $this->getStats();

        return $this->render($request, "dbstats.twig", [
            "dbtype" => $this->db->getConnectionType(),
            "tables" => $tables,
        ]);
    }

    protected function getStats()
    {
        switch ($this->db->getConnectionType()) {
            case "sqlite":
                $tables = [];

                $rows = $this->db->fetch("select name FROM sqlite_master WHERE `type` = 'table' ORDER BY name");
                foreach ($rows as $row) {
                    $tmp = $this->db->fetchOne("SELECT COUNT(1) AS `count` FROM `{$row["name"]}`");
                    $tables[] = [
                        "name" => $row["name"],
                        "row_count" => (int)$tmp["count"],
                    ];
                }

                break;

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
}

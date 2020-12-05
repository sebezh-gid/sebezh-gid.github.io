<?php
/**
 * File management.
 *
 * File archive operations.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Files extends CommonHandler
{
    public function onGetRecent(Request $request, Response $response, array $args)
    {
        $files = $this->db->fetch("SELECT `id`, `hash`, `name`, `kind`, `mime_type`, `created`, `length` FROM `files` ORDER BY `created` DESC", [], function ($em) {
            $type = explode("/", $em["mime_type"]);
            $type = $type[0];

            if ($type == "image")
                $image = "/i/thumbnails/{$em["id"]}.jpg";
            else
                $image = null;

            return [
                "id" => $em["id"],
                "type" => $type,
                "label" => $em["name"],
                "link" => "/wiki?name=File:{$em["id"]}",
                "image" => $image,
                "created" => strftime("%Y-%m-%d", $em["created"]),
            ];
        });

        return $this->render($request, "files-recent.twig", [
            "files" => $files,
        ]);
    }

    public function onShowFile(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetch("SELECT `id`, `hash`, `name`, `kind`, `mime_type`, `created`, `uploaded`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        return $this->render($request, "files-show.twig", [
            "file" => $file,
        ]);
    }

    public function onDownload(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetchOne("SELECT `name`, `hash`, `mime_type`, `created`, `original` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        $body = $this->fsget($file["original"]);

        return $this->sendCached($request, $body, $file["mime_type"], $file["created"]);
    }

    public function onPhoto(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetchOne("SELECT * FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        $body = $this->fsget($file["original"]);
        $hash = $file["hash"];
        $lastmod = $file["created"];

        return $this->sendCached($request, $body, "image/jpeg", $lastmod);
    }

    /**
     * Returns information on all files.
     **/
    public function onExport(Request $request, Response $response, array $args)
    {
        $files = $this->db->fetch("SELECT id, name, mime_type, length, created, hash FROM files ORDER BY id");

        $host = $request->getServerParam("HTTP_HOST");
        $proto = ($request->getServerParam("HTTPS")) == "on" ? "https" : "http";

        $base = $proto . "://" . $host;

        $files = array_map(function ($em) use ($base) {
            return [
                "id" => (int)$em["id"],
                "name" => $em["name"],
                "mime_type" => $em["mime_type"],
                "length" => (int)$em["length"],
                "created" => (int)$em["created"],
                "hash" => $em["hash"],
                "link" => $base . "/files/" . $em["id"] . "/download",
            ];
        }, $files);

        return $response->withJSON([
            "files" => $files,
        ]);
    }

    /**
     * Выгрузка файлов из базы данных в файловую систему.
     **/
    public function onDump(Request $request, Response $response, array $args)
    {
        $rows = $this->db->fetch("SELECT `id` FROM `files` WHERE `body` IS NOT NULL");
        foreach ($rows as $row) {
            $id = $row["id"];
            $body = $this->db->fetchcell("SELECT `body` FROM `files` WHERE `id` = ?", [$id]);

            $path = $this->fsput($body);
            $this->db->query("UPDATE `files` SET `body` = NULL, `original` = ? WHERE `id` = ?", [$path, $id]);
        }
    }
}

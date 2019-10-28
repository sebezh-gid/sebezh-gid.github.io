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


class Files extends \Ufw1\Handlers\Files
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

    public function xonDownload(Request $request, Response $response, array $args)
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
}

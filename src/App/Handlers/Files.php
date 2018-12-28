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
        $file = $this->db->fetchOne("SELECT `name`, `hash`, `mime_type`, `created`, `body` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        return $this->sendCached($request, $file["body"], $file["mime_type"], $file["created"]);
    }

    public function onThumbnail(Request $request, Response $response, array $args)
    {
        return $this->sendFromCache($request, function () use ($args) {
            $file = $this->db->fetchOne("SELECT `name`, `hash`, `mime_type`, `body`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
            if (empty($file))
                return $this->notfound();

            $img = imagecreatefromstring($file["body"]);
            if ($img === false) {
                error_log("file {$args["id"]} is not an image.");
                return $this->notfound();
            }

            if (!($body = $this->getImage($img)))
                return $this->notfound();

            return ["image/jpeg", $body];
        });
    }

    public function onPhoto(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetchOne("SELECT `name`, `created`, `hash`, `mime_type`, `body`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        $body = $file["body"];
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

    protected function getImage($img)
    {
        $img = $this->scaleImage($img, [
            "width" => 500,
        ]);

        $img = $this->sharpenImage($img);

        ob_start();
        imagejpeg($img, null, 85);
        return ob_get_clean();
    }

    protected function scaleImage($img, array $options)
    {
        $options = array_merge([
            "width" => null,
            "height" => null,
        ], $options);

        $iw = imagesx($img);
        $ih = imagesy($img);

        if ($options["width"] and !$options["height"]) {
            if ($options["width"] != $iw) {
                $r = $iw / $ih;
                $nw = $options["width"];
                $nh = round($nw / $r);

                $dst = imagecreatetruecolor($nw, $nh);

                $res = imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $iw, $ih);
                if (false === $res)
                    throw new \RuntimeException("could not resize the image");

                imagedestroy($img);
                $img = $dst;
            }
        } else {
            throw new \RuntimeException("unsupported thumbnail size");
        }

        return $img;
    }

    protected function sharpenImage($img)
    {
        $sharpenMatrix = array(
            array(-1.2, -1, -1.2),
            array(-1, 20, -1),
            array(-1.2, -1, -1.2),
        );

        // calculate the sharpen divisor
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));

        $offset = 0;

        imageConvolution($img, $sharpenMatrix, $divisor, $offset);

        return $img;
    }
}

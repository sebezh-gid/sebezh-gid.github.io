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
        $files = $this->db->fetch("SELECT `id`, `hash`, `real_name`, `kind`, `type`, `created`, `length` FROM `files` ORDER BY `created` DESC", [], function ($em) {
            $type = explode("/", $em["type"]);
            $type = $type[0];

            if ($type == "image")
                $image = "/i/thumbnails/{$em["id"]}.jpg";
            else
                $image = null;

            return [
                "id" => $em["id"],
                "type" => $type,
                "label" => $em["real_name"],
                "link" => "/files/{$em["id"]}",
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
        $file = $this->db->fetch("SELECT `id`, `hash`, `name`, `real_name`, `kind`, `type`, `created`, `uploaded`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        return $this->render($request, "files-show.twig", [
            "file" => $file,
        ]);
    }

    public function onDownload(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetchOne("SELECT `real_name`, `hash`, `type`, `body`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        $response = $response->withHeader("Content-Type", $file["type"])
            ->withHeader("Content-Length", $file["length"])
            ->withHeader("ETag", "\"{$file["hash"]}\"")
            ->withHeader("Cache-Control", "public, max-age=31536000")
            ->withHeader("Content-Disposition", "attachment; filename=\"" . urlencode($file["real_name"]) . "\"");

        $response->getBody()->write($file["body"]);
        return $response;
    }

    public function onThumbnail(Request $request, Response $response, array $args)
    {
        $path = $request->getUri()->getPath();

        if ($cached = $this->db->cacheGet($path)) {
            $body = $cached;
        } else {
            $file = $this->db->fetchOne("SELECT `real_name`, `hash`, `type`, `body`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
            if (empty($file))
                return $this->notfound($request);

            $img = imagecreatefromstring($file["body"]);
            if ($img === false) {
                error_log("file {$args["id"]} is not an image.");
                return $this->notfound($request);
            }

            if (!($body = $this->getImage($img)))
                return $this->notfound($request);

            $dst = $_SERVER["DOCUMENT_ROOT"] . $path;
            $dir = dirname($dst);
            if (is_dir($dir) and is_writable($dir)) {
                file_put_contents($dst, $body);
            } else {
                $this->db->cacheSet($path, $body);
            }
        }

        $hash = md5($body);

        return $this->sendCached($request, $body, $hash, "image/jpeg");
    }

    public function onPhoto(Request $request, Response $response, array $args)
    {
        $file = $this->db->fetchOne("SELECT `real_name`, `hash`, `type`, `body`, `length` FROM `files` WHERE `id` = ?", [$args["id"]]);
        if (empty($file))
            return $this->notfound($request);

        $body = $file["body"];
        $hash = $file["hash"];

        return $this->sendCached($request, $body, $hash, "image/jpeg");
    }

    /**
     * Sends a file with caching enabled.
     *
     * Supports ETag.
     **/
    protected function sendCached(Request $request, $body, $hash, $type)
    {
        $etag = '"' . $hash . '"';
        $response = new Response(200);

        $headers = $request->getHeaders();
        if (@$headers["HTTP_IF_NONE_MATCH"][0] == $etag) {
            return $response->withStatus(304)
                ->withHeader("ETag", $etag)
                ->withHeader("Cache-Control", "public, max-age=31536000");
        }

        $response = $response->withHeader("Content-Type", "image/jpeg")
            ->withHeader("ETag", "\"{$hash}\"")
            ->withHeader("Content-Length", strlen($body))
            ->withHeader("Cache-Control", "public, max-age=31536000");
        $response->getBody()->write($body);

        return $response;
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

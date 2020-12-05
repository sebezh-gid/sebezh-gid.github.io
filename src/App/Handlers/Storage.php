<?php
/**
 * File display.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Storage extends CommonHandler
{
    public function onGetItem(Request $request, Response $response, array $args)
    {
        $file = $this->db->fileGet($args["name"]);
        if (empty($file))
            return $this->notfound();

        $path = $_SERVER["DOCUMENT_ROOT"] . $request->getUri()->getPath();
        if (is_writable(dirname($path)))
            file_put_contents($path, $file["body"]);

        $type = $this->guessType($args["name"]);

        $response = $response->withHeader("Content-Type", $type)
            ->withHeader("Content-Length", strlen($file["body"]));
        $response->getBody()->write($file["body"]);

        return $response;
    }

    protected function guessType($name)
    {
        $ext = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
        switch ($ext) {
            case "png":
                return "image/png";
            case "jpg":
            case "jpeg":
                return "image/jpeg";
            default:
                return "application/ocet-stream";
        }
    }
}

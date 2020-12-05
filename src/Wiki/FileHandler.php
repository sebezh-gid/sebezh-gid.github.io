<?php
/**
 * Serve files.
 *
 * Reads files from the database and serves them.
 **/

namespace Wiki;

use Slim\Http\Request;
use Slim\Http\Response;

class FileHandler extends Handlers
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $name = $args["name"];
        $file = $this->db->getFileByName($name);

        $hash = empty($file["hash"])
            ? md5($file["body"])
            : $file["hash"];

        $response = $response->withHeader("Content-Type", $file["type"])
            ->withHeader("Content-Length", $file["length"])
            ->withHeader("ETag", "\"{$hash}\"")
            ->withHeader("Cache-Control", "max-age=31536000");
        $response->getBody()->write($file["body"]);

        return $response;
    }
}

<?php
/**
 * Handle file download.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\CommonHandler;

class Short extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        if (empty($_GET["name"]))
            return $this->notfound();

        $name = $_GET["name"];
        $code = $this->getCode($name);

        return $this->template->render($response, "short.twig", [
            "code" => $code,
            "name" => $name,
        ]);

        $ruName = $this->getRussianName($code);
        $enName = $this->getEnglishName($ruName);

        $params = [
            "link" => "https://sebezh-gid.ru/{$code}",
            "russian" => $ruName,
            "english" => $enName,
        ];

        debug($params);

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

    protected function getCode($name)
    {
        $code = $this->db->shortGetCode($name);
        if ($code)
            return $code;

        while (true) {
            $code = rand(1001, 9999);
            if ($this->db->shortAdd($name, $code))
                return $code;
        }
    }

    protected function getRussianName($code)
    {
        $name = $this->db->shortGetName($code);
        return $name;
    }

    protected function getEnglishName($name)
    {
        $page = $this->db->getPageByName($name);

        if (preg_match('@You can read this (page )?\[\[([^|]+)\|in English\]\]@', $page["source"], $m)) {
            return $m[2];
        }

        return null;
    }
}

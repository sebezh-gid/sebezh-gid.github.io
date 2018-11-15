<?php
/**
 * Most CLI actions.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class CLI extends CommonHandler
{
    public function onDefault(Request $request, Response $response, array $args)
    {
        switch ($args["action"]) {
            case "pull-files":
                return $this->onPullFiles($request, $response, $args);
            default:
                die("Unknown action: {$args["action"]}.\n");
        }
    }

    public function onPullFiles(Request $request, Response $response, array $args)
    {
        $files = $this->db->fetchkv("SELECT `id`, `hash` FROM `files`");

        if (!($remote = file_get_contents("https://sebezh-gid.ru/wiki/files.json"))) {
            error_log("pull-files: error fetching remote files.json");
            exit(1);
        }

        $remote = json_decode($remote, true);

        foreach ($remote["files"] as $rfile) {
            if (!array_key_exists($rfile["id"], $files)) {
                $url = "https://sebezh-gid.ru/files/{$rfile["id"]}/download";
                error_log("fetching {$url}");
                $body = file_get_contents($url);

                if (($ll = strlen($body)) != $rfile["length"]) {
                    error_log("pull-files: length mismatch: local={$ll}, remote={$rfile["length"]}; skipped.");
                    continue;
                }

                if (md5($body) != $rfile["hash"]) {
                    error_log("pull-files: hash mismatch, skipped.");
                    continue;
                }

                $kind = "other";
                if (0 === strpos($rfile["type"], "image/"))
                    $kind = "photo";

                $this->db->insert("files", [
                    "id" => $rfile["id"],
                    "name" => $rfile["name"],
                    "real_name" => $rfile["name"],
                    "type" => $rfile["type"],
                    "kind" => $kind,
                    "length" => $rfile["length"],
                    "hash" => $rfile["hash"],
                    "body" => $body,
                    "created" => $rfile["created"],
                    "uploaded" => $rfile["created"],
                ]);

            }
        }

        error_log("all files downloaded.");
    }
}

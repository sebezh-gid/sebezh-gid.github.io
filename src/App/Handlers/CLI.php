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
        if (!($url = $request->getParam("url"))) {
            $this->log("ERR pull-files: url not specified.");
            return;
        }

        $local = $this->db->fetchkv("SELECT `id`, `hash` FROM `files`");

        if (!($remote = file_get_contents($url))) {
            $this->log("ERR pull-files: error fetching remote index.");
            return;
        }

        $remote = json_decode($remote, true);
        if (!is_array($remote)) {
            $this->log("ERR pull-files: error fetching remote index.");
            return;
        }

        $all_count = count($remote["files"]);

        $remote = array_filter($remote["files"], function ($em) use ($local) {
            $id = $em["id"];
            if (!isset($local[$id]))
                return true;
            if ($local[$id] != $em["hash"])
                return true;
            return false;
        });

        $this->log("DBG pull-files: remote has %u files, %u to pull.", $all_count, count($remote));

        foreach (array_values($remote) as $idx => $rfile) {
            $id = $rfile["id"];

            if (!array_key_exists($id, $local)) {
                $this->log("DBG pull-files: fetching [%u/%u] file %u -- new", $idx + 1, count($remote), $id);
            } elseif ($local[$id] != $rfile["hash"]) {
                $this->log("DBG pull-files: fetching [%u/%u] file %u -- updated", $idx + 1, count($remote), $id);
            } else {
                continue;
            }

            $url = $rfile["link"];
            $body = file_get_contents($url);

            if (($ll = strlen($body)) != $rfile["length"]) {
                $this->log("WRN pull-files: length mismatch: local=%u, remote=%u; skipped.", $ll, $rfile["length"]);
                continue;
            }

            if (md5($body) != $rfile["hash"]) {
                $this->log("WRN pull-files: hash mismatch, skipped.");
                continue;
            }

            $kind = "other";
            if (0 === strpos($rfile["mime_type"], "image/"))
                $kind = "photo";

            if (isset($local[$id])) {
                $this->db->update("files", [
                    "id" => $rfile["id"],
                    "name" => $rfile["name"],
                    "real_name" => $rfile["name"],
                    "mime_type" => $rfile["mime_type"],
                    "kind" => $kind,
                    "length" => $rfile["length"],
                    "hash" => $rfile["hash"],
                    "body" => $body,
                    "created" => $rfile["created"],
                    "uploaded" => $rfile["created"],
                ], [
                    "id" => $id,
                ]);
            } else {
                $this->db->insert("files", [
                    "id" => $rfile["id"],
                    "name" => $rfile["name"],
                    "real_name" => $rfile["name"],
                    "mime_type" => $rfile["mime_type"],
                    "kind" => $kind,
                    "length" => $rfile["length"],
                    "hash" => $rfile["hash"],
                    "body" => $body,
                    "created" => $rfile["created"],
                    "uploaded" => $rfile["created"],
                ]);
            }

            unset($local[$id]);
        }

        error_log("all files downloaded.");
    }
}

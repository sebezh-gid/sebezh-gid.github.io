<?php
/**
 * Handle file uploads.
 *
 * Stores photos in the photo folder, creates smaller versions (400px wide).
 *
 * This is used by the photo upload script in the page editor.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use App\CommonHandler;

class Upload extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        return $this->render($request, "upload.twig");
    }

    public function onPost(Request $request, Response $response)
    {
        $this->requireAdmin($request);

        if (!($fid = $this->getFile($request))) {
            return $response->withJSON([
                "message" => "Не удалось получить файл.",
            ]);
        }

        $file = $this->db->fetchOne("SELECT `id`, `name`, `real_name`, `type`, `kind` FROM `files` WHERE `id` = ?", [$fid]);

        $name = "File:" . $fid;
        if (!($page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$name]))) {
            $source = "# {$file["real_name"]}\n\n";

            if ($file["kind"] == "photo")
                $source .= "[[image:{$fid}]]\n\n";

            if ($link = $request->getParam("link"))
                $source .= "Файл загружен [по ссылке]({$link}).\n\n";

            $this->db->insert("pages", [
                "name" => $name,
                "source" => $source,
                "created" => time(),
                "updated" => time(),
            ]);
        }

        $next = "/wiki/edit?name=" . urlencode($name);

        return $response->withJSON([
            "redirect" => $next,
        ]);
    }

    protected function getFile(Request $request)
    {
        $name = null;
        $type = null;
        $body = null;

        $link = $request->getParam("link");
        if (!empty($link)) {
            $file = \App\Common::fetch($link);
            if ($file["status"] == 200) {
                $name = $this->getFileName($link, $file);
                $type = $file["headers"]["content-type"];
                $body = $file["data"];
            } else {
                return false;
            }
        }

        elseif ($files = $request->getUploadedFiles()) {
            if (!empty($files["file"])) {
                $name = $files["file"]->getClientFilename();
                $type = $files["file"]->getClientMediaType();

                $tmp = tempnam($_SERVER["DOCUMENT_ROOT"], "upload_");
                $files["file"]->moveTo($tmp);
                $body = file_get_contents($tmp);
                unlink($tmp);
            } else {
                return false;
            }
        }

        $hash = md5($body);
        $old = $this->db->fetchOne("SELECT `id` FROM `files` WHERE `hash` = ?", [$hash]);
        if ($old)
            return $old["id"];

        switch (explode("/", $type)[0]) {
            case "image":
                $kind = "photo";
                break;
            default:
                $kind = "other";
                break;
        }

        return $this->db->insert("files", [
            "name" => $name,
            "real_name" => $name,
            "type" => $type,
            "kind" => $kind,
            "created" => time(),
            "uploaded" => time(),
            "length" => strlen($body),
            "body" => $body,
            "hash" => $hash,
        ]);
    }

    /**
     * Returns the name of the linked file.
     *
     * @param string $link File URL.
     * @param array $file Response headers.
     * @return string File name.
     **/
    protected function getFileName($link, array $file)
    {
        if (!empty($file["headers"]["content-disposition"])) {
            if (preg_match('@filename="([^"]+)"@', $file["headers"]["content-disposition"], $m)) {
                return $m[1];
            }
        }

        $url = parse_url($link);
        $name = basename($url["path"]);

        if (!($ext = pathinfo($name, PATHINFO_EXTENSION))) {
            switch ($file["headers"]["content-type"]) {
                case "image/png":
                    $name .= ".png";
                    break;
                case "image/jpg":
                case "image/jpeg":
                    $name .= ".jpg";
                    break;
                case "image/gif":
                    $name .= ".gif";
                    break;
            }
        }

        return $name;
    }
}

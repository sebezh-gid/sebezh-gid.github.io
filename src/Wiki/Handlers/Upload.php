<?php
/**
 * Handle file uploads.
 *
 * Stores photos in the photo folder, creates smaller versions (400px wide).
 *
 * This is used by the photo upload script in the page editor.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Wiki\CommonHandler;

class Upload extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        return $this->template->render($response, "upload.twig");
    }

    public function onPost(Request $request, Response $response)
    {
        $this->requireAdmin($request);

        $info = $this->getFile($request);
        if ($info === false)
            return $response->withJSON([
                "message" => "Не удалось получить файл.",
            ]);

        $name = "File:{$info["name"]}";
        $text = "# Файл {$info["real_name"]}\n\nОписание файла отсутствует.\n";

        $this->db->updatePage($name, $text);
        return $response->withJSON([
            "redirect" => "/wiki?name=" . urlencode($name),
        ]);
    }

    protected function getFile(Request $request)
    {
        $link = $request->getParam("link");
        if (!empty($link)) {
            $file = \Wiki\Common::fetch($link);
            if ($file["status"] == 200) {
                $real_name = $this->getFileName($link, $file);

                $name = \Wiki\Common::uuid($file["data"]);
                if ($ext = pathinfo($real_name, PATHINFO_EXTENSION))
                    $name .= "." . $ext;

                $res = [
                    "name" =>  $name,
                    "real_name" => $real_name,
                    "type" => $file["headers"]["content-type"],
                    "length" => strlen($file["data"]),
                    "created" => time(),
                    "body" => $file["data"],
                ];

                $this->db->saveFile($res);
                return $res;
            }
        } elseif ($files = $request->getUploadedFiles()) {
            if (!empty($files["file"]))
                return $this->receiveFile($files["file"]);
        }

        return false;
    }

    /**
     * Receive the uploaded file and save it.
     **/
    protected function receiveFile(UploadedFile $file)
    {
        $res = array(
            "name" => null,  // generate me
            "real_name" => $file->getClientFilename(),
            "type" => $file->getClientMediaType(),
            "length" => $file->getSize(),
            "created" => time(),
            "body" => null,  // fill me in
            );

        $ext = mb_strtolower(pathinfo($res["real_name"], PATHINFO_EXTENSION));
        if (!in_array($ext, ["jpg", "jpeg", "png", "gif"]))
            throw new RuntimeException("file of unsupported type");

        $tmp = tempnam($_SERVER["DOCUMENT_ROOT"], "upload_");
        $file->moveTo($tmp);

        $res["body"] = file_get_contents($tmp);
        unlink($tmp);

        $hash = md5($res["body"]);
        if ($file = $this->db->getFileByHash($hash))
            return $file;

        $part1 = substr(sha1($_SERVER["DOCUMENT_ROOT"]), 0, 10);
        $part2 = substr(sha1($res["body"]), 0, 10);
        $part3 = sprintf("%x", time());

        $res["name"] = sprintf("%s_%s_%s.%s", $part1, $part2, $part3, $ext);

        $this->db->saveFile($res);

        return $res;
    }

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

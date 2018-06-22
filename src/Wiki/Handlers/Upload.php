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

        $files = $request->getUploadedFiles();

        if (empty($files["file"])) {
            return $response->withJSON([
                "message" => "Не выбран файл.",
            ]);
        }

        $file = $files["file"];
        if ($file->getError() != UPLOAD_ERR_OK) {
            return $response->withJSON([
                "message" => "Не удалось принять файл.",
            ]);
        }

        $info = $this->receiveFile($file);

        $name = "File:{$info["name"]}";
        $text = "# Файл {$info["name"]}\n\nОписание файла отсутствует.\n";

        $this->db->updatePage($name, $text);
        return $response->withJSON([
            "redirect" => "/wiki?name=" . urlencode($name),
        ]);
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
}

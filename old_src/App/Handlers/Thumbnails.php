<?php
/**
 * Вывод превьюшки картинки.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Thumbnails extends CommonHandler
{
    /**
     * Вывод уменьшенного изображения файла.
     *
     * Если изображение не готово -- формирует его.
     **/
    public function onShow(Request $request, Response $response, array $args)
    {
        return $this->sendFromCache($request, function () use ($args) {
            $file = $this->db->fetchOne("SELECT * FROM `files` WHERE `id` = ?", [$args["id"]]);
            if (empty($file))
                return $this->notfound();

            if (empty($file["thumbnail"])) {
                if (!($body = $this->getThumbnail($file)))
                    $this->notfound();
                $path = $this->fsput($body);
                $this->db->query("UPDATE `files` SET `thumbnail` = ? WHERE `id` = ?", [$path, $file["id"]]);
            } else {
                $body = $this->fsget($file["thumbnail"]);
            }

            return ["image/jpeg", $body];
        });
    }

    /**
     * Формирование превьюшки указанного файла.
     **/
    protected function getThumbnail(array $file)
    {
        $original = $this->fsget($file["original"]);
        return \App\Thumbnailer::createFromString($original);
    }
}

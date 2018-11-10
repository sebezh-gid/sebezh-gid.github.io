<?php
/**
 * Handle QR codes.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Endroid\QrCode\ErrorCorrectionLevel;

use App\CommonHandler;

class Short extends CommonHandler
{
    public function onGetForm(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $recent = $this->db->shortsGetRecent();

        list($russian, $english, $link) = $this->getPageNames(@$_GET["name"]);

        return $this->render($request, "short-form.twig", [
            "title" => "Генератор табличек",
            "recent" => $recent,
            "plate_russian" => $russian,
            "plate_english" => $english,
            "plate_link" => $link,
        ]);
    }

    public function onGetPreview(Request $request, Response $response, array $args)
    {
        $params = array_merge([
            "russian" => "Без названия",
            "english" => "No title",
            "link" => "https://sebezh-gid.ru/s/1234",
            "code" => "1234",
        ], $_GET);

        $image = $this->renderImage($params);

        $response = $response->withHeader("Content-Type", "image/png")
            ->withHeader("Content-Length", strlen($image));
        $response->getBody()->write($image);

        return $response;
    }

    public function onCreate(Request $request, Response $response, array $args)
    {
        $params = array_merge([
            "russian" => null,
            "english" => null,
            "link" => null,
        ], $_POST);

        $code = $this->db->shortAdd($params["russian"], $params["english"], $params["link"]);

        return $response->withJSON([
            "redirect" => "/short/" . $code,
        ]);
    }

    public function onShowItem(Request $request, Response $response, array $args)
    {
        $code = $args["code"];
        $info = $this->db->shortsGetByCode($code);
        if (empty($info))
            return $this->notfound();

        return $this->render($request, "short-show.twig", [
            "title" => $info["name1"],
            "info" => $info,
        ]);
    }

    public function onGetImage(Request $request, Response $response, array $args)
    {
        $code = $args["code"];
        $info = $this->db->shortsGetByCode($code);
        if (empty($info))
            return $this->notfound();

        $base = $this->container->get("settings")["websiteBase"];

        $image = $this->renderImage([
            "russian" => $info["name1"],
            "english" => $info["name2"],
            "code" => $info["id"],
            "link" => $base . "/s/{$info["id"]}",
        ]);

        $this->db->filePut("short_{$info["id"]}.png", $image);

        $path = $_SERVER["DOCUMENT_ROOT"] . $request->getUri()->getPath();
        if (is_writable(dirname($path)))
            file_put_contents($path, $image);

        $response = $response->withHeader("Content-Type", "image/png")
            ->withHeader("Content-Length", strlen($image));
        $response->getBody()->write($image);

        return $response;
    }

    public function onRedirect(Request $request, Response $response, array $args)
    {
        $code = $args["code"];
        $info = $this->db->shortsGetByCode($code);
        if (empty($info))
            return $this->notfound();

        $next = "/wiki?name=" . urlencode($info["link"]);

        return $response->withRedirect($next, 302);
    }

    public function onGet(Request $request, Response $response, array $args)
    {
        if (empty($_GET["name"]))
            return $this->notfound();

        $name = $_GET["name"];
        $code = $this->getCode($name);

        return $this->render($request, "short.twig", [
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

    protected function getPageNames($name)
    {
        $russian = null;
        $english = null;
        $link = null;

        $page = $this->db->getPageByName($name);
        if (empty($page))
            return [$name, null];

        $page = \App\Util::parsePage($page);

        if ($page["language"] == "en") {
            $english = $page["title"];

            if (!empty($page["ru"])) {
                $link = $page["ru"];

                if ($tmp = $this->db->getPageByName($page["ru"])) {
                    $page = \App\Util::parsePage($tmp);
                    $russian = $page["title"];
                }
            }
        } else {
            $russian = $page["title"];
            $link = $page["name"];

            if (!empty($page["en"])) {
                if ($tmp = $this->db->getPageByName($page["en"])) {
                    $page = \App\Util::parsePage($tmp);
                    $english = $page["title"];
                }
            }
        }

        return [$russian, $english, $link];
    }

    protected function renderImage(array $params)
    {
        $rufont = __DIR__ . "/../../../data/Oswald-Bold.ttf";
        $enfont = __DIR__ . "/../../../data/Oswald-Regular.ttf";
        $xxfont = __DIR__ . "/../../../data/Lato-Regular.ttf";

        $canvas = $this->getCanvas();

        // Generate barcode.
        $barcode = $this->getBarCode($params["link"]);

        // Insert barcode.
        $bw = imagesx($barcode);
        $bh = imagesy($barcode);
        imagecopy($canvas, $barcode, 396, 486, 0, 0, $bw, $bh);

        if ($params["english"]) {
            // Write Russian name.
            imagettftext($canvas, 150, 0, 1750, 1306, 0x000000, $rufont, $params["russian"]);

            // Write English name.
            imagettftext($canvas, 130, 0, 1750, 1602, 0x000000, $enfont, $params["english"]);
        } else {
            // Write Russian name.
            imagettftext($canvas, 150, 0, 1750, 1602, 0x000000, $rufont, $params["russian"]);
        }

        // Write code.
        imagettftext($canvas, 74, 0, 245, 2167, 0x000000, $xxfont, "#" . $params["code"]);

        // Write label.
        imagettftext($canvas, 74, 0, 2725, 2167, 0x000000, $xxfont, "sebezh-gid.ru");

        // Return the image.
        ob_start();
        imagepng($canvas);
        return ob_get_clean();

        debug($barcode, $bw, $bh);

        $gen = new \Endroid\QrCode\QrCode($params["link"]);
        $gen->setSize(1117);
        $gen->setMargin(0);
        $gen->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);

        $data = $gen->writeString();
        return $data;
    }

    protected function getCanvas()
    {
        $fn = __DIR__ . "/../../../data/plate.png";
        $data = file_get_contents($fn);
        return imagecreatefromstring($data);
    }

    protected function getBarCode($link)
    {
        $gen = new \Endroid\QrCode\QrCode($link);
        $gen->setSize(1125);
        $gen->setMargin(0);
        $gen->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);

        $png = $gen->writeString();
        $img = imagecreatefromstring($png);

        return $img;
    }
}

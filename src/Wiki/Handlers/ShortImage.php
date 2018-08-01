<?php
/**
 * Изображение таблички.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Endroid\QrCode\ErrorCorrectionLevel;
use Wiki\CommonHandler;

class ShortImage extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $code = $args["code"];

        $nameRu = $this->getRussianName($code);
        $nameEn = $this->getEnglishName($nameRu);

        $params = [
            "code" => $code,
            "link" => "https://sebezh-gid.ru/{$code}",
            "russian" => $nameRu,
            "english" => $nameEn,
        ];

        $image = $this->renderImage($params);

        $dst = $_SERVER["DOCUMENT_ROOT"] . $request->getUri()->getPath();
        if (is_dir($dir = dirname($dst)) and is_writable($dir))
            file_put_contents($dst, $image);

        $response = $response->withHeader("Content-Type", "image/png")
            ->withHeader("Content-Length", strlen($image));
        $response->getBody()->write($image);

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

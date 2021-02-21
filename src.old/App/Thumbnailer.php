<?php
/**
 * Генератор превьюшек изображени.
 **/

namespace App;

class Thumbnailer
{
    public function createFromString($body)
    {
        if (!($img = imagecreatefromstring($body)))
            throw new \RuntimeException("this file is not an image");

        $me = new self;
        return $me->getImage($img);
    }

    protected function getImage($img)
    {
        $img = $this->scaleImage($img, [
            "width" => 500,
        ]);

        $img = $this->sharpenImage($img);

        ob_start();
        imagejpeg($img, null, 85);
        return ob_get_clean();
    }

    protected function scaleImage($img, array $options)
    {
        $options = array_merge([
            "width" => null,
            "height" => null,
        ], $options);

        $iw = imagesx($img);
        $ih = imagesy($img);

        if ($options["width"] and !$options["height"]) {
            if ($options["width"] != $iw) {
                $r = $iw / $ih;
                $nw = $options["width"];
                $nh = round($nw / $r);

                $dst = imagecreatetruecolor($nw, $nh);

                $res = imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $iw, $ih);
                if (false === $res)
                    throw new \RuntimeException("could not resize the image");

                imagedestroy($img);
                $img = $dst;
            }
        } else {
            throw new \RuntimeException("unsupported thumbnail size");
        }

        return $img;
    }

    protected function sharpenImage($img)
    {
        $sharpenMatrix = array(
            array(-1.2, -1, -1.2),
            array(-1, 20, -1),
            array(-1.2, -1, -1.2),
        );

        // calculate the sharpen divisor
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));

        $offset = 0;

        imageConvolution($img, $sharpenMatrix, $divisor, $offset);

        return $img;
    }
}

<?php

namespace App;

class Template
{
    public static function renderFile($templateName, array $data = array())
    {
        $root = $_SERVER["DOCUMENT_ROOT"] . "/../templates";
        $loader = new \Twig\Loader\FilesystemLoader($root);
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($templateName);
        return $template->render($data);
    }
}

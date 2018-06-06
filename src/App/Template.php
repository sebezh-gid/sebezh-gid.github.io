<?php

namespace App;

use League\CommonMark\CommonMarkConverter;

class Template
{
    public static function renderFile($templateName, array $data = array())
    {
        $root = $_SERVER["DOCUMENT_ROOT"] . "/../templates";
        $loader = new \Twig\Loader\FilesystemLoader($root);
        $twig = new \Twig\Environment($loader);

        $twig->addFilter(new \Twig\TwigFilter("markdown", function ($src) {
            $converter = new CommonMarkConverter();
            $html = $converter->convertToHtml($src);
            return $html;
        }, array("is_safe" => array("html"))));

        $template = $twig->load($templateName);
        return $template->render($data);
    }

    public static function renderPage($pageName, $pageText)
    {
        $md = new CommonMarkConverter();
        $html = $md->convertToHtml($pageText);

        $title = $pageName;
        $html = preg_replace_callback('@<h1>(.+)</h1>@', function ($m) use (&$title) {
            $title = $m[1];
            return "";
        }, $html, 1);

        $html = self::renderFile("page.twig", array(
            "page_name" => $pageName,
            "page_title" => $title,
            "page_text" => $pageText,
            "page_html" => $html,
            ));

        return $html;
    }
}

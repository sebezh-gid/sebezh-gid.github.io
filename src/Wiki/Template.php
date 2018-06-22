<?php

namespace Wiki;

use League\CommonMark\CommonMarkConverter;
use \Slim\Http\Response;

class Template
{
    protected $twig;

    public function __construct(array $settings)
    {
        $root = $settings["template_path"];
        $loader = new \Twig\Loader\FilesystemLoader($root);
        $this->twig = new \Twig\Environment($loader);

        $this->twig->addFilter(new \Twig\TwigFilter("markdown", function ($src) {
            $converter = new CommonMarkConverter();
            $html = $converter->convertToHtml($src);
            return $html;
        }, array("is_safe" => array("html"))));

        $this->twig->addFilter(new \Twig\TwigFilter("filesize", function ($size) {
            if ($size > 1048576)
                return sprintf("%.02f MB", $size / 1048576);
            else
                return sprintf("%.02f KB", $size / 1024);
        }));

        $this->twig->addFilter(new \Twig\TwigFilter("date_simple", function ($ts) {
            return strftime("%d.%m.%y, %H:%M", $ts);
        }));
    }

    public function render(Response $response, $fileName, array $data = array())
    {
        debug($data);
        $template = $this->twig->load($fileName);
        $html = $template->render($data);

        // Some typography.
        $html = preg_replace('@\s+--\s+@', '&nbsp;— ', $html);

        // Clean up.
        $html = preg_replace('@/>\s+<@', '/><', $html);
        $html = preg_replace('@>\s+<@', '><', $html);

        $response->getBody()->write($html);
        return $response;
    }

    public static function renderFile($templateName, array $data = array())
    {
        $root = __DIR__ . "/../../templates";
        $loader = new \Twig\Loader\FilesystemLoader($root);
        $twig = new \Twig\Environment($loader);

        $twig->addFilter(new \Twig\TwigFilter("markdown", function ($src) {
            $converter = new CommonMarkConverter();
            $html = $converter->convertToHtml($src);
            return $html;
        }, array("is_safe" => array("html"))));

        $data["host"] = $_SERVER["HTTP_HOST"];

        $template = $twig->load($templateName);
        $html = $template->render($data);

        // Some typography.
        $html = preg_replace('@\s+--\s+@', '&nbsp;— ', $html);

        // Clean up.
        $html = preg_replace('@/>\s+<@', '/><', $html);
        $html = preg_replace('@>\s+<@', '><', $html);

        return $html;
    }

    public static function renderPage($pageName, $pageText)
    {
        // Extract properties.
        list($props, $pageText) = self::extractProperties($pageName, $pageText);

        $md = new CommonMarkConverter();
        $html = $md->convertToHtml($pageText);

        // Extract page title.
        $html = preg_replace_callback('@<h1>(.+)</h1>@', function ($m) use (&$props) {
            $props["title"] = $m[1];
            return "";
        }, $html, 1);

        // Wiki links.
        $html = preg_replace_callback('@\[\[(.+?)\]\]@', function ($m) {
            $parts = explode("|", $m[1], 2);

            if (count($parts) == 1) {
                $target = $parts[0];
                $title = $parts[0];
            } else {
                $target = $parts[0];
                $title = $parts[1];
            }

            $link = sprintf("<a class=\"wiki\" href=\"/wiki?name=%s\" title=\"%s\">%s</a>", urlencode($target), htmlspecialchars($target), htmlspecialchars($title));

            return $link;
        }, $html);

        // Some typography.
        $html = preg_replace('@\s+--\s+@', '&nbsp;— ', $html);

        // Clean up.
        $html = preg_replace('@/>\s+<@', '/><', $html);
        $html = preg_replace('@>\s+<@', '><', $html);

        $html = self::renderFile("page.twig", array(
            "page_name" => $pageName,
            "page_title" => $props["title"],
            "page_text" => $pageText,
            "page_html" => $html,
            "page_props" => $props,
            ));

        return $html;
    }

    public static function extractProperties($pageName, $text)
    {
        $props = array(
            "lang" => "en",
            "title" => $pageName,
            );

        $lines = preg_split('@(\r\n|\n)@', $text);
        foreach ($lines as $idx => $line) {
            if (preg_match('@^([a-z0-9_]+):\s+(.+)$@', $line, $m)) {
                $props[$m[1]] = $m[2];
            } elseif ($line == "---") {
                $lines = array_slice($lines, $idx + 1);
                $text = implode("\r\n", $lines);
                break;
            }
        }

        return [$props, $text];
    }
}

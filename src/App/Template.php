<?php

namespace App;

use \Slim\Http\Response;

class Template
{
    protected $twig;

    protected $defaults;

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $settings = $container->get("settings")["templates"];

        $this->defaults = isset($settings["defaults"])
            ? $settings["defaults"]
            : [];

        $root = $settings["template_path"];
        $loader = new \Twig\Loader\FilesystemLoader($root);
        $this->twig = new \Twig\Environment($loader);

        $this->twig->addFilter(new \Twig\TwigFilter("markdown", function ($src) {
            $html = \Wik\Common::renderMarkdown($src);
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
        $data["host"] = $_SERVER["HTTP_HOST"];

        $html = $this->renderFile($fileName, $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function renderFile($fileName, array $data)
    {
        $data = $this->addDefaults($data);
        $data = array_merge($this->defaults, $data);

        $data = $this->addSpecialPages($data);

        if (@$_GET["debug"] == "tpl")
            debug($data);

        $template = $this->twig->load($fileName);
        $html = $template->render($data);
        $html = \App\Util::cleanHtml($html);

        return $html;
    }

    public function renderPage(array $data, $link_cb)
    {
        $pageName = $data["page_name"];
        $pageText = $data["page_source"];

        // Extract properties.
        list($props, $pageText) = self::extractProperties($pageName, $pageText);

        $html = \App\Common::renderMarkdown($pageText);

        $html = \App\Common::renderTOC($html);

        // Extract page title.
        $html = preg_replace_callback('@<h1>(.+)</h1>@', function ($m) use (&$props) {
            $props["title"] = $m[1];
            return "";
        }, $html, 1);

        // Wiki links.
        $html = preg_replace_callback('@\[\[(.+?)\]\]@', $link_cb, $html);

        $html = \App\Util::cleanHtml($html);

        $data["page_title"] = $props["title"];
        $data["page_html"] = $html;
        $data["page_props"] = $props;

        $html = $this->renderFile("page.twig", $data);

        return $html;
    }

    public static function extractProperties($pageName, $text)
    {
        $props = array(
            "language" => "ru",
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

    protected function addDefaults(array $data)
    {
        $lang = $dlang = isset($this->defaults["language"]) ? $this->defaults["language"] : "en";

        if (isset($this->defaults[$k = "strings_" . $lang]))
            $data["strings"] = $this->defaults[$k];
        elseif (isset($this->defaults[$k = "strings_" . $dlang]))
            $data["strings"] = $this->defaults[$k];

        return $data;
    }

    protected function addSpecialPages(array $data)
    {
        $db = $this->container->get("database");

        $keys = ["header", "footer", "sidebar"];
        foreach ($keys as $key) {
            $name = "wiki:" . $key;
            $page = $db->getPageByName($name);
            $data["page_" . $key] = $page ? $page["source"] : null;
        }

        return $data;
    }
}

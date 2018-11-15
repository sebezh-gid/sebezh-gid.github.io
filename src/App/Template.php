<?php

namespace App;


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

        $this->twig->addFilter(new \Twig\TwigFilter("megabytes", function ($size) {
            return sprintf("%.02f MB", $size / 1048576);
        }));

        $this->twig->addFilter(new \Twig\TwigFilter("date_simple", function ($ts) {
            return strftime("%d.%m.%y, %H:%M", $ts);
        }));

        $this->twig->addFilter(new \Twig\TwigFilter("date_rfc", function ($ts) {
            return date(DATE_RSS, $ts);
        }));
    }

    public function render($fileName, array $data = array())
    {
        $data["host"] = $_SERVER["HTTP_HOST"];
        $html = $this->renderFile($fileName, $data);
        return $html;
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

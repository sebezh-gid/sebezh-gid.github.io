<?php

namespace App;

use Slim\Http\Request;
use Slim\Http\Response;

class CommonHandler
{
    protected $container;

    /**
     * Set up the handler.
     **/
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($key)
    {
        switch ($key) {
            case "db":
                return $this->container->get("database");
            case "template":
                return $this->container->get("template");
            case "sphinx":
                return $this->container->get("sphinx");
            case "fts":
                return new \App\Search($this->db);
        }
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        switch ($request->getMethod()) {
            case "GET":
                return $this->onGet($request, $response, $args);
            case "POST":
                return $this->onPost($request, $response, $args);
            default:
                debug($request);
        }
    }

    protected function sessionGetId()
    {
        return @$_COOKIE["session_id"];
    }

    protected function sessionSave(array $data)
    {
        $sid = $this->sessionGetId();
        if (empty($sid)) {
            $sid = \App\Common::uuid();
            setcookie("session_id", $sid, time() + 86400 * 365, "/");
        }

        $this->db->sessionSave($sid, $data);
    }

    protected function requireAdmin(Request $request)
    {
        if ($this->isAdmin($request))
            return true;
        throw new \RuntimeException("access denied");
    }

    protected function isAdmin(Request $request)
    {
        if (!($sid = $this->sessionGetId()))
            return false;

        if (!($session = $this->db->sessionGet($sid)))
            return false;

        if (empty($session["user_id"]))
            return false;

        return true;
    }

    protected function render(Request $request, $templateName, array $data = [])
    {
        $html = $this->renderHTML($request, $templateName, $data);

        $response = new Response(200);
        $response->getBody()->write($html);
        return $response;
    }

    protected function renderHTML(Request $request, $templateName, array $data = [])
    {
        $html = $this->template->render($templateName, $data);
        return $html;
    }

    /**
     * Render markdown code to html.
     *
     * Handles wiki links and stuff.
     *
     * @param string $source Source code.
     * @return string Resulting HTML.
     **/
    protected function renderMarkdown($source)
    {
        // Process wiki links.
        $source = preg_replace_callback('@\[\[([^]]+)\]\]@', function ($m) {
            $link = $m[1];
            $label = $m[1];

            if (count($parts = explode("|", $m[1], 2)) == 2) {
                $link = $parts[0];
                $label = $parts[1];
            }

            $cls = "good";
            if (empty($this->db->fetch("SELECT `name` FROM `pages` WHERE `name` = ?", [$link])))
                $cls = "broken";

            $html = sprintf("<a href='/wiki?name=%s' class='wiki %s'>%s</a>", urlencode($link), $cls, htmlspecialchars($label));

            // TODO: embed files

            return $html;
        }, $source);

        $html = \App\Common::renderMarkdown($source);
        $html = \App\Common::renderTOC($html);

        $html = \App\Util::cleanHtml($html);

        return $html;
    }

    /**
     * Process wiki page syntax.
     *
     * @param string $name Page name.
     * @param string $source Page source.
     * @return array Page properties: title, html, etc.
     **/
    protected function processWikiPage($name, $source)
    {
        $res = [
            "name" => $name,
            "title" => $name,
            "image" => null,
            "summary" => null,
        ];

        $source = str_replace("\r\n", "\n", $source);
        $blocks = explode("\n---\n", $source, 2);

        if (count($blocks) == 2) {
            if (preg_match_all('@^([^:]+):\s*(.+)$@m', $blocks[0], $m)) {
                foreach ($m[1] as $idx => $key)
                    $res[$key] = $m[2][$idx];
            }

            $source = $blocks[1];
        }

        // Process wiki links.
        $source = preg_replace_callback('@\[\[([^]]+)\]\]@', function ($m) {
            // Embed images later.
            if (0 === strpos($m[1], "image:"))
                return $m[0];

            $link = $m[1];
            $label = $m[1];

            if (count($parts = explode("|", $m[1], 2)) == 2) {
                $link = $parts[0];
                $label = $parts[1];
            }

            $cls = "good";
            if (empty($this->db->fetch("SELECT `name` FROM `pages` WHERE `name` = ?", [$link])))
                $cls = "broken";

            $html = sprintf("<a href='/wiki?name=%s' class='wiki %s'>%s</a>", urlencode($link), $cls, htmlspecialchars($label));

            return $html;
        }, $source);

        $html = \App\Common::renderMarkdown($source);
        $html = \App\Common::renderTOC($html);

        // Embed images.
        $html = preg_replace_callback('@\[\[image:([^]]+)\]\]@', function ($m) use ($name) {
            $parts = explode(":", $m[1]);
            $fileId = array_shift($parts);

            $info = $this->db->fetchOne("SELECT `id`, `type`, `kind` FROM `files` WHERE `id` = ?", [$fileId]);
            if (empty($info))
                return "<!-- file {$fileid} does not exist -->";
            elseif ($info["kind"] != "photo")
                return "<!-- file {$fileid} is not an image -->";

            $className = "image";
            $iw = "auto";
            $ih = "auto";

            foreach ($parts as $part) {
                if (preg_match('@^width=(\d+)$@', $part, $m)) {
                    $iw = $m[1] . "px";
                }

                elseif (preg_match('@^height=(\d+)$@', $part, $m)) {
                    $ih = $m[1] . "px";
                }

                else {
                    $className .= " " . $part;
                }
            }

            if ($iw == "auto" and $ih == "auto")
                $ih = "150px";

            $small = "/i/thumbnails/{$fileId}.jpg";
            $large = "/i/photos/{$fileId}.jpg";
            $page = "/wiki?name=File:{$fileId}";

            // TODO: add caption from page
            // TODO: add lazy loading

            $html = "<a class='{$className}' href='{$page}' data-src='{$large}' data-fancybox='gallery' itemscope itemtype='http://schema.org/ImageObject'>";
            $html .= "<meta itemprop='contentUrl' content='{$large}'/>";
            $html .= "<img src='{$small}' style='width: {$iw}; height: {$ih}' itemprop='thumbnailUrl'/>";
            $html .= "</a>";

            return $html;
        }, $html);

        $html = preg_replace_callback('@<h1>(.+)</h1>@', function ($m) use ($res) {
            $res["title"] = $m[1];
            return "";
        }, $html);

        if (empty($res["summary"])) {
            if (preg_match('@<p>(.+?)</p>@', $html, $m)) {
                $res["summary"] = strip_tags($m[1]);
            }
        }

        $html = \App\Util::cleanHtml($html);
        $res["html"] = $html;

        return $res;
    }

    protected function notfound(Request $request)
    {
        return $this->render($request, "notfound.twig")->withStatus(404);
    }

    protected function search($query)
    {
        return array_map(function ($em) {
            $name = substr($em["key"], 5);
            $link = "/wiki?name=" . urlencode($name);

            return [
                "link" => $link,
                "title" => $name,
                "snippet" => @$em["meta"]["snippet"],
                "updated" => @$em["meta"]["updated"],
                "image" => @$em["meta"]["image"],
            ];
        }, $this->fts->search($query));
    }
}

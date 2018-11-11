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

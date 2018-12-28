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
        throw new \App\Errors\Unauthorized();
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

    /**
     * Renders the page using a template.
     *
     * Calls renderHTML(), then wraps the result in a Response(200).
     *
     * @param Request $request Request info, used to get host, path information, etc.
     * @param string $templateName File name, e.g. "pages.twig".
     * @param array $data Template variables.
     * @return Response ready to use response.
     **/
    protected function render(Request $request, $templateName, array $data = [])
    {
        $html = $this->renderHTML($request, $templateName, $data);

        $response = new Response(200);
        $response->getBody()->write($html);
        return $response->withHeader("Content-Type", "text/html; charset=utf-8");
    }

    /**
     * Renders the page using a template.
     *
     * @param Request $request Request info, used to get host, path information, etc.
     * @param string $templateName File name, e.g. "pages.twig".
     * @param array $data Template variables.
     * @return Response ready to use response.
     **/
    protected function renderHTML(Request $request, $templateName, array $data = [])
    {
        $defaults = [
            "host" => $request->getUri()->getHost(),
            "path" => $request->getUri()->getPath(),
            "get" => $request->getQueryParams(),
            "language" => "ru",
        ];

        $data = array_merge($defaults, $data);

        $lang = $data["language"];

        if (empty($data["no_database"])) {
            $lmap = [
                "wiki:footer:{$lang}" => "wiki_footer",
                "wiki:footer" => "wiki_footer",
                "wiki:sidebar:{$lang}" => "wiki_sidebar",
                "wiki:sidebar" => "wiki_sidebar",
            ];

            foreach ($lmap as $k => $v) {
                if (empty($data[$v]) and ($src = $this->db->fetchCell("SELECT `source` FROM `pages` WHERE `name` = ?", [$k])))
                    $data[$v] = $src;
            }
        }

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

            $html = sprintf("<a href='/wiki?name=%s' class='wiki %s'>%s</a>", rawurlencode($link), $cls, htmlspecialchars($label));

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
            $link = "/wiki?name=" . rawurlencode($name);

            return [
                "link" => $link,
                "title" => $name,
                "snippet" => @$em["meta"]["snippet"],
                "updated" => @$em["meta"]["updated"],
                "image" => @$em["meta"]["image"],
            ];
        }, $this->fts->search($query));
    }

    /**
     * Send some text to the error log.
     *
     * Handles multiline output, prefixes.
     **/
    protected function log()
    {
        $args = func_get_args();
        $text = call_user_func_array("sprintf", $args);

        if (preg_match('@^([^:]+:\s*)@', $text, $m)) {
            $prefix = $m[0];
            $text = substr($text, strlen($prefix));
        } else {
            $prefix = "";
        }

        $text = str_replace("\r\n", "\n", $text);
        $lines = preg_split('@\n@', $text, -1, PREG_SPLIT_NO_EMPTY);

        $lines = array_map(function ($line) use ($prefix) {
            return $prefix . $line;
        }, $lines);

        foreach ($lines as $line)
            error_log($line);
    }

    protected function taskAdd($url, $args = [], $priority = 0)
    {
        try {
            if ($args) {
                $qs = [];
                foreach ($args as $k => $v)
                    $qs[] = rawurlencode($k) . '=' . rawurlencode($v);
                $url .= "?" . implode("&", $qs);
            }

            $now = time();

            $this->db->insert("tasks", [
                "url" => $url,
                "priority" => $priority,
                "created" => $now,
                "attempts" => 0,
                "run_after" => $now,
            ]);

            $this->log("DBG tasks: scheduled %s", $url);
        } catch (\Exception $e) {
            $this->log("ERR tasks: error scheduling %s", $url);
        }
    }
}

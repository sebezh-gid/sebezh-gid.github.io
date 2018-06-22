<?php

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\CommonHandler;


class Page extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $pageName = $request->getQueryParam("name");
        $fresh = $request->getQueryParam("cache") == "no";

        if (empty($pageName))
            return $response->withRedirect("/wiki?name=Welcome", 302);

        switch ($request->getUri()->getHost()) {
            case "localhost":
            case "127.0.0.1":
                $fresh = true;
                break;
        }

        $page = $this->db->getPageByName($pageName);
        if ($page === false) {
            $status = 404;

            return $this->template->render($response, "nopage.twig", [
                "title" => "Page not found",
                "page_name" => $pageName,
            ]);
        } elseif (!empty($page["html"]) and !$fresh) {
            $status = 200;
            $html = $page["html"];
        } else {
            $status = 200;
            $html = $this->renderPage($page);
            $this->db->updatePageHtml($pageName, $html);
        }

        $response->getBody()->write($html);

        return $response->withStatus($status);
    }

    protected function renderPage(array $page)
    {
        $html = $this->template->renderPage($pageName, $page["source"], function ($m) {
            $parts = explode("|", $m[1], 2);

            if (count($parts) == 1) {
                $target = $parts[0];
                $title = $parts[0];
            } else {
                $target = $parts[0];
                $title = $parts[1];
            }

            $tmp = $this->db->getPageByName($target);
            $cls = $tmp ? "good" : "broken";

            $link = sprintf("<a class=\"wiki %s\" href=\"/wiki?name=%s\" title=\"%s\">%s</a>", $cls, urlencode($target), htmlspecialchars($target), htmlspecialchars($title));

            return $link;
        });

        return $html;
    }
}

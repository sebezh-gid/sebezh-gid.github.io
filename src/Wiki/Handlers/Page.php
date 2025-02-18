<?php
/**
 * Page display.
 *
 * Most work is done in ::renderPage()
 **/

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

        if (preg_match('@^#REDIRECT \[\[(.+)\]\]$@m', $page["source"], $m)) {
            $link = "/wiki?name=" . urlencode($m[1]);
            return $response->withRedirect($link, 303);
        }

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

    public function onEdit(Request $request, Response $response, array $args)
    {
        $pageName = $request->getQueryParam("name");
        if (empty($pageName))
            return $this->notfound();

        if (!$this->isAdmin($request)) {
            $back = "/w/edit?name=" . urlencode($pageName);
            $next = "/w/login?back=" . urlencode($back);
            return $response->withRedirect($next, 302);
        }

        $page = $this->db->getPageByName($pageName);
        if ($page === false) {
            if (preg_match('@^\d{4}$@', $pageName)) {
                $contents = "# sebezh-gid.ru #{$pageName}\n\n- Русский: [[страница]]\n- English: [[something]]";
            } else {
                $contents = "# {$pageName}\n\n**{$pageName}** -- something that we don't have information on, yet.\n";
            }
        } else {
            $contents = $page["source"];
        }

        return $this->template->render($response, "editor.twig", [
            "page_name" => $pageName,
            "page_source" => $contents,
            "is_editable" => $this->isAdmin($request),
        ]);
    }

    /**
     * Update page contents.
     **/
    public function onSave(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $name = $_POST["page_name"];
        $text = $_POST["page_source"];

        $this->db->updatePage($name, $text);

        return $response->withRedirect("/wiki?name=" . urlencode($name), 303);
    }

    /**
     * The main page rendering function.
     **/
    protected function renderPage(array $page)
    {
        $data = [
            "page_name" => $page["name"],
            "page_source" => $page["source"],
        ];

        if (preg_match('@^File:([0-9a-f_]{30}.+)@', $page["name"], $m)) {
            $file = $this->db->getFileByName($m[1]);
            if (!is_null($file)) {
                unset($file["body"]);

                if (preg_match('@^image/@', $file["type"])) {
                    $pi = pathinfo($file["name"]);
                    $file["link"] = "/files/{$file["name"]}";
                    $file["thumbnail"] = "/thumbnail/{$pi["filename"]}_small.{$pi["extension"]}";
                }

                $data["file"] = $file;
            }
        }

        $html = $this->template->renderPage($data, function ($m) {
            $parts = explode("|", $m[1], 2);

            if (count($parts) == 1) {
                $target = $parts[0];
                $title = $parts[0];
            } else {
                $target = $parts[0];
                $title = $parts[1];
            }

            if (preg_match('@^File:(.+)$@', $target, $m)) {
                $fname = $m[1];

                $file = $this->db->getFileByName($m[1]);
                if ($file and preg_match('@^image/@', $file["type"])) {
                    $fd = $this->db->getPageByName("File:" . $m[1]);
                    if (is_null($fd)) {
                        $caption = null;
                    } elseif (preg_match('@^# (.+)$@m', $fd["source"], $n)) {
                        $caption = htmlspecialchars(trim($n[1]));
                    } else {
                        $caption = null;
                    }

                    $link = "/files/" . $fname;
                    $pagelink = "/wiki?name=" . urlencode($target);
                    $thumbnail = "/thumbnail/" . str_replace(".jpg", "_small.jpg", $fname);

                    $pi = pathinfo($fname);
                    $thumbnail = "/thumbnail/" . $pi["filename"] . "_small." . $pi["extension"];

                    if ($caption)
                        $html = "<a class=\"image\" href=\"{$pagelink}\" data-src=\"{$link}\" data-fancybox=\"gallery\" data-caption=\"{$caption}\"><img src=\"{$thumbnail}\" alt=\"{$fname}\"/></a>";
                    else
                        $html = "<a class=\"image\" href=\"{$pagelink}\" data-src=\"{$link}\" data-fancybox=\"gallery\"><img src=\"{$thumbnail}\" alt=\"{$fname}\"/></a>";

                    return $html;
                }
            }

            $tmp = $this->db->getPageByName($target);
            $cls = $tmp ? "good" : "broken";

            $link = sprintf("<a class=\"wiki %s\" href=\"/wiki?name=%s\" title=\"%s\">%s</a>", $cls, urlencode($target), htmlspecialchars($target), htmlspecialchars($title));

            return $link;
        });

        return $html;
    }
}

<?php
/**
 * Page display.
 *
 * Most work is done in ::renderPage()
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Page extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $pageName = $request->getQueryParam("name");
        $fresh = $this->refresh($request);

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

            return $this->render($request, "nopage.twig", [
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

        return $this->render($request, "editor.twig", [
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

        $name = $request->getParam("page_name");
        $text = $request->getParam("page_source");

        // Back up current revision.
        $this->db->dbQuery("INSERT INTO `history` (`name`, `source`, `created`) SELECT `name`, `source`, `updated` FROM `pages` WHERE `name` = ?", [$name]);

        $now = time();

        $count = $this->db->update("pages", [
            "source" => $text,
            "html" => null,
            "updated" => $now,
        ], [
            "name" => $name,
        ]);

        if ($count == 0) {
            $this->db->insert("pages", [
                "name" => $name,
                "source" => $text,
                "created" => $now,
                "updated" => $now,
            ]);
        }

        // TODO: update index

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

            if (preg_match('@^File:(.+)$@i', $target, $m)) {
                $fname = $m[1];

                $caption = null;

                if ($file = $this->db->dbFetchOne("SELECT `id`, `kind`, `type` FROM `files` WHERE `hash` = ?", [$fname])) {
                    if ($file["kind"] == "photo") {
                        $link = "/files/{$file["id"]}";
                        $small = "/i/thumbnails/{$file["id"]}.jpg";
                        $large = "/files/{$file["id"]}/download";

                        if ($caption)
                            $html = "<a class=\"image\" href=\"{$link}\" data-src=\"{$large}\" data-fancybox=\"gallery\" data-caption=\"{$caption}\"><img src=\"{$small}\" alt=\"{$fname}\"/></a>";
                        else
                            $html = "<a class=\"image\" href=\"{$link}\" data-src=\"{$large}\" data-fancybox=\"gallery\"><img src=\"{$small}\" alt=\"{$fname}\"/></a>";

                        return $html;
                    }

                    elseif ($file["kind"] == "audio") {
                        $html = "<audio id='file_{$file["id"]}' controls='controls' preload='metadata'>";
                        $html .= "<source src='/files/{$file["id"]}/download' type='{$file["type"]}'/>";
                        $html .= "Ваш браузер не поддерживает аудио, пожалуйста, <a href='/files/{$file["id"]}/download'>скачайте файл</a>.";
                        $html .= "</audio>";

                        return $html;
                    }
                }
            }

            $tmp = $this->db->getPageByName($target);
            $cls = $tmp ? "good" : "broken";

            $link = sprintf("<a class=\"wiki %s\" href=\"/wiki?name=%s\" title=\"%s\">%s</a>", $cls, urlencode($target), htmlspecialchars($target), htmlspecialchars($title));

            return $link;
        });

        return $html;
    }

    protected function refresh(Request $request)
    {
        $headers = $request->getHeaders();

        $cacheControl = @$headers["HTTP_CACHE_CONTROL"][0];

        // Refresh, Firefox
        /*
        if ($cacheControl == "max-age=0")
            return true;
        */

        // Shift-Refresh, Firefox
        if ($cacheControl == "no-cache")
            return true;

        return false;
    }
}

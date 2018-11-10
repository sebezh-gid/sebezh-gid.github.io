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

        $page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pageName]);
        if (empty($page)) {
            $search = $this->search($pageName);

            $response = $this->render($request, "nopage.twig", [
                "title" => "Page not found",
                "page_name" => $pageName,
                "search_results" => $search,
            ]);

            return $response->withStatus(404);
        }

        $page = $this->processWikiPage($page["name"], $page["source"]);

        if (!empty($page["redirect"])) {
            // TODO: check if page exists.
            $link = "/wiki?name=" . urlencode($page["redirect"]);
            return $response->withRedirect($link, 303);
        }

        return $this->render($request, "page.twig", [
            "page" => $page,
        ]);
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

        $page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pageName]);
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
        $this->db->query("INSERT INTO `history` (`name`, `source`, `created`) SELECT `name`, `source`, `updated` FROM `pages` WHERE `name` = ?", [$name]);

        if (empty($text)) {
            $this->db->query("DELETE FROM `pages` WHERE `name` = ?", [$name]);

            $this->fts->reindexDocument("page:" . $name, null, null);

            return $response->withRedirect("/", 303);
        } else {
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

            $page = $this->processWikiPage($name, $text);
            $text = $this->getPageText($page);
            $snippet = $this->getPageSnippet($page);

            $this->fts->reindexDocument("page:" . $name, $page["title"], $text, [
                "snippet" => $snippet,
                "updated" => $now,
            ]);

            return $response->withRedirect("/wiki?name=" . urlencode($name), 303);
        }
    }

    /**
     * CLI: reindex all pages.
     **/
    public function onCliReindex()
    {
        error_log("reindex: preparing pages.");

        $pages = $this->db->fetch("SELECT * FROM `pages`", [], function ($row) {
            $page = $this->processWikiPage($row["name"], $row["source"]);

            $image = null;
            if (preg_match('@<img[^>]+/>@', $page["html"], $m)) {
                if (preg_match('@src="([^"]+)"@', $m[0], $m)) {
                    $image = $m[1];
                }
            }

            return [
                "key" => "page:" . $row["name"],
                "title" => $page["title"],
                "body" => $this->getPageText($page),
                "meta" => [
                    "snippet" => $this->getPageSnippet($page),
                    "updated" => $row["updated"],
                    "image" => $image,
                ],
            ];
        });

        error_log("reindex: updating.");
        $this->fts->reindexAll($pages);

        error_log("reindex: done.");
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

                if ($file = $this->db->fetch("SELECT `id`, `kind`, `type` FROM `files` WHERE `hash` = ?", [$fname])) {
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

    protected function getPageText(array $page)
    {
        $html = $page["html"];
        $html = str_replace("><", "> <", $html);
        $text = trim(strip_tags($html));
        return $text;
    }

    protected function getPageSnippet(array $page)
    {
        $html = $page["html"];

        if (preg_match('@<p>(.+?)</p>@ms', $html, $m)) {
            $text = strip_tags($m[1]);
            return $text;
        }

        return null;
    }
}

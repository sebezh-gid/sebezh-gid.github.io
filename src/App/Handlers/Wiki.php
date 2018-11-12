<?php
/**
 * Wiki pages.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Wiki extends CommonHandler
{
    /**
     * Display a single page.
     **/
    public function onRead(Request $request, Response $response, array $args)
    {
        $pageName = $request->getQueryParam("name");
        $fresh = $this->refresh($request);

        if (empty($pageName))
            return $response->withRedirect("/wiki?name=Welcome", 302);

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

        if (empty($page["html"]) or $fresh) {
            $page = $this->processWikiPage($page["name"], $page["source"]);

            if (!empty($page["redirect"])) {
                // TODO: check if page exists.
                $link = "/wiki?name=" . urlencode($page["redirect"]);
                return $response->withRedirect($link, 303);
            }

            $html = $this->renderHTML($request, "page.twig", [
                "page" => $page,
            ]);

            $this->db->update("pages", [
                "html" => $html,
            ], [
                "name" => $page["name"],
            ]);
        } else {
            $html = $page["html"];
        }

        $response->getBody()->write($html);
        return $response->withHeader("Content-Type", "text/html; charset=utf-8");
    }

    public function onEdit(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $pageName = $request->getQueryParam("name");
        if (empty($pageName))
            return $this->notfound();

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
     * Specific file upload.
     **/
    public function onUpload(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $comment = null;

        if ($link = $request->getParam("link")) {
            $file = \App\Common::fetch($link);
            if ($file["status"] == 200) {
                $name = basename(explode("?", $link)[0]);
                $type = $file["headers"]["content-type"];
                $body = $file["data"];

                $comment = "Файл загружен [по ссылке]({$link}).\n\n";
            } else {
                return $response->withJSON([
                    "message" => "Не удалось загрузить файл.",
                ]);
            }
        }

        elseif ($files = $request->getUploadedFiles()) {
            if (!empty($files["file"])) {
                $name = $files["file"]->getClientFilename();
                $type = $files["file"]->getClientMediaType();

                $tmp = tempnam($_SERVER["DOCUMENT_ROOT"], "upload_");
                $files["file"]->moveTo($tmp);
                $body = file_get_contents($tmp);
                unlink($tmp);
            } else {
                return $response->withJSON([
                    "message" => "Не удалось принять файл.",
                ]);
            }
        }

        $fid = $this->db->addFile($name, $type, $body);

        $pname = "File:" . $fid;
        if (!($page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pname]))) {
            $text = "# {$name}\n\n";
            $text .= "[[image:{$fid}]]\n\n";
            if ($comment)
                $text .= $comment;

            $this->savePage($pname, $text);
        }

        return $response->withJSON([
            "callback" => "editor_insert",
            "callback_args" => "[[image:{$fid}]]",
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

        if ($next = $this->savePage($name, $text))
            return $response->withRedirect("/wiki?name=" . urlencode($next), 303);
        else
            return $response->withRedirect("/", 303);
    }

    public function onIndex(Request $request, Response $response, array $args)
    {
        $sort = $request->getParam("sort");
        $pages = $this->db->listPages($sort);

        return $this->render($request, "index.twig", [
            "pages" => $pages,
        ]);
    }

    /**
     * CLI: reindex all pages.
     **/
    public function onCliReindex()
    {
        error_log("reindex: preparing pages.");

        $pages = $this->db->fetch("SELECT * FROM `pages`", [], function ($row) {
            $page = $this->processWikiPage($row["name"], $row["source"]);

            return [
                "key" => "page:" . $row["name"],
                "title" => $page["title"],
                "body" => $this->getPageText($page),
                "meta" => [
                    "snippet" => $this->getPageSnippet($page),
                    "updated" => $row["updated"],
                    "image" => $this->getPageImage($page),
                ],
            ];
        });

        error_log("reindex: updating.");
        $this->fts->reindexAll($pages);

        error_log("reindex: done.");
    }

    public function onCliUpdateImages(Request $request, Response $response, array $args)
    {
        $this->db->beginTransaction();

        $pages = $this->db->fetch("SELECT `id`, `name`, `source` FROM `pages`");
        foreach ($pages as $page) {
            $source = preg_replace_callback('@<img[^>]+/>@', function ($m) {
                $attrs = \App\Util::parseHtmlAttrs($m[0]);
                if (empty($attrs["src"]))
                    return $m[0];

                $src = "public" . str_replace("_small", "", $attrs["src"]);
                if (file_exists($src) and is_readable($src)) {
                    $parts = ["image"];

                    $name = basename($src);
                    $type = mime_content_type($src);
                    if (!($id = $this->db->addFile($name, $type, file_get_contents($src))))
                        return $m[0];  // TODO: exception

                    $parts[] = $id;
                    error_log("file {$src} saved by nr.{$id}");

                    if (!empty($attrs["width"]))
                        $parts[] = "width=" . $attrs["width"];
                    if (!empty($attrs["height"]))
                        $parts[] = "height=" . $attrs["height"];

                    $html = "[[" . implode(":", $parts) . "]]";
                    return $html;
                }

                return $m[0];
            }, $page["source"]);

            if ($source != $page["source"]) {
                $this->savePage($page["name"], $source);
            }
        }

        $this->db->commit();
    }

    protected function savePage($name, $text)
    {
        $page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$name]);
        if (empty($page)) {
            $page = ["name" => $name];
            $id = null;
        } else {
            $id = $page["id"];
        }

        $page["source"] = $text;

        // Back up current revision.
        $this->db->query("INSERT INTO `history` (`name`, `source`, `created`) SELECT `name`, `source`, `updated` FROM `pages` WHERE `name` = ?", [$name]);

        if (!trim($text)) {
            $this->db->query("DELETE FROM `pages` WHERE `name` = ?", [$name]);
            $this->fts->reindexDocument("page:" . $name, null, null);
            if ($id) {
                $this->db->query("UPDATE pages SET html = null WHERE id IN (SELECT page_id FROM backlinks WHERE link = ?)", [$name]);
                $this->db->query("DELETE FROM `backlinks` WHERE `page_id` = ?", [$id]);
            }
            return null;
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
                $id = $this->db->insert("pages", [
                    "name" => $name,
                    "source" => $text,
                    "created" => $now,
                    "updated" => $now,
                ]);
            }

            $page = $this->processWikiPage($name, $text);
            $text = $this->getPageText($page);
            $snippet = $this->getPageSnippet($page);
            $image = $this->getPageImage($page);

            $this->fts->reindexDocument("page:" . $name, $page["title"], $text, [
                "snippet" => $snippet,
                "updated" => $now,
                "image" => $image,
            ]);

            // Update backlinks.
            if (preg_match_all('@<a\s+([^>]+)>@', $page["html"], $m)) {
                $links = [];

                foreach ($m[0] as $tag) {
                    $attrs = \App\Util::parseHtmlAttrs($tag);
                    if (!empty($attrs["href"]) and 0 === strpos($attrs["href"], "/wiki?name=")) {
                        $link = urldecode(substr($attrs["href"], 11));
                        if (!in_array($link, $links))
                            $links[] = $link;
                    }
                }

                $this->db->query("DELETE FROM `backlinks` WHERE `page_id` = ?", [$id]);
                foreach ($links as $link) {
                    $this->db->insert("backlinks", [
                        "page_id" => $id,
                        "link" => $link,
                    ]);
                }
            }

            // Reset linked cache.
            $this->db->query("UPDATE pages SET html = null WHERE id IN (SELECT page_id FROM backlinks WHERE link = ?)", [$name]);

            return $name;
        }
    }

    /**
     * Check if the page needs to be served fresh.
     *
     * Detects F5 and Shift-F5.
     *
     * @param Request $request Request.
     * @return bool True if refresh was requested.
     **/
    protected function refresh(Request $request)
    {
        if ($request->getParam("debug"))
            return true;

        $headers = $request->getHeaders();

        $cacheControl = @$headers["HTTP_CACHE_CONTROL"][0];

        // Refresh, Firefox
        if ($cacheControl == "max-age=0")
            return true;

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

    protected function getPageImage(array $page)
    {
        $image = null;

        if (!empty($page["image"]))
            return $page["image"];

        if (preg_match('@<img[^>]+/>@ms', $page["html"], $m)) {
            if (preg_match('@src="([^"]+)"@', $m[0], $n)) {
                $image = $n[1];
            }

            elseif (preg_match("@src='([^']+)'@", $m[0], $n)) {
                $image = $n[1];
            }
        }

        return $image;
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
            $title = $link;

            if ($fpage = $this->db->fetchOne("SELECT `name`, `source` FROM `pages` WHERE `name` = ?", [$link])) {
                // nothing
            } else {
                $cls = "broken";
                $title = "Нет такой страницы";
            }

            $html = sprintf("<a href='/wiki?name=%s' class='wiki %s' title='%s'>%s</a>", urlencode($link), $cls, htmlspecialchars($title), htmlspecialchars($label));

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
            $title = "untitled";

            if ($fpage = $this->db->fetchOne("SELECT `source` FROM `pages` WHERE `name` = ?", ["File:" . $fileId])) {
                if (preg_match('@^# (.+)$@m', $fpage["source"], $n)) {
                    $title = htmlspecialchars($n[1]);
                }
            }

            // TODO: add lazy loading

            $html = "<a class='{$className}' href='{$page}' data-src='{$large}' data-fancybox='gallery' itemscope itemtype='http://schema.org/ImageObject' title='{$title}'>";
            $html .= "<meta itemprop='contentUrl' content='{$large}'/>";
            $html .= "<img src='{$small}' style='width: {$iw}; height: {$ih}' itemprop='thumbnailUrl' alt='{$title}'/>";
            $html .= "</a>";

            return $html;
        }, $html);

        $html = preg_replace_callback('@<h1>(.+)</h1>@', function ($m) use (&$res) {
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
}

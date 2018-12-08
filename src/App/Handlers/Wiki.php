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
        if (empty($page) or empty($page["source"])) {
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

            // Fix case.
            if ($page["name"] != $pageName) {
                $link = "/wiki?name=" . urlencode($page["name"]);
                return $response->withRedirect($link, 301);
            }

            $html = $this->renderHTML($request, "wiki-page.twig", [
                "language" => $page["language"],
                "page" => $page,
                "canonical_link" => "/wiki?name=" . urlencode($page["name"]),
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

        $section = $request->getQueryParam("section");

        $page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pageName]);
        if ($page === false) {
            if (preg_match('@^\d{4}$@', $pageName)) {
                $contents = "# sebezh-gid.ru #{$pageName}\n\n- Русский: [[страница]]\n- English: [[something]]";
            } elseif (preg_match('@^File:(\d+)$@', $pageName, $m)) {
                if ($file = $this->db->fetchOne("SELECT name, type FROM files WHERE id = ?", [$m[1]])) {
                    if (0 === strpos($file["type"], "image/")) {
                        $contents = "# {$file["name"]}\n\n[[image:{$m[1]}]]";
                    } else {
                        $contents = "# other";
                    }
                } else {
                    return $this->notfound($request);
                }
            } else {
                $contents = "# {$pageName}\n\n**{$pageName}** -- something that we don't have information on, yet.\n";
            }
        } else {
            $contents = $page["source"];
        }

        if ($section) {
            $tmp = $this->findSection($contents, $section);
            if (empty($tmp["wanted"]))
                $section = "";
            else
                $contents = $tmp["wanted"];
        }

        return $this->render($request, "wiki-edit.twig", [
            "page_name" => $pageName,
            "page_section" => $section,
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
        $section = $request->getParam("page_section");

        if ($section) {
            if ($page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$name])) {
                $parts = $this->findSection($page["source"], $section);
                $parts["wanted"] = rtrim($text) . PHP_EOL . PHP_EOL;

                $text = implode("", $parts);
            }
        }

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

    public function onBacklinks(Request $request, Response $response, array $args)
    {
        if (!($name = $request->getParam("name")))
            return $this->notfound($request);

        $names = $this->db->fetch("SELECT `name` FROM `pages` WHERE `id` IN (SELECT `page_id` FROM `backlinks` WHERE `link` = ?) ORDER BY `name`", [$name]);
        return $this->render($request, "backlinks.twig", [
            "name" => $name,
            "pages" => $names,
        ]);
    }

    public function onFilesRSS(Request $request, Response $response, array $args)
    {
        $files = $this->db->fetch("SELECT id, name, real_name, type, kind, length, created, hash FROM files ORDER BY created DESC LIMIT 20");

        // Load descriptions.
        $files = array_map(function ($row) {
            $row["title"] = $row["name"];

            $pname = "File:" . $row["id"];
            if ($page = $this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pname])) {
                if (preg_match('@^#\s+(.+)$@m', $page["source"], $m)) {
                    $row["title"] = trim($m[1]);
                }
            }

            return $row;
        }, $files);

        return $this->renderXML($request, "files-rss.twig", [
            "files" => $files,
            "last_update" => $files[0]["created"],
        ]);
    }

    public function onFilesJSON(Request $request, Response $response, array $args)
    {
        $files = $this->db->fetch("SELECT id, name, real_name, type, kind, length, created, hash FROM files ORDER BY created");

        $files = array_map(function ($em) {
            return [
                "id" => (int)$em["id"],
                "name" => $em["name"],
                "type" => $em["type"],
                "created" => (int)$em["created"],
                "length" => (int)$em["length"],
                "hash" => $em["hash"],
            ];
        }, $files);

        return $response->withJSON([
            "files" => $files,
        ]);
    }

    public function onPagesRSS(Request $request, Response $response, array $args)
    {
        $pages = $this->db->fetch("SELECT * FROM `pages` WHERE name NOT LIKE 'File:%' ORDER BY `created` DESC LIMIT 20");

        $pages = array_map(function ($em) {
            $p = $this->processWikiPage($em["name"], $em["source"]);

            return [
                "name" => $em["name"],
                "title" => $p["title"],
                "created" => $em["created"],
                "link" => "/wiki?name=" . urlencode($em["name"]),
            ];
        }, $pages);

        $lastUpdate = $this->db->fetchCell("SELECT MAX(updated) FROM `pages`");

        return $this->renderXML($request, "pages-rss.twig", [
            "pages" => $pages,
            "last_update" => $lastUpdate,
        ]);
    }

    /**
     * Process clipboard text.
     *
     * Detects links to images, replaces with an [[image:N]], returns replacement text.
     **/
    public function onEmbedClipboard(Request $request, Response $response, array $args)
    {
        $res = [
            "replace" => [],
            "open" => [],
        ];

        $text = $request->getParam("text");

        if (preg_match('@^https?://[^\s]+$@', $text, $m)) {
            $url = $m[0];
            $doc = \App\Common::fetch($url);

            if ($type = @$doc["headers"]["content-type"]) {
                if (0 === strpos($type, "image/")) {
                    $name = basename(explode("?", $url)[0]);
                    $type = explode(";", $type)[0];
                    $id = $this->db->addFile($name, $type, $doc["data"]);
                    $res["replace"][] = [
                        "src" => $url,
                        "dst" => "[[image:{$id}]]",
                    ];

                    $pageName = "File:{$id}";
                    if (!$this->db->fetchOne("SELECT * FROM `pages` WHERE `name` = ?", [$pageName]))
                        $res["open"][] = "/wiki/edit?name=File:{$id}";
                }
            }
        } else {
            error_log("clipboard: no links.");
        }

        return $response->withJSON($res);
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

    /**
     * Update page contents.
     *
     * Sends current revision to history.
     * Saves the new revision.
     * Updates backlinks.
     * Flushes cache for the edited page and all that links here.
     **/
    protected function savePage($name, $text)
    {
        $isSpecial = preg_match('@^wiki:@', $name);

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

        // Flush cache of linking pages.
        $this->db->query("UPDATE pages SET html = NULL WHERE id IN (SELECT page_id FROM backlinks WHERE name = ?)", [$name]);

        if (!trim($text)) {
            $this->db->query("DELETE FROM `pages` WHERE `name` = ?", [$name]);
            $this->fts->reindexDocument("page:" . $name, null, null);
            if ($id) {
                $this->db->query("UPDATE pages SET html = null WHERE id IN (SELECT page_id FROM backlinks WHERE link = ?)", [$name]);
                $this->db->query("DELETE FROM `backlinks` WHERE `page_id` = ?", [$id]);
            }

            return $name;
        } else {
            $now = time();

            if ($id) {
                $this->db->update("pages", [
                    "source" => $text,
                    "html" => null,
                    "updated" => $now,
                ], [
                    "id" => $page["id"],
                ]);

                $this->db->query("DELETE FROM `pages` WHERE `name` = ? AND `id` != ?", [$name, $page["id"]]);
            } else {
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

            if (!$isSpecial) {
                $this->fts->reindexDocument("page:" . $name, $page["title"], $text, [
                    "snippet" => $snippet,
                    "updated" => $now,
                    "image" => $image,
                ]);
            }

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

        // strip_tags mishandles scripts, and we use them heavily for microdata,
        // so just strip them off in advance.
        $html = preg_replace('@<script.*?</script>@', '', $html);

        if (preg_match_all('@<p>(.+?)</p>@ms', $html, $m)) {
            foreach ($m[0] as $_html) {
                if ($text = strip_tags($_html)) {
                    return $text;
                }
            }
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
            "images" => [],
            "summary" => null,
            "language" => "ru",
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

            // Embed maps.
            if (0 === strpos($m[1], "map:")) {
                $parts = explode(":", $m[1]);

                $id = mt_rand(1111, 9999);
                $tag = $parts[1];

                $html = "<div id='map_{$id}' class='map' data-src='/map/points.json?tag=" . $tag . "'></div>";
                return $html;
            }

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
        $html = preg_replace_callback('@\[\[image:([^]]+)\]\]@', function ($m) use ($name, &$res) {
            $parts = explode(":", $m[1]);
            $fileId = array_shift($parts);

            $small = "/i/thumbnails/{$fileId}.jpg";
            $large = "/i/photos/{$fileId}.jpg";
            $page = "/wiki?name=File:{$fileId}";
            $title = "untitled";

            $info = $this->db->fetchOne("SELECT `id`, `type`, `kind` FROM `files` WHERE `id` = ?", [$fileId]);
            if (empty($info)) {
                $small = "/images/placeholder.png";
                $large = "/images/placeholder.png";
                $title = "File missing.";

                $_size = getimagesize($_SERVER["DOCUMENT_ROOT"] . "/images/placeholder.png");
                $w = $_size[0];
                $h = $_size[1];
                $rate = $w / $h;
            } elseif ($info["kind"] != "photo") {
                return "<!-- file {$fileid} is not an image -->";
            } else {
                list($w, $h) = $this->getImageSize($fileId);
                $rate = $w / $h;
            }

            $className = "image";
            $iw = "auto";
            $ih = "auto";

            foreach ($parts as $part) {
                if (preg_match('@^width=(\d+)$@', $part, $m)) {
                    $iw = $m[1] . "px";
                    $ih = round($m[1] / $rate) . "px";
                }

                elseif (preg_match('@^height=(\d+)$@', $part, $m)) {
                    $ih = $m[1] . "px";
                    $iw = round($m[1] * $rate) . "px";
                }

                else {
                    $className .= " " . $part;
                }
            }

            if ($iw == "auto" and $ih == "auto") {
                $ih = "150px";
                $iw = round(150 * $rate) . "px";
            }

            $res["images"][] = [
                "src" => $large,
                "width" => $w,
                "height" => $h,
            ];

            if ($fpage = $this->db->fetchOne("SELECT `source` FROM `pages` WHERE `name` = ?", ["File:" . $fileId])) {
                if (preg_match('@^# (.+)$@m', $fpage["source"], $n)) {
                    $title = htmlspecialchars($n[1]);
                }
            }

            // TODO: add lazy loading

            $html = "<a class='{$className}' href='{$page}' data-src='{$large}' data-fancybox='gallery' title='{$title}'>";
            $html .= "<img src='{$small}' style='width: {$iw}; height: {$ih}' alt='{$title}'/>";
            $html .= "</a>";

            $html .= "<script type='application/ld+json'>" . json_encode([
                "@context" => "http://schema.org",
                "@type" => "ImageObject",
                "contentUrl" => $large,
                "name" => $title,
                "thumbnail" => $small,
            ]) . "</script>";

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

        if (preg_match_all('@<img[^>]+>@', $html, $m)) {
            foreach ($m[0] as $_img) {
                $attrs = \App\Util::parseHtmlAttrs($_img);
            }
        }

        $html = \App\Util::cleanHtml($html);
        $res["html"] = $html;

        return $res;
    }

    protected function renderXML(Request $request, $templateName, array $data)
    {
        $def = $this->container->get("settings")["templates"];
        if (!empty($def["defaults"]))
            $data = array_merge($def["defaults"], $data);

        $xml = $this->template->render($templateName, $data);

        $xml = preg_replace('@>\s*<@', "><", $xml);

        $response = new Response(200);
        $response->getBody()->write($xml);
        return $response->withHeader("Content-Type", "text/xml; charset=utf-8");
    }

    protected function getImageSize($fileId)
    {
        $body = $this->db->fetchcell("SELECT body FROM files WHERE id = ?", [$fileId]);

        $img = imagecreatefromstring($body);
        $w = imagesx($img);
        $h = imagesy($img);

        return [$w, $h];
    }

    /**
     * Find specific section in page source.
     *
     * @param string $text Page source.
     * @param string $sectionName The name of desired section.
     * @return array Keys: before, wanted, after.
     **/
    protected function findSection($text, $sectionName)
    {
        // Simplify line endings.
        $text = str_replace("\r\n", "\n", $text);

        $before = null;
        $wanted = null;
        $after = null;

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            if ($after !== null) {
                $after .= $line . PHP_EOL;
                continue;
            }

            $found = preg_match('@^#+\s*(.+)$@', $line, $m);

            if ($wanted !== null) {
                if ($found) {
                    $after .= $line . PHP_EOL;
                    continue;
                } else {
                    $wanted .= $line . PHP_EOL;
                }
            }

            else {
                if ($found and trim($m[1]) == $sectionName) {
                    $wanted .= $line . PHP_EOL;
                    continue;
                } else {
                    $before .= $line . PHP_EOL;
                }
            }
        }

        $res = [
            "before" => $before,
            "wanted" => $wanted,
            "after" => $after,
        ];

        return $res;
    }
}

<?php
/**
 * Maps.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Maps extends CommonHandler
{
    const PIC_HEIGHT = 100;

    /**
     * Show the whole map.
     **/
    public function onMain(Request $request, Response $response, array $args)
    {
        return $this->render($request, "maps-main.twig", [
            "body_class" => "whole_map",
        ]);
    }

    /**
     * List all POIs.
     **/
    public function onAllJSON(Request $request, Response $response, array $args)
    {
        $is_admin = $this->isAdmin($request);

        $poi = $this->db->fetch("SELECT id, created, title, link, ll, icon, description FROM map_poi WHERE ll <> '' AND id IN (SELECT poi_id FROM map_tags WHERE tag = 'public') ORDER BY created DESC");

        $markers = array_map(function ($row) use ($is_admin) {
            $popup = $this->getPopup($row, $is_admin);

            return [
                "latlng" => explode(",", $row["ll"]),
                "icon" => $row["icon"],
                "html" => $popup,
            ];
        }, $poi);

        return $response->withJSON([
            "markers" => $markers,
        ]);
    }

    /**
     * List POI by tag.
     **/
    public function onPoints(Request $request, Response $response, array $args)
    {
        $tag = $request->getParam("tag");
        $tag = str_replace(" ", " ", $tag);
        $tag = mb_strtolower($tag);
        $poi = $this->db->fetch("SELECT * FROM map_poi WHERE `id` IN (SELECT `poi_id` FROM `map_tags` WHERE `tag` = ?) AND `ll` <> '' ORDER BY created DESC", [$tag]);

        $markers = array_map(function ($row) {
            return [
                "latlng" => explode(",", $row["ll"]),
                "title" => $row["title"],
                "link" => $row["link"],
                "description" => $row["description"],
                "icon" => $row["icon"],
            ];
        }, $poi);

        return $response->withJSON([
            "markers" => $markers,
        ]);
    }

    /**
     * New POI form.
     **/
    public function onAdd(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        return $this->render($request, "add-poi.twig", [
        ]);
    }

    /**
     * Edit a POI.
     **/
    public function onEdit(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $id = $request->getParam("id");
        $poi = $this->db->fetchOne("SELECT * FROM `map_poi` WHERE `id` = ?", [$id]);
        if (empty($poi))
            return $this->notfound($request);

        return $this->render($request, "edit-poi.twig", [
            "poi" => $poi,
        ]);
    }

    /**
     * Update or create a POI.
     **/
    public function onSave(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $mode = $request->getParam("mode");
        $form = $request->getParam("form");

        if (empty($form["title"]) and !empty($form["id"])) {
            $id = $form["id"];
            $this->db->query("DELETE FROM `map_tags` WHERE `poi_id` = ?", [$id]);
            $this->db->query("DELETE FROM `map_poi` WHERE `id` = ?", [$id]);
        }

        elseif (!empty($form["ll"])) {
            $description = $this->prepareDescription($form);

            if (empty($form["id"])) {
                $id = $this->db->insert("map_poi", [
                    "created" => strftime("%Y-%m-%d %H:%M:%S"),
                    "ll" => $form["ll"],
                    "title" => $form["title"],
                    "link" => $form["link"],
                    "description" => $description,
                    "icon" => $form["icon"],
                    "tags" => $form["tags"],
                ]);
            } else {
                $id = $form["id"];
                unset($form["id"]);

                $this->db->update("map_poi", [
                    "ll" => $form["ll"],
                    "title" => $form["title"],
                    "link" => $form["link"],
                    "description" => $description,
                    "icon" => $form["icon"],
                    "tags" => $form["tags"],
                ], [
                    "id" => $id,
                ]);
            }

            $this->db->query("DELETE FROM `map_tags` WHERE `poi_id` = ?", [$id]);
            $tags = preg_split('@,\s*@', $form["tags"], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($tags as $tag)
                $this->db->insert("map_tags", [
                    "poi_id" => $id,
                    "tag" => mb_strtolower($tag),
                ]);
        }

        if ($mode == "embed") {
            return $response->withJSON([
                "callback" => "map_embed_close",
            ]);
        }

        return $response->withJSON([
            "redirect" => "/map",
        ]);
    }

    public function onSuggestLL(Request $request, Response $response, array $args)
    {
        $tag = $request->getParam("tag");

        $rows = $this->db->fetch("SELECT ll FROM map_poi t1 INNER JOIN map_tags t2 ON t2.poi_id = t1.id WHERE t2.tag = ?", [$tag]);
        if (empty($rows))
            $rows = $this->db->fetch("SELECT ll FROM map_poi t1 INNER JOIN map_tags t2 ON t2.poi_id = t1.id WHERE t2.tag = ?", ["public"]);
        elseif (empty($rows))
            $rows = $this->db->fetch("SELECT ll FROM map_poi");

        // Empty POI database, default.
        if (empty($rows)) {
            $res = [
                "ll" => [56.16972, 28.73091],
            ];
        } else {
            $lat = $lng = [];
            foreach ($rows as $row) {
                $parts = explode(",", $row["ll"]);
                $lat[] = floatval($parts[0]);
                $lng[] = floatval($parts[1]);
            }

            $lat = array_sum($lat) / count($lat);
            $lng = array_sum($lng) / count($lng);

            $res = [
                "ll" => [$lat, $lng]
            ];
        }

        return $response->withJSON($res);
    }

    protected function getPopup(array $poi, $is_admin = false)
    {
        $img = null;
        $link = htmlspecialchars($poi["link"]);

        $description = preg_replace_callback('@<img[^>]+>@', function ($m) use (&$img) {
            $attrs = \App\Util::parseHtmlAttrs($m[0]);

            $w = 100;
            $h = 100;

            if (isset($attrs["width"]) and isset($attrs["height"])) {
                $r = $attrs["width"] / $attrs["height"];
                $h = self::PIC_HEIGHT;
                $w = round($h * $r);
            } elseif (file_exists($fp = $_SERVER["DOCUMENT_ROOT"] . $attrs["src"])) {
                $size = getimagesize($fp);
                $r = $size[0] / $size[1];
                $h = self::PIC_HEIGHT;
                $w = round($h * $r);
            } elseif (preg_match('@^/i/thumbnails/(\d+)\.jpg$@', $attrs["src"], $m)) {
                $body = $this->db->fetchcell("SELECT body FROM files WHERE id = ?", [$m[1]]);
                if ($body) {
                    $img = imagecreatefromstring($body);
                    $sw = imagesx($img);
                    $sh = imagesy($img);
                    $r = $sw / $sh;
                    $h = self::PIC_HEIGHT;
                    $w = round($h * $r);
                }
            }

            $src = $attrs["src"];
            $img = "<img src='{$src}' width='{$w}' height='{$h}'/>";

            return "";
        }, $poi["description"]);

        $html = "<table><tbody><tr>";

        if ($img) {
            if ($link)
                $img = "<a href='{$link}'>{$img}</a>";
            $html .= "<td>{$img}</td>";
        }

        $html .= "<td class='t2'>";

        if ($link)
            $html .= sprintf("<div class='title'><a href='%s'>%s</a></div>", $link, htmlspecialchars($poi["title"]));
        else
            $html .= sprintf("<div class='title'>%s</div>", htmlspecialchars($poi["title"]));

        $html .= trim($description);

        $parts = explode(",", $poi["ll"]);
        $ll = sprintf("%.4f, %.4f", $parts[0], $parts[1]);
        $edit = $is_admin ? " &middot; <a href='/map/edit?id={$poi["id"]}'>править</a>" : "";
        $html .= "<div class='coords'>{$ll}{$edit}</div>";

        $html .= "</td></tr></tbody></table>";

        return $html;
    }

    protected function prepareDescription(array $form)
    {
        $description = $form["description"];

        // If no image was specified -- get one from the page.
        if (false === strpos($description, "<img")) {
            if (preg_match('@^/wiki\?name=(.+)$@', $form["link"], $m)) {
                $name = urldecode($m[1]);
                if ($page = $this->db->fetchcell("SELECT source FROM pages WHERE name = ?", [$name])) {
                    if (preg_match('@\[\[image:(\d+)[:\]]@', $page, $n)) {
                        $img = "<img src='/i/thumbnails/{$n[1]}.jpg'/>";
                        $description = trim($img . "\n\n" . $description);
                    }
                }
            }
        }

        $description = preg_replace_callback('@<img[^>]+>@', function ($m) {
            $attrs = \App\Util::parseHtmlAttrs($m[0]);

            $sw = $sh = null;

            if (isset($attrs["width"]) and isset($attrs["height"])) {
                $sw = $attrs["width"];
                $sh = $attrs["heigth"];
            }

            elseif (preg_match('@^/i/thumbnails/(\d+)\.jpg$@', $attrs["src"], $m)) {
                if ($image = $this->db->fetchcell("SELECT body FROM files WHERE id = ?", [$m[1]])) {
                    if ($image = imagecreatefromstring($image)) {
                        $sw = imagesx($image);
                        $sh = imagesy($image);
                    }
                }
            }

            elseif (file_exists($fp = $_SERVER["DOCUMENT_ROOT"] . $attr["src"])) {
                $size = getimagesize($fp);
                $sw = $size[0];
                $sh = $size[1];
            }

            if ($sw and $sh) {
                $r = $sw / $sh;
                $h = 100;
                $w = round($h * $r);

                $src = htmlspecialchars($attrs["src"]);
                return "<img src='{$src}' width='{$w}' height='{$h}'/>";
            }

            $src = htmlspecialchars($attrs["src"]);
            return "<img src='{$src}' width='100' height='100'/>";
        }, $description);

        return $description;
    }
}

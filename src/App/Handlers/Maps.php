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
    /**
     * List maps.
     **/
    public function onList(Request $request, Response $response, array $args)
    {
        $poi = $this->db->fetch("SELECT id, created, title, link, icon, tags FROM map_poi ORDER BY created DESC");

        return $this->render($request, "maps.twig", [
            "poi" => $poi,
        ]);
    }

    /**
     * List all POIs.
     **/
    public function onAllJSON(Request $request, Response $response, array $args)
    {
        $poi = $this->db->fetch("SELECT id, created, title, link, ll, icon, tags FROM map_poi ORDER BY created DESC");

        $markers = array_map(function ($row) {
            return [
                "latlng" => explode(",", $row["ll"]),
                "title" => $row["title"],
                "link" => "/map/edit?id={$row["id"]}",
                "description" => "#{$row["id"]}",
                "icon" => $row["icon"],
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
        $poi = $this->db->fetch("SELECT id, created, title, link, ll, icon, tags FROM map_poi WHERE `id` IN (SELECT `poi_id` FROM `map_tags` WHERE `tag` = ?) ORDER BY created DESC", [$tag]);

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

        $form = $request->getParam("form");

        if (empty($form["id"])) {
            $id = $this->db->insert("map_poi", [
                "created" => strftime("%Y-%m-%d %H:%M:%S"),
                "ll" => $form["ll"],
                "title" => $form["title"],
                "link" => $form["link"],
                "description" => $form["description"],
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
                "description" => $form["description"],
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

        return $response->withJSON([
            "redirect" => "/map",
        ]);
    }
}

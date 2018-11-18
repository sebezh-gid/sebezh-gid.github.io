<?php
/**
 * Show search results.
 * Currently only renders the template, we're using Yandex search.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Handlers;

class Search extends Handlers
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $query = $request->getParam("query");
        if (!trim($query))
            return $response->withRedirect("/wiki?name=%D0%92%D0%B2%D0%B5%D0%B4%D0%B5%D0%BD%D0%B8%D0%B5");

        $short = $this->db->shortsGetByCode($query);
        if ($short) {
            $next = "/wiki?name=" . urlencode($short["link"]);
            return $response->withRedirect($next, 303);
        }

        $results = $this->search($query);

        $this->db->insert("search_log", [
            "date" => strftime("%Y-%m-%d %H:%M:%S"),
            "query" => $query,
            "results" => count($results),
        ]);

        $wikiName = \App\Common::wikiName($query);
        $hasPage = $this->db->getPageByName($wikiName) ? true : false;

        return $this->render($request, "search.twig", [
            "query" => $query,
            "wikiName" => $wikiName,
            "has_page" => $hasPage,
            "results" => $results,
            "edit_link" => "/wiki/edit?name=" . urlencode($wikiName),
        ]);
    }

    public function onLog(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $rows = $this->db->fetch("SELECT * FROM `search_log` ORDER BY `date` DESC LIMIT 100");

        return $this->render($request, "search-log.twig", [
            "entries" => $rows,
        ]);
    }
}

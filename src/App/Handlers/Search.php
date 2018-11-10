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

        $short = $this->db->shortsGetByCode($query);
        if ($short) {
            $next = "/wiki?name=" . urlencode($short["link"]);
            return $response->withRedirect($next, 303);
        }

        $results = $this->search($query);

        $wikiName = \App\Common::wikiName($query);
        $hasPage = $this->db->getPageByName($wikiName) ? true : false;

        return $this->render($request, "search.twig", [
            "query" => $query,
            "wikiName" => $wikiName,
            "has_page" => $hasPage,
            "results" => $results,
            "edit_link" => "/w/edit?name=" . urlencode($wikiName),
        ]);
    }
}

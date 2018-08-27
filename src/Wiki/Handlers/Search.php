<?php
/**
 * Show search results.
 * Currently only renders the template, we're using Yandex search.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\Handlers;

class Search extends Handlers
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $query = @$_GET["query"];

        $short = $this->db->shortsGetByCode($query);
        if ($short) {
            $next = "/wiki?name=" . urlencode($short["link"]);
            return $response->withRedirect($next, 303);
        }

        try {
            $res = $query ? $this->sphinx->search($query) : null;
            $error = false;
        } catch (\Exception $e) {
            $res = [];
            $error = true;
        }

        if (count($res) == 1) {
            $link = "/wiki?name=" . urlencode($res[0]["name"]);
            return $response->withRedirect($link, 303);
        }

        $wikiName = \Wiki\Common::wikiName($query);
        $hasPage = $this->db->getPageByName($wikiName) ? true : false;

        return $this->template->render($response, "search.twig", [
            "query" => $query,
            "wikiName" => $wikiName,
            "has_page" => $hasPage,
            "results" => $res,
            "search_error" => $error,
        ]);
    }
}

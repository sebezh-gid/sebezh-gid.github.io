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
        try {

            $res = $query ? $this->sphinx->search($query) : null;
            $error = false;
        } catch (\Exception $e) {
            $res = [];
            $error = true;
        }

        return $this->template->render($response, "search.twig", [
            "query" => $query,
            "results" => $res,
            "search_error" => $error,
        ]);
    }
}

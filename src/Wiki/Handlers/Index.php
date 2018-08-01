<?php

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\CommonHandler;


class Index extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $pages = $this->db->listPages(@$_GET["sort"]);

        return $this->template->render($response, "index.twig", [
            "pages" => $pages,
        ]);
    }
}

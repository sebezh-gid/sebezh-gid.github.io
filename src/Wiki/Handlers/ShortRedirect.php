<?php
/**
 * Переход по короткой ссылке.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\CommonHandler;

class ShortRedirect extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $name = $this->db->shortGetName($args["code"]);
        if (empty($name))
            return $this->notfound();

        // TODO: detect agent language.

        $link = "/wiki?name=" . urlencode($name);
        return $response->withRedirect($link, 301);
    }
}

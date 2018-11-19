<?php
/**
 * Custom error handler.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Error extends CommonHandler
{
    public function __invoke(Request $request, Response $response, array $args)
    {
        $e = $args["exception"];

        $tpl = "error.twig";
        $status = 500;
        $data = [];
        $data["path"] = $request->getUri()->getPath();

        if ($e instanceof \App\Errors\Unauthorized) {
            $tpl = "unauthorized.twig";
            $status = 401;
        }

        $response = $this->render($request, $tpl, $data);
        return $response->withStatus($status);
    }
}

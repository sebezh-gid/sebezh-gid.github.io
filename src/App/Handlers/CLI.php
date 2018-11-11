<?php
/**
 * Most CLI actions.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class CLI extends CommonHandler
{
    public function onDefault(Request $request, Response $response, array $args)
    {
        die("Unknown action: {$args["action"]}.\n");
    }
}

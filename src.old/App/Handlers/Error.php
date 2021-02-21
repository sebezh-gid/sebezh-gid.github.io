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

        $stack = $e->getTraceAsString();
        $root = dirname(dirname(dirname(__DIR__)));
        $stack = str_replace($root . "/", "", $stack);

        $data["e"] = [
            "class" => get_class($e),
            "message" => $e->getMessage(),
            "stack" => $stack,
        ];

        // Database is busy.  This happens when another thread is writing
        // to the database and we cannot open it even for reading.
        // Cannot use template rendering, as it could involve database queries.
        // Return a static pre-configured web page.
        if ($e instanceof \PDOException and false !== strpos($data["e"]["message"], "unable to open database file")) {
            $tpl = "database-busy.twig";
            $status = 503;
            $data["no_database"] = true;
        }

        elseif ($e instanceof \App\Errors\Unauthorized) {
            $tpl = "unauthorized.twig";
            $status = 401;
        }

        try {
            $response = $this->render($request, $tpl, $data);
        } catch (\Exception $e) {
            error_log("ERR: could not render error page using {$tpl}: " . $e->getMessage());
            debug($data["e"]);
        }

        return $response->withStatus($status);
    }
}

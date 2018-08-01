<?php

namespace Wiki;

use Slim\Http\Request;
use Slim\Http\Response;

class CommonHandler
{
    protected $container;

    /**
     * Set up the handler.
     **/
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($key)
    {
        switch ($key) {
            case "db":
                return $this->container->get("database");
            case "template":
                return $this->container->get("template");
            case "sphinx":
                return $this->container->get("sphinx");
        }
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        switch ($request->getMethod()) {
            case "GET":
                return $this->onGet($request, $response, $args);
            case "POST":
                return $this->onPost($request, $response, $args);
            default:
                debug($request);
        }
    }

    protected function requireAdmin(Request $request)
    {
        if ($this->isAdmin($request))
            return true;
        throw new \RuntimeException("access denied");
    }

    protected function isAdmin(Request $request)
    {
        switch ($request->getUri()->getHost()) {
            case "127.0.0.1":
            case "localhost":
            case "local.sebezh-gid.ru":
                return true;
            default:
                return false;
        }
    }
}

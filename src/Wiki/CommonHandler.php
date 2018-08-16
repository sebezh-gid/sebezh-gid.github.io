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

    protected function sessionGetId()
    {
        return @$_COOKIE["session_id"];
    }

    protected function sessionSave(array $data)
    {
        $sid = $this->sessionGetId();
        if (empty($sid)) {
            $sid = \Wiki\Common::uuid();
            setcookie("session_id", $id, time() + 86400 * 365, "/");
        }

        $this->db->sessionSave($sid, $data);
    }

    protected function requireAdmin(Request $request)
    {
        if ($this->isAdmin($request))
            return true;
        throw new \RuntimeException("access denied");
    }

    protected function isAdmin(Request $request)
    {
        if (!($sid = $this->sessionGetId()))
            return false;

        if (!($session = $this->db->sessionGet($sid)))
            return false;

        if (empty($session["user_id"]))
            return false;

        return true;
    }

    protected function render(Response $response, $templateName, array $data = [])
    {
        return $this->template->render($response, $templateName, $data);
    }
}

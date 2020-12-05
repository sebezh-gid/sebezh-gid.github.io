<?php
/**
 * Account operations.
 **/

namespace Wiki\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use Wiki\CommonHandler;


class Account extends CommonHandler
{
    public function onGetLoginForm(Request $request, Response $response, array $args)
    {
        $back = @$_GET["back"];

        return $this->render($response, "login.twig", [
            "title" => "Идентификация",
            "back" => $back,
        ]);
    }

    public function onLogin(Request $request, Response $response, array $args)
    {
        $acc = $this->db->accountGet($_POST["login"]);
        if (empty($acc)) {
            return $response->withJSON([
                "message" => "Нет такого пользователя.",
            ]);
        }

        if ($_POST["password"] != $acc["password"]) {
            return $response->withJSON([
                "message" => "Пароль не подходит.",
            ]);
        }

        $this->sessionSave([
            "user_id" => $acc["id"],
        ]);

        $next = $_POST["back"];

        return $response->withJSON([
            "redirect" => $next,
        ]);
    }
}

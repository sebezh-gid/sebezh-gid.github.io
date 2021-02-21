<?php
/**
 * Account operations.
 *
 * Lets users log in.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;


class Account extends CommonHandler
{
    public function onGetLoginForm(Request $request, Response $response, array $args)
    {
        $back = @$_GET["back"];

        return $this->render($request, "login.twig", [
            "title" => "Идентификация",
            "back" => $back,
        ]);
    }

    public function onLogin(Request $request, Response $response, array $args)
    {
        $login = $request->getParam("login");
        $password = $request->getParam("password");
        $next = $request->getParam("back");

        $acc = $this->db->fetchOne("SELECT * FROM `accounts` WHERE `login` = ?", [$login]);
        if (empty($acc)) {
            return $response->withJSON([
                "message" => "Нет такого пользователя.",
            ]);
        }

        if (!password_verify($password, $acc["password"])) {
            return $response->withJSON([
                "message" => "Пароль не подходит.",
            ]);
        }

        if ($acc["enabled"] == 0) {
            return $response->withJSON([
                "message" => "Учётная запись отключена.",
            ]);
        }

        $this->sessionSave([
            "user_id" => $acc["id"],
        ]);

        $this->db->update("accounts", [
            "last_login" => strftime("%Y-%m-%d %H:%M:%S"),
        ], [
            "id" => $acc["id"],
        ]);

        return $response->withJSON([
            "redirect" => $next,
        ]);
    }
}

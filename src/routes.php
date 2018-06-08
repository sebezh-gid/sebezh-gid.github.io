<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', function (Request $request, Response $response, array $args) {
    $pageName = $request->getQueryParam("name");
    $fresh = $request->getQueryParam("cache") == "no";

    if ($_SERVER["HTTP_HOST"] == "localhost:8080")
        $fresh = true;

    if (empty($pageName))
        return $response->withRedirect("/wiki?name=Welcome", 302);

    $db = $this->get("database");

    $page = $db->getPageByName($pageName);
    if ($page === false) {
        $status = 404;

        $html = \Wiki\Template::renderFile("nopage.twig", array(
            "title" => "Page not found",
            "page_name" => $pageName,
            ));
    } elseif (!empty($page["html"]) and !$fresh) {
        $status = 200;

        $html = $page["html"];
    } else {
        $status = 200;

        $html = \Wiki\Template::renderPage($pageName, $page["source"]);
        $db->updatePageHtml($pageName, $html);
    }

    $response->getBody()->write($html);

    return $response->withStatus($status);
});

$app->get("/edit", '\Wiki\Handlers:getEdit');
$app->post("/edit", '\Wiki\Handlers:postEdit');

$app->get("/index", '\Wiki\Handlers:getIndex');

$app->get("/", '\Wiki\Handlers:getHome');

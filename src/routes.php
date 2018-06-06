<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', function (Request $request, Response $response, array $args) {
    $pageName = $request->getQueryParam("name");
    $fresh = $request->getQueryParam("cache") == "no";

    if (empty($pageName))
        return $response->withRedirect("/wiki?name=Welcome", 302);

    $page = \Wiki\Database::getPageByName($pageName);
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
        \Wiki\Database::updatePageHtml($pageName, $html);
    }

    $response->getBody()->write($html);

    return $response->withStatus($status);
});

$app->get("/edit", function (Request $request, Response $response, array $args) {
    return \Wiki\Handlers::getEdit($request, $response);
});

$app->post("/edit", function (Request $request, Response $response, array $args) {
    return \Wiki\Handlers::postEdit($request, $response);
});

$app->get("/index", function (Request $request, Response $response, array $args) {
    return \Wiki\Handlers::getIndex($request, $response);
});

$app->get("/", function (Request $request, Response $response, array $args) {
    return \Wiki\Handlers::getHome($request, $response);
});

<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', function (Request $request, Response $response, array $args) {
	$pageName = $request->getQueryParam("name");
	die(var_dump($pageName));
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

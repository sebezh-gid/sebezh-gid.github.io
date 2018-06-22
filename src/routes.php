<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', '\Wiki\Handlers\Page');

$app->any("/edit", '\Wiki\Handlers\EditPage');
$app->any("/upload", '\Wiki\Handlers\Upload');

$app->get("/files", \Wiki\Handlers\FileList::class . ":onGet");
$app->get("/files/{name:.*}", \Wiki\FileHandler::class . ':onGet');
$app->get("/thumbnail/{name:.*}", \Wiki\Handlers\Thumbnail::class . ":onGet");

$app->get("/search", \Wiki\Handlers\Search::class . ":onGet");

$app->get("/index", '\Wiki\Handlers:getIndex');

$app->get("/", '\Wiki\Handlers:getHome');

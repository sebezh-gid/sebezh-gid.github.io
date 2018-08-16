<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', '\Wiki\Handlers\Page');

$app->get('/short', '\Wiki\Handlers\Short');
$app->get('/short/{code:[0-9]{4}}.png', '\Wiki\Handlers\ShortImage');
$app->get('/{code:[0-9]{4}}', '\Wiki\Handlers\ShortRedirect');

$app->any("/edit", '\Wiki\Handlers\EditPage');
$app->any("/upload", '\Wiki\Handlers\Upload');

$app->get("/files", \Wiki\Handlers\FileList::class . ":onGet");
$app->get("/files/{name:.*}", '\Wiki\Handlers\File');
$app->get("/thumbnail/{name:.*}", \Wiki\Handlers\Thumbnail::class . ":onGet");

$app->get("/search", \Wiki\Handlers\Search::class . ":onGet");

$app->get("/index", '\Wiki\Handlers\Index');

$app->get("/sitemap.xml", '\Wiki\Handlers\Sitemap');

$app->get("/", '\Wiki\Handlers:getHome');

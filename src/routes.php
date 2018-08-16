<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/wiki', '\Wiki\Handlers\Page');
$app->get("/w/edit", '\Wiki\Handlers\Page:onEdit');
$app->post("/w/edit", '\Wiki\Handlers\Page:onSave');

$app->get('/short', '\Wiki\Handlers\Short:onGetForm');
$app->post('/short', '\Wiki\Handlers\Short:onCreate');
$app->get('/short/preview', '\Wiki\Handlers\Short:onGetPreview');
$app->get('/short/{code:[0-9]{4}}', '\Wiki\Handlers\Short:onShowItem');
$app->get('/i/short/{code:[0-9]{4}}.png', '\Wiki\Handlers\Short:onGetImage');
$app->get('/s/{code:[0-9]{4}}', '\Wiki\Handlers\Short:onRedirect');

$app->get('/short/{code:[0-9]{4}}.png', '\Wiki\Handlers\ShortImage');
$app->get('/{code:[0-9]{4}}', '\Wiki\Handlers\ShortRedirect');

$app->any("/upload", '\Wiki\Handlers\Upload');

$app->get("/files", \Wiki\Handlers\FileList::class . ":onGet");
$app->get("/files/{name:.*}", '\Wiki\Handlers\File');
$app->get("/thumbnail/{name:.*}", \Wiki\Handlers\Thumbnail::class . ":onGet");

$app->get("/w/login", '\Wiki\Handlers\Account:onGetLoginForm');
$app->post("/w/login", '\Wiki\Handlers\Account:onLogin');

$app->get("/search", \Wiki\Handlers\Search::class . ":onGet");

$app->get("/index", '\Wiki\Handlers\Index');

$app->get("/sitemap.xml", '\Wiki\Handlers\Sitemap');

$app->get("/", '\Wiki\Handlers:getHome');

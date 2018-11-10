<?php

use Slim\Http\Request;
use Slim\Http\Response;

// CLI routes.
if (PHP_SAPI == "cli") {
    $app->post('/cli/reindex', '\App\Handlers\Page:onCliReindex');
}

// Web routes.

$app->get('/wiki', '\App\Handlers\Page');
$app->get("/w/edit", '\App\Handlers\Page:onEdit');
$app->post("/w/edit", '\App\Handlers\Page:onSave');

$app->get('/short', '\App\Handlers\Short:onGetForm');
$app->post('/short', '\App\Handlers\Short:onCreate');
$app->get('/short/preview', '\App\Handlers\Short:onGetPreview');
$app->get('/short/{code:[0-9]{4}}', '\App\Handlers\Short:onShowItem');
$app->get('/i/short/{code:[0-9]{4}}.png', '\App\Handlers\Short:onGetImage');
$app->get('/s/{code:[0-9]{4}}', '\App\Handlers\Short:onRedirect');

$app->get('/short/{code:[0-9]{4}}.png', '\App\Handlers\ShortImage');
$app->get('/{code:[0-9]{4}}', '\App\Handlers\ShortRedirect');

$app->any("/upload", '\App\Handlers\Upload');

$app->get('/files', '\App\Handlers\Files:onGetRecent');
$app->get('/files/{id:[0-9]+}', '\App\Handlers\Files:onShowFile');
$app->get('/files/{id:[0-9]+}/download', '\App\Handlers\Files:onDownload');
$app->get('/i/thumbnails/{id:[0-9]+}.jpg', '\App\Handlers\Files:onThumbnail');

// $app->get("/files", \App\Handlers\FileList::class . ":onGet");

$app->get("/files/{name:.*}", '\App\Handlers\File');
$app->get("/thumbnail/{name:.*}", \App\Handlers\Thumbnail::class . ":onGet");

$app->get("/w/login", '\App\Handlers\Account:onGetLoginForm');
$app->post("/w/login", '\App\Handlers\Account:onLogin');

$app->get("/f/{name}", '\App\Handlers\Storage:onGetItem');

$app->get("/search", \App\Handlers\Search::class . ":onGet");

$app->get("/index", '\App\Handlers\Index');

$app->get("/sitemap.xml", '\App\Handlers\Sitemap');

$app->get("/", '\App\Handlers:getHome');

<?php

use Slim\Http\Request;
use Slim\Http\Response;

// CLI routes.
if (PHP_SAPI == "cli") {
    $app->post('/cli/reindex', '\App\Handlers\Wiki:onCliReindex');
    $app->post('/cli/update-images', '\App\Handlers\Wiki:onCliUpdateImages');
    $app->post('/cli/{action:.+}', '\App\Handlers\CLI:onDefault');
}

// Web routes.

$app->get('/wiki', '\App\Handlers\Wiki:onRead');
$app->get("/wiki/edit", '\App\Handlers\Wiki:onEdit');
$app->post("/wiki/edit", '\App\Handlers\Wiki:onSave');
$app->get("/wiki/index", '\App\Handlers\Wiki:onIndex');
$app->post("/wiki/upload", '\App\Handlers\Wiki:onUpload');
$app->post("/wiki/embed-clipboard", '\App\Handlers\Wiki:onEmbedClipboard');
$app->get("/wiki/files.rss", '\App\Handlers\Wiki:onFilesRSS');
$app->get("/wiki/files.json", '\App\Handlers\Wiki:onFilesJSON');
$app->get("/wiki/pages.rss", '\App\Handlers\Wiki:onPagesRSS');
$app->get("/wiki/backlinks", '\App\Handlers\Wiki:onBacklinks');

$app->get("/map", '\App\Handlers\Maps:onList');
$app->get("/map/add", '\App\Handlers\Maps:onAdd');
$app->get("/map/edit", '\App\Handlers\Maps:onEdit');
$app->post("/map/save", '\App\Handlers\Maps:onSave');
$app->get("/map/all.json", '\App\Handlers\Maps:onAllJSON');
$app->get("/map/points.json", '\App\Handlers\Maps:onPoints');
$app->post("/map/suggest-ll", '\App\Handlers\Maps:onSuggestLL');

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
$app->get('/i/photos/{id:[0-9]+}.jpg', '\App\Handlers\Files:onPhoto');

// $app->get("/files", \App\Handlers\FileList::class . ":onGet");

$app->get("/files/{name:.*}", '\App\Handlers\File');

$app->get("/login", '\App\Handlers\Account:onGetLoginForm');
$app->post("/login", '\App\Handlers\Account:onLogin');

$app->get('/admin/database', '\App\Handlers\Database:onStatus');

$app->get("/f/{name}", '\App\Handlers\Storage:onGetItem');

$app->get("/search", \App\Handlers\Search::class . ":onGet");
$app->get("/search/log", \App\Handlers\Search::class . ":onLog");

$app->get("/sitemap.xml", '\App\Handlers\Sitemap');

$app->get("/", '\App\Handlers:getHome');

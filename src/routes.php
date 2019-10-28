<?php

use Slim\Http\Request;
use Slim\Http\Response;


\App\Handlers\Admin::setupRoutes($app);
\App\Handlers\Account::setupRoutes($app);
\App\Handlers\Wiki::setupRoutes($app);
\App\Handlers\Files::setupRoutes($app);

$app->get ('/map', '\App\Handlers\Maps:onMain');
$app->get ('/map/add', '\App\Handlers\Maps:onAdd');
$app->get ('/map/edit', '\App\Handlers\Maps:onEdit');
$app->post('/map/save', '\App\Handlers\Maps:onSave');
$app->get ('/map/all.json', '\App\Handlers\Maps:onAllJSON');
$app->get ('/map/points.json', '\App\Handlers\Maps:onPoints');
$app->post('/map/suggest-ll', '\App\Handlers\Maps:onSuggestLL');

$app->get ('/short', '\App\Handlers\Short:onGetForm');
$app->post('/short', '\App\Handlers\Short:onCreate');
$app->get ('/short/preview', '\App\Handlers\Short:onGetPreview');
$app->get ('/short/{code:[0-9]{4}}', '\App\Handlers\Short:onShowItem');
$app->get ('/i/short/{code:[0-9]{4}}.png', '\App\Handlers\Short:onGetImage');
$app->get ('/s/{code:[0-9]{4}}', '\App\Handlers\Short:onRedirect');

$app->get ('/short/{code:[0-9]{4}}.png', '\App\Handlers\ShortImage');
$app->get ('/{code:[0-9]{4}}', '\App\Handlers\ShortRedirect');

$app->any ('/upload', '\App\Handlers\Upload');

$app->get ('/search', \App\Handlers\Search::class . ':onGet');
$app->get ('/search/log', \App\Handlers\Search::class . ':onLog');

$app->get ('/sitemap.xml', '\App\Handlers\Sitemap');

$app->get ('/', '\App\Handlers:getHome');

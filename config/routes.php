<?php

declare(strict_types=1);

$app->get('/', \App\Home\Actions\HomeAction::class);
$app->get('/wiki', \App\Wiki\Actions\ShowPageAction::class);

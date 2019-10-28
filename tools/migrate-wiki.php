<?php
/**
 * Migrate wiki from the old pages table to the nodes.
 **/

require __DIR__ . '/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';


function find_admin($nf)
{
    $nodes = $nf->where('published = 1 AND deleted = 0 AND `type` = \'user\' ORDER BY id');
    foreach ($nodes as $node) {
        if ($node['role'] == 'admin')
            return $node;
    }
}


$container = $app->getContainer();
$db = $container->get('database');
$nf = $container->get('node');
$wiki = $container->get('wiki');

$admin = find_admin($nf);
if (empty($admin))
    die("No active admin.\n");

$db->beginTransaction();

$db->query('DELETE FROM `nodes` WHERE `type` IN (\'wiki\', \'file\')');

$last = (int)$db->fetchcell('SELECT MAX(id) FROM nodes');
$db->query(sprintf('ALTER TABLE nodes AUTO_INCREMENT = %u', $last + 1));

$files = $db->fetch('SELECT * FROM `files` ORDER BY `id`');
foreach ($files as $file) {
    $node = [
        'type' => 'file',
        'name' => $file['name'],
        'mime_type' => $file['mime_type'],
        'created' => strftime('%Y-%m-%d %H:%M:%S', $file['created']),
        'key' => $file['hash'],
        'published' => 1,
        'deleted' => 0,
        'fname' => $file['original'],
        'length' => $file['length'],
    ];

    if ($file['original']) {
        $node['files']['original'] = [
            'type' => $file['mime_type'],
            'path' => $file['original'],
            'length' => $file['length'],
            'storage' => 'local',
            'url' => "/node/{$file['id']}/download/original",
        ];
    }

    if ($file['thumbnail']) {
        $node['files']['small'] = [
            'type' => $file['mime_type'],
            'path' => $file['thumbnail'],
            'storage' => 'local',
            'url' => "/node/{$file['id']}/download/small",
        ];
    }

    $node = $nf->save($node);

    $db->query('UPDATE `nodes` SET `id` = ?, `updated` = ? WHERE `id` = ?',
        [$file['id'], strftime('%Y-%m-%d %H:%M:%S', $file['uploaded']), $node['id']]);
}

// Move file descriptions into new nodes.
$pages = $db->fetch('SELECT * FROM `pages` WHERE name LIKE \'File:%\'');
foreach ($pages as $page) {
    $fid = (int)substr($page['name'], 5);
    $node = $nf->get($fid);
    $node['description'] = $page['source'];
    $nf->save($node);
}

$pages = $db->fetch('SELECT * FROM `pages` WHERE `name` NOT LIKE \'File:%\' ORDER BY `created`');
foreach ($pages as $page) {
    $node = $wiki->updatePage($page['name'], $page['source'], $admin);
    $node['created'] = strftime('%Y-%m-%d %H:%M:%S', $page['created']);
    $node = $nf->save($node);
}

$db->commit();

<?php

$settings = include __DIR__ . '/config/settings.php';

if (!isset($settings['phinx.dsn'])) {
    throw new \RuntimeException('phinx.dsn not set');
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/src/Migrations',
    ],

    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'default',
        'default' => $settings['phinx.dsn'],
    ],

    'version_order' => 'creation',
];

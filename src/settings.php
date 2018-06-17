<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Template settings.
        'templates' => [
            'template_path' => __DIR__ . '/../templates',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'dsn' => [
            'name' => 'sqlite:' . __DIR__ . '/../data/database.sqlite',
            'user' => null,
            'password' => null,
        ],

        'wiki' => [
            'homePage' => 'Введение',
        ],

        'thumbnails' => [
            'small' => [
                'width' => 200,
            ],
        ],

        'sphinx' => [
            'host' => '127.0.0.1',
            'port' => 9306,
            'index' => 'wiki',
        ],
    ],
];

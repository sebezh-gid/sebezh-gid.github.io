<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'websiteBase' => 'https://sebezh-gid.ru',

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Template settings.
        'templates' => [
            'template_path' => __DIR__ . '/../templates',
            'defaults' => [
                'base_url' => 'https://sebezh-gid.ru',
                'language' => 'ru',
                'strings_ru' => [
                    'site_name' => 'Гид по Себежу',
                ],
                'strings_en' => [
                    'site_name' => 'Sebezh Guide',
                ],
            ]
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'dsn' => [
            'name' => 'mysql:dbname=sebgid',
            'user' => 'sebgid',
            'password' => 'sebgid725',
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

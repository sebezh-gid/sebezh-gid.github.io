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
            'name' => 'mysql:dbname=sebezh_gid',
            'user' => 'sebezh_gid',
            'password' => '8FCbf7B7',
        ],

        'wiki' => [
            'homePage' => 'Введение',
        ],

        'wiki_meta_defaults_ru' => [
            '^File' => [
                'keywords' => 'Гид по Себежу, файл, фотография, фото',
                'summary' => 'Архив фотографий и документов путеводителя по Себежской земле.',
            ],
            '\s(год|января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)$' => [
                'keywords' => 'Гид по Себежу, даты, события, история, календарь',
                'summary' => 'Календарь и хроника в путеводителе по Себежской земле.',
            ],
            '.' => [
                'keywords' => 'гид, путеводитель, туризм, Себеж, Себежская земля, Себежский район, псковщина',
                'summary' => 'Путеводитель но Себежской земле: статьи, рекомендации, фотографии, история.',
            ],
        ],

        'wiki_meta_defaults_en' => [
            '^File' => [
                'keywords' => 'files, photos, illustrations, Sebezh Guide, Guide to Sebezh',
                'summary' => 'Photo archive for the Guide to Sebezh.',
            ],
            '.' => [
                'keywords' => 'guide, travel, voyage, tourism, Sebezh, Pskov, Russia',
                'summary' => 'Guide to the Sebezh land: articles, photos, history, knowledge base.',
            ],
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

        'files' => [
            'path' => __DIR__ . "/../data/files/" . $_SERVER["HTTP_HOST"],
            'fmode' => 0644,
            'dmode' => 0775,
        ],
    ],
];

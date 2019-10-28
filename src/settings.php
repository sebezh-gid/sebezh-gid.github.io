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
            'path' => __DIR__ . '/../tmp/php.log.%Y%m%d',
            'symlink' => __DIR__ . '/../tmp/php.log',
        ],

        'dsn' => [
            'name' => 'mysql:dbname=sebezh_gid',
            'user' => 'sebezh_gid',
            'password' => '8FCbf7B7',
            'bootstrap' => [
                'SET NAMES utf8',
            ],
        ],

        'wiki' => [
            'homePage' => 'Введение',
            'editor_roles' => ['admin', 'editor'],
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

        'files' => [
            'path' => __DIR__ . "/../data/files/sebezh-gid.ru",
            'fmode' => 0644,
            'dmode' => 0775,
        ],

        'nodes_idx' => [
            'file' => ['kind'],
            'user' => ['email'],
        ],

        'node_forms' => [
            'file' => [
                'edit_title' => 'Редактирование файла',
                'fields' => [
                    'name' => [
                        'label' => 'Название файла',
                        'type' => 'textline',
                        'required' => true,
                    ],
                    'kind' => [
                        'label' => 'Тип содержимого',
                        'type' => 'select',
                        'options' => [
                            'photo' => 'фотография',
                            'video' => 'видео',
                            'audio' => 'звук',
                            'other' => 'другое',
                        ],
                    ],
                    'mime_type' => [
                        'label' => 'Тип MIME',
                        'type' => 'textline',
                        'required' => true,
                    ],
                    'files' => [
                        'label' => 'Варианты файла',
                        'type' => 'fileparts',
                    ],
                ],
            ],
            'user' => [
                'new_title' => 'Добавление пользователя',
                'edit_title' => 'Редактирование профиля пользователя',
                'fields' => [
                    'name' => [
                        'label' => 'Фамилия, имя',
                        'type' => 'textline',
                        'required' => true,
                        'placeholder' => 'Сусанин Иван',
                    ],
                    'email' => [
                        'label' => 'Email',
                        'type' => 'textline',
                        'required' => true,
                    ],
                    'phone' => [
                        'label' => 'Номер телефона',
                        'type' => 'textline',
                    ],
                    'role' => [
                        'label' => 'Роль в работе сайта',
                        'type' => 'select',
                        'options' => [
                            'nobody' => 'никто',
                            'user' => 'пользователь',
                            'editor' => 'редактор',
                            'admin' => 'администратор',
                        ],
                    ],
                    'published' => [
                        'label' => 'разрешить доступ',
                        'type' => 'checkbox',
                    ],
                ],
            ],
            'wiki' => [
                'edit_title' => 'Редактирование страницы',
                'fields' => [
                    'name' => [
                        'label' => 'Название страницы',
                        'type' => 'textline',
                        'required' => true,
                    ],
                    'source' => [
                        'label' => 'Текст',
                        'type' => 'textarea',
                        'rows' => 10,
                        'class' => 'markdown',
                        'required' => true,
                        'help' => 'Можно использовать <a href="http://ilfire.ru/kompyutery/shpargalka-po-sintaksisu-markdown-markdaun-so-vsemi-samymi-populyarnymi-tegami/" target="blank">форматирование Markdown</a>.',
                    ],
                    'published' => [
                        'type' => 'hidden',
                    ],
                    'deleted' => [
                        'type' => 'checkbox',
                        'label' => 'удалить статью',
                    ],
                ],
            ],  // picture
        ],  // node_forms

        'admin' => [
            'allowed_roles' => ['admin'],
        ],
    ],
];

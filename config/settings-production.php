<?php

return [
    'dsn' => [
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER') ?: null,
        'password' => getenv('DB_PASSWORD') ?: null,
        'bootstrap' => [
            'SET NAMES utf8',
        ],
    ],
];

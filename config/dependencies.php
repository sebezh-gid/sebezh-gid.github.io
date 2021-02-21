<?php

declare(strict_types=1);

return [
    \App\Database\DatabaseInterface::class => function ($container) {
        return $container->get(\App\Database\PdoDatabase::class);
    },
];

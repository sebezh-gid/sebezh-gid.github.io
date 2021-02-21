<?php

declare(strict_types=1);

return [
    \App\Database\DatabaseInterface::class => function ($container) {
        return $container->get(\App\Database\PdoDatabase::class);
    },

    \App\Templates\TemplateInterface::class => function ($container) {
        return $container->get(\App\Templates\TwigTemplates::class);
    },
];

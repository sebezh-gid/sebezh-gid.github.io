<?php

declare(strict_types=1);

namespace App\Templates;

use Psr\Http\Message\ResponseInterface as Response;

interface TemplateInterface
{
    public function render(string $templateName, array $data): string;
}

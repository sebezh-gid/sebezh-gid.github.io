<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractResponder
{
    public function __construct()
    {
    }

    abstract public function getResponse(Response $response, array $payload): Response;
}

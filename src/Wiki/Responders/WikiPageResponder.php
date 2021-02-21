<?php

declare(strict_types=1);

namespace App\Wiki\Responders;

use App\AbstractResponder;
use Psr\Http\Message\ResponseInterface as Response;

class WikiPageResponder extends AbstractResponder
{
    public function getResponse(Response $response, array $payload): Response
    {
        dd($payload);
    }
}

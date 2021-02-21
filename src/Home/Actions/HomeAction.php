<?php

declare(strict_types=1);

namespace App\Home\Actions;

use App\AbstractAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeAction extends AbstractACtion
{
    public function invoke(Request $request, Response $response, array $args): Response
    {
        return $response;
    }
}

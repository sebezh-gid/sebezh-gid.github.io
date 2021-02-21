<?php

declare(strict_types=1);

namespace App\Wiki\Actions;

use App\AbstractAction;
use App\Config;
use App\Wiki\Wiki;
use App\Wiki\Responders\WikiPageResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ShowPageAction extends AbstractAction
{
    /** @var Wiki **/
    protected $wiki;

    /** @var WikiPageResponder **/
    protected $responder;

    public function __construct(
        Wiki $wiki,
        WikiPageResponder $responder
    ) {
        parent::__construct();
        $this->responder = $responder;
        $this->wiki = $wiki;
    }

    public function invoke(Request $request, Response $response, array $args): Response
    {
        $qs = $request->getQueryParams();

        $name = $qs['name'] ?? null;
        $page = $this->wiki->getPageByName($name);

        $response = $this->responder->getResponse($response, [$page]);
        return $response;
    }
}

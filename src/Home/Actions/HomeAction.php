<?php

declare(strict_types=1);

namespace App\Home\Actions;

use App\AbstractAction;
use App\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeAction extends AbstractACtion
{
    /** @var string **/
    protected $redirect;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->redirect = $config->get('home.redirect');
    }

    public function invoke(Request $request, Response $response, array $args): Response
    {
        if ($this->redirect === null) {
            throw new \RuntimeException('Home page redirect not set.');
        }

        return $response
            ->withHeader('Location', $this->redirect)
            ->withStatus(302);
    }
}

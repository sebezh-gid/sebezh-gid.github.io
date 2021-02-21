<?php

declare(strict_types=1);

namespace App\Wiki\Responders;

use App\AbstractResponder;
use Psr\Http\Message\ResponseInterface as Response;
use App\Templates\TemplateInterface;

class WikiPageResponder extends AbstractResponder
{
    /** @var TemplateInterface **/
    protected $tpl;

    public function __construct(TemplateInterface $tpl)
    {
        parent::__construct();
        $this->tpl = $tpl;
    }

    public function getResponse(Response $response, array $payload): Response
    {
        $html = $this->tpl->render('wiki-page', [
        ]);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}

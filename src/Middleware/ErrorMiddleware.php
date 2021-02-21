<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class ErrorMiddleware
{
    /** @var ResponseFactoryInterface **/
    protected $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $message = get_class($e) . ': ' . $e->getMessage() . "\n";
            $message .= "---\n";
            $message .= $e->getTraceAsString();

            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($message);
            return $response->withHeader('Content-Type', 'text/plain')
                ->withStatus(500);
        }
    }

    protected function hasCacheControl(Response $response): bool
    {
        $headers = $response->getHeader('cache-control');
        return count($headers) > 0;
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CachingMiddleware
{
    protected int $ttl;

    public function __construct(Config $config)
    {
        $this->ttl = $config->get('cache.default-ttl', 3600);
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        if ($request->getMethod() === 'GET' && $response->getStatusCode() === 200) {
            if (!$this->hasCacheControl($response)) {
                $response = $response->withHeader('Cache-Control', sprintf('public, max-age=%u', $this->ttl));
            }
        }

        return $response;
    }

    protected function hasCacheControl(Response $response): bool
    {
        $headers = $response->getHeader('cache-control');
        return count($headers) > 0;
    }
}

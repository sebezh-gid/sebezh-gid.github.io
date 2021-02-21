<?php

declare(strict_types=1);

namespace App;

use App\Errors\BadRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

abstract class AbstractAction
{
    /** @var Request **/
    protected $request;

    /** @var Response **/
    protected $response;

    public function __construct()
    {
    }

    /**
     * Compatibility setup.
     *
     * Slim v3 used to add request/response to the container, but now it doesn't.
     * This way we either have to pass them around all the time, or override this
     * method to make them available as class properties.
     **/
    final public function __invoke(Request $request, Response $response): Response
    {
        $this->request = $request;
        $this->response = $response;
        $routeArgs = $request->getAttribute('__route__')->getArguments();

        $ts = microtime(true);
        $response = $this->invoke($request, $response, $routeArgs);

        if (defined('REQUEST_START_TS')) {
            $response = $response->withHeader('X-Init-Duration', strval($ts - REQUEST_START_TS));
        }

        return $response->withHeader('X-PMU', strval(memory_get_peak_usage()))
            ->withHeader('X-Handler-Duration', strval(microtime(true) - $ts));
    }

    abstract protected function invoke(Request $request, Response $response, array $args): Response;

    protected function getRequestJson(): array
    {
        $ct = $this->request->getHeaderLine('Content-Type');
        if ($ct !== 'application/json') {
            throw new BadRequest('request content type must be application/json');
        }

        $body = $this->request->getBody()->getContents();
        return json_decode($body, true);
    }

    protected function sendEmptyResponse(): Response
    {
        $this->response->getBody()->write('');
        return $this->response->withStatus(204);
    }

    protected function sendHTML(string $html, int $status = 200): Response
    {
        $this->response->getBody()->write($html);

        return $this->response
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/html')
        ;
    }

    protected function sendText(Response $response, string $text, int $status = 200): Response
    {
        $response->getBody()->write($text);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/plain')
        ;
    }

    /**
     * @param mixed $data
     **/
    protected function sendJson($data): Response
    {
        $body = json_encode($data);

        $this->response->getBody()->write($body);

        return $this->response->withHeader('Content-Type', 'application/json');
    }

    protected function shouldHardRefresh(): bool
    {
        $cacheControl = $this->request->getServerParams()['HTTP_CACHE_CONTROL'] ?? null;
        return $cacheControl === 'no-cache';
    }

    protected function shouldRefresh(): bool
    {
        $cacheControl = $this->request->getServerParams()['HTTP_CACHE_CONTROL'] ?? null;
        return $cacheControl === 'max-age=0';
    }

    protected function badParam(string $message): Response
    {
        throw new BadRequest('BadParameter');
    }

    protected function serviceNotAvailable(string $message): Response
    {
        throw new \RuntimeException('service unavailable');
    }
}

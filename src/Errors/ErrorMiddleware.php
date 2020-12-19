<?php

/**
 * Error handling middleware.
 *
 * If bugsnag.key config option is set, sends bug reports to bugsnag.com.
 * If log_errors ini option is set, sends reports to PHP error log.
 **/

declare(strict_types=1);

namespace App\Errors;

use App\Helpers\Config;
use Bugsnag\Client as BugsnagClient;
use ErrorException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Throwable;

class ErrorMiddleware
{
    /**
     * @var string
     **/
    protected $key;

    /**
     * @var bool
     **/
    protected $log;

    /**
     * @var ResponseFactoryInterface
     **/
    protected $responseFactory;

    public function __construct(Config $config, ResponseFactoryInterface $responseFactory)
    {
        $this->key = $config->get('bugsnag.key');
        $this->log = (bool)(int)ini_get('log_errors');
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
                if ((error_reporting() & $errno) === 0) {
                    return false;
                } else {
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                }
            });

            return $handler->handle($request);
        } catch (HttpNotFoundException $e) {
            return $this->sendJSON([
                'message' => 'Route not found.',
                'reported' => false,
                'logged' => false,
            ], 404);
        } catch (HttpMethodNotAllowedException $e) {
            return $this->sendJSON([
                'message' => 'Method not allowed.',
                'reported' => false,
                'logged' => false,
            ], 405);
        } catch (Throwable $e) {
            $this->logError($e);
            $this->reportError($e);

            return $this->sendJSON([
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'reported' => $this->key !== null,
                'logged' => $this->log,
            ], 500);
        } finally {
            restore_error_handler();
        }
    }

    protected function logError(Throwable $e): void
    {
        if ($this->log) {
            $message = sprintf("%s: %s\n", get_class($e), $e->getMessage());
            $message .= sprintf("File: %s line %d\n", $e->getFile(), $e->getLine());
            $message .= $e->getTraceAsString();
            error_log($message);
        }
    }

    protected function reportError(Throwable $e): void
    {
        if ($this->key !== null) {
            $client = BugsnagClient::make($this->key);
            $client->notifyException($e);
        }
    }

    protected function sendJSON(array $data, int $status): Response
    {
        $response = $this->responseFactory->createResponse()
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode($data));

        return $response;
    }
}

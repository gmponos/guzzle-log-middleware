<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use GuzzleLogMiddleware\Handler\MultiRecordArrayHandler;
use GuzzleLogMiddleware\Handler\HandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A class to log HTTP Requests and Responses of Guzzle.
 *
 * @author George Mponos <gmponos@gmail.com>
 */
final class LogMiddleware
{
    /**
     * @var bool
     */
    private $onFailureOnly;

    /**
     * Decides if you need to log statistics or not.
     *
     * @var bool
     */
    private $logStatistics;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransferStats|null
     */
    private $stats = null;

    /**
     * Creates a callable middleware for logging requests and responses.
     *
     * @param bool $onFailureOnly The request and the response will be logged only in cases there is considered a failure.
     * @param bool $logStatistics If this is true an extra row will be added that will contain some HTTP statistics.
     */
    public function __construct(
        LoggerInterface $logger,
        ?HandlerInterface $handler = null,
        bool $onFailureOnly = false,
        bool $logStatistics = false
    ) {
        $this->logger = $logger;
        $this->onFailureOnly = $onFailureOnly;
        $this->logStatistics = $logStatistics;
        $this->handler = $handler === null ? new MultiRecordArrayHandler() : $handler;
    }

    /**
     * Called when the middleware is handled by the client.
     *
     * @return callable(RequestInterface, array): PromiseInterface
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $this->setOptions($options);

            if ($this->logStatistics && !isset($options['on_stats'])) {
                $options['on_stats'] = function (TransferStats $stats) {
                    $this->stats = $stats;
                };
            }

            return $handler($request, $options)
                ->then(
                    $this->handleSuccess($request, $options),
                    $this->handleFailure($request, $options)
                );
        };
    }

    /**
     * Returns a function which is handled when a request was successful.
     */
    private function handleSuccess(RequestInterface $request, array $options): callable
    {
        return function (ResponseInterface $response) use ($request, $options) {
            // if onFailureOnly is true then it must not log the response since it was successful.
            if ($this->onFailureOnly === false) {
                $this->handler->log($this->logger, $request, $response, null, $this->stats, $options);
            }

            return $response;
        };
    }

    /**
     * Returns a function which is handled when a request was rejected.
     */
    private function handleFailure(RequestInterface $request, array $options): callable
    {
        return function (\Exception $reason) use ($request, $options) {
            if ($reason instanceof RequestException && $reason->hasResponse() === true) {
                $this->handler->log($this->logger, $request, $reason->getResponse(), $reason, $this->stats, $options);
                return \GuzzleHttp\Promise\rejection_for($reason);
            }

            $this->handler->log($this->logger, $request, null, $reason, $this->stats, $options);
            return \GuzzleHttp\Promise\rejection_for($reason);
        };
    }

    private function setOptions(array $options): void
    {
        if (!isset($options['log'])) {
            return;
        }

        $options = $options['log'];

        $options = array_merge([
            'on_exception_only' => $this->onFailureOnly,
            'statistics' => $this->logStatistics,
        ], $options);

        $this->stats = null;
        $this->onFailureOnly = $options['on_exception_only'];
        $this->logStatistics = $options['statistics'];
    }
}

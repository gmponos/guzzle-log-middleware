<?php

namespace Gmponos\GuzzleLogger\Middleware;

use Closure;
use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Handler\HandlerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A class to log HTTP Requests and Responses of Guzzle.
 *
 * @author George Mponos <gmponos@gmail.com>
 */
class LoggerMiddleware
{
    /**
     * @var bool Whether or not to log requests as they are made.
     */
    private $onExceptionOnly;

    /**
     * @var bool
     */
    private $logStatistics;

    /**
     * @var HandlerInterface
     */
    private $normalizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Creates a callable middleware for logging requests and responses.
     *
     * @param LoggerInterface $logger
     * @param HandlerInterface $normalizer
     * @param bool $onExceptionOnly The request and the response will be logged only in cases there is an exception or if they status code exceeds the thresholds.
     * @param bool $logStatistics If this is true an extra row will be added that will contain some HTTP statistics.
     */
    public function __construct(
        LoggerInterface $logger,
        HandlerInterface $normalizer = null,
        $onExceptionOnly = false,
        $logStatistics = false
    ) {
        $this->logger = $logger;
        $this->onExceptionOnly = $onExceptionOnly;
        $this->logStatistics = $logStatistics;
        $this->normalizer = $normalizer === null ? new ArrayHandler() : $normalizer;
    }

    /**
     * Called when the middleware is handled by the client.
     *
     * @param callable $handler
     * @return Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $this->setOptions($options);

            if ($this->onExceptionOnly === false) {
                $this->normalizer->log($this->logger, $request, $options);
                if ($this->logStatistics && !isset($options['on_stats'])) {
                    $options['on_stats'] = function (TransferStats $stats) {
                        $this->normalizer->log($this->logger, $stats);
                    };
                }
            }

            return $handler($request, $options)->then(
                $this->handleSuccess($request, $options),
                $this->handleFailure($request, $options)
            );
        };
    }

    /**
     * Returns a function which is handled when a request was successful.
     *
     * @param RequestInterface $request
     * @param array $options
     * @return Closure
     */
    private function handleSuccess(RequestInterface $request, array $options)
    {
        return function (ResponseInterface $response) use ($request, $options) {
            // On exception only is true then it must not log the response since it was successful.
            if ($this->onExceptionOnly === false) {
                $this->normalizer->log($this->logger, $response, $options);
            }

            return $response;
        };
    }

    /**
     * Returns a function which is handled when a request was rejected.
     *
     * @param RequestInterface $request
     * @param array $options
     * @return Closure
     */
    private function handleFailure(RequestInterface $request, array $options)
    {
        return function (\Exception $reason) use ($request, $options) {
            if ($this->onExceptionOnly === true) {
                // This means that the request was not logged and since an exception happened we need to log the request too.
                $this->normalizer->log($this->logger, $request, $options);
            }

            if ($reason instanceof RequestException && $reason->hasResponse()) {
                $this->normalizer->log($this->logger, $reason->getResponse(), $options);
                return \GuzzleHttp\Promise\rejection_for($reason);
            }

            $this->normalizer->log($this->logger, $reason, $options);
            return \GuzzleHttp\Promise\rejection_for($reason);
        };
    }

    /**
     * @param array $options
     * @return void
     */
    private function setOptions(array $options)
    {
        if (!isset($options['log'])) {
            return;
        }

        $options = $options['log'];

        $options = array_merge([
            'on_exception_only' => $this->onExceptionOnly,
            'statistics' => $this->logStatistics,
            'sensitive' => false,
        ], $options);

        $this->onExceptionOnly = $options['on_exception_only'];
        $this->logStatistics = $options['statistics'];
    }
}

<?php

namespace Gmponos\GuzzleLogger\Middleware;

use Closure;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerMiddleware
{

    use LoggerAwareTrait;

    /**
     * @var bool Whether or not to log requests as they are made.
     */
    protected $logRequests;

    /**
     * @var bool
     */
    protected $logStatistics;

    /**
     * @var array
     */
    private $thresholds;

    /**
     * @var array
     */
    private $logCodeLevel = [];

    /**
     * Creates a callable middleware for logging requests and responses.
     *
     * @param LoggerInterface $logger
     * @param bool            $logRequests
     * @param bool            $logStatistics
     * @param array           $thresholds
     */
    public function __construct(LoggerInterface $logger, $logRequests = true, $logStatistics = false, array $thresholds = [])
    {
        $this->setLogger($logger);
        $this->logRequests = $logRequests;
        $this->logStatistics = $logStatistics;
        $this->thresholds = array_merge([
            'error' => 499,
            'warning' => 399,
        ], $thresholds);
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

            if ($this->logRequests === true) {
                $this->logRequest($request);
                if ($this->logStatistics && !isset($options['on_stats'])) {
                    $options['on_stats'] = function (TransferStats $stats) {
                        $this->logger->debug("Guzzle HTTP statistics", [
                            'time' => $stats->getTransferTime(),
                            'uri' => $stats->getEffectiveUri(),
                        ]);
                    };
                }
            }

            return $handler($request, $options)->then(
                $this->handleSuccess($request),
                $this->handleFailure($request)
            );
        };
    }

    /**
     * Returns the default log level for a response.
     *
     * @param ResponseInterface|ResponseInterface $message
     * @return string LogLevel
     */
    private function getLogLevel($message = null)
    {
        if ($message === null || ($message instanceof \Exception)) {
            return LogLevel::CRITICAL;
        }

        if ($message instanceof RequestInterface) {
            return LogLevel::DEBUG;
        }

        if ($message instanceof ResponseInterface) {
            $code = $message->getStatusCode();
            if ($code === 0) {
                return LogLevel::CRITICAL;
            }

            if (isset($this->logCodeLevel[$code])) {
                return $this->logCodeLevel[$code];
            }

            if ($this->thresholds['error'] !== null && $code > $this->thresholds['error']) {
                return LogLevel::CRITICAL;
            }

            if ($this->thresholds['warning'] !== null && $code > $this->thresholds['warning']) {
                return LogLevel::ERROR;
            }

            return LogLevel::DEBUG;
        }
    }

    /**
     * @param RequestInterface $request
     */
    private function logRequest(RequestInterface $request)
    {
        $this->logger->log(
            $this->getLogLevel($request),
            "Guzzle HTTP request",
            $this->withRequestContext($request)
        );
    }

    /**
     * @param ResponseInterface|null $response
     */
    private function logResponse(ResponseInterface $response)
    {
        $this->logger->log(
            $this->getLogLevel($response),
            'Guzzle HTTP response',
            $this->withResponseContext($response)
        );
    }

    /**
     * Returns a function which is handled when a request was successful.
     *
     * @param RequestInterface $request
     * @return Closure
     */
    protected function handleSuccess(RequestInterface $request)
    {
        return function (ResponseInterface $response) use ($request) {

            if ($this->logRequests === true) {
                $this->logResponse($response);
                return $response;
            }

            if ($response->getStatusCode() > $this->thresholds['warning']) {
                $this->logRequest($request);
                $this->logResponse($response);
            }
            return $response;
        };
    }

    /**
     * Returns a function which is handled when a request was rejected.
     *
     * @param RequestInterface $request
     * @return Closure
     */
    protected function handleFailure(RequestInterface $request)
    {
        return function (\Exception $reason) use ($request) {
            if ($this->logRequests === false) {
                $this->logRequest($request);
            }

            if ($reason instanceof RequestException) {
                if ($reason->hasResponse()) {
                    $this->logResponse($reason->getResponse());
                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            }

            $this->logger->log($this->getLogLevel($reason), 'Guzzle HTTP exception', $this->withReasonContext($reason));
            return \GuzzleHttp\Promise\rejection_for($reason);
        };
    }

    /**
     * Merges and return the response context
     *
     * @param \Exception $reason
     * @param array      $context
     * @return array
     */
    private function withReasonContext(\Exception $reason, array $context = [])
    {
        $context['reason']['code'] = $reason->getCode();
        $context['reason']['message'] = $reason->getMessage();
        return $context;
    }

    /**
     * Merges and return the request context
     *
     * @param RequestInterface $request
     * @param array            $context
     * @return array
     */
    private function withRequestContext(RequestInterface $request, array $context = [])
    {
        $context['request']['method'] = $request->getMethod();
        $context['request']['headers'] = $request->getHeaders();
        $context['request']['uri'] = $request->getRequestTarget();
        $context['request']['version'] = 'HTTP/' . $request->getProtocolVersion();
        $context['request'] = array_merge($context['request'], $this->getBodyContext($request));
        return $context;
    }

    /**
     * Merges and return the response context
     *
     * @param ResponseInterface $response
     * @param array             $context
     * @return array
     */
    private function withResponseContext(ResponseInterface $response, array $context = [])
    {
        $context['response']['headers'] = $response->getHeaders();
        $context['response']['statusCode'] = $response->getStatusCode();
        $context['response']['version'] = 'HTTP/' . $response->getProtocolVersion();
        $context['response']['message'] = $response->getReasonPhrase();
        $context['response'] = array_merge($context['response'], $this->getBodyContext($response));
        return $context;
    }

    /**
     * @param RequestInterface|ResponseInterface $message
     * @return array
     */
    public function getBodyContext($message)
    {
        if ($message->getBody()->getSize() === 0) {
            return [];
        }

        if ($message->getBody()->getSize() >= 3500) {
            $result['summary'] = $message->getBody()->read(200) . ' (truncated...)';
            $result['body'] = "Body was truncated because of it's size";
        } else {
            $result['body'] = $message->getBody()->getContents();
            $isJson = preg_grep('/application\/json/', $message->getHeader("Content-Type"));
            if (!empty($isJson)) {
                $result['body'] = json_decode($result['body'], true);
            }
        }

        $message->getBody()->rewind();
        return $result;
    }

    /**
     * @param array $options
     */
    private function setOptions(array $options)
    {
        $defaults = [
            'requests' => $this->logRequests,
            'statistics' => $this->logStatistics,
            'warning_threshold' => 399,
            'error_threshold' => 499,
            'levels' => [],
        ];

        if (!isset($options['log'])) {
            return;
        }

        $options = array_merge($defaults, $options['log']);
        $this->logCodeLevel = $options['levels'];
        $this->thresholds['warning'] = $options['warning_threshold'];
        $this->thresholds['error'] = $options['error_threshold'];
        $this->logRequests = $options['requests'];
        $this->logStatistics = $options['statistics'];
    }
}

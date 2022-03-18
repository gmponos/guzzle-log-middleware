<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use GuzzleHttp\MessageFormatterInterface;
use GuzzleHttp\TransferStats;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Delegates responsibility of log message formatting to MessageFormatterInterface.
 */
class MessageFormatterHandler extends AbstractHandler
{
    /**
     * @var MessageFormatterInterface
     */
    private $messageFormatter;

    public function __construct(
        MessageFormatterInterface $messageFormatter,
        LogLevelStrategyInterface $logLevelStrategy = null
    ) {
        $this->messageFormatter = $messageFormatter;
        $this->logLevelStrategy = $logLevelStrategy === null ? $this->getDefaultStrategy() : $logLevelStrategy;
    }

    public function log(LoggerInterface $logger, RequestInterface $request, ?ResponseInterface $response = null, ?Throwable $exception = null, ?TransferStats $stats = null, array $options = []): void
    {
        if (
            $request->getBody()->isSeekable() === false
            || $request->getBody()->isReadable() === false
            || (
                $response !== null && (
                    $response->getBody()->isSeekable() === false
                    || $response->getBody()->isReadable() === false
                )
            )
        ) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $message = $this->messageFormatter->format($request, $response, $exception);
        $reason = $exception ?? $response ?? $request;
        $level = $this->logLevelStrategy->getLevel($reason, $options);
        $logger->log($level, $message);
    }
}

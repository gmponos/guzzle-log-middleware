<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use GuzzleHttp\TransferStats;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class StringHandler extends AbstractHandler
{
    public function __construct(LogLevelStrategyInterface $logLevelStrategy = null)
    {
        $this->logLevelStrategy = $logLevelStrategy === null ? $this->getDefaultStrategy() : $logLevelStrategy;
    }

    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $exception = null,
        ?TransferStats $stats = null,
        array $options = []
    ): void {
        $this->logRequest($logger, $request, $options);

        if ($stats !== null) {
            $this->logStats($logger, $stats, $options);
        }

        if ($response !== null) {
            $this->logResponse($logger, $response, $options);
            return;
        }

        if ($exception !== null) {
            $this->logReason($logger, $exception, $options);
        }
    }

    private function logRequest(LoggerInterface $logger, RequestInterface $value, array $options): void
    {
        // we do not allow to record the message if the body is not seekable.
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $str = \GuzzleHttp\Psr7\Message::toString($value);

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, 'Guzzle HTTP request:' . "\n" . $str);
    }

    private function logResponse(LoggerInterface $logger, ResponseInterface $value, array $options): void
    {
        // we do not allow to record the message if the body is not seekable.
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $str = \GuzzleHttp\Psr7\Message::toString($value);

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, 'Guzzle HTTP response:' . "\n" . $str);
    }

    private function logReason(LoggerInterface $logger, Throwable $exception, array $options): void
    {
        $level = $this->logLevelStrategy->getLevel($exception, $options);
        $logger->log($level, sprintf('Guzzle HTTP exception: %s', $exception->getMessage()), [
            'exception' => $exception,
        ]);
    }

    private function logStats(LoggerInterface $logger, TransferStats $value, array $options): void
    {
        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, sprintf(
            'Guzzle HTTP transfer time: %s for uri: %s',
            $value->getTransferTime(),
            $value->getEffectiveUri()
        ));
    }
}

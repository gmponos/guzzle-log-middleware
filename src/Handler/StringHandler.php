<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use GuzzleHttp\TransferStats;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class StringHandler extends AbstractHandler
{
    /**
     * @param LogLevelStrategyInterface|null $logLevelStrategy
     */
    public function __construct(LogLevelStrategyInterface $logLevelStrategy = null)
    {
        $this->logLevelStrategy = $logLevelStrategy === null ? $this->getDefaultStrategy() : $logLevelStrategy;
    }

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param \Exception $exception
     * @param TransferStats $stats
     * @param array $options
     * @return void
     */
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Exception $exception,
        ?TransferStats $stats,
        array $options
    ): void {
        $this->logRequest($logger, $request, $options);
        $this->logResponse($logger, $response, $options);
        $this->logReason($logger, $exception, $options);
        $this->logStats($logger, $stats, $options);
    }

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $value
     * @param array $options
     * @return void
     */
    private function logRequest(LoggerInterface $logger, RequestInterface $value, array $options): void
    {
        $level = $this->logLevelStrategy->getLevel($value, $options);
        // we do not allow to record the message if the body is not seekable.
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $str = \GuzzleHttp\Psr7\str($value);
        $logger->log($level, 'Guzzle HTTP request:' . "\n" . $str);
    }

    /**
     * @param LoggerInterface $logger
     * @param ResponseInterface|null $value
     * @param array $options
     * @return void
     */
    private function logResponse(LoggerInterface $logger, ?ResponseInterface $value, array $options): void
    {
        if ($value === null) {
            return;
        }

        // we do not allow to record the message if the body is not seekable.
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $str = \GuzzleHttp\Psr7\str($value);

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, 'Guzzle HTTP response:' . "\n" . $str);
    }

    /**
     * @param LoggerInterface $logger
     * @param \Exception|null $value
     * @param array $options
     * @return void
     */
    private function logReason(LoggerInterface $logger, ?\Exception $value, array $options): void
    {
        if ($value === null) {
            return;
        }

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, sprintf('Guzzle HTTP exception: %s', (string)$value));
    }

    /**
     * @param LoggerInterface $logger
     * @param TransferStats|null $value
     * @param array $options
     * @return void
     */
    private function logStats(LoggerInterface $logger, ?TransferStats $value, array $options): void
    {
        if ($value === null) {
            return;
        }

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, sprintf(
            'Guzzle HTTP transfer time: %s for uri: %s',
            $value->getTransferTime(),
            $value->getEffectiveUri()
        ));
    }
}

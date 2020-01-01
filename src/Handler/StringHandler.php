<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use Exception;
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
     * @param ResponseInterface|null $response
     * @param Exception|null $exception
     * @param TransferStats|null $stats
     * @param array $options
     * @return void
     */
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?Exception $exception,
        ?TransferStats $stats,
        array $options
    ): void
    {
        $this->logRequest($logger, $request, $options);

        if ($stats !== null) {
            $this->logStats($logger, $stats, $options);
        }

        if ($response !== null) {
            $this->logResponse($logger, $response, $options);
        } else {
            if ($exception !== null) {
                $this->logReason($logger, $exception, $options);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $value
     * @param array $options
     * @return void
     */
    private function logRequest(LoggerInterface $logger, RequestInterface $value, array $options): void
    {
        // we do not allow to record the message if the body is not seekable.
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
            return;
        }

        $str = \GuzzleHttp\Psr7\str($value);

        $level = $this->logLevelStrategy->getLevel($value, $options);
        $logger->log($level, 'Guzzle HTTP request:' . "\n" . $str);
    }

    /**
     * @param LoggerInterface $logger
     * @param ResponseInterface $value
     * @param array $options
     * @return void
     */
    private function logResponse(LoggerInterface $logger, ResponseInterface $value, array $options): void
    {
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
     * @param Exception $exception
     * @param array $options
     * @return void
     */
    private function logReason(LoggerInterface $logger, Exception $exception, array $options): void
    {
        $level = $this->logLevelStrategy->getLevel($exception, $options);
        $logger->log($level, sprintf('Guzzle HTTP exception: %s', $exception->getMessage()), [
            'exception' => $exception
        ]);
    }

    /**
     * @param LoggerInterface $logger
     * @param TransferStats $value
     * @param array $options
     * @return void
     */
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
